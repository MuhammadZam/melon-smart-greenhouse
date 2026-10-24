import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import net from 'node:net';

const dir='docs/phase16_2/evidence';
const decode=p=>{const b=fs.readFileSync(p);return b[0]===255&&b[1]===254?b.toString('utf16le').slice(1):b.toString('utf8').replace(/^\uFEFF/,'');};
const read=n=>JSON.parse(decode(`${dir}/${n}`));
const v=read('verification.json'),after=read('history-after.json'),routes=read('endpoint-classification.json');
const files=['IMPLEMENTATION_REPORT','SECURITY_FIX','DEDUPLICATION_FIX','FUZZY_ZERO_FIX','REGRESSION_TEST_RESULTS','REMAINING_RISKS'].map(n=>`docs/PHASE_16_2_${n}.md`);
const missingLinks=[];
for(const file of files){for(const m of fs.readFileSync(file,'utf8').matchAll(/\]\(([^)]+)\)/g)){if(!/^https?:/.test(m[1])&&!fs.existsSync(path.resolve(path.dirname(file),m[1].split('#')[0])))missingLinks.push({file,target:m[1]});}}
const original=read('database-before.json').Hash;
const backupHash=crypto.createHash('sha256').update(fs.readFileSync(`${dir}/pre-migration.sqlite`)).digest('hex').toUpperCase();
const categories=Object.fromEntries(['PUBLIC','AUTHENTICATED USER','ADMIN ONLY','AUTHORIZED DEVICE INGESTION'].map(c=>[c,routes.filter(r=>r.category===c).length]));
const portOpen=await new Promise(resolve=>{const socket=net.connect({host:'127.0.0.1',port:8018});socket.setTimeout(1500);socket.once('connect',()=>{socket.destroy();resolve(true);});socket.once('error',()=>resolve(false));socket.once('timeout',()=>{socket.destroy();resolve(false);});});
const checks={six_reports:files.every(f=>fs.statSync(f).size>2000),links_resolve:missingLinks.length===0,tests:v.regression.tests==='297'&&v.regression.failures==='0'&&v.regression.errors==='0',new_tests:v.new_cases===60,history_unchanged:v.historical_data_unchanged,backup_matches_original:original===backupHash,ledger_installed_empty:after.ingestion_ledger?.count===0,ledger_primary_key:after.ingestion_ledger?.columns.some(c=>c.name==='identity_hash'&&c.pk===1),foreign_keys_clean:after.foreign_key_violations.length===0,routes55:routes.length===55,device_post_only:categories['AUTHORIZED DEVICE INGESTION']===1,simulator_server_stopped:!portOpen};
const result={completed:'2026-10-24',checks,endpoint_categories:categories,missingLinks,all_pass:Object.values(checks).every(Boolean)};
fs.writeFileSync(`${dir}/delivery-verification.json`,JSON.stringify(result,null,2));console.log(JSON.stringify(result,null,2));if(!result.all_pass)process.exitCode=1;
