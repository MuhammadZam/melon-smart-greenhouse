import {chromium} from 'playwright';import fs from 'node:fs';
const dir='docs/phase16_3',credentials=JSON.parse(fs.readFileSync(`${dir}/evidence/credentials.json`)),browser=await chromium.launch({channel:'msedge',headless:true});
try {
const page=await browser.newPage({viewport:{width:375,height:812}});await page.goto('http://127.0.0.1:8019/login');await page.fill('#email',credentials.email);await page.fill('#password',credentials.password);await Promise.all([page.waitForURL('**/dashboard'),page.locator('button[type=submit]').click()]);
await page.goto('http://127.0.0.1:8019/actuators');const button=page.locator('button:not([disabled])').filter({hasText:/Nyalakan \(ON\)|Matikan \(OFF\)/}).first();await button.click();await page.waitForTimeout(1000);
const result=await page.getByRole('dialog').evaluate(e=>({rect:e.getBoundingClientRect().toJSON(),style:e.getAttribute('style'),display:getComputedStyle(e).display,position:getComputedStyle(e).position,active:document.activeElement.outerHTML,ancestors:[e.parentElement,e.parentElement.parentElement,e.parentElement.parentElement.parentElement].map(n=>({tag:n.tagName,classes:n.className,transform:getComputedStyle(n).transform,filter:getComputedStyle(n).filter,backdrop:getComputedStyle(n).backdropFilter}))}));
await page.screenshot({path:`${dir}/screenshots/after/375_simulation_modal.png`});fs.writeFileSync(`${dir}/evidence/modal-check.json`,JSON.stringify(result,null,2));console.log(JSON.stringify(result,null,2));
} finally {await browser.close();}
