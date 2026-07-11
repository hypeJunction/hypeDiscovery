<?php

namespace hypeJunction\Discovery;

use Elgg\Exceptions\Http\PageNotFoundException;
use Elgg\IntegrationTestCase;

/**
 * The `opengraph` route (/opengraph/{segments}) renders through the dispatcher
 * resources/opengraph.php, which delegates to Router::opengraphHandler().
 *
 * The dispatcher view was missing after the 2.x -> 7.x port, so /opengraph/edit/<guid>
 * and /opengraph/share/<guid> 404'd via ResourceNotFoundException (a 500-class
 * "view missing") instead of resolving (bd elgg-migrate-ckn0c). These tests exercise
 * the route -> dispatcher -> handler path and lock in that unresolved input is a clean
 * PageNotFoundException, never a fatal.
 */
class OpengraphDispatcherTest extends IntegrationTestCase {

	public function up() {
		$libFile = dirname(__DIR__, 5) . '/lib/functions.php';
		if (!function_exists('hypeJunction\\Discovery\\is_discoverable_type')) {
			require_once $libFile;
		}
	}

	public function down() {}

	private function render(string $segments): string {
		return elgg_view_resource('opengraph', ['segments' => $segments]);
	}

	public function testEmptySegmentsThrowPageNotFound(): void {
		$this->expectException(PageNotFoundException::class);
		$this->render('');
	}

	public function testUnknownSectionThrowsPageNotFound(): void {
		// opengraphHandler switches on segments[0]; an unmatched value yields no
		// content -> false -> the dispatcher 404s rather than fataling.
		$this->expectException(PageNotFoundException::class);
		$this->render('not-a-real-section/123');
	}

	public function testEditWithMissingEntityThrowsPageNotFound(): void {
		$user = $this->createUser();
		_elgg_services()->session_manager->setLoggedInUser($user);
		$this->expectException(PageNotFoundException::class);
		try {
			$this->render('edit/999999999');
		} finally {
			_elgg_services()->session_manager->removeLoggedInUser();
		}
	}

	public function testEditOfNonEditableEntityThrowsPageNotFound(): void {
		$owner = $this->createUser();
		$other = $this->createUser();
		$entity = $this->createObject([
			'subtype' => 'blog',
			'owner_guid' => $owner->guid,
			'access_id' => ACCESS_PUBLIC,
		]);
		// $other cannot edit $owner's entity -> handler returns false -> 404.
		_elgg_services()->session_manager->setLoggedInUser($other);
		$this->expectException(PageNotFoundException::class);
		try {
			$this->render("edit/{$entity->guid}");
		} finally {
			_elgg_services()->session_manager->removeLoggedInUser();
		}
	}
}
