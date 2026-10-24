import {chromium} from 'playwright';
import fs from 'node:fs';
const stage=process.argv[2]||'before', dir='docs/phase16_3', base='http://127.0.0.1:8019';
const credentials=JSON.parse(fs.readFileSync(`${dir}/evidence/credentials.json`));
const out={stage,browser:'Installed Microsoft Edge / Playwright',database:'disposable SQLite copy',pages:[],errors:[]};
const save=()=>fs.writeFileSync(`${dir}/evidence/browser-${stage}.json`,JSON.stringify(out,null,2));
const browser=await chromium.launch({channel:'msedge',headless:true});
try {
 const context=await browser.newContext({acceptDownloads:true});
 const page=await context.newPage();
 page.on('pageerror',e=>out.errors.push(String(e)));
 for(const [width,height] of [[375,812],[768,1024],[1366,768],[1920,1080]]) {
  await page.setViewportSize({width,height});
  // Login screenshots use a separate guest session at every viewport.
  const guest=await browser.newContext({viewport:{width,height}}), gp=await guest.newPage();
  await navigate(gp,base+'/login');
  await gp.screenshot({path:`${dir}/screenshots/${stage}/${width}x${height}_login.png`});
  out.pages.push({width,height,name:'login',...await metrics(gp)});await guest.close();save();
  if(width===375) {
   await navigate(page,base+'/login');
   await page.fill('[name=email]',credentials.email);await page.fill('[name=password]',credentials.password);
   await Promise.all([page.waitForURL(u=>!u.pathname.includes('login')),page.click('button[type=submit]')]);
  }
  for(const [name,path] of Object.entries({dashboard:'/dashboard',monitoring:'/monitorings',plants:'/plants',charts:'/charts',alarms:'/alarms',fuzzy:'/fuzzy/history',actuators:'/actuators',sheets:'/admin/integrations/google-sheets',reports:'/reports/monitoring'})) {
   const response=await navigate(page,base+path);
   await page.waitForTimeout(name==='dashboard'||name==='charts'?1200:400);
   await page.evaluate(()=>document.fonts.ready);
   await page.screenshot({path:`${dir}/screenshots/${stage}/${width}x${height}_${name}.png`});
   out.pages.push({width,height,name,status:response.status(),path:new URL(page.url()).pathname,...await metrics(page)});save();
   if(stage==='after'&&[375,1366].includes(width)&&['dashboard','reports','fuzzy','actuators','sheets','monitoring'].includes(name)) await page.screenshot({path:`${dir}/screenshots/${stage}/${width}x${height}_${name}_full.png`,fullPage:true});
  }
 }
 await context.close();
} catch(e) {out.errors.push(String(e));process.exitCode=1;} finally {save();await browser.close();}
console.log(JSON.stringify({stage,pages:out.pages.length,overflow:out.pages.filter(p=>p.documentWidth>p.width).map(p=>`${p.width}/${p.name}:${p.documentWidth}`),errors:out.errors}));
async function navigate(page,url) {await page.bringToFront();for(let attempt=0;attempt<3;attempt++){try{return await page.goto(url,{waitUntil:'domcontentloaded',timeout:20000});}catch(e){if(attempt===2)throw e;await page.waitForTimeout(1000);}}}
async function metrics(page) {return page.evaluate(()=>({documentWidth:document.documentElement.scrollWidth,background:getComputedStyle(document.body).backgroundColor,font:getComputedStyle(document.body).fontFamily,charts:[...document.querySelectorAll('canvas')].map(c=>({id:c.id,width:c.clientWidth,height:c.clientHeight,initialized:!!window.Chart?.getChart(c)})),overflowElements:[...document.querySelectorAll('main *')].filter(e=>{const r=e.getBoundingClientRect();return r.width>0&&r.right>innerWidth+1&&!e.closest('.overflow-x-auto')}).slice(0,12).map(e=>({tag:e.tagName,class:e.className,right:Math.round(e.getBoundingClientRect().right)}))}));}
