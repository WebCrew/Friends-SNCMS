/* Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
// Isolated HTTPS SMTP capture and HTTP flow; never contacts external mail recipients.
import fs from 'node:fs/promises';
import tls from 'node:tls';
import net from 'node:net';
import {execFile,spawn} from 'node:child_process';
import {promisify} from 'node:util';
import path from 'node:path';
import os from 'node:os';
import {fileURLToPath} from 'node:url';
import assert from 'node:assert/strict';
const root=fileURLToPath(new URL('../',import.meta.url)),run=promisify(execFile),dir=await fs.mkdtemp(path.join(os.tmpdir(),'friends-account-http-'));
const php=process.env.FRIENDS_PHP_EXE||'php',loaded=(await run(php,['-m'])).stdout;
const args=[];
for(const name of ['pdo_sqlite','openssl']) if(!loaded.split(/\r?\n/).includes(name)) args.push('-d','extension='+(process.platform==='win32'?path.join(path.dirname(php),'ext/php_'+name+'.dll'):name));
const key=path.join(dir,'key.pem'),cert=path.join(dir,'cert.pem');
let server,web; const sockets=new Set(),deliveries=[];let count=0;
const check=(ok,label)=>{assert.ok(ok,label);count++;console.log('PASS '+label);};
try {
 await run(process.env.FRIENDS_OPENSSL_EXE||'openssl',['req','-x509','-newkey','rsa:2048','-nodes','-keyout',key,'-out',cert,'-days','1','-subj','/CN=127.0.0.1','-addext','subjectAltName=IP:127.0.0.1']);
 server=tls.createServer({key:await fs.readFile(key),cert:await fs.readFile(cert)},socket=>{
  sockets.add(socket);socket.on('close',()=>sockets.delete(socket));socket.on('error',()=>{});socket.write('220 fixture ESMTP\r\n');
  let buffer='',data=null;
  socket.on('data',chunk=>{buffer+=chunk.toString();while(buffer.includes('\r\n')){const at=buffer.indexOf('\r\n'),line=buffer.slice(0,at);buffer=buffer.slice(at+2);
   if(data!==null){if(line==='.') {deliveries.push(data);data=null;socket.write('250 Accepted\r\n');}else data+=line+'\r\n';continue;}
   if(line.startsWith('EHLO'))socket.write('250 fixture\r\n');
   else if(line.startsWith('MAIL FROM:')||line.startsWith('RCPT TO:'))socket.write('250 OK\r\n');
   else if(line==='DATA'){data='';socket.write('354 Data\r\n');}
   else if(line==='QUIT')socket.end('221 Bye\r\n');
   else socket.end('500 Unexpected\r\n');
  }});
 });
 await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
 await run(php,[...args,path.join(root,'tests/account-mail-fixture.php'),dir,String(server.address().port)]);
 const probe=net.createServer();await new Promise(resolve=>probe.listen(0,'127.0.0.1',resolve));const port=probe.address().port;await new Promise(resolve=>probe.close(resolve));
 web=spawn(php,[...args,'-d','openssl.cafile='+cert,'-S','127.0.0.1:'+port,'-t',root,path.join(root,'bin/router.php')],{env:{...process.env,FRIENDS_CONFIG:path.join(dir,'config.php')},stdio:'ignore'});
 const base='http://127.0.0.1:'+port+'/';
 class Client {
  cookies=new Map();csrf='';
  async request(route,fields=null,params={}){const url=new URL('index.php',base);url.search=new URLSearchParams({r:route,...params});const response=await fetch(url,{redirect:'manual',method:fields?'POST':'GET',headers:{Cookie:[...this.cookies].map(([k,v])=>k+'='+v).join('; ')},body:fields?new URLSearchParams({csrf:this.csrf,...fields}):undefined});for(const raw of response.headers.getSetCookie()){const pair=raw.split(';')[0],at=pair.indexOf('=');this.cookies.set(pair.slice(0,at),pair.slice(at+1));}return response;}
  async html(route,params={}){const response=await this.request(route,null,params),html=await response.text();this.csrf=/name="csrf" value="([a-f0-9]+)"/.exec(html)?.[1]||this.csrf;return html;}
 }
 const client=new Client();let started=false;
 for(let i=0;i<40;i++){try{await client.html('login');started=true;break;}catch{await new Promise(r=>setTimeout(r,100));}}
 assert.ok(started,'local server starts');
 check((await client.html('login')).includes('r=forgot'),'login links to password recovery');
 const html=await client.html('register'),ticket=/name="signup_ticket" value="([a-f0-9]+)"/.exec(html)[1];await new Promise(r=>setTimeout(r,2100));
 const password='Initial Test Password!';
 let response=await client.request('register',{action:'register',name:'Test User',username:'testuser',email:'test@example.test',password,signup_ticket:ticket,contact_fax:''});
 check(response.status===303&&response.headers.get('location').includes('r=login')&&deliveries.length===1,'registration sends verification and returns to login');
 const state=async()=>JSON.parse((await run(php,[...args,path.join(root,'tests/account-mail-fixture.php'),dir,'state'])).stdout);
 check((await state())[0].status==='pending','new account awaits email confirmation');
 await client.html('login');await client.request('login',{action:'login',identity:'testuser',password});
 check((await client.request('feed')).headers.get('location').includes('r=login'),'unverified account cannot sign in');
 const link=index=>new URL(Buffer.from(deliveries[index].split('\r\n\r\n')[1].replaceAll('\r\n',''),'base64').toString().match(/https:\/\/community\.example\.test\/index\.php\?[^\s]+/)[0]);
 const verify=link(0).searchParams.get('token');
 response=await client.request('verify',null,{token:verify});
 check(response.status===200&&response.headers.get('referrer-policy')==='no-referrer'&&(await state())[0].status==='pending','email scanner GET does not consume verification');
 await client.html('verify',{token:verify});await client.request('verify',{action:'verify',token:verify},{token:verify});
 check((await state())[0].status==='active','confirmation POST activates account');
 check((await client.html('verify',{token:verify})).includes('role="alert"'),'verification replay shows invalid-link message');
 await client.html('login');await client.request('login',{action:'login',identity:'testuser',password});
 check((await client.request('feed')).status===200,'verified account can sign in');
 const guest=new Client();await guest.html('forgot');
 await guest.request('forgot',{action:'forgot',email:'missing@example.test'});const unknown=await guest.html('forgot');
 await guest.request('forgot',{action:'forgot',email:'test@example.test'});const known=await guest.html('forgot');
 const notice=s=>/role="status"[^>]*>([^<]+)/.exec(s)?.[1];
 check(notice(unknown)===notice(known)&&!!notice(known)&&deliveries.length===2,'recovery response conceals account existence');
 const reset=link(1).searchParams.get('token'),newPassword='Updated Test Password!';
 await guest.html('reset',{token:reset});await guest.request('reset',{action:'reset',token:reset,password:newPassword,password_confirm:newPassword},{token:reset});
 check((await state())[0].auth_version===3,'password reset revokes previous session version');
 check((await client.request('feed')).headers.get('location').includes('r=login'),'previous signed-in session is rejected after reset');
 check((await guest.html('reset',{token:reset})).includes('role="alert"'),'reset link cannot be reused');
 await guest.html('login');await guest.request('login',{action:'login',identity:'testuser',password:newPassword});
 check((await guest.request('feed')).status===200,'new password signs in successfully');
 console.log(count+' email-account HTTP checks passed with two isolated SMTP deliveries.');
} finally {
 if(web&&web.exitCode===null){web.kill();await new Promise(resolve=>web.once('exit',resolve));}
 for(const socket of sockets)socket.destroy();if(server?.listening)await new Promise(resolve=>server.close(resolve));
 if(path.dirname(path.resolve(dir))!==path.resolve(os.tmpdir())||!path.basename(dir).startsWith('friends-account-http-'))throw Error('Unsafe cleanup');
 await fs.rm(dir,{recursive:true,force:true});
}
