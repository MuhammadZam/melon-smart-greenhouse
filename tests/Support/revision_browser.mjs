import { chromium } from 'playwright';
import fs from 'node:fs';
const base='http://127.0.0.1:8027';
const out='docs/revision/evidence';
const browser=await chromium.launch({channel:'msedge',headless:true});
const context=await browser.newContext({viewport:{width:1440,height:1000}});
const page=await context.newPage();
const errors=[];page.on('pageerror',e=>errors.push(e.message));
const results={responsive:[],routes:[],javascriptErrors:errors};
try {
  await page.goto(base+'/dashboard');
  if (!page.url().includes('/login')) throw Error('Guest access not denied');
  await page.locator('input[name=email]').fill('admin@melon.com');
  await page.locator('input[name=password]').fill('password123');
  await Promise.all([page.waitForURL('**/dashboard'),page.locator('button[type=submit]').click()]);
  for(const width of [360,390,768,1024,1440]){
    await page.setViewportSize({width,height:1000});
    for(const route of ['/dashboard','/monitorings','/monitorings/create','/reports/monitoring','/actuators']){
      const r=await page.goto(base+route);await page.waitForTimeout(180);
      if(r.status()!==200)throw Error(route+' status '+r.status());
      const data=await page.evaluate(()=>({width:innerWidth,scrollWidth:document.documentElement.scrollWidth,charts:[...document.querySelectorAll('canvas')].map(x=>x.id),text:document.body.innerText}));
      const bad=['Kelembapan Udara','EC Nutrisi','Intensitas Cahaya','Suhu Nutrisi','Level Tandon','FAN-01'].filter(x=>data.text.includes(x));
      const overflow=data.scrollWidth>width+1;
      results.responsive.push({width,route,overflow,retiredLabels:bad});
      if(overflow||bad.length)throw Error(JSON.stringify(results.responsive.at(-1)));
      if(route==='/dashboard'){
        for(const id of ['tempChart','phChart','soilMoistureChart'])if(!data.charts.includes(id))throw Error('Missing active chart '+id);
        await page.screenshot({path:`${out}/dashboard-${width}.png`});
        if(width===1440) await page.locator('#charts').screenshot({path:`${out}/charts-desktop.png`,style:'header { visibility: hidden !important; }'});
      }
      if(route==='/reports/monitoring'&&width===1440)await page.screenshot({path:`${out}/report-desktop.png`});
    }
  }
  for(const route of ['/alarms','/admin/thresholds','/fuzzy/history','/admin/fuzzy/config','/admin/integrations/google-sheets','/plants']){
    const r=await page.goto(base+route);results.routes.push({route,status:r.status()});
    if(r.status()!==200)throw Error(route+' '+r.status());
    if(route.includes('google-sheets')) {
      const text=await page.locator('body').innerText();
      if(text.includes('4-Parameter')||!text.includes('water_ph'))throw Error('Stale Sheets contract');
    }
  }
  await page.goto(base+'/reports/monitoring');
  const tableColumns=await page.locator('table').evaluateAll(tables=>tables.map(t=>({headers:t.querySelectorAll('thead tr:last-child th').length,body:[...t.querySelectorAll('tbody tr')].filter(r=>!r.querySelector('[colspan]')).map(r=>r.children.length)})));
  results.reportTableColumns=tableColumns;
  for(const t of tableColumns)if(t.headers&&t.body.some(n=>n!==t.headers))throw Error('Report columns mismatch '+JSON.stringify(t));
  for(const format of ['csv','pdf']){
    const r=await context.request.get(base+'/reports/monitoring/export/'+format);if(r.status()!==200)throw Error(format+' export failed');
    const body=await r.body();fs.writeFileSync(`${out}/monitoring.${format}`,body);results[format]={status:r.status(),bytes:body.length};
  }
  if(errors.length)throw Error('JS errors '+errors.join(';'));
  results.passed=true;
} catch(e){results.passed=false;results.failure=String(e);process.exitCode=1;await page.screenshot({path:out+'/browser-failure.png'});}
finally{fs.writeFileSync(out+'/browser-results.json',JSON.stringify(results,null,2));await browser.close();}
console.log(JSON.stringify(results,null,2));
