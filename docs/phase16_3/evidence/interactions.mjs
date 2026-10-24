import {chromium} from 'playwright';import fs from 'node:fs';
const dir='docs/phase16_3',base='http://127.0.0.1:8019',credentials=JSON.parse(fs.readFileSync(`${dir}/evidence/credentials.json`));
const browser=await chromium.launch({channel:'msedge',headless:true});const out={checks:[],errors:[]};
const save=()=>fs.writeFileSync(`${dir}/evidence/interactions.json`,JSON.stringify(out,null,2));
const check=(name,pass,detail)=>{out.checks.push({name,pass:!!pass,detail});save();};
try {
 const page=await browser.newPage({viewport:{width:375,height:812},acceptDownloads:true});page.on('pageerror',e=>out.errors.push(String(e)));
 const go=async path=>{await page.goto(base+path,{waitUntil:'domcontentloaded'});await page.waitForTimeout(180);};
 await go('/login');
 await page.locator('button[type=submit]').click();check('login required validation',await page.locator('#email').evaluate(e=>!e.validity.valid));
 await page.fill('#email',credentials.email);await page.fill('#password',credentials.password);
 await Promise.all([page.waitForURL('**/dashboard'),page.locator('button[type=submit]').click()]);
 check('admin login',new URL(page.url()).pathname==='/dashboard');
 for(const width of [375,768]) {
  await page.setViewportSize({width,height:width===375?812:1024});
  const toggle=page.locator('#navigation-toggle');await toggle.click();await page.waitForTimeout(350);
  check(`drawer open ${width}`,await toggle.getAttribute('aria-expanded')==='true'&&await page.locator('.app-shell').evaluate(e=>e.inert));
  check(`drawer focus ${width}`,await page.evaluate(()=>document.querySelector('aside').contains(document.activeElement)));
  const last=page.locator('aside nav a').last();await last.focus();await page.keyboard.press('Tab');
  check(`drawer focus wraps ${width}`,await page.evaluate(()=>document.querySelector('aside').contains(document.activeElement)));
  await page.screenshot({path:`${dir}/screenshots/after/${width}_drawer.png`});
  await page.keyboard.press('Escape');await page.waitForTimeout(350);
  check(`drawer escape focus return ${width}`,await toggle.getAttribute('aria-expanded')==='false'&&await toggle.evaluate(e=>document.activeElement===e));
  await toggle.click();await page.waitForTimeout(350);await page.locator('[data-sidebar-backdrop]').click({position:{x:width-15,y:200}});await page.waitForTimeout(350);
  check(`drawer overlay closes ${width}`,await toggle.getAttribute('aria-expanded')==='false');
  await page.locator('#alarm-notification-btn').click();await page.waitForTimeout(200);
  const bounds=await page.locator('#alarm-notifications').boundingBox();check(`notifications contained ${width}`,bounds&&bounds.x>=0&&bounds.x+bounds.width<=width);
  await page.screenshot({path:`${dir}/screenshots/after/${width}_notifications.png`});await page.keyboard.press('Escape');
  check(`notification escape ${width}`,await page.locator('#alarm-notification-btn').getAttribute('aria-expanded')==='false');
 }
 await page.setViewportSize({width:375,height:812});await go('/monitorings');
 const table=page.locator('main .overflow-x-auto').first();await table.focus();await page.keyboard.press('ArrowRight');await page.waitForTimeout(250);
 check('table keyboard horizontal scroll',await table.evaluate(e=>e.scrollLeft>0&&e.tabIndex===0));
 await page.fill('#search','PHASE163-NO-SUCH-PLANT');await Promise.all([page.waitForURL(u=>u.search.includes('PHASE163')),page.getByRole('button',{name:'Terapkan Filter'}).click()]);
 check('monitoring empty filter',await page.locator('main tbody tr').count()===1);await page.getByRole('link',{name:'Reset',exact:true}).click();
 check('monitoring filter reset',new URL(page.url()).search==='');
 const next=page.locator('main a[href*="page=2"]:visible').first();check('pagination available',await next.count()>0);if(await next.count()){await next.click();check('pagination page 2',new URL(page.url()).searchParams.get('page')==='2');}
 await go('/plants/create');await page.locator('main button[type=submit]').click();check('plant required validation',await page.locator('#name').evaluate(e=>!e.validity.valid));
 await page.locator('main form').evaluate(e=>e.noValidate=true);await Promise.all([page.waitForNavigation(),page.locator('main button[type=submit]').click()]);
 check('server validation linked to field',await page.locator('#name').getAttribute('aria-describedby')==='error-name'&&await page.locator('#error-name').isVisible());
 await page.screenshot({path:`${dir}/screenshots/after/375_validation.png`});
 await go('/dashboard');await page.waitForTimeout(1100);
 check('13 chart slots initialized or truthful empty state',await page.evaluate(()=>[...document.querySelectorAll('canvas')].length===13&&[...document.querySelectorAll('canvas')].every(c=>Chart.getChart(c)||document.querySelector('#empty-'+c.id)?.getClientRects().length>0)));
 for(const width of [375,768,1366,1920]) {
  await page.setViewportSize({width,height:900});await page.waitForTimeout(300);
  check(`chart resize ${width}`,await page.evaluate(()=>[...document.querySelectorAll('canvas')].filter(c=>c.getClientRects().length>0).every(c=>c.clientWidth>0&&c.getBoundingClientRect().right<=innerWidth+1&&c.clientWidth<=c.parentElement.clientWidth+1)));
 }
 await page.setViewportSize({width:375,height:812});const canvas=page.locator('#tempChart');await canvas.scrollIntoViewIfNeeded();await page.waitForTimeout(350);
 const point=await canvas.evaluate(c=>{const p=Chart.getChart(c).getDatasetMeta(0).data[0];return{x:p.x,y:p.y};});await canvas.hover({position:point});await page.waitForTimeout(200);
 check('chart tooltip on observation',await canvas.evaluate(c=>Chart.getChart(c).tooltip.getActiveElements().length>0));
 const legend=await canvas.evaluate(c=>{let h=Chart.getChart(c).legend.legendHitBoxes[0];return{x:h.left+h.width/2,y:h.top+h.height/2};});await canvas.click({position:legend});
 check('chart legend toggles series',await canvas.evaluate(c=>!Chart.getChart(c).isDatasetVisible(0)));await canvas.click({position:legend});
 await page.screenshot({path:`${dir}/screenshots/after/375_chart_detail.png`});
 await go('/dashboard?date_from=2099-01-01&date_to=2099-01-02');check('chart empty state',await page.getByText('Kombinasi filter tanaman atau rentang tanggal',{exact:false}).isVisible());
 await go('/dashboard');const plantValue=await page.locator('#plant_filter option').nth(1).getAttribute('value');await page.selectOption('#plant_filter',plantValue);
 await Promise.all([page.waitForNavigation(),page.locator('main form button[type=submit]').click()]);check('dashboard plant filter submitted',new URL(page.url()).searchParams.get('plant_id')===plantValue);
 await go('/reports/monitoring');await page.fill('#date_from','2099-01-01');await page.fill('#date_to','2099-01-02');
 await Promise.all([page.waitForNavigation(),page.locator('#report-filter-form button[type=submit]').click()]);check('report date filter submitted',new URL(page.url()).searchParams.get('date_from')==='2099-01-01');
 await page.getByRole('link',{name:'Reset',exact:true}).click();check('report filter reset',new URL(page.url()).search==='');
 for(const type of ['csv','pdf']){const [download]=await Promise.all([page.waitForEvent('download',{timeout:120000}),page.locator(`#btn-export-${type}`).click()]);const path=`${dir}/evidence/report.${type}`;await download.saveAs(path);const data=fs.readFileSync(path);check(`${type} export download`,type==='pdf'?data.subarray(0,4).toString()==='%PDF':data.length>100,{bytes:data.length});}
 await go('/actuators');const enabled=page.locator('button:not([disabled])').filter({hasText:/Nyalakan \(ON\)|Matikan \(OFF\)/}).first();await enabled.click();await page.waitForTimeout(250);
 const modal=page.getByRole('dialog');check('simulation modal opens',await modal.isVisible());check('modal keyboard focus',await modal.evaluate(e=>e.contains(document.activeElement)));
 await page.screenshot({path:`${dir}/screenshots/after/375_simulation_modal.png`});await page.keyboard.press('Escape');await page.waitForTimeout(200);check('modal escape restores focus',!await modal.isVisible()&&await enabled.evaluate(e=>e===document.activeElement));
 await enabled.click();await page.getByRole('button',{name:'Batal',exact:true}).click();await page.waitForTimeout(250);check('modal cancel',!await modal.isVisible());
 await enabled.click();await page.fill('#modal-command-notes','Phase 16.3 isolated UI simulation');
 await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Konfirmasi & Eksekusi',exact:true}).click()]);check('simulation success feedback',await page.getByRole('status').isVisible());
 await go('/admin/integrations/google-sheets');check('sheets mode visible',await page.locator('main').getByText('Mock',{exact:false}).count()>0);
 await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Sinkronkan Sekarang',exact:true}).click()]);check('mock Sheets sync feedback',await page.getByRole('status').isVisible()||await page.getByRole('alert').count()>0);
 for(const [path,field,value] of [['/plants','search','PHASE163-NO-SUCH-PLANT'],['/alarms','severity','critical'],['/fuzzy/history','category','BURUK']]) {
  await go(path);const control=page.locator(`main [name="${field}"]`);if(field==='search')await control.fill(value);else await control.selectOption(value);
  await Promise.all([page.waitForNavigation(),page.locator('main button[type=submit]').first().click()]);check(`${path} filter applies`,new URL(page.url()).searchParams.get(field)===value);
  await page.getByRole('link',{name:'Reset',exact:true}).click();check(`${path} reset`,new URL(page.url()).search==='');
 }
 await page.route('**/api/notifications/status',r=>r.abort());await go('/dashboard');await page.locator('#alarm-notification-btn').click();check('notification network error feedback',await page.getByText('Status alarm belum dapat dimuat.',{exact:false}).isVisible());await page.unroute('**/api/notifications/status');await page.getByRole('button',{name:'Coba lagi'}).click();await page.waitForTimeout(350);check('notification retry recovers',!await page.getByText('Status alarm belum dapat dimuat.',{exact:false}).isVisible());
 await page.keyboard.press('Escape');await page.getByRole('button',{name:'Keluar dari sistem'}).click();check('logout returns to login',new URL(page.url()).pathname==='/login');
}catch(e){out.errors.push(String(e));process.exitCode=1;}finally{out.pass=out.errors.length===0&&out.checks.every(c=>c.pass);save();await browser.close();}
console.log(JSON.stringify(out,null,2));
