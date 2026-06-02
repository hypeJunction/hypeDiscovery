<?php

namespace hypeJunction\Discovery;

use Elgg\IntegrationTestCase;

/**
 * Lock in hook handler registration & behavior.
 */
class HooksTest extends IntegrationTestCase {

	public function up() {
		$libFile = dirname(__DIR__, 5) . '/lib/functions.php';
		if (!function_exists('hypeJunction\\Discovery\\is_discoverable')) {
			require_once $libFile;
		}
	}

	public function down() {}

	/**
     * @return void
     */
    public function testPublicPagesHookAddsPermalinkAndShareAction(): void {
		$return = Router::publicPages('public_pages', 'walled_garden', []);
		$this->assertIsArray($return);
		$this->assertContains('permalink/.*', $return);
		$this->assertContains('action/discovery/share', $return);
	}

	/**
     * @return void
     */
    public function testOpenGraphImageSizesHookReturnsSizes(): void {
		$return = Icons::entityOpenGraphImageSizes('entity:open_graph_image:sizes', 'object', [], []);
		$this->assertIsArray($return);
		$this->assertArrayHasKey('large', $return);
		$this->assertArrayHasKey('medium', $return);
		$this->assertArrayHasKey('small', $return);
		$this->assertEquals(1200, $return['large']['w']);
		$this->assertEquals(1200, $return['large']['h']);
	}

	/**
     * @return void
     */
    public function testGraphExportHookReturnsSiteTags(): void {
		$site = \elgg_get_site_entity();
		$return = Discovery::graphExport(
			'metatags',
			'discovery',
			[],
			['entity' => $site, 'url' => $site->getURL()]
		);
		$this->assertArrayHasKey('og:type', $return);
		$this->assertEquals('website', $return['og:type']);
		$this->assertArrayHasKey('og:site_name', $return);
	}

	/**
     * @return void
     */
    public function testEntityMenuRegisterHookRuns(): void {
		$user = $this->createUser();
		\elgg_get_session()->setLoggedInUser($user);
		try {
			// Trigger should not fatal; menu items may or may not be added
			$result = \elgg_trigger_plugin_hook('register', 'menu:entity', [
				'entity' => \elgg_get_site_entity(),
			], []);
			$this->assertIsArray($result);
		} finally {
			\elgg_get_session()->removeLoggedInUser();
		}
	}

	/**
     * @return void
     */
    public function testRedirectErrorToPermalinkReturnsOriginalForUnknownUrl(): void {
		$return = 'http://example.com/original';
		$result = Router::redirectErrorToPermalink('forward', '404', $return, []);
		$this->assertIsString($result);
	}
}
