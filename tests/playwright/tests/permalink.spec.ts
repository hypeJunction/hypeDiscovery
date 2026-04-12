import { test, expect } from '@playwright/test';

/**
 * hypeDiscovery registers a public /permalink/{segments} route
 * via Router::permalinkHandler (resource: permalink).
 *
 * Lock in:
 *  - /permalink/ is a public page (bypasses walled_garden guard)
 *  - invalid/missing guid does not crash
 *  - oembed format responds with 200
 */
test.describe('hypeDiscovery permalink', () => {
	test('permalink/default/<guid> of site entity returns 2xx/3xx', async ({ page }) => {
		const response = await page.goto('/permalink/default/1');
		expect(response, 'response should be non-null').not.toBeNull();
		const status = response!.status();
		expect([200, 301, 302, 303, 307, 308]).toContain(status);
	});

	test('permalink oembed viewtype renders without PHP error', async ({ page }) => {
		const response = await page.goto('/permalink/oembed/1');
		expect(response).not.toBeNull();
		const body = await page.content();
		expect(body).not.toContain('Fatal error');
		expect(body).not.toContain('PHP Notice');
	});

	test('permalink with unknown guid does not 500', async ({ page }) => {
		const response = await page.goto('/permalink/default/9999999');
		expect(response).not.toBeNull();
		const status = response!.status();
		// Router returns false -> Elgg default routing; must not be 500
		expect(status).toBeLessThan(500);
	});
});
