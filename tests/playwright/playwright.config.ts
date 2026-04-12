import { defineConfig } from '@playwright/test';

// In Docker: ELGG_BASE_URL=http://elgg (container networking)
export default defineConfig({
	testDir: './tests',
	timeout: 30000,
	retries: 0,
	workers: 1,
	use: {
		baseURL: process.env.ELGG_BASE_URL || 'http://elgg',
		headless: true,
		ignoreHTTPSErrors: true,
	},
	projects: [{ name: 'chromium', use: { browserName: 'chromium' } }],
});
