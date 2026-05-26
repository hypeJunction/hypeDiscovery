<?php

namespace hypeJunction\Discovery;

/**
 * Discovery class.
 */
class Discovery {

	/**
	 * Prepare alternate link tags
	 *
	 * @param \Elgg\Event $event "head", "page"
	 * @return array
	 */
	public static function prepareAlternateLinks(\Elgg\Event $event) {
		$return = $event->getValue();
		$params = $event->getParams();

		$title = \elgg_extract('title', $params);

		return \elgg_call(ELGG_IGNORE_ACCESS, function () use ($return, $title) {
			$url = \elgg_get_current_url();
			$entity = get_entity_from_url($url);

			if (is_embeddable($entity)) {
				$return['links']['json+oembed'] = [
					'rel' => 'alternate',
					'type' => 'application/json+oembed',
					'href' => get_entity_permalink($entity, 'json+oembed'),
					'title' => $title,
				];

				if (\elgg_is_active_plugin('data_views')) {
					$return['links']['xml+oembed'] = [
						'rel' => 'alternate',
						'type' => 'application/xml+oembed',
						'href' => get_entity_permalink($entity, 'xml+oembed'),
						'title' => $title,
					];
				}
			}

			return $return;
		});
	}

	/**
	 * Prepare open graph and other discovery tags
	 *
	 * @param \Elgg\Event $event "head", "page"
	 * @return array
	 */
	public static function prepareMetas(\Elgg\Event $event) {
		$return = $event->getValue();
		$params = $event->getParams();

		$url = \elgg_get_current_url();
		$metatags = get_discovery_metatags($url);

		if (empty($metatags)) {
			return;
		}

		if (!isset($metatags['og:title']) && isset($params['title'])) {
			$metatags['og:title'] = $params['title'];
		}

		if (!empty($metatags) && is_array($metatags)) {
			foreach ($metatags as $name => $content) {
				if (!$content) {
					continue;
				}

				$name_parts = explode(':', $name);
				$namespace = array_shift($name_parts);

				$ogp = ['og', 'fb', 'article', 'profile', 'book', 'music', 'video', 'profile', 'website'];
				if (in_array($namespace, $ogp)) {
					// OGP tags use 'property=""' attribute
					$return['metas'][$name] = [
						'property' => $name,
						'content' => $content,
					];
				} else {
					$return['metas'][$name] = [
						'name' => $name,
						'content' => $content,
					];
				}
			}
		}

		return $return;
	}

	/**
	 * Get exportable representation of an entity for oEmbed
	 *
	 * @param \Elgg\Event $event "export:entity", "oembed"
	 * @return array
	 */
	public static function oEmbedExport(\Elgg\Event $event) {
		$return = $event->getValue();
		$params = $event->getParams();

		$entity = \elgg_extract('entity', $params);
		$maxwidth = \elgg_extract('maxwidth', $params);
		$maxheight = \elgg_extract('maxheight', $params);
		$height = $maxheight ?: 480;
		$width = $maxwidth ?: 640;

		if (!is_embeddable($entity)) {
			return $return;
		}

		$return['type'] = 'rich';
		$return['thumbnail_url'] = $entity->getIconURL([
			'size' => 'medium',
			'type' => 'open_graph_image',
		]);

		$iframe_attrs = \elgg_format_attributes([
			'src' => get_entity_permalink($entity, 'oembed'),
			'frameborder' => 0,
			'height' => $height,
			'width' => $width,
			'scrolling' => 'auto',
			'seamless' => true,
		]);

		$return['html'] = "<iframe $iframe_attrs></iframe>";
		$return['width'] = $width;
		$return['height'] = $height;

		return $return;
	}

	/**
	 * Header metatags
	 *
	 * @param \Elgg\Event $event "metatags", "discovery"
	 * @return array
	 */
	public static function graphExport(\Elgg\Event $event) {
		$return = $event->getValue();
		$params = $event->getParams();

		$entity = \elgg_extract('entity', $params);
		$url = \elgg_extract('url', $params);

		$site = \elgg_get_site_entity();
		$site_tags = [
			'og:type' => 'website',
			'og:site_name' => $site->og_site_name,
			'og:image' => get_discovery_image_url($site),
			'og:url' => $url,
			'og:description' => get_discovery_description($site),
			'fb:app_id' => $site->fb_app_id,
			'twitter:card' => 'summary',
			'twitter:site' => $site->twitter_site,
		];

		$return = array_merge($return, $site_tags);

		if (!is_discoverable($entity)) {
			return $return;
		}

		$type = $entity->getType();
		$subtype = $entity->getSubtype();

		$image_url = get_discovery_image_url($entity);
		if ($image_url && file_exists($image_url)) {
			$image_size = getimagesize($image_url);
			$image_width = $image_size[0];
			$image_height = $image_size[1];
		}

		switch ($type) {
			default:
			case 'object':
				$owner = $entity->getOwnerEntity();
				$entity_tags = [
					'og:type' => 'article',
					'og:title' => get_discovery_title($entity),
					'og:image' => $image_url,
					'og:image:width' => $image_width,
					'og:image:height' => $image_height,
					'og:url' => get_entity_permalink($entity),
					'og:description' => get_discovery_description($entity),
					'article:published_time' => date('Y-m-d', $entity->time_created),
					'article:author' => ($owner) ? $owner->getURL() : '',
					'article:tags' => get_discovery_keywords($entity),
					'twitter:creator' => ($owner) ? $owner->twitter : '',
				];
				break;

			case 'user':
				$entity_tags = [
					'og:type' => 'profile',
					'og:title' => get_discovery_title($entity),
					'og:image' => $image_url,
					'og:image:width' => $image_width,
					'og:image:height' => $image_height,
					'og:url' => get_entity_permalink($entity),
					'og:description' => get_discovery_description($entity),
					'profile:username' => $entity->username,
					'twitter:creator' => $entity->twitter,
				];
				break;

			case 'site':
				$entity_tags = [];
				break;
		}

		$return = array_merge($return, $entity_tags);

		return $return;
	}
}
