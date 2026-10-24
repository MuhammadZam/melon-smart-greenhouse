import fs from 'node:fs';
for(const view of ['plants/create','plants/edit','monitorings/create','monitorings/edit']){
 const path=`resources/views/${view}.blade.php`;let text=fs.readFileSync(path,'utf8');
 text=text.replace(/(<button\s+type="submit"\s+class=")/,'$1ui-button-primary ');fs.writeFileSync(path,text);
}
for(const file of fs.readdirSync('resources/views',{recursive:true}).filter(p=>p.endsWith('.blade.php')&&!p.endsWith('monitoring_pdf.blade.php'))){
 const path=`resources/views/${file}`;let text=fs.readFileSync(path,'utf8');
 text=text.replace(/(?:group-)?hover:-?translate-[xy]-[\d.]+/g,'');
 text=text.replace(/class="([^"]*)"/g,(_,classes)=>`class="${classes.replace(/[ \t]{2,}/g,' ').trim()}"`).replace(/[ \t]+$/gm,'');
 fs.writeFileSync(path,text);
}
