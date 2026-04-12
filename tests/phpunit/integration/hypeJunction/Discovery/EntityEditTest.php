<?php

namespace hypeJunction\Discovery;

use Elgg\IntegrationTestCase;

/**
 * Lock in entity-level open graph metadata persistence.
 * The discovery/edit action sets og_title, og_description, og_keywords,
 * discoverable, embeddable — verify metadata round-trips through an ElggObject.
 */
class EntityEditTest extends IntegrationTestCase {

	public function up() {}
	public function down() {}

	public function testOpenGraphMetadataPersists(): void {
		$user = $this->createUser();
		$entity = new \ElggObject();
		$entity->setSubtype('blog');
		$entity->owner_guid = $user->guid;
		$entity->container_guid = elgg_get_site_entity()->guid;
		$entity->access_id = ACCESS_PUBLIC;
		$entity->title = 'OG test';

		$entity->og_title = 'Custom OG Title';
		$entity->og_description = 'Custom OG Description';
		$entity->og_keywords = ['alpha', 'beta'];
		$entity->discoverable = true;
		$entity->embeddable = true;
		$this->assertNotFalse($entity->save());

		_elgg_services()->entityCache->delete($entity->guid);
		$loaded = get_entity($entity->guid);

		$this->assertEquals('Custom OG Title', $loaded->og_title);
		$this->assertEquals('Custom OG Description', $loaded->og_description);
		$this->assertEquals(['alpha', 'beta'], (array) $loaded->og_keywords);
		$this->assertTrue((bool) $loaded->discoverable);
		$this->assertTrue((bool) $loaded->embeddable);

		$entity->delete();
	}

	public function testNonOwnerCannotEditEntity(): void {
		$owner = $this->createUser();
		$other = $this->createUser();
		$entity = $this->createObject(['subtype' => 'blog', 'owner_guid' => $owner->guid]);

		$this->assertTrue($entity->canEdit($owner->guid));
		$this->assertFalse($entity->canEdit($other->guid));
	}
}
