import fs from 'node:fs';
const read=p=>{let b=fs.readFileSync(p);return JSON.parse(b[0]===255&&b[1]===254?b.toString('utf16le').slice(1):b.toString('utf8').replace(/^\uFEFF/,''));};
const x=read('docs/audit/evidence/composer-audit.json');
const out={composer:[],npm:read('docs/audit/evidence/npm-audit.json').metadata,routes:read('docs/audit/evidence/routes.json').length};
for(const [p,entries] of Object.entries(x.advisories||{}))for(const a of entries)out.composer.push({package:p,title:a.title,severity:a.severity,url:a.link});
fs.writeFileSync('docs/audit/evidence/dependency-summary.json',JSON.stringify(out,null,2));console.log(JSON.stringify(out,null,2));
