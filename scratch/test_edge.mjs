import { chromium } from 'playwright';

(async () => {
    try {
        const browser = await chromium.launch({
            channel: 'msedge',
            headless: true
        });
        const page = await browser.newPage();
        await page.goto('http://127.0.0.1:8000/login');
        const title = await page.title();
        console.log('SUCCESS: Page title is: ' + title);
        await browser.close();
    } catch (e) {
        console.error('ERROR: ' + e.message);
    }
})();
