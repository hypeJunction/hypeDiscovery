<?php

namespace hypeJunction\Discovery;

use ElggEntity;
use ElggMenuItem;
use ElggSite;

/**
 * Menus class.
 */
class Menus {

	/**
	 * Setup entity menu
	 *
	 * @param \Elgg\Event $event "register", "menu:entity"
	 * @return ElggMenuItem[]
	 */
	public static function entityMenuSetup(\Elgg\Event $event) {
		$return = $event->getValue();
		$params = $event->getParams();

		$entity = elgg_extract('entity', $params);

		if ($entity->canEdit() && is_discoverable_type($entity)) {
			if (elgg_is_active_plugin('menus_api')) {
				$text = elgg_echo('discovery:edit');
			} else {
				$text = elgg_view_icon('eye');
			}

			$return[] = ElggMenuItem::factory([
				'name' => 'discovery:edit',
				'text' => $text,
				'href' => "opengraph/edit/$entity->guid",
				'title' => elgg_echo('discovery:edit'),
				'link_class' => 'elgg-lightbox',
				'data-colorbox-opts' => json_encode([
					'maxWidth' => '600px',
				]),
				'data' => [
					'icon' => 'eye',
				],
				'priority' => 700,
				'deps' => ['elgg/lightbox'],
			]);
		}

		if (is_discoverable($entity)) {
			if (elgg_is_active_plugin('menus_api')) {
				$text = elgg_echo('discovery:entity:share');
			} else {
				$text = elgg_view_icon('share');
			}

			$return[] = ElggMenuItem::factory([
				'name' => 'discovery:share',
				'text' => $text,
				'href' => "opengraph/share/$entity->guid",
				'title' => elgg_echo('discovery:entity:share'),
				'link_class' => 'elgg-lightbox',
				'data-colorbox-opts' => json_encode([
					'maxWidth' => '600px',
				]),
				'data' => [
					'icon' => 'share',
				],
				'priority' => 700,
				'deps' => ['elgg/lightbox'],
			]);
		}

		return $return;
	}

	/**
	 * Setup entity menu
	 *
	 * @param \Elgg\Event $event "register", "menu:extras"
	 * @return ElggMenuItem[]
	 */
	public static function extrasMenuSetup(\Elgg\Event $event) {
		$return = $event->getValue();

		$share_url = elgg_get_current_url();
		$entity = get_entity_from_url($share_url);
		$guid = $entity->guid;
		if ($entity instanceof ElggSite) {
			$guid = '';
		}

		$providers = get_discovery_providers();
		if (empty($providers)) {
			return;
		}

		$text = elgg_view_icon('share');
		$return[] = ElggMenuItem::factory([
			'name' => 'discovery:share',
			'text' => $text,
			'href' => elgg_http_add_url_query_elements("opengraph/share/$guid", [
				'share_url' => $share_url,
			]),
			'title' => elgg_echo('discovery:entity:share'),
			'link_class' => 'elgg-lightbox',
			'data-colorbox-opts' => json_encode([
				'maxWidth' => '600px',
			]),
			'data' => [
				'icon' => 'share',
			],
			'priority' => 700,
			'deps' => ['elgg/lightbox'],
		]);

		return $return;
	}

	/**
	 * Setup share menu
	 *
	 * @param \Elgg\Event $event "register", "menu:discovery_share"
	 * @return ElggMenuItem[]
	 */
	public static function shareMenuSetup(\Elgg\Event $event) {
		$return = $event->getValue();
		$params = $event->getParams();

		$providers = get_discovery_providers();
		if (empty($providers)) {
			return;
		}

		$share_url = elgg_extract('share_url', $params);

		$entity = elgg_extract('entity', $params);
		if ($entity instanceof ElggEntity) {
			$share_url = $entity->getVolatileData('discovery:share_url') ?: $entity->getURL();
			if (!is_discoverable($entity)) {
				return;
			}
		}

		foreach ($providers as $provider) {
			$return[] = ElggMenuItem::factory([
				'name' => "discovery:$provider",
				'text' => elgg_format_element('span', ['class' => "webicon $provider"]),
				'href' => get_share_action_url($provider, $entity->guid, elgg_get_current_url(), $share_url),
				'is_action' => true,
				'item_class' => 'svg',
				'title' => elgg_echo('discovery:share', [elgg_echo("discovery:provider:$provider")]),
				'target' => '_blank',
			]);
		}

		return $return;
	}

	/**
	 * Setup menu
	 *
	 * @param \Elgg\Event $event "register", "menu:scraper:card"
	 * @return ElggMenuItem[]
	 */
	public static function setupCardMenu(\Elgg\Event $event) {
		$return = $event->getValue();
		$params = $event->getParams();

		$user = elgg_get_logged_in_user_entity();
		if (!$user) {
			return;
		}

		$href = elgg_extract('href', $params);
		if (!$href) {
			return;
		}

		$return[] = ElggMenuItem::factory([
			'name' => 'discovery:share',
			'text' => elgg_view_icon('share-alt-square'),
			'href' => elgg_http_add_url_query_elements('opengraph/share', [
				'share_url' => $href,
			]),
			'title' => elgg_echo('discovery:entity:share'),
			'link_class' => 'elgg-lightbox',
			'data-colorbox-opts' => json_encode([
				'maxWidth' => '600px',
			]),
			'data' => [
				'icon' => 'share',
			],
			'priority' => 700,
			'deps' => ['elgg/lightbox'],
		]);

		return $return;
	}
}
