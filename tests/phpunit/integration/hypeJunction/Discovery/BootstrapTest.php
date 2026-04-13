<?php

namespace hypeJunction\Discovery;

use Elgg\IntegrationTestCase;

/**
 * Pre-migration behavior lock-in: plugin bootstrap, routes, actions, hooks,
 * views and library functions are registered as expected.
 */
class BootstrapTest extends IntegrationTestCase {

	public function up() {
		$libFile = dirname(__DIR__, 5) . '/lib/functions.php';
		if (file_exists($libFile) && !function_exists('hypeJunction\\Discovery\\is_discoverable')) {
			require_once $libFile;
		}
	}

	public function down() {}

	public function testPluginLoadable(): void {
		$plugin = elgg_get_plugin_from_id('hypediscovery');
		$this->assertNotNull($plugin, 'Plugin hypeDiscovery should be loadable');
	}

	public function testSettingsSaveActionRegistered(): void {
		$this->assertTrue(
			elgg_action_exists('hypediscovery/settings/save'),
			'Action hypediscovery/settings/save should be registered'
		);
	}

	public function testDiscoverySiteActionRegistered(): void {
		$this->assertTrue(
			elgg_action_exists('discovery/site'),
			'Action discovery/site should be registered (admin)'
		);
	}

	public function testDiscoveryShareActionRegistered(): void {
		$this->assertTrue(
			elgg_action_exists('discovery/share'),
			'Action discovery/share should be registered (public)'
		);
	}

	public function testDiscoveryEditActionRegistered(): void {
		$this->assertTrue(
			elgg_action_exists('discovery/edit'),
			'Action discovery/edit should be registered'
		);
	}

	public function testSettingsViewExists(): void {
		$this->assertTrue(
			elgg_view_exists('plugins/hypediscovery/settings'),
			'Plugin settings view should exist'
		);
	}

	public function testPermalinkResourceViewExists(): void {
		$this->assertTrue(
			elgg_view_exists('resources/permalink'),
			'resources/permalink view should exist'
		);
	}

	public function testFormsShareViewExists(): void {
		$this->assertTrue(elgg_view_exists('forms/discovery/share'));
	}

	public function testFormsEditViewExists(): void {
		$this->assertTrue(elgg_view_exists('forms/discovery/edit'));
	}

	public function testFormsSiteViewExists(): void {
		$this->assertTrue(elgg_view_exists('forms/discovery/site'));
	}

	public function testFrameworkPublicViewExists(): void {
		$this->assertTrue(elgg_view_exists('framework/discovery/public'));
	}

	public function testFrameworkIconViewExists(): void {
		$this->assertTrue(elgg_view_exists('framework/discovery/icon'));
	}

	public function testOembedResourceViewExists(): void {
		// oEmbed viewtype for resources/permalink
		$this->assertTrue(
			elgg_view_exists('resources/permalink', 'oembed')
			|| elgg_view_exists('resources/permalink', 'json')
		);
	}

	public function testLibraryFunctionsLoaded(): void {
		$this->assertTrue(function_exists('hypeJunction\\Discovery\\is_discoverable'));
		$this->assertTrue(function_exists('hypeJunction\\Discovery\\is_embeddable'));
		$this->assertTrue(function_exists('hypeJunction\\Discovery\\get_entity_permalink'));
		$this->assertTrue(function_exists('hypeJunction\\Discovery\\get_guid_from_url'));
		$this->assertTrue(function_exists('hypeJunction\\Discovery\\get_entity_from_url'));
		$this->assertTrue(function_exists('hypeJunction\\Discovery\\get_user_hash'));
		$this->assertTrue(function_exists('hypeJunction\\Discovery\\get_discovery_title'));
		$this->assertTrue(function_exists('hypeJunction\\Discovery\\get_discovery_description'));
		$this->assertTrue(function_exists('hypeJunction\\Discovery\\get_discovery_image_url'));
		$this->assertTrue(function_exists('hypeJunction\\Discovery\\get_discovery_keywords'));
		$this->assertTrue(function_exists('hypeJunction\\Discovery\\get_discovery_providers'));
		$this->assertTrue(function_exists('hypeJunction\\Discovery\\get_discoverable_type_subtype_pairs'));
		$this->assertTrue(function_exists('hypeJunction\\Discovery\\get_embeddable_type_subtype_pairs'));
		$this->assertTrue(function_exists('hypeJunction\\Discovery\\get_share_action_url'));
		$this->assertTrue(function_exists('hypeJunction\\Discovery\\get_provider_url'));
		$this->assertTrue(function_exists('hypeJunction\\Discovery\\get_oembed_response'));
		$this->assertTrue(function_exists('hypeJunction\\Discovery\\get_discovery_metatags'));
	}

	public function testClassesAutoload(): void {
		$this->assertTrue(class_exists(Discovery::class));
		$this->assertTrue(class_exists(Icons::class));
		$this->assertTrue(class_exists(Menus::class));
		$this->assertTrue(class_exists(Router::class));
		$this->assertTrue(class_exists(Analytics::class));
	}
}
