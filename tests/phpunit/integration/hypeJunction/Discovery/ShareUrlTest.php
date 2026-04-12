<?php

namespace hypeJunction\Discovery;

use Elgg\IntegrationTestCase;

/**
 * Lock in share URL and provider URL construction.
 */
class ShareUrlTest extends IntegrationTestCase {

	public function up() {
		$libFile = dirname(__DIR__, 5) . '/lib/functions.php';
		if (!function_exists('hypeJunction\\Discovery\\get_share_action_url')) {
			require_once $libFile;
		}
	}

	public function down() {}

	public function testGetShareActionUrlContainsActionPath(): void {
		$url = get_share_action_url('twitter', 123, 'http://example.com/ref', 'http://example.com/share');
		$this->assertStringContainsString('action/discovery/share', $url);
		$this->assertStringContainsString('provider=twitter', $url);
		$this->assertStringContainsString('guid=123', $url);
	}

	public function testGetProviderUrlFacebook(): void {
		$url = get_provider_url('facebook', null, '', 'http://example.com/page');
		$this->assertStringContainsString('facebook.com/sharer', $url);
		$this->assertStringContainsString('u=', $url);
	}

	public function testGetProviderUrlTwitter(): void {
		$url = get_provider_url('twitter', null, '', 'http://example.com/page');
		$this->assertStringContainsString('twitter.com/intent/tweet', $url);
	}

	public function testGetProviderUrlLinkedIn(): void {
		$url = get_provider_url('linkedin', null, '', 'http://example.com/page');
		$this->assertStringContainsString('linkedin.com/shareArticle', $url);
	}

	public function testGetProviderUrlPinterest(): void {
		$url = get_provider_url('pinterest', null, '', 'http://example.com/page');
		$this->assertStringContainsString('pinterest.com/pin/create', $url);
	}
}
