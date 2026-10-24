import fs from 'node:fs';
import {createCanvas,loadImage} from '../../audit/tools/node_modules/@napi-rs/canvas/index.js';
const dir='docs/ui_flat',before=JSON.parse(fs.readFileSync(`${dir}/evidence/browser-before.json`)),after=JSON.parse(fs.readFileSync(`${dir}/evidence/browser-after.json`));
const comparisons=[];
for(const p of after.pages) {
 const stem=`${p.width}x${p.height}_${p.name}`,b=await loadImage(`${dir}/screenshots/before/${stem}.png`),a=await loadImage(`${dir}/screenshots/after/${stem}.png`);
 const bc=createCanvas(b.width,b.height),ac=createCanvas(a.width,a.height);bc.getContext('2d').drawImage(b,0,0);ac.getContext('2d').drawImage(a,0,0);
 const bd=bc.getContext('2d').getImageData(0,0,b.width,b.height).data,ad=ac.getContext('2d').getImageData(0,0,a.width,a.height).data;let changed=0;
 for(let i=0;i<bd.length;i+=4)if(Math.max(...[0,1,2].map(k=>Math.abs(bd[i+k]-ad[i+k])))>20)changed++;
 comparisons.push({name:p.name,viewport:`${p.width}x${p.height}`,before:`screenshots/before/${stem}.png`,after:`screenshots/after/${stem}.png`,changedPixelsPercent:Math.round(changed/(b.width*b.height)*10000)/100});
 if(p.name==='dashboard'&&[375,1366].includes(p.width)) {
  const scale=Math.min(1,900/p.width),w=Math.round(p.width*scale),h=Math.round(p.height*scale),c=createCanvas(w*2+36,h+56),ctx=c.getContext('2d');ctx.fillStyle='#0f172a';ctx.fillRect(0,0,c.width,c.height);ctx.font='bold 18px sans-serif';ctx.fillStyle='#fff';ctx.fillText('BEFORE',12,28);ctx.fillText('AFTER',w+24,28);ctx.drawImage(b,12,44,w,h);ctx.drawImage(a,w+24,44,w,h);fs.writeFileSync(`${dir}/screenshots/comparison_${p.width}_dashboard.png`,c.toBuffer('image/png'));
 }
}
for(const [width,height] of [[375,812],[768,1024],[1366,768],[1920,1080]]) {
 const pages=after.pages.filter(p=>p.width===width),tw=width<1000?190:350,th=Math.round(height/width*tw),c=createCanvas((tw+12)*5+12,(th+38)*Math.ceil(pages.length/5)+12),ctx=c.getContext('2d');ctx.fillStyle='#0f172a';ctx.fillRect(0,0,c.width,c.height);ctx.font='14px sans-serif';
 for(let i=0;i<pages.length;i++){let x=12+i%5*(tw+12),y=12+Math.floor(i/5)*(th+38);ctx.fillStyle='#fff';ctx.fillText(pages[i].name,x,y+16);ctx.drawImage(await loadImage(`${dir}/screenshots/after/${width}x${height}_${pages[i].name}.png`),x,y+24,tw,th);}
 fs.writeFileSync(`${dir}/screenshots/overview_${width}.png`,c.toBuffer('image/png'));
}
fs.writeFileSync(`${dir}/evidence/visual-comparisons.json`,JSON.stringify(comparisons,null,2));
const options=[...new Set(comparisons.map(p=>p.name))].map(x=>`<option>${x}</option>`).join('');
fs.writeFileSync(`${dir}/visual-comparison.html`,`<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>UI Flat Refinement — Before / After</title><style>body{background:#020617;color:#e2e8f0;font:16px system-ui;margin:24px}a{color:#6ee7b7}select{font:inherit;padding:10px;background:#0f172a;color:white}article{margin:32px 0;padding:16px;border:1px solid #334155;border-radius:16px}.pair{display:grid;grid-template-columns:1fr 1fr;gap:16px}img{width:100%;height:auto}figure{margin:0}figcaption{padding:8px}@media(max-width:700px){.pair{grid-template-columns:1fr}}</style><h1>UI Flat Refinement — Perbandingan visual</h1><p>88 pasangan screenshot asli Microsoft Edge. Before: desain dark existing; After: tema putih flat. Selisih piksel adalah perubahan yang diharapkan, bukan batas lulus regresi.</p><label for="page">Halaman </label><select id="page"><option value="all">Semua</option>${options}</select>${comparisons.map(p=>`<article data-page="${p.name}"><h2>${p.name} · ${p.viewport}</h2><p>Perubahan piksel: ${p.changedPixelsPercent}%</p><div class="pair"><figure><figcaption>Before</figcaption><a href="${p.before}"><img loading="lazy" alt="Before ${p.name} ${p.viewport}" src="${p.before}"></a></figure><figure><figcaption>After</figcaption><a href="${p.after}"><img loading="lazy" alt="After ${p.name} ${p.viewport}" src="${p.after}"></a></figure></div></article>`).join('')}<script>document.querySelector('select').addEventListener('change',e=>document.querySelectorAll('article').forEach(a=>a.hidden=e.target.value!=='all'&&a.dataset.page!==e.target.value))</script></html>`);
console.log(`${comparisons.length} comparison pairs and four overview contact sheets written.`);


