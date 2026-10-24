import fs from 'node:fs';
for(const f of fs.readdirSync('resources/views',{recursive:true}).filter(f=>f.endsWith('.blade.php')&&!f.endsWith('monitoring_pdf.blade.php'))){
 const p=`resources/views/${f}`;let t=fs.readFileSync(p,'utf8');
 t=t.replaceAll('hover:text-white','hover:text-ink');
 t=t.replace(/animate-spin-hover/g,'');
 t=t.replace(/<div class="(flex flex-col (?:md|sm):flex-row[^"\n]*?) p-5 sm:p-6 [^"\n]*?">/, '<div class="ui-page-header $1">');
 fs.writeFileSync(p,t);
}
const p='resources/views/dashboard/index.blade.php';let t=fs.readFileSync(p,'utf8');
t=t.replace(/<div class="[^"]*" id="(sensor-overview-section|alarm-panel|actuator-summary-widget|fuzzy-analysis-widget|integration-health-section)">/g,'<div class="ui-section space-y-5" id="$1">');
t=t.replace(/(<div class=")([^"\n]*)("[^>]*>\s*<!-- 1\. Suhu Udara -->)/,'$1ui-sensor-grid $2$3');
t=t.replace('Database Live Analytics','Berdasarkan rekaman tersimpan').replace('Section A — Greenhouse Overview','Ringkasan greenhouse').replace('Section B — Sensor Overview','8 parameter sensor').replace('Smart Greenhouse','Pembacaan terbaru').replace('Dashboard Monitoring Tanaman Melon','Sensor lingkungan');
// Timestamp is shared by all eight readings; show it on each sensor for clear provenance.
let start=t.indexOf('<!-- 8 Sensor Parameters Grid -->'),end=t.indexOf('<!-- ALARM & NOTIFICATION PANEL');
let sensor=t.slice(start,end);
sensor=sensor.replace(/(<div class="text-xs text-muted tabular-nums mt-1">[\s\S]*?<\/div>)/g,`$1\n                @if($latestMonitoring)<time datetime="{{ $latestMonitoring->monitored_at->toIso8601String() }}">{{ $latestMonitoring->monitored_at->format('d M Y, H:i') }} WIB</time>@endif`);
t=t.slice(0,start)+sensor+t.slice(end);fs.writeFileSync(p,t);
// Remove implementation-stack copy from the persistent application footer.
const app='resources/views/layouts/app.blade.php';let shell=fs.readFileSync(app,'utf8');shell=shell.replace('Laravel 11 &bull; Tailwind CSS &bull; Chart.js &bull; MySQL','Pemantauan greenhouse');fs.writeFileSync(app,shell);
