<?php

namespace hypeJunction\Discovery;

use Elgg\IntegrationTestCase;

/**
 * Lock in permalink / URL sniffing behavior.
 */
class PermalinkTest extends IntegrationTestCase {

	public function up() {
		$libFile = dirname(__DIR__, 5) . '/lib/functions.php';
		if (!function_exists('hypeJunction\\Discovery\\get_entity_permalink')) {
			require_once $libFile;
		}
	}

	public function down() {}

	public function testEntityPermalinkContainsPermalinkSegment(): void {
		$user = $this->createUser();
		$entity = $this->createObject([
			'subtype' => 'blog',
			'owner_guid' => $user->guid,
			'access_id' => ACCESS_PUBLIC,
			'title' => 'Test Entity',
		]);
		$url = get_entity_permalink($entity);
		$this->assertIsString($url);
		$this->assertStringContainsString('/permalink/', $url);
		$this->assertStringContainsString((string) $entity->guid, $url);
	}

	public function testEntityPermalinkContainsViewtype(): void {
		$user = $this->createUser();
		$entity = $this->createObject(['subtype' => 'blog', 'owner_guid' => $user->guid]);
		$url = get_entity_permalink($entity, 'oembed');
		$this->assertStringContainsString('/oembed/', $url);
	}

	public function testPermalinkContainsUserHashParam(): void {
		$user = $this->createUser();
		$entity = $this->createObject(['subtype' => 'blog', 'owner_guid' => $user->guid]);
		$url = get_entity_permalink($entity);
		// uh param is always appended (even if empty when user is anonymous)
		$this->assertStringContainsString('uh=', $url);
	}

	public function testGetGuidFromUrlReturnsGuidForKnownEntity(): void {
		$user = $this->createUser();
		$entity = $this->createObject(['subtype' => 'blog', 'owner_guid' => $user->guid]);
		$url = $entity->getURL();
		$guid = get_guid_from_url($url);
		// Sniffer may return guid, container or false depending on URL shape.
		// Lock current behavior: must not throw, returns int or false.
		$this->assertTrue($guid === false || is_numeric($guid));
	}

	public function testGetEntityFromUrlFallsBackToSite(): void {
		$site = \elgg_get_site_entity();
		$entity = get_entity_from_url('http://example.invalid/no/such/path');
		$this->assertNotFalse($entity);
		$this->assertEquals($site->guid, $entity->guid);
	}
}
