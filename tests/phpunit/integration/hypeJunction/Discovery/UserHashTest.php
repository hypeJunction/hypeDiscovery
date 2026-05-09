<?php

namespace hypeJunction\Discovery;

use Elgg\IntegrationTestCase;

/**
 * Lock in user hash generation, persistence, and lookup behavior.
 */
class UserHashTest extends IntegrationTestCase {

	public function up() {
		$libFile = dirname(__DIR__, 5) . '/lib/functions.php';
		if (!function_exists('hypeJunction\\Discovery\\get_user_hash')) {
			require_once $libFile;
		}
	}

	public function down() {}

	/**
     * @return void
     */
    public function testGetUserHashAssignsPermanentHash(): void {
		$user = $this->createUser();
		$this->assertEmpty($user->discovery_permanent_hash);

		$hash = get_user_hash($user->guid);
		$this->assertNotEmpty($hash);
		$this->assertEquals(32, strlen($hash)); // md5

		// And persists
		$user2 = get_entity($user->guid);
		$this->assertEquals($hash, $user2->discovery_permanent_hash);
	}

	/**
     * @return void
     */
    public function testGetUserHashReturnsSameHashOnSubsequentCalls(): void {
		$user = $this->createUser();
		$h1 = get_user_hash($user->guid);
		$h2 = get_user_hash($user->guid);
		$this->assertEquals($h1, $h2);
	}

	/**
     * @return void
     */
    public function testGetUserFromHashReturnsFalseForEmpty(): void {
		$this->assertFalse(get_user_from_hash(''));
	}

	/**
     * @return void
     */
    public function testGetUserFromHashResolvesKnownUser(): void {
		$user = $this->createUser();
		$hash = get_user_hash($user->guid);

		$resolved = get_user_from_hash($hash);
		$this->assertNotFalse($resolved);
		$this->assertEquals($user->guid, $resolved->guid);
	}
}
