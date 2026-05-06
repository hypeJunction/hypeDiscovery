<?php

namespace hypeJunction\Discovery;

use ElggIcon;

/**
 * Icons class.
 */
class Icons {

	/**
	 * Maps old og icons to new open graph image icons
	 *
	 * @param \Elgg\Event $event 'entity:icon:url', 'site', 'object', 'user' or 'group'
	 * @return string
	 */
	public static function entityIconURL(\Elgg\Event $event) {
		$params = $event->getParams();

		$entity = elgg_extract('entity', $params);
		$size = elgg_extract('size', $params);

		$og_sizes = [
			'_og' => 'small',
			'_og_large' => 'medium',
			'_og_high' => 'large'
		];

		if (isset($og_sizes[$size])) {
			return $entity->getIconURL([
				'size' => $og_sizes[$size],
				'type' => 'open_graph_image'
			]);
		}
	}

	/**
	 * Open graph image url
	 *
	 * @param \Elgg\Event $event 'entity:open_graph_image:url', 'site', 'object', 'user' or 'group'
	 * @return string
	 */
	public static function entityOpenGraphImageURL(\Elgg\Event $event) {
		$params = $event->getParams();

		$entity = elgg_extract('entity', $params);
		/* @var $entity \ElggEntity */
		$size = elgg_extract('size', $params, 'medium');

		if (!$entity->hasIcon($size, 'open_graph_image')) {
			// Default icons are smaller
			$sizes = [
				'small' => 'medium',
				'medium' => 'large',
				'large' => 'master',
			];
			if ($entity->hasIcon($sizes[$size])) {
				$icon = $entity->getIcon($sizes[$size]);
			} else {
				$icon = elgg_get_site_entity()->getIcon($size, 'open_graph_image');
			}
		} else {
			$icon = $entity->getIcon($size, 'open_graph_image');
		}
		
		return elgg_get_inline_url($icon, false);
	}

	/**
	 * Configure open graph image sizes
	 *
	 * @param \Elgg\Event $event "entity:open_graph_image:sizes", "object", "user", "group", "site"
	 * @return array
	 */
	public static function entityOpenGraphImageSizes(\Elgg\Event $event) {
		$return = $event->getValue();
		$sizes = [
			'large' => [
				'w' => 1200,
				'h' => 1200,
				'square' => false,
				'upscale' => false,
			],
			'medium' => [
				'w' => 600,
				'h' => 600,
				'square' => false,
				'upscale' => false,
			],
			'small' => [
				'w' => 200,
				'h' => 200,
				'square' => false,
				'upscale' => true,
			],
			'original' => [],
		];
		return array_merge($return, $sizes);
	}

	/**
	 * Configure open graph image file
	 *
	 * @param \Elgg\Event $event "entity:open_graph_image:file", "object", "user", "group", "site"
	 * @return ElggIcon
	 */
	public static function entityOpenGraphImageFile(\Elgg\Event $event) {
		$return = $event->getValue();
		$params = $event->getParams();

		// mapping to old size config
		$og_sizes = [
			'small' => '_og',
			'medium' => '_og_large',
			'large' => '_og_high',
		];

		$entity = elgg_extract('entity', $params);
		$size = elgg_extract('size', $params, 'medium');

		$size = elgg_extract($size, $og_sizes, $size);

		$return->owner_guid = $entity->owner_guid ?: $entity->guid;
		$return->setFilename("og_image/$entity->guid/$size.jpg");

		return $return;
	}
}
