<?php

namespace hypeJunction\Discovery;

/**
 * Router class.
 */
class Router {

	/**
	 * Handle incoming discovery traffic
	 *
	 * @param array $segments URL segments
	 * @return boolean
	 */
	public static function permalinkHandler($segments) {

		$viewtype = array_shift($segments);
		$referrer_hash = array_shift($segments);
		if (!preg_match('/^[a-f0-9]{32}$/i', $referrer_hash)) {
			// The hash was moved into URL query parameters
			$guid = $referrer_hash;
			$referrer_hash = get_input('uh');
		} else {
			$guid = array_shift($segments);
			set_input('uh', $referrer_hash);
		}

		switch ($viewtype) {
			case 'image':
				// BC router
				$size = array_shift($segments);
				$url = elgg_call(ELGG_IGNORE_ACCESS, function () use ($guid, $size) {
					$entity = get_entity($guid);
					return $entity->getIconURL($size);
				});
				elgg_redirect_response($url);
				return;

			default:
				switch ($viewtype) {
					case 'json+oembed':
					case 'json oembed':
						$viewtype = 'json';
						break;

					case 'xml+oembed':
					case 'xml oembed':
						$viewtype = 'xml';
						break;
				}

				$valid_viewtypes = ['default', 'json', 'xml', 'oembed'];
				if (!in_array($viewtype, $valid_viewtypes)) {
					$viewtype = 'default';
				}

				elgg_set_viewtype($viewtype);

				if (!$guid || !elgg_entity_exists($guid)) {
					return false;
				}

				// TODO(6.x): wrap in elgg_call(ELGG_IGNORE_ACCESS) — block has multiple early returns, a mid-block redirect, and a no-arg elgg_set_ignore_access() read; cannot bracket safely
				$ia = elgg_set_ignore_access();
				$entity = get_entity($guid);

				if (!has_access_to_entity($entity) && !is_discoverable($entity)) {
					elgg_set_ignore_access($ia);
					return false;
				}

				elgg_register_event_handler('head', 'page', function(\Elgg\Event $event) use ($entity) {
					$return = $event->getValue();
					if (isset($return['links']['canonical'])) {
						return;
					}

					if (elgg_is_active_plugin('hypeSeo')) {
						$svc = \hypeJunction\Seo\RewriteService::getInstance();
						$data = $svc->getRewriteRulesFromGUID($entity->getURL());
						if (isset($data['sef_path'])) {
							$return['links']['canonical'] = [
								'href' => elgg_normalize_url($data['sef_path']),
								'rel' => 'canonical',
							];
							return $return;
						}
					}

					$return['links']['canonical'] = [
						'href' => $entity->getURL(),
						'rel' => 'canonical',
					];

					return $return;
				});

				$forward_url = false;

				$is_walled = elgg_get_config('walled_garden') && !elgg_is_logged_in();
				if (has_access_to_entity($entity) && $viewtype == 'default' && !$is_walled) {
					$forward_url = $entity->getURL();
				}

				$forward_url = elgg_trigger_event_results('entity:referred', $entity->getType(), [
					'entity' => $entity,
					'user_hash' => $referrer_hash,
					'referrer' => $_SERVER['HTTP_REFERER'],
				], $forward_url);

				if ($forward_url) {
					elgg_set_ignore_access($ia);
					elgg_redirect_response($forward_url);
				}

				if (elgg_get_plugin_setting('nocrawl', 'hypediscovery')) {
					elgg_set_http_header('X-Robots-Tag: noindex', true);

					elgg_register_event_handler('head', 'page', function(\Elgg\Event $event) {
						$return = $event->getValue();
						$return['metas'][] = [
							'name' => 'robots',
							'content' => 'noindex',
						];

						return $return;
					});
				}

				echo elgg_view_resource('permalink', [
					'viewtype' => $viewtype,
					'user_hash' => $referrer_hash,
					'guid' => $guid,
					'entity' => $entity,
				]);

				elgg_set_ignore_access($ia);
				return true;
		}

		return false;
	}

	/**
	 * Handle discovery
	 *
	 * @param array  $segments URL segments after the handler
	 * @param string $handler  Page handler identifier
	 * @return boolean
	 */
	public static function opengraphHandler($segments, $handler) {

		switch ($segments[0]) {
			case 'edit':
				$guid = $segments[1];
				$entity = get_entity($guid);

				if (!$entity instanceof \ElggEntity || !$entity->canEdit() || !is_discoverable_type($entity)) {
					return false;
				}

				$title = elgg_echo('discovery:entity:settings');
				$content = elgg_view('framework/discovery/edit', [
					'entity' => $entity
				]);
				$sidebar = false;
				$filter = false;
				break;

			case 'share':
				$entity = null;
				$share_url = get_input('share_url');

				$guid = $segments[1];
				if ($guid) {
					$entity = get_entity($guid);
					if (!$entity) {
						return false;
					}

					if (!$share_url) {
						$share_url = $entity->getURL();
					}
					
					$entity->setVolatileData('discovery:share_url', $share_url);
				}

				$title = elgg_echo('discovery:entity:share');
				$content = elgg_view('forms/discovery/share', [
					'entity' => $entity,
					'share_url' => $share_url,
				]);

				$sidebar = false;
				$filter = false;
				break;
		}

		if ($content) {
			if (elgg_is_xhr()) {
				echo $content;
			} else {
				$layout = elgg_view_layout('default', [
					'title' => $title,
					'content' => $content,
					'filter' => $filter,
					'sidebar' => $sidebar,
				]);
				echo elgg_view_page($title, $layout);
			}

			return true;
		}

		return false;
	}

	/**
	 * Add discovery pages to public domain
	 *
	 * @param \Elgg\Event $event "public_pages", "walled_garden"
	 * @return array
	 */
	public static function publicPages(\Elgg\Event $event) {
		$return = $event->getValue();
		$return[] = 'permalink/.*';
		$return[] = 'action/discovery/share';
		return $return;
	}

	/**
	 * Route old web services endpoint to the new one
	 *
	 * @param \Elgg\Event $event "route", "services"
	 * @return array
	 */
	public static function servicesRoute(\Elgg\Event $event) {
		$type = $event->getType();
		$return = $event->getValue();
		$params = $event->getParams();

		if (!is_array($return)) {
			return;
		}

		$identifier = elgg_extract('identifier', $params);
		$segments = (array) elgg_extract('segments', $params, []);

		if ($identifier !== 'services') {
			return;
		}

		if (array_shift($segments) !== 'api') {
			return;
		}

		if (array_shift($segments) !== 'rest') {
			return;
		}

		if (array_shift($segments) !== 'oembed') {
			return;
		}

		$method = get_input('method');
		if ($method !== 'oembed') {
			return;
		}

		$format = get_input('format', 'json');
		$url = get_input('origin');
		$maxwidth = get_input('maxwidth');
		$maxheight = get_input('maxheight');

		$permalink = elgg_call(ELGG_IGNORE_ACCESS, function () use ($url, $format) {
			$entity = get_entity_from_url(urldecode($url));
			return get_entity_permalink($entity, "{$format}+oembed");
		});

		if (!$permalink) {
			// TODO(6.x): forward('', '404') is an error-page forward, not a plain redirect; map to elgg_error_response() or throw an appropriate HTTP exception
			forward('', '404');
		}

		$permalink = elgg_http_add_url_query_elements($permalink, [
			'maxwidth' => $maxwidth,
			'maxheight' => $maxheight,
		]);

		elgg_redirect_response($permalink);
	}

	/**
	 * Redirect to entity permalink instead of an error page if entity is discoverable
	 *
	 * @param \Elgg\Event $event "forward", "403"|"404"
	 * @return array
	 */
	public static function redirectErrorToPermalink(\Elgg\Event $event) {
		$return = $event->getValue();

		return elgg_call(ELGG_IGNORE_ACCESS, function () use ($return) {
			$url = elgg_get_current_url();
			$entity = get_entity_from_url($url);

			if (is_discoverable($entity)) {
				return get_entity_permalink($entity);
			}

			return $return;
		});
	}
}
