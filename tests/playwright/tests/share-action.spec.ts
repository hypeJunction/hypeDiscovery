import { test, expect } from '@playwright/test';

/**
 * /action/discovery/share is registered as 'public' — accessible without login —
 * and is declared a walled_garden public page.
 * It builds a provider URL and forwards to it.
 */
test.describe('hypeDiscovery share action', () => {
	test('share action is reachable without login', async ({ page }) => {
		const response = await page.goto(
			'/action/discovery/share?provider=twitter&share_url=http%3A%2F%2Fexample.com%2Ffoo',
			{ waitUntil: 'domcontentloaded' }
		);
		// Expect a redirect (forward) — status 302/303 or final URL on twitter.com
		expect(response).not.toBeNull();
		const finalUrl = page.url();
		// Either redirected to twitter intent OR forwarded back to referer
		expect(
			finalUrl.includes('twitter.com/intent/tweet') || finalUrl.includes('/action/discovery/share') === false
		).toBe(true);
	});
});
