<?php

namespace hypeJunction\Discovery;

use Elgg\Event;
use Elgg\IntegrationTestCase;
use hypeJunction\Discovery\Upgrades\EncodeSettingsAsJson;

/**
 * Regression coverage for the Elgg 7.x migration fixes applied to hypeDiscovery
 * that are not already asserted by the sibling integration tests.
 *
 * Each test pins the FIXED behaviour of a specific migration commit so a
 * forward-port regression (re-introducing a removed symbol, breaking the ESM
 * form, mis-shaping the upgrade batch, camelCasing the plugin id, etc.) fails
 * loudly instead of at runtime on a booted Elgg 7 site.
 */
class MigrationFixesTest extends IntegrationTestCase {

	private $previous_providers;
	private $previous_discoverable;

	public function up() {
		$libFile = dirname(__DIR__, 5) . '/lib/functions.php';
		if (!function_exists('hypeJunction\\Discovery\\get_guid_from_url')) {
			require_once $libFile;
		}

		$plugin = elgg_get_plugin_from_id('hypediscovery');
		if ($plugin) {
			$this->previous_providers = $plugin->getSetting('providers');
			$this->previous_discoverable = $plugin->getSetting('discovery_type_subtype_pairs');
		}
	}

	public function down() {
		$plugin = elgg_get_plugin_from_id('hypediscovery');
		if ($plugin) {
			$plugin->setSetting('providers', (string) $this->previous_providers);
			$plugin->setSetting('discovery_type_subtype_pairs', (string) $this->previous_discoverable);
		}
	}

	private function makeEvent($value, array $params = []): Event {
		$event = $this->getMockBuilder(Event::class)->disableOriginalConstructor()->getMock();
		$event->method('getValue')->willReturn($value);
		$event->method('getParams')->willReturn($params);
		$event->method('getParam')->willReturnCallback(function (string $key, $default = null) use ($params) {
			return array_key_exists($key, $params) ? $params[$key] : $default;
		});
		return $event;
	}

	private function pluginRoot(): string {
		return dirname(__DIR__, 5);
	}

	/**
	 * bdcec54 — Upgrade\Batch became abstract in 6.x. EncodeSettingsAsJson must
	 * extend the abstract base, pin its version, and expose run(Result,$offset):Result.
	 */
	public function testEncodeSettingsBatchExtendsAbstractBaseWithCorrectShape(): void {
		$this->assertTrue(
			(new \ReflectionClass(\Elgg\Upgrade\Batch::class))->isAbstract(),
			'Core \\Elgg\\Upgrade\\Batch must be abstract on 6.x+'
		);

		$batch = new EncodeSettingsAsJson();
		$this->assertInstanceOf(\Elgg\Upgrade\Batch::class, $batch);
		$this->assertSame(2026041600, $batch->getVersion());
		$this->assertSame(3, $batch->countItems());

		$run = new \ReflectionMethod($batch, 'run');
		$this->assertSame(
			\Elgg\Upgrade\Result::class,
			(string) $run->getReturnType(),
			'run() must be typed to return \\Elgg\\Upgrade\\Result'
		);
	}

	/**
	 * bdcec54 / f1b8143 — running the batch over a legacy serialize()d setting
	 * re-encodes it to JSON and reports success without instantiating objects.
	 */
	public function testEncodeSettingsBatchReEncodesLegacySerializedSetting(): void {
		$plugin = elgg_get_plugin_from_id('hypediscovery');
		$plugin->setSetting('providers', serialize(['facebook', 'twitter']));

		$result = new \Elgg\Upgrade\Result();
		$batch = new EncodeSettingsAsJson();
		$batch->run($result, 0);

		$stored = $plugin->getSetting('providers');
		$this->assertJson($stored, 'legacy serialized setting must be rewritten as JSON');
		$this->assertSame(['facebook', 'twitter'], json_decode($stored, true));
	}

	/**
	 * 57d5775 — the discovery edit form was migrated to a real ESM module and the
	 * removed getManifest() was dropped; edit.php loads it via elgg_import_esm().
	 */
	public function testEditFormUsesEsmModuleWithoutRemovedGetManifest(): void {
		$mjs = $this->pluginRoot() . '/views/default/forms/discovery/edit.mjs';
		$this->assertFileExists($mjs);

		$src = (string) file_get_contents($mjs);
		$this->assertMatchesRegularExpression('/^\s*import\s/m', $src, 'edit.mjs must be an ES module');
		$this->assertStringNotContainsString('getManifest', $src, 'getManifest() was removed on 7.x');
		$this->assertStringNotContainsString('define(', $src, 'legacy AMD define() must be gone');

		$form = (string) file_get_contents($this->pluginRoot() . '/views/default/forms/discovery/edit.php');
		$this->assertStringContainsString(
			"elgg_import_esm('forms/discovery/edit')",
			$form,
			'edit form view must import the ESM module'
		);
	}

	/**
	 * c6d8266 — get_user_by_username() was removed in 5.x; get_guid_from_url must
	 * resolve a username-shaped profile URL to its user guid via elgg_get_user_by_username().
	 */
	public function testGetGuidFromUrlResolvesUsernameToUserGuid(): void {
		$user = $this->createUser();
		$url = elgg_get_site_url() . 'profile/' . $user->username;

		$guid = get_guid_from_url($url);
		$this->assertSame((int) $user->guid, (int) $guid);
	}

	/**
	 * 440518c / 6fce766 — namespaced helpers must be required at the top of
	 * elgg-plugin.php (composer autoload.files is too late for git-tracked customs).
	 */
	public function testElggPluginManifestRequiresLibFunctionsAtTop(): void {
		$manifest = (string) file_get_contents($this->pluginRoot() . '/elgg-plugin.php');
		$this->assertMatchesRegularExpression(
			'/^\s*(<\?php\s*)?require_once\s+__DIR__\s*\.\s*[\'"]\/lib\/functions\.php[\'"]\s*;/m',
			$manifest,
			'elgg-plugin.php must require_once lib/functions.php before the returned config array'
		);
	}

	/**
	 * 04c56e6 — every plugin-id callsite must use the lowercase id. camelCase
	 * silently resolves to null on 4.x+, so lowercase must resolve and camelCase must not.
	 */
	public function testLowercasePluginIdResolvesCamelCaseDoesNot(): void {
		$this->assertNotNull(
			elgg_get_plugin_from_id('hypediscovery'),
			'lowercase plugin id must resolve'
		);
		$this->assertNull(
			elgg_get_plugin_from_id('hypeDiscovery'),
			'camelCase plugin id must NOT resolve (proves why lowercase is required)'
		);
	}

	/**
	 * f3c902e / 4fff1c5 — hooks->events migration: menu setup handlers take a
	 * single \Elgg\Event and return an array of ElggMenuItem (never ->add()).
	 */
	public function testShareMenuHandlerReturnsArrayOfItemsWhenConfigured(): void {
		$plugin = elgg_get_plugin_from_id('hypediscovery');
		$plugin->setSetting('providers', json_encode(['facebook', 'twitter']));
		$plugin->setSetting('discovery_type_subtype_pairs', json_encode(['object::blog']));

		$entity = $this->createObject(['subtype' => 'blog', 'access_id' => ACCESS_PUBLIC]);
		$result = Menus::shareMenuSetup($this->makeEvent([], ['entity' => $entity]));

		$this->assertIsArray($result);
		$this->assertNotEmpty($result);
		$this->assertContainsOnlyInstancesOf(\ElggMenuItem::class, $result);
	}

	/**
	 * fb93f20 / f5ffae6 / 40e40ff / 7d0008e / a1730f1 — the removed language and
	 * CSS/JS helpers must be replaced by their 7.x equivalents across the plugin.
	 */
	public function testSevenXCoreHelperReplacementsAreUsed(): void {
		$src = (string) file_get_contents($this->pluginRoot() . '/lib/functions.php');
		$globs = [
			$this->pluginRoot() . '/classes/hypeJunction/Discovery/*.php',
			$this->pluginRoot() . '/classes/hypeJunction/Discovery/*/*.php',
			$this->pluginRoot() . '/views/*/*.php',
			$this->pluginRoot() . '/views/*/*/*.php',
			$this->pluginRoot() . '/views/*/*/*/*.php',
		];
		foreach ($globs as $glob) {
			foreach (glob($glob) as $file) {
				$src .= (string) file_get_contents($file);
			}
		}

		// Removed symbols must be absent (their presence fatals on 7.x).
		$this->assertDoesNotMatchRegularExpression('/(?<![\w>$:\\\\])get_current_language\s*\(/', $src);
		$this->assertDoesNotMatchRegularExpression('/(?<![\w>$:\\\\])elgg_get_language\s*\(/', $src);
		$this->assertDoesNotMatchRegularExpression('/(?<![\w>$:\\\\])elgg_load_css\s*\(/', $src);
		$this->assertDoesNotMatchRegularExpression('/(?<![\w>$:\\\\])elgg_unregister_css\s*\(/', $src);

		// The 7.x replacement for the language lookup must be present.
		$this->assertStringContainsString('elgg_get_current_language(', $src);
	}
}
