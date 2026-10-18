/* Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
import assert from 'node:assert/strict';
const base=new URL(process.env.FRIENDS_TEST_URL||'http://127.0.0.1:8097/'),password=process.env.FRIENDS_TEST_PASSWORD;
if(!['localhost','127.0.0.1'].includes(base.hostname)||!password)throw Error('Use a disposable localhost fixture and password.');
let count=0;const check=(ok,label)=>{assert.ok(ok,label);count++;console.log('PASS '+label);};
class Client {
 cookies=new Map();csrf='';
 async request(route,fields=null,params={}){const url=new URL('index.php',base);url.search=new URLSearchParams({r:route,...params});const response=await fetch(url,{redirect:'manual',method:fields?'POST':'GET',headers:{Cookie:[...this.cookies].map(([k,v])=>k+'='+v).join('; ')},body:fields?new URLSearchParams({csrf:this.csrf,...fields}):undefined});
  for(const raw of response.headers.getSetCookie()){const pair=raw.split(';')[0],at=pair.indexOf('=');this.cookies.set(pair.slice(0,at),pair.slice(at+1));}const bytes=await response.arrayBuffer();return new Response(bytes,{status:response.status,headers:response.headers});}
 async html(route='feed',params={}){const html=await(await this.request(route,null,params)).text();this.csrf=/name="csrf" value="([a-f0-9]+)"/.exec(html)?.[1]||this.csrf;return html;}
 async login(identity){await this.html('login');await this.request('login',{action:'login',identity,password});await this.html();}
}
const admin=new Client(),member=new Client(),guest=new Client();await admin.login('andreas');await member.login('mira');
let html=await admin.html();check(html.includes('auto-link-preview')&&!html.includes('name="post_link"'),'composer has automatic preview and no separate URL field');
check((await guest.request('link_preview',{url:'http://127.0.0.1/denied'})).status===400,'anonymous preview requests denied');
check((await admin.request('link_preview',{csrf:'wrong',url:'http://127.0.0.1/denied'})).status===400,'preview API requires valid CSRF');
const url='http://127.0.0.1/automatic-'+Date.now();
await admin.request('feed',{action:'post',body:'Immediate publish '+url,visibility:'members'});html=await admin.html();
const latest=s=>/<article class="card post"[^>]*>([\s\S]*?)<div class="post-stats">/.exec(s)?.[1]||'';
check(latest(html).includes('class="link-card"')&&latest(html).includes('href="'+url+'"'),'text-only POST persists and renders automatic card without prior preview request');
check(latest(await member.html()).includes(url),'member-visible card reaches other members');
const previewUrl='http://127.0.0.1/preview-first-'+Date.now();
const preview=await admin.request('link_preview',{url:previewUrl});check(preview.status===200&&(await preview.json()).url===previewUrl,'automatic preview API returns safe metadata');
await admin.request('feed',{action:'post',body:'Preview then publish '+previewUrl,visibility:'members'});html=await admin.html();
check(latest(html).includes('class="link-card"')&&latest(html).includes(previewUrl),'previewed URL is preserved in published card');
const changed='http://127.0.0.1/current-'+Date.now();await admin.request('feed',{action:'post',body:'Changed text '+changed,post_link:previewUrl,visibility:'members'});
check(latest(await admin.html()).includes('href="'+changed+'"'),'current URL wins over stale legacy input');
const privateUrl='http://127.0.0.1/private-link-'+Date.now();await admin.request('feed',{action:'post',body:'Private card '+privateUrl,visibility:'private'});
check(latest(await admin.html()).includes(privateUrl)&&!(await member.html()).includes(privateUrl),'automatic cards respect private post visibility');
await admin.request('admin',{action:'admin_uploads',upload_mb:5,upload_daily_mb:100,feed_emojis:1,feed_images:1,feed_files:1,profile_images:1});
html=await admin.html();check(!html.includes('auto-link-preview'),'admin can disable automatic previews');
await admin.request('feed',{action:'post',body:'Ordinary URL while disabled http://127.0.0.1/no-preview',visibility:'members'});
html=await admin.html();check(!latest(html).includes('class="link-card"')&&latest(html).includes('no-preview'),'disabled previews allow text links without a card');
await admin.request('admin',{action:'admin_uploads',upload_mb:5,upload_daily_mb:100,feed_emojis:1,feed_images:1,feed_files:1,profile_images:1,link_previews:1});
check((await admin.html()).includes('auto-link-preview'),'admin can restore automatic previews');
console.log(count+' automatic-link HTTP checks passed. No external network requests.');

