import {chromium} from 'playwright';
import fs from 'node:fs';
const dir='docs/audit/evidence', base='http://127.0.0.1:8017';
const browser=await chromium.launch({channel:'msedge',headless:true});
const out={date:'2026-10-22',database:'isolated browser.sqlite copy',pages:[],responsive:[],errors:[],exports:{}};
const save=()=>fs.writeFileSync(`${dir}/browser.json`,JSON.stringify(out,null,2));
process.on('uncaughtException',error=>{out.errors.push(String(error));save();console.error(error);process.exit(1);});
const context=await browser.newContext({viewport:{width:1366,height:768},acceptDownloads:true});
const page=await context.newPage();
page.on('pageerror',e=>out.errors.push(String(e)));
for(const path of ['/dashboard','/reports/monitoring','/admin/thresholds']) {
 const r=await page.goto(base+path);out.pages.push({role:'guest',path,status:r.status(),redirected:page.url().endsWith('/login')});
}
await page.fill('[name=email]',process.env.AUDIT_ADMIN_EMAIL||'admin@melon.com');await page.fill('[name=password]',process.env.AUDIT_ADMIN_PASSWORD||'');
await Promise.all([page.waitForURL(url=>!url.pathname.includes('login')),page.click('button[type=submit]')]);
for(const path of ['/dashboard','/plants','/monitorings','/alarms','/actuators','/actuators/history','/fuzzy/history','/admin/fuzzy/config','/admin/thresholds','/admin/integrations/google-sheets','/reports/monitoring','/admin/users']) {
 const t=Date.now();const r=await page.goto(base+path);await page.waitForLoadState('networkidle');
 out.pages.push({role:'admin',path,status:r.status(),finalPath:new URL(page.url()).pathname,ms:Date.now()-t,title:await page.title(),charts:await page.locator('canvas').count()});
 save();
}
for(const [width,height] of [[375,812],[768,1024],[1366,768],[1920,1080]]) {
 await page.setViewportSize({width,height});
 for(const path of ['/dashboard','/reports/monitoring']) {
  await page.goto(base+path);await page.waitForLoadState('networkidle');
  await page.screenshot({path:`${dir}/${width}x${height}_${path.includes('reports')?'reports':'dashboard'}_viewport.png`});
  const metrics=await page.evaluate(()=>({width:innerWidth,documentWidth:document.documentElement.scrollWidth,bodyWidth:document.body.scrollWidth,chartCount:document.querySelectorAll('canvas').length,background:getComputedStyle(document.body).backgroundColor,font:getComputedStyle(document.body).fontFamily,firstIconWidth:document.querySelector('svg')?.getBoundingClientRect().width}));
  out.responsive.push({width,height,path,...metrics,overflow:metrics.documentWidth>width});
  save();
 }
}
await page.goto(base+'/reports/monitoring');
for(const type of ['csv','pdf']) {
 const [download]=await Promise.all([page.waitForEvent('download'),page.locator(`a[href*="/export/${type}"]`).first().click()]);
 const file=`${dir}/sample-report.${type}`;await download.saveAs(file);
 const b=fs.readFileSync(file);out.exports[type]={bytes:b.length,signature:b.subarray(0,8).toString('hex')};
 if(type==='csv') {const rows=b.toString('utf8').split('\n');out.exports.csv.lines=rows.length;out.exports.csv.header=rows[0];}
 save();
}
await context.close();
const observer=await browser.newContext();const op=await observer.newPage();
await op.goto(base+'/login');await op.fill('[name=email]',process.env.AUDIT_OBSERVER_EMAIL||'pengamat@melon.com');await op.fill('[name=password]',process.env.AUDIT_OBSERVER_PASSWORD||'');
await Promise.all([op.waitForURL('**/dashboard'),op.click('button[type=submit]')]);
for(const path of ['/admin/thresholds','/admin/fuzzy/config','/admin/integrations/google-sheets','/plants/create','/monitorings/create','/reports/monitoring','/actuators']) {
 const r=await op.goto(base+path);out.pages.push({role:'pengamat',path,status:r.status()});
}
await browser.close();fs.writeFileSync(`${dir}/browser.json`,JSON.stringify(out,null,2));console.log(JSON.stringify(out,null,2));
