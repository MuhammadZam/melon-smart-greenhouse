import {chromium} from 'playwright';
import fs from 'node:fs';
const d='docs/audit/evidence',base='http://127.0.0.1:8017',out=JSON.parse(fs.readFileSync(`${d}/browser.json`));
const browser=await chromium.launch({channel:'msedge',headless:true});
try {
 const context=await browser.newContext();const page=await context.newPage();
 await page.goto(base+'/login');await page.fill('[name=email]',process.env.AUDIT_OBSERVER_EMAIL||'pengamat@melon.com');await page.fill('[name=password]',process.env.AUDIT_OBSERVER_PASSWORD||'');
 await Promise.all([page.waitForURL('**/dashboard'),page.click('button[type=submit]')]);
 for(const path of ['/admin/thresholds','/admin/fuzzy/config','/admin/integrations/google-sheets','/plants/create','/monitorings/create','/reports/monitoring','/actuators']) {
  const r=await page.goto(base+path);out.pages.push({role:'pengamat',path,status:r.status()});
 }
 fs.writeFileSync(`${d}/browser.json`,JSON.stringify(out,null,2));
 const t=Date.now(),response=await context.request.get(base+'/reports/monitoring/export/pdf',{timeout:120000});
 const body=await response.body();out.exports.pdf={status:response.status(),ms:Date.now()-t,bytes:body.length,signature:body.subarray(0,8).toString('hex')};
 fs.writeFileSync(`${d}/sample-report.${response.ok()?'pdf':'pdf-error.html'}`,body);
 console.log(JSON.stringify(out.exports));
} catch(e) {out.errors.push(String(e));console.error(e);} finally {fs.writeFileSync(`${d}/browser.json`,JSON.stringify(out,null,2));await browser.close();}
