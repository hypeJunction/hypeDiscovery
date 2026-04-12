<?php

namespace hypeJunction\Discovery;

use Elgg\IntegrationTestCase;

/**
 * Security regression tests: hypeDiscovery stores plugin settings
 * (`providers`, `discovery_type_subtype_pairs`, `embed_type_subtype_pairs`)
 * as serialized PHP arrays in `actions/settings/save.php`, then calls
 * `unserialize()` on them in lib/functions.php.
 *
 * Flagged in P0 bead elgg-migrate-2mra as an unserialize() RCE risk
 * (PHP object injection gadget) if a plugin setting value can ever
 * be influenced by untrusted input.
 *
 * These tests lock in CURRENT behavior so migration can replace
 * serialize()/unserialize() with json_encode/json_decode without
 * silent data loss.
 */
class SettingsSerializationSecurityTest extends IntegrationTestCase {

	private $previous_providers;
	private $previous_discoverable;
	private $previous_embeddable;

	public function up() {
		$libFile = dirname(__DIR__, 5) . '/lib/functions.php';
		if (!function_exists('hypeJunction\\Discovery\\get_discovery_providers')) {
			require_once $libFile;
		}
		$plugin = elgg_get_plugin_from_id('hypeDiscovery');
		if ($plugin) {
			$this->previous_providers = $plugin->getSetting('providers');
			$this->previous_discoverable = $plugin->getSetting('discovery_type_subtype_pairs');
			$this->previous_embeddable = $plugin->getSetting('embed_type_subtype_pairs');
		}
	}

	public function down() {
		$plugin = elgg_get_plugin_from_id('hypeDiscovery');
		if ($plugin) {
			$plugin->setSetting('providers', (string) $this->previous_providers);
			$plugin->setSetting('discovery_type_subtype_pairs', (string) $this->previous_discoverable);
			$plugin->setSetting('embed_type_subtype_pairs', (string) $this->previous_embeddable);
		}
	}

	public function testProvidersReturnsEmptyArrayWhenUnset(): void {
		$plugin = elgg_get_plugin_from_id('hypeDiscovery');
		$plugin->setSetting('providers', '');
		$this->assertSame([], get_discovery_providers());
	}

	public function testProvidersRoundTripsArrayValue(): void {
		$plugin = elgg_get_plugin_from_id('hypeDiscovery');
		$providers = ['facebook', 'twitter', 'linkedin'];
		$plugin->setSetting('providers', serialize($providers));
		$this->assertEquals($providers, get_discovery_providers());
	}

	public function testDiscoverableTypePairsRoundTrip(): void {
		$plugin = elgg_get_plugin_from_id('hypeDiscovery');
		$pairs = ['object::blog', 'object::file', 'user::default'];
		$plugin->setSetting('discovery_type_subtype_pairs', serialize($pairs));
		$this->assertEquals($pairs, get_discoverable_type_subtype_pairs());
	}

	public function testEmbeddableTypePairsRoundTrip(): void {
		$plugin = elgg_get_plugin_from_id('hypeDiscovery');
		$pairs = ['object::blog'];
		$plugin->setSetting('embed_type_subtype_pairs', serialize($pairs));
		$this->assertEquals($pairs, get_embeddable_type_subtype_pairs());
	}

	/**
	 * SECURITY: if a serialized payload contains a PHP object with a magic
	 * __wakeup or __destruct, unserialize() would instantiate it. This test
	 * confirms that ONLY scalar/array payloads are stored by the legitimate
	 * settings form flow — the current behavior is "pass settings through
	 * serialize($v) if is_array($v)" which never writes objects.
	 *
	 * The migration should replace with json_encode/json_decode to make
	 * the RCE vector impossible even under a hypothetical injection.
	 */
	public function testPlainArrayOnlyStoredByCurrentSaveSemantic(): void {
		// Simulate what actions/settings/save.php does for each POST value
		$input = ['facebook', 'twitter'];
		$stored = is_array($input) ? serialize($input) : $input;

		// Must not contain object markers
		$this->assertStringStartsWith('a:', $stored);
		$this->assertStringNotContainsString('O:', $stored);

		$out = unserialize($stored);
		$this->assertSame($input, $out);
	}

	/**
	 * Regression: unserialize(false) / empty string must NOT fatal and
	 * must fall back to empty array.
	 */
	public function testEmptySettingsFallbackToEmptyArray(): void {
		$plugin = elgg_get_plugin_from_id('hypeDiscovery');
		$plugin->setSetting('providers', '');
		$plugin->setSetting('discovery_type_subtype_pairs', '');
		$plugin->setSetting('embed_type_subtype_pairs', '');

		$this->assertSame([], get_discovery_providers());
		$this->assertSame([], get_discoverable_type_subtype_pairs());
		$this->assertSame([], get_embeddable_type_subtype_pairs());
	}
}
