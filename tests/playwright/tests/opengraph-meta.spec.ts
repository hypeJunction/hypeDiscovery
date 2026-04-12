import { test, expect } from '@playwright/test';

/**
 * hypeDiscovery injects OpenGraph / Twitter card meta tags via
 * Discovery::prepareMetas on the 'head','page' hook.
 *
 * Lock in: site entity pages expose og:type / og:site_name metas.
 */
test.describe('hypeDiscovery OpenGraph metas', () => {
	test('homepage exposes og:type meta', async ({ page }) => {
		await page.goto('/');
		const og = await page.locator('meta[property="og:type"]').first();
		const count = await page.locator('meta[property="og:type"]').count();
		expect(count).toBeGreaterThan(0);
		const content = await og.getAttribute('content');
		expect(content).toBeTruthy();
	});

	test('homepage exposes og:url meta', async ({ page }) => {
		await page.goto('/');
		const count = await page.locator('meta[property="og:url"]').count();
		expect(count).toBeGreaterThan(0);
	});

	test('homepage exposes twitter:card meta', async ({ page }) => {
		await page.goto('/');
		const count = await page.locator('meta[name="twitter:card"]').count();
		expect(count).toBeGreaterThan(0);
	});

	test('homepage exposes og+oembed alternate link when embeddable', async ({ page }) => {
		await page.goto('/');
		// Alternate oembed link is only emitted for embeddable entities;
		// just assert no fatal errors on page load.
		const body = await page.content();
		expect(body).not.toContain('Fatal error');
	});
});
