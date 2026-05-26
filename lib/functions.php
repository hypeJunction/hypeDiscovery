<?php

namespace hypeJunction\Discovery;

use ElggEntity;
use ElggIcon;
use ElggSite;
use ElggUser;
use UFCOE\Elgg\SiteUrl;

/**
 * Check if the entity can be discovered from a remote resource
 *
 * @param ElggEntity $entity Entity
 * @return bool
 */
function is_discoverable($entity) {
	if ($entity instanceof ElggSite) {
		return true;
	}

	if (!$entity instanceof ElggEntity) {
		return false;
	}

	if (!is_discoverable_type($entity)) {
		return false;
	}

	if (isset($entity->discoverable)) {
		return (bool) $entity->discoverable;
	}

	switch ($entity->access_id) {
		case ACCESS_PUBLIC:
			return true;
		case ACCESS_LOGGED_IN:
			if (!\elgg_get_plugin_setting('bypass_access', 'hypediscovery')) {
				return false;
			}
			return true;
		default:
			return false;
	}
}

/**
 * Check if entity type/subtype are specified for discovery in plugin settings
 *
 * @param ElggEntity $entity  Entity
 * @param string     $type    Entity type (if no entity is provided)
 * @param string     $subtype Entity subtype (if no entity is provided)
 * @return boolean
 */
function is_discoverable_type($entity = null, $type = '', $subtype = '') {
	if ($entity instanceof ElggEntity) {
		$type = $entity->getType();
		$subtype = $entity->getSubtype() ?: 'default';
	}

	if (!in_array("{$type}::{$subtype}", get_discoverable_type_subtype_pairs())) {
		return false;
	}

	return true;
}

/**
 * Check if the entity can be embedded on a remote resource
 *
 * @param ElggEntity $entity Entity
 * @return bool
 */
function is_embeddable($entity) {
	if ($entity instanceof ElggSite) {
		return false;
	}

	if (!$entity instanceof ElggEntity) {
		return false;
	}

	if (!is_embeddable_type($entity)) {
		return false;
	}

	if (!is_discoverable($entity)) {
		return false;
	}

	return (bool) $entity->embeddable;
}

/**
 * Check if entity type/subtype are specified for embedding in plugin settings
 *
 * @param ElggEntity $entity  Entity
 * @param string     $type    Entity type (if no entity provided)
 * @param string     $subtype Entity subtype (if no entity provided)
 * @return bool
 */
function is_embeddable_type($entity = null, $type = '', $subtype = '') {
	if ($entity instanceof ElggEntity) {
		$type = $entity->getType();
		$subtype = $entity->getSubtype() ?: 'default';
	}

	if (!in_array("{$type}::{$subtype}", get_embeddable_type_subtype_pairs())) {
		return false;
	}

	return true;
}

/**
 * Construct a share action URL
 *
 * @param string  $provider  Social media provider
 * @param integer $guid      Entity guid
 * @param string  $referrer  Referring URL
 * @param string  $share_url URL to share
 * @return string
 */
function get_share_action_url($provider, $guid = 0, $referrer = '', $share_url = '') {
	$base_url = \elgg_normalize_url('action/discovery/share');
	return \elgg_http_add_url_query_elements($base_url, ['provider' => $provider, 'guid' => $guid, 'referrer' => $referrer, 'share_url' => $share_url]);
}

/**
 * Construct sharing endpoint URL for a provider
 *
 * @param string     $provider  Social media provider
 * @param ElggEntity $entity    Entity
 * @param string     $referrer  Referrer URL
 * @param string     $share_url URL to share
 * @return string|false
 */
function get_provider_url($provider, $entity = null, $referrer = '', $share_url = '') {
	$site = \elgg_get_site_entity();
	if (!$entity && $share_url) {
		$title = '';
		$tags = '';
		$description = '';
		$media = '';
		$via = $site->twitter_site;
	} else {
		if (!$entity instanceof ElggEntity) {
			$url = \elgg_get_current_url();
			$permalink = $referrer ? $referrer : $url;
			$guid = get_guid_from_url($permalink);
			if ($guid) {
				$entity = get_entity($guid);
			}
		}

		if (!is_discoverable($entity)) {
			return false;
		}

		$share_url = get_entity_permalink($entity);
		$title = get_discovery_title($entity);
		$description = get_discovery_description($entity);
		$tags = $entity->tags;
		$owner = $entity->getOwnerEntity();
		$via = $owner->twitter ? $owner->twitter : $site->twitter_site;
		$media = get_discovery_image_url($entity);
	}

	$elements = [];
	switch ($provider) {
		case 'facebook':
			$base_url = 'https://www.facebook.com/sharer/sharer.php';
			$elements = ['u' => $share_url, 't' => $title];
			break;
		case 'twitter':
			$base_url = 'https://twitter.com/intent/tweet';
			$elements = ['url' => $share_url, 'hashtags' => is_array($tags) ? implode(',', $tags) : $tags, 'via' => $via ? str_replace('@', '', $via) : ''];
			break;
		case 'linkedin':
			$base_url = 'http://www.linkedin.com/shareArticle';
			$elements = ['mini' => true, 'url' => $share_url, 'title' => $title, 'source' => $site->og_site_name, 'summary' => $description];
			break;
		case 'pinterest':
			$base_url = 'https://pinterest.com/pin/create/button/?url';
			$elements = ['url' => $share_url, 'media' => $media, 'description' => $title];
			break;
		case 'googleplus':
			$base_url = 'https://plus.google.com/share';
			$elements = ['url' => $share_url, 'title' => $title, 'summary' => $description];
			break;
	}

	if ($base_url) {
		return \elgg_http_add_url_query_elements($base_url, $elements);
	}

	return $share_url;
}

/**
 * Get entity permalink
 *
 * @param ElggEntity $entity   Entity
 * @param string     $viewtype Viewtype to render the permalink for
 * @return string
 */
function get_entity_permalink($entity, $viewtype = 'default') {
	if (!$entity instanceof ElggEntity) {
		return \elgg_get_current_url();
	}

	$user_guid = \elgg_get_logged_in_user_guid();
	$user_hash = get_user_hash($user_guid);
	$url = $entity->getVolatileData('discovery:share_url');
	if (!$url || \elgg_get_config('walled_garden')) {
		$title = \elgg_get_friendly_title(get_discovery_title($entity));
		$segments = ['permalink', $viewtype, $entity->guid, $title];
		$url = \elgg_normalize_url(implode('/', $segments));
	}

	return \elgg_http_add_url_query_elements($url, ['uh' => $user_hash]);
}

/**
 * Sniff a URL for a known entity GUID
 *
 * @param string $url URL
 * @return integer|false
 */
function get_guid_from_url($url) {
	$site_url = new SiteUrl(\elgg_get_site_url());
	$path = $site_url->getSitePath($url);
	if (!$path) {
		return false;
	}

	$guid = $path->getGuid();
	if ($guid) {
		return $guid;
	}

	$username = $path->getUsername();
	if ($username) {
		$user = get_user_by_username($username);
		if ($user) {
			return $user->guid;
		}
	}

	$container_guid = $path->getContainerGuid();
	if ($container_guid) {
		return $container_guid;
	}

	return false;
}

/**
 * Get entity from URL
 *
 * @param string $url URL
 * @return ElggEntity|false
 */
function get_entity_from_url($url) {
	$guid = get_guid_from_url($url);
	$entity = get_entity($guid);
	return $entity ?: \elgg_get_site_entity();
}

/**
 * Identify user by assigned hash
 *
 * @param string $hash Hash
 * @return ElggUser|false
 */
function get_user_from_hash($hash = '') {
	if (!$hash) {
		return false;
	}

	$users = \elgg_get_entities(['types' => 'user', 'metadata_names' => ['discovery_permanent_hash', 'discovery_temporary_hash'], 'metadata_values' => $hash, 'limit' => 1]);
	return $users ? $users[0] : false;
}

/**
 * Get or assign an identifying hash to the user
 *
 * @param int $guid User GUID
 * @return null|string
 */
function get_user_hash($guid) {
	$hash = '';
	$user = get_entity($guid);
	if (!$user) {
		$session = \elgg_get_session();
		if ($session->get('discovery_hash')) {
			$hash = $session->get('discovery_hash');
		} else if (get_input('uh')) {
			$hash = get_input('uh');
			$session->set('discovery_hash', $hash);
		}
	} else {
		$hash = $user->discovery_permanent_hash;
		if (!$hash) {
			$hash = md5($user->guid . time() . \elgg_generate_password());
			$user->discovery_permanent_hash = $hash;
		}
	}

	return (string) $hash;
}

/**
 * Get oEmbed representation of the page
 *
 * @param ElggEntity|string $entity    Entity (or URL for BC)
 * @param string            $format    Response format (json|xml)
 * @param int               $maxwidth  Max width of the embed
 * @param int               $maxheight Max height of the embed
 * @return array
 */
function get_oembed_response($entity, $format = 'json', $maxwidth = 0, $maxheight = 0) {
	$response = \elgg_call(ELGG_IGNORE_ACCESS, function () use ($entity, $maxwidth, $maxheight) {
		if ($entity instanceof ElggEntity) {
			$url = get_entity_permalink($entity, 'oembed');
		} else if (is_string($entity)) {
			$url = $entity;
			$url = urldecode($url);
			$entity = get_entity_from_url($url);
		} else {
			$url = \elgg_get_current_url();
			$entity = get_entity_from_url($url);
		}

		$params = ['origin' => $url, 'entity' => $entity, 'maxwidth' => $maxwidth, 'maxheight' => $maxheight];
		$oembed = ['type' => 'link', 'version' => '1.0', 'title' => get_discovery_title($entity)];
		return \elgg_trigger_event_results('export:entity', 'oembed', $params, $oembed);
	});
	$response['format'] = $format;
	return $response;
}

/**
 * Get OpenGraph, Twitter tags for a URL
 *
 * @param string $url URL
 * @return array
 */
function get_discovery_metatags($url) {
	$metatags = \elgg_call(ELGG_IGNORE_ACCESS, function () use ($url) {
		$entity = get_entity_from_url($url);
		return \elgg_trigger_event_results('metatags', 'discovery', ['entity' => $entity, 'url' => $url], []);
	});
	return $metatags;
}

/**
 * Get discoverable title
 *
 * @param ElggEntity $entity Entity
 * @return string
 */
function get_discovery_title($entity) {
	if (!$entity instanceof ElggEntity || !is_discoverable($entity)) {
		$entity = \elgg_get_site_entity();
	}

	if (!empty($entity->og_title)) {
		$title = $entity->og_title;
	} else {
		$title = $entity->getDisplayName();
	}

	return $title;
}

/**
 * Get discoverable description
 *
 * @param ElggEntity $entity Entity
 * @return string
 */
function get_discovery_description($entity) {
	if (!$entity instanceof ElggEntity || !is_discoverable($entity)) {
		$entity = \elgg_get_site_entity();
	}

	$description = '';
	if (!empty($entity->og_description)) {
		$description = $entity->og_description;
	} else if (!empty($entity->description)) {
		$description = $entity->description;
	}

	return \elgg_view('output/excerpt', ['text' => $description, 'num_chars' => \elgg_get_plugin_setting('excerpt_description', 'hypediscovery', 250)], false, false, 'default');
}

/**
 * Get discoverable icon object
 *
 * @param ElggEntity $entity Entity
 * @return ElggIcon|false
 */
function get_discovery_icon($entity) {
	foreach (['open_graph_image', 'cover', 'icon'] as $type) {
		$icon = $entity->getIcon('large', $type);
		if ($icon->exists()) {
			return $icon;
		}
	}

	return false;
}

/**
 * Get discoverable image URL
 *
 * @param ElggEntity $entity Entity
 * @return string|void
 */
function get_discovery_image_url($entity) {
	if (!$entity instanceof ElggEntity || !is_discoverable($entity)) {
		$entity = \elgg_get_site_entity();
	}

	$icon = get_discovery_icon($entity);
	if ($icon) {
		return \elgg_get_inline_url($icon);
	}
}

/**
 * Get discoverable keywords
 *
 * @param ElggEntity $entity Entity
 * @return string
 */
function get_discovery_keywords($entity) {
	if (!$entity instanceof ElggEntity || !is_discoverable($entity)) {
		$entity = \elgg_get_site_entity();
	}

	if (isset($entity->og_keywords)) {
		return $entity->og_keywords;
	} else if ($entity->tags) {
		return $entity->tags;
	}
}

/**
 * List discovery providers
 * @return array
 */
function get_discovery_providers() {
	return _decode_setting_array(\elgg_get_plugin_setting('providers', 'hypediscovery'));
}

/**
 * Returns configured discoverable type/subtype pairs
 * @return array
 */
function get_discoverable_type_subtype_pairs() {
	return _decode_setting_array(\elgg_get_plugin_setting('discovery_type_subtype_pairs', 'hypediscovery'));
}

/**
 * Returns configured embeddable type/subtype pairs
 * @return array
 */
function get_embeddable_type_subtype_pairs() {
	return _decode_setting_array(\elgg_get_plugin_setting('embed_type_subtype_pairs', 'hypediscovery'));
}

/**
 * Decode an array stored in a plugin setting. Prefers JSON, falls back to
 * legacy serialize() with object instantiation disabled.
 *
 * @param string|null $raw Raw setting value
 * @return array
 */
function _decode_setting_array($raw) {
	if (!$raw) {
		return [];
	}

	$decoded = json_decode($raw, true);
	if (!is_array($decoded)) {
		$decoded = @unserialize($raw, ['allowed_classes' => false]);
	}

	return is_array($decoded) ? $decoded : [];
}
