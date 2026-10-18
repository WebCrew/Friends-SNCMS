/* Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
import fs from 'node:fs/promises';
import tls from 'node:tls';
import net from 'node:net';
import {execFile,spawn} from 'node:child_process';
import {promisify} from 'node:util';
import path from 'node:path';
import os from 'node:os';
import {fileURLToPath} from 'node:url';
import assert from 'node:assert/strict';
const run=promisify(execFile),root=fileURLToPath(new URL('../',import.meta.url)),directory=await fs.mkdtemp(path.join(os.tmpdir(),'friends-smtp-test-'));
const key=path.join(directory,'key.pem'),cert=path.join(directory,'cert.pem');
await run(process.env.FRIENDS_OPENSSL_EXE || 'openssl',['req','-x509','-newkey','rsa:2048','-nodes','-keyout',key,'-out',cert,'-days','1','-subj','/CN=127.0.0.1','-addext','subjectAltName=IP:127.0.0.1']);
const options={key:await fs.readFile(key),cert:await fs.readFile(cert)};
const secureContext=tls.createSecureContext(options);
let deliveries=[];
function conversation(socket,starttls=false,hello=true) {
 socket.on('error',()=>{});if(hello)socket.write('220 fixture ESMTP\r\n');
 let buffer='',dataMode=false,auth=0;
 socket.on('data',function handler(chunk){
  buffer+=chunk.toString();
  while(buffer.includes('\r\n')){
   const at=buffer.indexOf('\r\n'),line=buffer.slice(0,at);buffer=buffer.slice(at+2);
   if(dataMode){if(line==='.') {deliveries.push(dataMode);dataMode=false;socket.write('250 Accepted\r\n');}else dataMode+=line+'\r\n';continue;}
   if(auth===1){assert.equal(Buffer.from(line,'base64').toString(),'fixture-user');auth=2;socket.write('334 UGFzc3dvcmQ6\r\n');continue;}
   if(auth===2){assert.equal(Buffer.from(line,'base64').toString(),'fixture-password');auth=0;socket.write('235 Authenticated\r\n');continue;}
   if(line.startsWith('EHLO'))socket.write('250-fixture\r\n250 STARTTLS\r\n');
   else if(line==='STARTTLS'&&starttls){
    socket.write('220 Start TLS\r\n',()=>{
     socket.removeListener('data',handler);
     const secured=new tls.TLSSocket(socket,{isServer:true,secureContext});
     secured.on('error',()=>{});conversation(secured,false,false);
    });return;
   }else if(line==='AUTH LOGIN'){auth=1;socket.write('334 VXNlcm5hbWU6\r\n');}
   else if(line.startsWith('MAIL FROM:')||line.startsWith('RCPT TO:'))socket.write('250 OK\r\n');
   else if(line==='DATA'){dataMode='\r\n';socket.write('354 Data\r\n');}
   else if(line==='QUIT'){socket.end('221 Bye\r\n');}
   else throw Error('Unexpected SMTP command');
  }
 });
}
const php=process.env.FRIENDS_PHP_EXE || 'php',phpRoot=path.dirname(php);
const loaded=(await run(php,['-m'])).stdout;
const extensionArgs=[];
for(const name of ['pdo_sqlite','openssl']) if(!loaded.split(/\\r?\\n/).includes(name)) extensionArgs.push('-d','extension='+(process.platform==='win32'?path.join(phpRoot,'ext/php_'+name+'.dll'):name));
async function test(mode,trusted){
 const fixture=await fs.mkdtemp(path.join(directory,mode+'-'));
 const server=mode==='tls'?tls.createServer(options,s=>conversation(s)):net.createServer(s=>conversation(s,true));
 server.on('tlsClientError',()=>{});
 await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
 const args=[...extensionArgs];
 if(trusted)args.push('-d','openssl.cafile='+cert);
 args.push(path.join(root,'tests/smtp-client.php'),fixture,String(server.address().port),mode);
 const child=spawn(php,args,{stdio:['ignore','pipe','pipe']});let output='';child.stdout.on('data',chunk=>output+=chunk);
 const code=await new Promise(resolve=>child.on('exit',resolve));
 await new Promise(resolve=>server.close(resolve));
 assert.equal(code,trusted?0:2);assert.equal(output,trusted?'accepted':'rejected');
 console.log('PASS '+mode+' '+(trusted?'verified TLS, authentication and MIME delivery':'untrusted certificate rejected'));
}
await test('tls',true);await test('starttls',true);await test('tls',false);
assert.equal(deliveries.length,2);assert.ok(deliveries.every(message=>message.includes('Content-Transfer-Encoding: base64')));
console.log('PASS two isolated deliveries, no external recipients or services.');

if(path.dirname(path.resolve(directory))!==path.resolve(os.tmpdir()) || !path.basename(directory).startsWith('friends-smtp-test-')) throw Error('Unsafe temporary cleanup target.');
await fs.rm(directory,{recursive:true,force:true});
