import fs from 'node:fs';
for(const view of ['layouts/app','layouts/auth']) {
 const path=`resources/views/${view}.blade.php`;
 let text=fs.readFileSync(path,'utf8');
 text=text.replace(/    <!-- Google Fonts -->[\s\S]*?(?=    @vite)/,'');
 fs.writeFileSync(path,text);
}
for(const view of ['auth/login','plants/create','plants/edit','monitorings/create','monitorings/edit']) {
 const path=`resources/views/${view}.blade.php`;
 let text=fs.readFileSync(path,'utf8');
 const fields=[...text.matchAll(/@error\('([^']+)'\)\s*<p/g)].map(m=>m[1]);
 for(const field of new Set(fields)) {
  text=text.replaceAll(`@error('${field}')\n`, `@error('${field}')\n`); // Preserve Blade validation branches.
  text=text.replace(new RegExp(`(@error\\('${field}'\\)\\s*)<p\\b`,'g'),`$1<p id="error-${field}" role="alert"`);
  text=text.replace(new RegExp(`(<(?:input|select|textarea)\\b[^>]*\\bname="${field}")`,'g'),`$1 aria-invalid="@error('${field}')true@else false@enderror" aria-describedby="@error('${field}')error-${field}@enderror"`);
 }
 text=text.replace(/(\$errors->any\(\)\)\s*)<div /g,'$1<div role="alert" ');
 fs.writeFileSync(path,text);
}
const path='resources/views/dashboard/index.blade.php';let text=fs.readFileSync(path,'utf8');
const names={temp:'Suhu udara',ph:'pH nutrisi',humidity:'Kelembapan udara',ec:'Konduktivitas listrik',soilMoisture:'Kelembapan tanah',light:'Intensitas cahaya',nutrientTemp:'Suhu nutrisi',tankLevel:'Level tandon',height:'Tinggi tanaman',density:'Kerapatan tanaman',condition:'Distribusi kondisi tanaman',status:'Distribusi status tanaman',frequency:'Frekuensi pengamatan'};
text=text.replace(/<canvas id="([^\"]+)Chart"><\/canvas>/g,(all,id)=>`<canvas id="${id}Chart" role="img" aria-label="Grafik ${names[id]||id}; nilai pengamatan tersedia di tabel riwayat di bawah">Grafik ${names[id]||id}. Lihat tabel riwayat monitoring untuk data pengamatan.</canvas>`);
fs.writeFileSync(path,text);
