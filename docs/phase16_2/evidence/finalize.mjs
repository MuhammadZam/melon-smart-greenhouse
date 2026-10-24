import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import {execFileSync} from 'node:child_process';

const root=process.cwd(), dir='docs/phase16_2/evidence';
const read=p=>{const b=fs.readFileSync(p);return b[0]===255&&b[1]===254?b.toString('utf16le').slice(1):b.toString('utf8').replace(/^\uFEFF/,'');};
const json=p=>JSON.parse(read(`${dir}/${p}`));
const hash=p=>crypto.createHash('sha256').update(fs.readFileSync(p)).digest('hex').toUpperCase();
const before=json('history-before.json'),after=json('history-after.json');
const unchanged=JSON.stringify(before.tables)===JSON.stringify(after.tables);
const attrs=s=>Object.fromEntries([...s.matchAll(/(\w+)="([^"]*)"/g)].map(m=>[m[1],m[2]]));
function junit(name){const s=read(`${dir}/${name}`);const summary=attrs(s.match(/<testsuite\s+([^>]+)>/)[1]);const cases=[...s.matchAll(/<testcase\s+([^>]+)\/?\s*>/g)].map(m=>attrs(m[1]));return {summary,cases};}
const baseline=junit('baseline.xml'),regression=junit('regression.xml');
const snapshot=new Map(json('source-before.json').map(x=>[x.Path.toLowerCase(),x.Hash]));
const paths=[];
function walk(p){for(const f of fs.readdirSync(p,{withFileTypes:true})){const n=path.join(p,f.name);if(f.isDirectory()){if(f.name!=='__pycache__')walk(n);}else if(/\.(php|py|md|xml)$/.test(f.name))paths.push(n);}}
for(const d of ['app','config','database/migrations','database/seeders','routes','tests','iot-simulator'])walk(d);
paths.push('bootstrap/app.php','phpunit.xml');
const modified=paths.filter(p=>snapshot.get(path.resolve(p).toLowerCase())!==hash(p)).map(p=>p.replaceAll('\\','/')).sort();
const lint=[];
for(const file of modified.filter(p=>p.endsWith('.php')))lint.push({file,result:execFileSync('C:/xampp/php/php.exe',['-l',file],{encoding:'utf8'}).trim()});
const routes=json('routes-after.json').map(r=>{
 const middleware=r.middleware.join('; '),method=r.method;
 let category='PUBLIC';
 if(r.uri==='api/monitorings'&&method==='POST')category='AUTHORIZED DEVICE INGESTION';
 else if(middleware.includes('role:admin') || (/^plants\//.test(r.uri)&&/create|edit/.test(r.uri)) || (r.uri==='plants'&&method==='POST') || (r.uri==='plants/{plant}'&&!method.includes('GET')) || (/^monitorings\//.test(r.uri)&&r.uri.includes('edit')) || (r.uri==='monitorings/{monitoring}'&&!method.includes('GET')))category='ADMIN ONLY';
 else if(middleware.includes('auth')||middleware.includes('AuthenticateInternalApi'))category='AUTHENTICATED USER';
 return {...r,category};
});
const newCases=regression.cases.filter(c=>c.class.includes('Phase16_2'));
const evidence={completed:'2026-10-24 Asia/Jakarta',baseline:baseline.summary,regression:regression.summary,new_cases:newCases.length,new_case_assertions:newCases.reduce((a,c)=>a+Number(c.assertions),0),historical_tables:Object.keys(after.tables).length,historical_data_unchanged:unchanged,history_counts:Object.fromEntries(Object.entries(after.tables).map(([k,v])=>[k,v.count])),modified_files:modified,route_count:routes.length,concurrent:json('concurrent.json'),simulator:json('simulator.json'),legacy:json('legacy-findings.json'),lint};
fs.writeFileSync(`${dir}/verification.json`,JSON.stringify(evidence,null,2));
fs.writeFileSync(`${dir}/endpoint-classification.json`,JSON.stringify(routes,null,2));
fs.writeFileSync(`${dir}/new-test-cases.json`,JSON.stringify(newCases,null,2));
if(!unchanged||Number(regression.summary.failures)||Number(regression.summary.errors)||Number(regression.summary.skipped)||newCases.length<60)throw Error('Phase verification failed');
console.log(JSON.stringify({tests:regression.summary.tests,assertions:regression.summary.assertions,newTests:newCases.length,historicalTablesUnchanged:unchanged,changedFiles:modified.length,routes:routes.length,phpLint:lint.length},null,2));
