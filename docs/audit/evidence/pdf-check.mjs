import fs from 'node:fs';
import {createRequire} from 'node:module';
import {getDocument} from '../tools/node_modules/pdfjs-dist/legacy/build/pdf.mjs';
const require=createRequire(import.meta.url);
const {createCanvas}=require('../tools/node_modules/@napi-rs/canvas');
const pdf=await getDocument({data:new Uint8Array(fs.readFileSync('docs/audit/evidence/sample-report.pdf')),useSystemFonts:true}).promise;
const out={pages:pdf.numPages,dimensions:[],rendered:[]}, texts=[];
for(let i=1;i<=pdf.numPages;i++) {
 const page=await pdf.getPage(i),v=page.getViewport({scale:1});
 out.dimensions.push({page:i,width:v.width,height:v.height});
 const t=await page.getTextContent();texts.push(`PAGE ${i}\n`+t.items.map(x=>x.str).join(' '));
 if([1,Math.ceil(pdf.numPages/2),pdf.numPages].includes(i)) {
  const viewport=page.getViewport({scale:1.4}),canvas=createCanvas(Math.ceil(viewport.width),Math.ceil(viewport.height));
  await page.render({canvasContext:canvas.getContext('2d'),viewport}).promise;
  fs.writeFileSync(`docs/audit/evidence/pdf-page-${i}.png`,canvas.toBuffer('image/png'));out.rendered.push(i);
 }
}
const text=texts.join('\n\n');out.has500Warning=/500/.test(text)&&/dibatasi|batas|maksimal|terbaru/i.test(text);out.hasProvisional=/PROVISIONAL/i.test(text);out.hasFilters=/filter|sumber/i.test(text);out.hasPagination=/halaman/i.test(text);
fs.writeFileSync('docs/audit/evidence/pdf-content.txt',text);fs.writeFileSync('docs/audit/evidence/pdf-check.json',JSON.stringify(out,null,2));console.log(JSON.stringify(out,null,2));
