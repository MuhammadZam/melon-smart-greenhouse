import fs from 'node:fs';
for(const file of ['fuzzy/history','actuators/history']) {
 const path=`resources/views/${file}.blade.php`;
 let text=fs.readFileSync(path,'utf8');
 text=text.replace(/<label(?![^>]*\bfor=)([^>]*>[\s\S]*?<\/label>\s*)(<(?:input|select|textarea)\b[^>]*\bname="([^"]+)"[^>]*>)/g,(all,body,control,name)=>{
  const id=control.match(/\bid="([^"]+)"/)?.[1]||`filter-${name}`;
  if(!/\bid=/.test(control))control=control.replace(/^(<\w+)/,`$1 id="${id}"`);
  return `<label for="${id}"${body}${control}`;
 });
 fs.writeFileSync(path,text);
}
const labels={plant_id:'Tanaman',parameter_key:'Parameter sensor',severity:'Tingkat alarm',status:'Status alarm',date_from:'Tanggal mulai',date_to:'Tanggal akhir'};
let path='resources/views/alarms/index.blade.php',text=fs.readFileSync(path,'utf8');
text=text.replace(/<(?:select|input)\b[^>]*\bname="([^"]+)"[^>]*>/g,(tag,name)=>tag.replace(/^(<\w+)/,`$1 aria-label="${labels[name]||name}"`));
fs.writeFileSync(path,text);
path='resources/views/admin/integrations/google_sheets.blade.php';text=fs.readFileSync(path,'utf8');
text=text.replace(/<(?:select|input)\b[^>]*\bname="(device_id|name|type)"[^>]*>/g,(tag,name)=>tag.replace(/^(<\w+)/,`$1 aria-label="${{device_id:'ID perangkat',name:'Nama perangkat',type:'Jenis perangkat'}[name]}"`));
fs.writeFileSync(path,text);
