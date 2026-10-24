import fs from 'node:fs';
const root='resources/views';
const files=fs.readdirSync(root,{recursive:true}).filter(p=>p.endsWith('.blade.php')&&!p.endsWith('monitoring_pdf.blade.php'));
const neutral=['slate','gray','zinc','neutral','stone'];
function token(t){
 if(/^(?:[a-z-]+:)*(?:bg-gradient-|bg-linear-|from-|via-|to-|shadow|backdrop-blur|blur-|animate-(?:pulse|ping|spin)|group-hover:scale|hover:scale)/.test(t))return '';
 if(/(?:^|:)(?:from|via|to|shadow)-/.test(t))return '';
 if(t==='bg-clip-text')return '';
 if(t==='text-transparent')return 'text-ink';
 t=t.replace(/rounded(?:-(?:full|xl|2xl|3xl|lg|md))?(?=$|\s)/g,'rounded-[2px]');
 t=t.replace(/duration-(?:\d+)/g,'duration-150').replace(/scale-\[0\.99\]/g,'scale-[0.98]');
 if(t==='font-extrabold'||t==='font-bold')return 'font-semibold';
 if(t==='font-mono')return 'tabular-nums';
 if(t==='uppercase'||t==='tracking-widest'||t==='tracking-wider')return '';
 t=t.replace(/text-\[(?:9|10|11)px\]/g,'text-xs');
 if(t==='text-white'||t==='text-slate-950')return 'text-current';
 t=t.replace(/\b(text|bg|border|ring|divide|placeholder|decoration)-(slate|gray|zinc|neutral|stone)-(\d+)(?:\/[\d.]+)?/g,(_,kind,color,shade)=>{
  if(kind==='text')return `text-${+shade>=400&&+shade<=600?'muted':'ink'}`;
  if(kind==='bg')return `bg-${+shade>=900?'canvas':'surface'}`;
  if(kind==='border'||kind==='divide')return `${kind}-line`;
  if(kind==='placeholder')return 'placeholder-muted';
  return `${kind}-accent`;
 });
 t=t.replace(/\b(text|bg|border|ring|decoration)-(emerald|green|teal|sky|blue|cyan|indigo|violet|purple|lime|yellow|amber|orange|rose|red)-(\d+)(?:\/(\d+(?:\.\d+)?))?/g,(_,kind,color,shade,alpha)=>{
  const danger=['rose','red'].includes(color),warn=['amber','orange','yellow'].includes(color),brand=['emerald','green','teal'].includes(color);
  if(kind==='ring')return 'ring-accent';
  if(kind==='text')return `text-${danger?'danger':warn?'warning':brand?'accent':'muted'}`;
  if(kind==='border')return `border-${danger?'danger/25':warn?'warning/25':'line'}`;
  if(kind==='bg'){
   if(alpha)return `bg-${danger?'danger-soft':warn?'warning-soft':brand?'accent-soft':'surface'}`;
   if(+shade>=500&&+shade<=700)return `bg-${danger?'danger':brand?'accent':'surface'}`;
   return `bg-${brand?'accent-soft':'surface'}`;
  }
  return `${kind}-accent`;
 });
 return t;
}
for(const file of files){const path=`${root}/${file}`;let text=fs.readFileSync(path,'utf8');
 text=text.replace(/<!-- (?:Background Ambient Glow|Ambient Lighting Background|Background Glow) -->\s*<div[^>]*>\s*(?:<div[^>]*><\/div>\s*)+<\/div>/g,'');
 text=text.replace(/    <!-- Google Fonts -->[\s\S]*?(?=    @vite)/g,'');
 text=text.replace(/<html lang="id" class="dark">/g,'<html lang="id">');
 // Match only utility-looking tokens; Blade expressions, bindings, data and logic remain intact.
 text=text.replace(/(?<![\w#])(?:[a-z-]+:)*(?:bg-gradient-[\w-]+|bg-linear-[\w-]+|bg-clip-text|text-transparent|(?:from|via|to|shadow)-[\w/.[\]-]+|shadow|backdrop-blur-[\w-]+|blur-\[[^\]]+\]|blur-\w+|animate-(?:pulse|ping|spin)|(?:group-hover:|hover:)?scale-105|rounded(?:-(?:full|xl|2xl|3xl|lg|md|sm))?|duration-\d+|font-(?:bold|extrabold|mono)|uppercase|tracking-(?:widest|wider)|text-\[(?:9|10|11)px\]|text-white|(?:text|bg|border|ring|divide|placeholder|decoration)-(?:slate|gray|zinc|neutral|stone|emerald|green|teal|sky|blue|cyan|indigo|violet|purple|lime|yellow|amber|orange|rose|red)-\d+(?:\/[\d.]+)?)(?![\w-])/g,token);
 // Gradient CTAs now use an explicit flat primary treatment.
 text=text.replace(/<(a|button)\b([\s\S]*?)class="([^"]*)"/g,(all,tag,attrs,classes)=>{
  if((file==='auth/login.blade.php'&&tag==='button'&&/type="submit"/.test(attrs))||(file==='welcome.blade.php'&&/route\('login'\)/.test(attrs)))classes+=' ui-button-primary';
  return `<${tag}${attrs}class="${classes.replace(/ {2,}/g,' ').trim()}"`;
 });
 if(!file.startsWith('layouts')&&!file.startsWith('components')&&file!=='welcome.blade.php'&&file!=='auth/login.blade.php')text=text.replace(/<(h[12]) class="/, '<$1 class="ui-page-title ');
 fs.writeFileSync(path,text);
}
console.log(`Refined ${files.length} web templates; PDF print template preserved.`);
