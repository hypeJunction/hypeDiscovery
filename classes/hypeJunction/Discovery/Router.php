<?php

namespace hypeJunction\Discovery;

class Router {

	/**
	 * Handle incoming discovery traffic
	 *
	 * @param array $segments URL segments
	 * @return boolean
	 */
	public static function permalinkHandler($segments) {

		switch ($segments[0]) {
			case 'image' :
				// BC router
				$ia = elgg_set_ignore_access(true);
				$entity = get_entity($segments[1]);
				$url = forward($entity->getIconURL($segments[2]));
				elgg_set_ignore_access($ia);
				forward($url);
				return;

			default :
				echo elgg_view_resource('permalink', [
					'viewtype' => $segments[0],
					'user_hash' => $segments[1],
					'guid' => $segments[2],
				]);
				return true;
		}

		return false;
	}

	/**
	 * Handle discovery
	 *
	 * @param array $segments
	 * @param string $handler
	 * @return boolean
	 */
	public static function opengraphHandler($segments, $handler) {

		switch ($segments[0]) {

			case 'edit' :
				$guid = $segments[1];
				$entity = get_entity($guid);

				if (!elgg_instanceof($entity) || !$entity->canEdit() || !is_discoverable_type($entity)) {
					return false;
				}

				$title = elgg_echo('discovery:entity:settings');
				$content = elgg_view('framework/discovery/edit', array(
					'entity' => $entity
				));
				$sidebar = false;
				$filter = false;
				break;

			case 'share' :
				$guid = $segments[1];
				$entity = get_entity($guid);

				if (!$entity) {
					return false;
				}

				$title = elgg_echo('discovery:entity:share');
				$content = elgg_view('forms/discovery/share', array(
					'entity' => $entity
				));
				$sidebar = false;
				$filter = false;
				break;
		}

		if ($content) {
			if (elgg_is_xhr()) {
				echo $content;
			} else {
				$layout = elgg_view_layout('content', array(
					'title' => $title,
					'content' => $content,
					'filter' => $filter,
					'sidebar' => $sidebar,
				));
				echo elgg_view_page($title, $layout);
			}
			return true;
		}
		return false;
	}

	/**
	 * Add discovery pages to public domain
	 *
	 * @param string $hook	 "public_pages"
	 * @param string $type	 "walled_garden"
	 * @param array  $return Public pages
	 * @return array
	 */
	public static function publicPages($hook, $type, $return) {
		$return[] = 'permalink/.*';
		$return[] = 'action/discovery/share';
		return $return;
	}

	/**
	 * Route old web services endpoint to the new one
	 * 
	 * @param string $hook   "route"
	 * @param string $type   "services"
	 * @param array  $return Route details
	 * @param array  $params Hook params
	 * @return array
	 */
	public static function servicesRoute($hook, $type, $return, $params) {

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

		$ia = elgg_set_ignore_access(true);
		$entity = get_entity_from_url(urldecode($url));
		$permalink = get_entity_permalink($entity, "{$format}+oembed");
		elgg_set_ignore_access($ia);

		if (!$permalink) {
			forward('', '404');
		}

		$permalink = elgg_http_add_url_query_elements($permalink, [
			'maxwidth' => $maxwidth,
			'maxheight' => $maxheight,
		]);

		forward($permalink);

	}
}
