import {chromium} from 'playwright';import fs from 'node:fs';
const dir='docs/phase16_3',base='http://127.0.0.1:8019',credentials=JSON.parse(fs.readFileSync(`${dir}/evidence/credentials.json`));
const browser=await chromium.launch({channel:'msedge',headless:true});const out=[];
try {const page=await browser.newPage({viewport:{width:375,height:812}});
for(const path of ['/login','/dashboard','/monitorings','/plants','/charts','/alarms','/fuzzy/history','/actuators','/admin/integrations/google-sheets','/reports/monitoring','/plants/create','/monitorings/create']){
 await page.goto(base+path,{waitUntil:'domcontentloaded'});
 if(path==='/dashboard'&&page.url().includes('/login')){await page.fill('[name=email]',credentials.email);await page.fill('[name=password]',credentials.password);await Promise.all([page.waitForURL('**/dashboard'),page.locator('button[type=submit]').click()]);}
 await page.addScriptTag({path:`${dir}/tools/node_modules/axe-core/axe.min.js`});
 const result=await page.evaluate(async()=>await axe.run(document,{runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21aa','wcag22aa']}}));
 out.push({path,violations:result.violations.map(v=>({id:v.id,impact:v.impact,description:v.description,nodes:v.nodes.map(n=>({target:n.target,summary:n.failureSummary}))})),incomplete:result.incomplete.map(v=>({id:v.id,nodes:v.nodes.length})),passes:result.passes.length});
 fs.writeFileSync(`${dir}/evidence/accessibility.json`,JSON.stringify(out,null,2));
}
}finally{await browser.close();}
console.log(JSON.stringify(out.map(p=>({path:p.path,violations:p.violations.map(v=>({id:v.id,nodes:v.nodes.length}))})),null,2));
