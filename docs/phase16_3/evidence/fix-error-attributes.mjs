import fs from 'node:fs';
for(const view of ['auth/login','plants/create','plants/edit','monitorings/create','monitorings/edit']) {
 const path=`resources/views/${view}.blade.php`;let text=fs.readFileSync(path,'utf8');
 text=text.replace(/aria-invalid="@error\('([^']+)'\)true@else false@enderror"/g,(_,f)=>`aria-invalid="{{ $errors->has('${f}') ? 'true' : 'false' }}"`);
 text=text.replace(/aria-describedby="@error\('([^']+)'\)error-[^"]+@enderror"/g,(_,f)=>`aria-describedby="{{ $errors->has('${f}') ? 'error-${f}' : '' }}"`);
 fs.writeFileSync(path,text);
}
