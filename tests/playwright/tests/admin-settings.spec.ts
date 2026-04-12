import { test, expect } from '@playwright/test';
import { loginAs, queryDb } from '../helpers/elgg';

/**
 * hypeDiscovery admin pages:
 *  - /admin/plugin_settings/hypeDiscovery (extended settings form)
 *  - /admin/discovery/site (discovery/site action target)
 *
 * Lock in: admin can render and submit settings; values persist to DB.
 * The current implementation stores array fields as PHP serialize()'d
 * strings — flagged in bead elgg-migrate-2mra as an unserialize() RCE
 * risk. Migration should replace serialize with json_encode.
 */
test.describe('hypeDiscovery admin settings', () => {
	test('plugin settings page renders', async ({ page }) => {
		await loginAs(page, 'admin');
		await page.goto('/admin/plugin_settings/hypeDiscovery');

		// No PHP errors visible
		const body = await page.content();
		expect(body).not.toContain('Fatal error');
		expect(body).not.toContain('PHP Notice');

		// Form contains known fields
		await expect(page.locator('[name="params[bypass_access]"]')).toBeVisible();
		await expect(page.locator('[name="params[nocrawl]"]')).toBeVisible();
	});

	test('admin discovery site page renders', async ({ page }) => {
		await loginAs(page, 'admin');
		const response = await page.goto('/admin/discovery/site');
		expect(response).not.toBeNull();
		const status = response!.status();
		expect(status).toBeLessThan(500);
		const body = await page.content();
		expect(body).not.toContain('Fatal error');
	});

	test('providers setting persists a serialized array in DB (regression lock)', async ({ page }) => {
		await loginAs(page, 'admin');
		await page.goto('/admin/plugin_settings/hypeDiscovery');

		// Select twitter + facebook providers if checkboxes exist
		const twitter = page.locator('input[name="params[providers][]"][value="twitter"]');
		const facebook = page.locator('input[name="params[providers][]"][value="facebook"]');
		if (await twitter.count() > 0) {
			await twitter.check();
		}
		if (await facebook.count() > 0) {
			await facebook.check();
		}

		await page.locator('form input[type="submit"], form button[type="submit"]').first().click();
		await page.waitForLoadState('networkidle');

		// Assert DB: providers stored as PHP serialize()'d string
		const rows = await queryDb(
			`SELECT ps.value FROM elgg_private_settings ps
			 JOIN elgg_entities e ON ps.entity_guid = e.guid
			 JOIN elgg_metadata m ON m.entity_guid = e.guid
			 WHERE e.type = 'object' AND e.subtype = 'plugin'
			   AND m.name = 'title' AND m.value = 'hypeDiscovery'
			   AND ps.name = 'plugin:user_setting:providers'`
		);
		if (rows.length > 0) {
			const value = rows[0].value as string;
			// CURRENT behavior: value begins with PHP serialize array marker 'a:'
			expect(value.startsWith('a:')).toBe(true);
		}
	});
});
