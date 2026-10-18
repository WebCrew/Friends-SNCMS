/* Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
// Only use disposable, uninstalled localhost fixtures. Apache tests exercise the real .htaccess.
import assert from 'node:assert/strict';
const root=new URL(process.env.FRIENDS_INSTALL_URL||'http://127.0.0.1:8093/');
const nested=new URL(process.env.FRIENDS_NESTED_URL||'http://127.0.0.1:8094/community/');
const unprotected=new URL(process.env.FRIENDS_UNPROTECTED_URL||'http://127.0.0.1:8096/');
for(const url of [root,nested,unprotected]) if(!['localhost','127.0.0.1'].includes(url.hostname)) throw Error('Local disposable fixtures only.');
let count=0;
const check=(ok,message)=>{assert.ok(ok,message);count++;console.log('PASS '+message);};
for(const base of [root,nested]) {
 const name=base.pathname==='/'?'domain root':'subdirectory';
 let response=await fetch(base,{redirect:'manual'}),html=await response.text();
 check(response.status===200&&html.includes('install_check')&&html.includes('Server prüfen'),'bare URL automatically opens installer in '+name);
 check(html.includes('href="public/assets/app.css"')&&html.includes('src="public/assets/app.js"'),'installer uses correct asset paths in '+name);
 for(const file of ['app.css','tokens.css','features.css','app.js','features.js','mark.svg']){
  response=await fetch(new URL('public/assets/'+file,base));
  check(response.status===200,'public asset loads in '+name+': '+file);
 }
 response=await fetch(new URL('install.php',base));
 check(response.status===200&&(await response.text()).includes('install_check'),'install.php opens the same installer in '+name);
 for(const file of ['app/bootstrap.php','database/schema.sql','storage/config.php','storage/files/sample.png','resources/lang/de.php','plugins/welcome/plugin.php','themes/ember/manifest.json','tests/features.php','bin/router.php','docs/INSTALL.md','.htaccess','.git/config','public/index.php','install.php/extra']){
  response=await fetch(new URL(file,base),{redirect:'manual'});
  check(response.status===403||response.status===404,'private URL denied in '+name+': '+file);
 }
 response=await fetch(base);html=await response.text();
 const cookie=response.headers.getSetCookie().map(value=>value.split(';')[0]).join('; ');
 const csrf=/name="csrf" value="([a-f0-9]+)"/.exec(html)?.[1];
 response=await fetch(new URL('index.php?r=install',base),{method:'POST',redirect:'manual',headers:{Cookie:cookie},body:new URLSearchParams({csrf,action:'install_check'})});
 check(response.status===303,'server check submits normally in '+name);
 response=await fetch(new URL('index.php?r=install',base),{headers:{Cookie:cookie}});html=await response.text();
 check(response.status===200&&html.includes('install_database')&&html.includes('name="database"'),'guided database step is reachable in '+name);
}
for(const entry of ['', 'index.php','install.php','public/index.php']){
 const response=await fetch(new URL(entry,unprotected),{redirect:'manual',headers:{'Friends-Root-Guard':'1','Redirect-Friends-Root-Guard':'1'}});
 const html=await response.text();
 check(response.status===503&&html.includes('.htaccess')&&!html.includes('install_check'),'missing server protection stops '+(entry||'bare URL')+' even with spoofed headers');
}
console.log('\n'+count+' installer/root-hosting HTTP checks passed. Database creation is not exercised.');
