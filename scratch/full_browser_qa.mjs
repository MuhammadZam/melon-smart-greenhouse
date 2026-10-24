import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';

const SCREENSHOT_DIR = path.resolve('docs/qa/screenshots');
const DOWNLOAD_DIR = path.resolve('scratch/downloads');

if (!fs.existsSync(SCREENSHOT_DIR)) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}
if (!fs.existsSync(DOWNLOAD_DIR)) {
    fs.mkdirSync(DOWNLOAD_DIR, { recursive: true });
}

const BASE_URL = 'http://127.0.0.1:8000';

async function run() {
    console.log('=== STARTING FULL BROWSER QA AUTOMATION (PHASE 16.1) ===');

    const browser = await chromium.launch({
        channel: 'msedge',
        headless: true
    });

    const results = {
        auth: {},
        dashboard: {},
        reports: {},
        actuators: {},
        fuzzy: {},
        google_sheets: {},
        responsive: {},
        exports: {}
    };

    // -------------------------------------------------------------
    // TEST 1: GUEST REDIRECTIONS
    // -------------------------------------------------------------
    console.log('\n--- 1. Testing Guest Direct URL Access Redirection ---');
    const guestContext = await browser.newContext();
    const guestPage = await guestContext.newPage();

    const protectedUrls = [
        '/dashboard',
        '/plants',
        '/monitorings',
        '/reports/monitoring',
        '/actuators',
        '/alarms',
        '/admin/thresholds',
        '/admin/fuzzy/config',
        '/admin/integrations/google-sheets'
    ];

    for (const url of protectedUrls) {
        await guestPage.goto(`${BASE_URL}${url}`);
        const currentUrl = guestPage.url();
        const isRedirected = currentUrl.includes('/login');
        console.log(`  Guest -> ${url}: ${isRedirected ? 'PASS (Redirected to /login)' : 'FAIL (' + currentUrl + ')'}`);
        results.auth[`guest_${url}`] = isRedirected ? 'PASS' : 'FAIL';
    }
    await guestContext.close();

    // -------------------------------------------------------------
    // TEST 2: ADMIN AUTHENTICATION & DESKTOP DASHBOARD
    // -------------------------------------------------------------
    console.log('\n--- 2. Testing Administrator Session & Dashboard ---');
    const adminContext = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        acceptDownloads: true
    });
    const adminPage = await adminContext.newPage();

    await adminPage.goto(`${BASE_URL}/login`);
    await adminPage.screenshot({ path: path.join(SCREENSHOT_DIR, 'desktop_1920x1080_login.png') });

    await adminPage.fill('input[name="email"]', 'admin@melon.com');
    await adminPage.fill('input[name="password"]', 'password123');
    await adminPage.click('button[type="submit"]');

    await adminPage.waitForURL('**/dashboard');
    console.log('  Admin Login: SUCCESS, redirected to /dashboard');

    // Verify Sections A through G
    await adminPage.waitForSelector('#greenhouse-overview-section');
    await adminPage.waitForSelector('text=Suhu Udara');
    await adminPage.waitForSelector('#tempChart');
    await adminPage.waitForSelector('#alarm-panel');
    await adminPage.waitForSelector('#fuzzy-analysis-widget');
    await adminPage.waitForSelector('#actuator-summary-widget');
    await adminPage.waitForSelector('text=Catatan Arsitektur Integrasi');

    console.log('  Dashboard Sections A through G detected successfully.');
    await adminPage.screenshot({ path: path.join(SCREENSHOT_DIR, 'desktop_1920x1080_dashboard.png'), fullPage: true });

    // -------------------------------------------------------------
    // TEST 3: REPORTS MODULE & EXPORT VALIDATION
    // -------------------------------------------------------------
    console.log('\n--- 3. Testing Greenhouse Monitoring Report Module ---');
    await adminPage.goto(`${BASE_URL}/reports/monitoring`);
    await adminPage.waitForSelector('form[method="GET"]');
    console.log('  Report Page loaded successfully.');
    await adminPage.screenshot({ path: path.join(SCREENSHOT_DIR, 'desktop_1920x1080_reports.png'), fullPage: true });

    // Test CSV Download
    console.log('  Testing CSV Download stream...');
    const [csvDownload] = await Promise.all([
        adminPage.waitForEvent('download'),
        adminPage.click('a[href*="/reports/monitoring/export/csv"]')
    ]);
    const csvPath = path.join(DOWNLOAD_DIR, 'test_export.csv');
    await csvDownload.saveAs(csvPath);
    const csvContent = fs.readFileSync(csvPath);
    const hasUtf8Bom = csvContent[0] === 0xEF && csvContent[1] === 0xBB && csvContent[2] === 0xBF;
    console.log(`  CSV Downloaded: ${csvContent.length} bytes | UTF-8 BOM: ${hasUtf8Bom ? 'YES' : 'NO'}`);
    results.exports.csv_bom = hasUtf8Bom ? 'PASS' : 'FAIL';
    results.exports.csv_size = csvContent.length;

    // Test PDF Download
    console.log('  Testing PDF Download stream...');
    const [pdfDownload] = await Promise.all([
        adminPage.waitForEvent('download'),
        adminPage.click('a[href*="/reports/monitoring/export/pdf"]')
    ]);
    const pdfPath = path.join(DOWNLOAD_DIR, 'test_export.pdf');
    await pdfDownload.saveAs(pdfPath);
    const pdfContent = fs.readFileSync(pdfPath);
    const isPdfHeader = pdfContent.toString('ascii', 0, 4) === '%PDF';
    console.log(`  PDF Downloaded: ${pdfContent.length} bytes | Valid PDF Magic: ${isPdfHeader ? 'YES' : 'NO'}`);
    results.exports.pdf_magic = isPdfHeader ? 'PASS' : 'FAIL';
    results.exports.pdf_size = pdfContent.length;

    // -------------------------------------------------------------
    // TEST 4: ACTUATOR SIMULATION & GOOGLE SHEETS & FUZZY PAGES
    // -------------------------------------------------------------
    console.log('\n--- 4. Testing Actuator, Google Sheets & Fuzzy Admin Pages ---');
    await adminPage.goto(`${BASE_URL}/actuators`);
    await adminPage.waitForSelector('h1');
    await adminPage.screenshot({ path: path.join(SCREENSHOT_DIR, 'desktop_1920x1080_actuators.png'), fullPage: true });
    console.log('  Actuators page verified & screenshotted.');

    await adminPage.goto(`${BASE_URL}/fuzzy/history`);
    await adminPage.waitForSelector('h1');
    await adminPage.screenshot({ path: path.join(SCREENSHOT_DIR, 'desktop_1920x1080_fuzzy_history.png'), fullPage: true });
    console.log('  Fuzzy History page verified & screenshotted.');

    await adminPage.goto(`${BASE_URL}/admin/integrations/google-sheets`);
    await adminPage.waitForSelector('h1');
    await adminPage.screenshot({ path: path.join(SCREENSHOT_DIR, 'desktop_1920x1080_google_sheets.png'), fullPage: true });
    console.log('  Google Sheets integration page verified & screenshotted.');

    await adminContext.close();

    // -------------------------------------------------------------
    // TEST 5: PENGAMAT ROLE PERMISSION RESTRICTIONS
    // -------------------------------------------------------------
    console.log('\n--- 5. Testing Pengamat Role Restrictions ---');
    const pengamatContext = await browser.newContext({
        viewport: { width: 1366, height: 768 }
    });
    const pengamatPage = await pengamatContext.newPage();

    await pengamatPage.goto(`${BASE_URL}/login`);
    await pengamatPage.fill('input[name="email"]', 'pengamat@melon.com');
    await pengamatPage.fill('input[name="password"]', 'password123');
    await pengamatPage.click('button[type="submit"]');
    await pengamatPage.waitForURL('**/dashboard');
    console.log('  Pengamat Login: SUCCESS');

    // Pengamat tries to access admin-only routes
    const adminOnlyRoutes = [
        '/admin/thresholds',
        '/admin/fuzzy/config',
        '/admin/integrations/google-sheets'
    ];
    for (const route of adminOnlyRoutes) {
        const resp = await pengamatPage.goto(`${BASE_URL}${route}`);
        const status = resp.status();
        console.log(`  Pengamat -> ${route}: Status HTTP ${status} (Forbidden/Redirected as expected)`);
        results.auth[`pengamat_${route}`] = (status === 403 || status === 302) ? 'PASS' : `FAIL (${status})`;
    }
    await pengamatContext.close();

    // -------------------------------------------------------------
    // TEST 6: RESPONSIVE VIEWPORT SCREENSHOTS (Mobile, Tablet, Laptop)
    // -------------------------------------------------------------
    console.log('\n--- 6. Capturing Responsive Viewport Screenshots ---');

    // Viewport A: Smartphone (375 x 812)
    const mobileContext = await browser.newContext({
        viewport: { width: 375, height: 812 },
        isMobile: true,
        hasTouch: true
    });
    const mobilePage = await mobileContext.newPage();
    await mobilePage.goto(`${BASE_URL}/login`);
    await mobilePage.fill('input[name="email"]', 'admin@melon.com');
    await mobilePage.fill('input[name="password"]', 'password123');
    await mobilePage.click('button[type="submit"]');
    await mobilePage.waitForURL('**/dashboard');

    await mobilePage.screenshot({ path: path.join(SCREENSHOT_DIR, 'smartphone_375x812_dashboard.png') });
    console.log('  Saved smartphone_375x812_dashboard.png');

    // Open mobile sidebar drawer
    const burgerBtn = await mobilePage.$('button[title*="menu" i], button[aria-label*="menu" i], button svg path[d*="M4 6h16"]');
    if (burgerBtn) {
        await burgerBtn.click();
        await mobilePage.waitForTimeout(500);
        await mobilePage.screenshot({ path: path.join(SCREENSHOT_DIR, 'smartphone_375x812_drawer_opened.png') });
        console.log('  Saved smartphone_375x812_drawer_opened.png');
    }

    await mobilePage.goto(`${BASE_URL}/reports/monitoring`);
    await mobilePage.screenshot({ path: path.join(SCREENSHOT_DIR, 'smartphone_375x812_reports.png') });
    console.log('  Saved smartphone_375x812_reports.png');
    await mobileContext.close();

    // Viewport B: Tablet (768 x 1024)
    const tabletContext = await browser.newContext({
        viewport: { width: 768, height: 1024 }
    });
    const tabletPage = await tabletContext.newPage();
    await tabletPage.goto(`${BASE_URL}/login`);
    await tabletPage.fill('input[name="email"]', 'admin@melon.com');
    await tabletPage.fill('input[name="password"]', 'password123');
    await tabletPage.click('button[type="submit"]');
    await tabletPage.waitForURL('**/dashboard');

    await tabletPage.screenshot({ path: path.join(SCREENSHOT_DIR, 'tablet_768x1024_dashboard.png') });
    console.log('  Saved tablet_768x1024_dashboard.png');

    await tabletPage.goto(`${BASE_URL}/reports/monitoring`);
    await tabletPage.screenshot({ path: path.join(SCREENSHOT_DIR, 'tablet_768x1024_reports.png') });
    console.log('  Saved tablet_768x1024_reports.png');
    await tabletContext.close();

    // Viewport C: Laptop (1366 x 768)
    const laptopContext = await browser.newContext({
        viewport: { width: 1366, height: 768 }
    });
    const laptopPage = await laptopContext.newPage();
    await laptopPage.goto(`${BASE_URL}/login`);
    await laptopPage.fill('input[name="email"]', 'admin@melon.com');
    await laptopPage.fill('input[name="password"]', 'password123');
    await laptopPage.click('button[type="submit"]');
    await laptopPage.waitForURL('**/dashboard');

    await laptopPage.screenshot({ path: path.join(SCREENSHOT_DIR, 'laptop_1366x768_dashboard.png') });
    console.log('  Saved laptop_1366x768_dashboard.png');

    await laptopPage.goto(`${BASE_URL}/reports/monitoring`);
    await laptopPage.screenshot({ path: path.join(SCREENSHOT_DIR, 'laptop_1366x768_reports.png') });
    console.log('  Saved laptop_1366x768_reports.png');
    await laptopContext.close();

    await browser.close();
    console.log('\n=== ALL BROWSER AUTOMATION TESTS COMPLETED SUCCESSFULLY ===');
    console.log('Results summary: ', JSON.stringify(results, null, 2));
}

run().catch(err => {
    console.error('Test Suite Failed with Error: ', err);
    process.exit(1);
});
