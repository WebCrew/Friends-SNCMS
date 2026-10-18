/* Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
// Run only against a disposable local seed-preview fixture and synthetic upload files.
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import path from 'node:path';
const base=new URL(process.env.FRIENDS_TEST_URL||'http://127.0.0.1:8093/'),password=process.env.FRIENDS_TEST_PASSWORD,uploads=process.env.FRIENDS_TEST_UPLOADS;
if(!['localhost','127.0.0.1'].includes(base.hostname)||!password||!uploads)throw Error('Use a local disposable fixture, password and uploads directory.');
let count=0;const check=(ok,label)=>{assert.ok(ok,label);count++;console.log('PASS '+label);};
class Client {
 cookies=new Map();csrf='';
 async request(route,fields=null,files=[],params={}){const url=new URL('index.php',base);url.search=new URLSearchParams({r:route,...params});let body;
  if(fields){body=new FormData();body.set('csrf',this.csrf);for(const[k,v]of Object.entries(fields))body.set(k,String(v));for(const[key,name,type]of files)body.append(key,new Blob([await readFile(path.join(uploads,name))],{type}),name);}
  const response=await fetch(url,{method:fields?'POST':'GET',body,redirect:'manual',headers:{Cookie:[...this.cookies].map(([k,v])=>k+'='+v).join('; ')}});
  for(const raw of response.headers.getSetCookie()){const pair=raw.split(';')[0],at=pair.indexOf('=');this.cookies.set(pair.slice(0,at),pair.slice(at+1));} const bytes=await response.arrayBuffer(); return new Response(bytes,{status:response.status,headers:response.headers});
 }
 async html(route,params={}){const html=await(await this.request(route,null,[],params)).text();this.csrf=/name="csrf" value="([a-f0-9]+)"/.exec(html)?.[1]||this.csrf;return html;}
 async login(identity){await this.html('login');await this.request('login',{action:'login',identity,password});await this.html('feed');}
}
const owner=new Client(),stranger=new Client(),admin=new Client(),guest=new Client();await owner.login('mira');await stranger.login('jonas');await admin.login('andreas');
let html=await owner.html('feed');check(html.includes('refinements.css')&&html.includes('class="composer-tool"')&&html.includes('chat-dock'),'feed exposes refined tools and persistent chat dock');
check(html.includes('data-peer="1"')&&!html.includes('data-peer="3"'),'dock only lists accepted active friends');
await owner.request('groups',{action:'community',kind:'group',name:'Refinement upload group',description:'Disposable fixture'});
html=await owner.html('groups');const id=/r=community(?:&amp;|&)id=(\d+)[\s\S]*?Refinement upload group/.exec(html)[1];
// The newly created group is first in the newest-first directory.
await owner.html('community',{id});
await owner.request('community',{action:'community_images',id},[['avatar','avatar.png','image/png'],['header','community-header.png','image/png']],{id});
html=await owner.html('community',{id});const mediaIds=s=>[...new Set([...s.matchAll(/r=media(?:&amp;|&)id=(\d+)/g)].map(m=>m[1]))];const ids=mediaIds(html);
check(ids.length===2,'owner can upload separate logo and cover');
for(const media of ids){const response=await stranger.request('media',null,[],{id:media});check(response.status===200&&response.headers.get('content-type')==='image/png','other signed-in members can view community artwork');check((await guest.request('media',null,[],{id:media})).status===404,'guest cannot read protected community media');}
await stranger.html('community',{id});await stranger.request('community',{action:'community_images',id,remove_avatar:1,remove_header:1},[],{id});
check((await stranger.html('community',{id})).includes('role="alert"'),'unrelated member cannot remove artwork');
check(mediaIds(await owner.html('community',{id})).join(',')===ids.join(','),'denied change preserves both images');
await owner.request('community',{action:'community_images',id},[['avatar','avatar.png','image/png'],['header','readme.txt','text/plain']],{id});
html=await owner.html('community',{id});check(html.includes('role="alert"')&&mediaIds(html).join(',')===ids.join(','),'invalid second file rolls back replacement and preserves prior images');
await admin.html('community',{id});await admin.request('community',{action:'community_images',id,remove_avatar:1},[],{id});
html=await owner.html('community',{id});check(mediaIds(html).length===1,'administrator can remove logo without deleting cover');
const remaining=mediaIds(html)[0],removed=ids.find(value=>value!==remaining);check((await owner.request('media',null,[],{id:removed})).status===404,'removed artwork can no longer be fetched');
await admin.html('admin',{section:'voice_room'});await admin.request('admin',{action:'voice_settings',voice_setup:'stun'},[],{section:'voice_room'});
html=await admin.html('admin',{section:'voice_room'});check(html.includes('value="stun" selected')&&html.includes('coturn')&&html.includes('eturnal'),'guided STUN mode saves and free server guides render');
await stranger.request('admin',{action:'voice_settings',voice_setup:'direct'},[],{section:'voice_room'});
check((await admin.html('admin',{section:'voice_room'})).includes('value="stun" selected'),'ordinary member cannot change voice configuration');
await admin.request('admin',{action:'voice_settings',voice_setup:'direct'},[],{section:'voice_room'});
check((await admin.html('admin',{section:'voice_room'})).includes('value="direct" selected'),'external STUN can be disabled again');
check((await guest.request('chat_api',null,[],{peer:1,history:1})).status===401,'chat history requires authentication');
check((await stranger.request('chat_api',null,[],{peer:1,history:1})).status===400,'history option does not bypass friendship checks');
console.log(count+' refinement HTTP checks passed. Synthetic localhost data only.');

