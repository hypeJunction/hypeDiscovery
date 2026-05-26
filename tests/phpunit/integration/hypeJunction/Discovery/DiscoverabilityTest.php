<?php

namespace hypeJunction\Discovery;

use Elgg\IntegrationTestCase;
use ElggObject;

/**
 * Lock in is_discoverable() / is_embeddable() behavior.
 */
class DiscoverabilityTest extends IntegrationTestCase {

	public function up() {
		$libFile = dirname(__DIR__, 5) . '/lib/functions.php';
		if (!function_exists('hypeJunction\\Discovery\\is_discoverable')) {
			require_once $libFile;
		}

		// Configure plugin settings so "object::blog" is discoverable
		$plugin = \elgg_get_plugin_from_id('hypediscovery');
		if ($plugin) {
			$this->previous_discoverable = $plugin->getSetting('discovery_type_subtype_pairs');
			$this->previous_embeddable = $plugin->getSetting('embed_type_subtype_pairs');
			$plugin->setSetting('discovery_type_subtype_pairs', json_encode(['object::blog']));
			$plugin->setSetting('embed_type_subtype_pairs', json_encode(['object::blog']));
		}
	}

	public function down() {
		$plugin = \elgg_get_plugin_from_id('hypediscovery');
		if ($plugin) {
			$plugin->setSetting('discovery_type_subtype_pairs', $this->previous_discoverable ?? '');
			$plugin->setSetting('embed_type_subtype_pairs', $this->previous_embeddable ?? '');
		}
	}

	private $previous_discoverable;
	private $previous_embeddable;

	public function testSiteEntityAlwaysDiscoverable(): void {
		$site = \elgg_get_site_entity();
		$this->assertTrue(is_discoverable($site));
	}

	public function testNonEntityNotDiscoverable(): void {
		$this->assertFalse(is_discoverable(null));
		$this->assertFalse(is_discoverable('not an entity'));
	}

	public function testPublicEntityOfRegisteredTypeIsDiscoverable(): void {
		$entity = $this->createObject(['subtype' => 'blog', 'access_id' => ACCESS_PUBLIC]);
		$this->assertTrue(is_discoverable($entity));
	}

	public function testPrivateEntityIsNotDiscoverable(): void {
		$entity = $this->createObject(['subtype' => 'blog', 'access_id' => ACCESS_PRIVATE]);
		$this->assertFalse(is_discoverable($entity));
	}

	public function testUnregisteredTypeNotDiscoverable(): void {
		$entity = $this->createObject(['subtype' => 'not_registered_subtype', 'access_id' => ACCESS_PUBLIC]);
		$this->assertFalse(is_discoverable($entity));
	}

	public function testExplicitDiscoverableMetadataOverridesAccess(): void {
		$entity = $this->createObject(['subtype' => 'blog', 'access_id' => ACCESS_PUBLIC]);
		$entity->discoverable = false;
		$this->assertFalse(is_discoverable($entity));

		$entity->discoverable = true;
		$this->assertTrue(is_discoverable($entity));
	}

	public function testSiteIsNotEmbeddable(): void {
		$site = \elgg_get_site_entity();
		$this->assertFalse(is_embeddable($site));
	}

	public function testEmbeddableRequiresFlag(): void {
		$entity = $this->createObject(['subtype' => 'blog', 'access_id' => ACCESS_PUBLIC]);
		$this->assertFalse(is_embeddable($entity)); // no embeddable flag
		$entity->embeddable = true;
		$this->assertTrue(is_embeddable($entity));
	}

	public function testEmbeddableRequiresDiscoverable(): void {
		$entity = $this->createObject(['subtype' => 'blog', 'access_id' => ACCESS_PRIVATE]);
		$entity->embeddable = true;
		// Not discoverable => not embeddable
		$this->assertFalse(is_embeddable($entity));
	}
}
