import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/browser',
    timeout: 45000,
    workers: 1,
    expect: { timeout: 15000 },
    use: {
        baseURL: process.env.DAMI_QA_URL || 'http://192.168.18.25',
        channel: 'chrome',
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
});
