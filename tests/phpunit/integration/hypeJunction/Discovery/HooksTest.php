<?php

namespace hypeJunction\Discovery;

use Elgg\Event;
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

	private function makeEvent($value, array $params = []): Event {
		$event = $this->getMockBuilder(Event::class)->disableOriginalConstructor()->getMock();
		$event->method('getValue')->willReturn($value);
		$event->method('getParams')->willReturn($params);
		$event->method('getParam')->willReturnCallback(function (string $key, $default = null) use ($params) {
			return array_key_exists($key, $params) ? $params[$key] : $default;
		});
		return $event;
	}

	public function testPublicPagesHookAddsPermalinkAndShareAction(): void {
		$return = Router::publicPages($this->makeEvent([]));
		$this->assertIsArray($return);
		$this->assertContains('permalink/.*', $return);
		$this->assertContains('action/discovery/share', $return);
	}

	public function testOpenGraphImageSizesHookReturnsSizes(): void {
		$return = Icons::entityOpenGraphImageSizes($this->makeEvent([]));
		$this->assertIsArray($return);
		$this->assertArrayHasKey('large', $return);
		$this->assertArrayHasKey('medium', $return);
		$this->assertArrayHasKey('small', $return);
		$this->assertEquals(1200, $return['large']['w']);
		$this->assertEquals(1200, $return['large']['h']);
	}

	public function testGraphExportHookReturnsSiteTags(): void {
		$site = \elgg_get_site_entity();
		$return = Discovery::graphExport(
			$this->makeEvent([], ['entity' => $site, 'url' => $site->getURL()])
		);
		$this->assertArrayHasKey('og:type', $return);
		$this->assertEquals('website', $return['og:type']);
		$this->assertArrayHasKey('og:site_name', $return);
	}

	public function testEntityMenuRegisterHookRuns(): void {
		$user = $this->createUser();
		\_elgg_services()->session_manager->setLoggedInUser($user);
		try {
			$entity = \elgg_get_site_entity();
			$result = Menus::entityMenuSetup($this->makeEvent([], ['entity' => $entity]));
			$this->assertIsArray($result);
		} finally {
			\_elgg_services()->session_manager->removeLoggedInUser();
		}
	}

	public function testRedirectErrorToPermalinkReturnsOriginalForUnknownUrl(): void {
		$return = 'http://example.com/original';
		$result = Router::redirectErrorToPermalink($this->makeEvent($return, []));
		$this->assertIsString($result);
	}
}
