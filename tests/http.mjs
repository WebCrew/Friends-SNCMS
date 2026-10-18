/* Friends-SNCMS · Author: Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
// Run only against a disposable seed-preview fixture. Uses synthetic audio, never a microphone.
import assert from 'node:assert/strict';
const base = new URL(process.env.FRIENDS_TEST_URL || 'http://127.0.0.1:8090/');
if (!['127.0.0.1','localhost'].includes(base.hostname)) throw new Error('HTTP tests are restricted to a local disposable fixture.');
const password = process.env.FRIENDS_TEST_PASSWORD;
if (!password) throw new Error('Set FRIENDS_TEST_PASSWORD to the password of the disposable preview fixture.');
let checks = 0;
const check = (condition, text) => { assert.ok(condition,text); checks++; console.log('PASS '+text); };
class Client {
  cookies = new Map();
  async get(route='login',params={}) { const url=new URL('index.php',base); url.search=new URLSearchParams({r:route,...params}); return this.request(url); }
  async request(url,options={}) {
    const response=await fetch(url,{...options,redirect:'manual',headers:{...options.headers,Cookie:[...this.cookies].map(([k,v])=>k+'='+v).join('; ')}});
    for(const cookie of response.headers.getSetCookie()) { const pair=cookie.split(';')[0],at=pair.indexOf('='); this.cookies.set(pair.slice(0,at),pair.slice(at+1)); }
    return response;
  }
  async form(route,fields,file=null) {
    const url=new URL('index.php',base); url.searchParams.set('r',route);
    const body=new FormData(); for(const [key,value] of Object.entries(fields)) body.set(key,String(value));
    if(file) body.set('audio',new Blob([file],{type:'audio/ogg'}),'synthetic-voice.ogg');
    return this.request(url,{method:'POST',body});
  }
  async login(name) {
    let response=await this.get(); let html=await response.text();
    const csrf=/name="csrf" value="([a-f0-9]+)"/.exec(html)?.[1];
    response=await this.form('login',{csrf,action:'login',identity:name,password});
    assert.equal(response.status,303);
    response=await this.get('messages'); html=await response.text();
    this.csrf=/name="csrf" value="([a-f0-9]+)"/.exec(html)?.[1];
    return html;
  }
}
function page(serial,sequence,flags,granule,payload) {
  const header=Buffer.alloc(28); header.write('OggS'); header[5]=flags; header.writeBigUInt64LE(BigInt(granule),6); header.writeUInt32LE(serial,14); header.writeUInt32LE(sequence,18); header[26]=1; header[27]=payload.length;
  const data=Buffer.concat([header,payload]); let crc=0;
  for(const byte of data) { crc=(crc^(byte<<24))>>>0; for(let bit=0;bit<8;bit++) crc=((crc<<1)^((crc&0x80000000)?0x04c11db7:0))>>>0; }
  data.writeUInt32LE(crc,22); return data;
}
const head=Buffer.alloc(19); head.write('OpusHead'); head[8]=1; head[9]=1; head.writeUInt16LE(312,10); head.writeUInt32LE(48000,12);
const tags=Buffer.alloc(20); tags.write('OpusTags'); tags.writeUInt32LE(4,8); tags.write('Test',12);
const ogg=Buffer.concat([page(7,0,2,0,head),page(7,1,0,0,tags),page(7,2,4,960,Buffer.from([0xf8,0xff,0xfe]))]);
const guest=new Client(),alice=new Client(),bob=new Client(),outsider=new Client();
let response=await guest.get('chat_api'); const initialCookie=response.headers.get('set-cookie'); check(response.status===401,'unauthenticated chat API denied');
response=await guest.get(); const html=await response.text();
check(response.headers.get('content-security-policy').includes("object-src 'none'"),'restrictive content security policy');
check(response.headers.get('permissions-policy')==='microphone=(self), camera=()','microphone constrained to this origin; camera disabled');
check(initialCookie?.includes('HttpOnly') && initialCookie?.includes('SameSite=Lax'),'session cookie protections');
response=await guest.form('login',{action:'login',csrf:'wrong',identity:'andreas',password}); check(response.status===303,'invalid CSRF form redirected without sign-in');
response=await guest.get('admin'); check(response.status===303,'CSRF rejection does not grant admin access');
await alice.login('andreas'); await bob.login('mira'); await outsider.login('jonas');
response=await guest.get('voice_api'); check(response.status===401,'guest cannot use voice room endpoint');
response=await alice.form('voice_api',{csrf:'wrong',voice_action:'state'}); check(response.status===400,'voice room rejects invalid CSRF');
response=await alice.form('voice_api',{csrf:alice.csrf,voice_action:'state'}); let roomState=await response.json(); check(response.status===200 && roomState.members.length===0,'authenticated room state starts empty');
response=await alice.form('voice_api',{csrf:alice.csrf,voice_action:'join'}); const roomAlice=await response.json(); check(response.status===200 && !!roomAlice.voice_token,'explicit room join through HTTP');
response=await alice.form('voice_api',{csrf:alice.csrf,voice_action:'join'}); check(response.status===400,'second tab join refused');
response=await bob.form('voice_api',{csrf:bob.csrf,voice_action:'sync',voice_token:roomAlice.voice_token}); check(response.status===400,'room token cannot be reused by another account');
response=await bob.form('voice_api',{csrf:bob.csrf,voice_action:'join'}); const roomBob=await response.json(); check(response.status===200 && roomBob.members.length===2,'second participant joins shared room');
const roomOffer={type:'offer',sdp:'v=0\r\nm=audio 9 UDP/TLS/RTP/SAVPF 111\r\n'};
response=await alice.form('voice_api',{csrf:alice.csrf,voice_action:'signal',voice_token:roomAlice.voice_token,target_session:roomBob.session_id,kind:'offer',payload:JSON.stringify(roomOffer)}); check(response.status===200,'room signaling sent through authenticated HTTP');
response=await bob.form('voice_api',{csrf:bob.csrf,voice_action:'sync',voice_token:roomBob.voice_token,muted:1}); roomState=await response.json(); check(response.status===200 && roomState.signals[0]?.kind==='offer' && !JSON.stringify(roomState.members).includes('token_hash'),'recipient receives signaling without credential disclosure');
response=await outsider.form('voice_api',{csrf:outsider.csrf,voice_action:'sync',voice_token:roomAlice.voice_token}); check(response.status===400,'unjoined member cannot poll room signaling');
response=await bob.form('voice_api',{csrf:bob.csrf,voice_action:'leave',voice_token:roomBob.voice_token}); check(response.status===200,'participant explicitly leaves room');
response=await alice.form('voice_api',{csrf:alice.csrf,voice_action:'signal',voice_token:roomAlice.voice_token,target_session:roomBob.session_id,kind:'offer',payload:JSON.stringify(roomOffer)}); check(response.status===400,'departed browser cannot receive new signaling');
response=await alice.form('voice_api',{csrf:alice.csrf,voice_action:'leave',voice_token:roomAlice.voice_token}); check(response.status===200,'last participant leaves room');
response=await alice.form('chat_api',{csrf:alice.csrf,chat_action:'call_start',peer:2,offer:roomOffer.sdp}); check(response.status===400,'removed direct-call endpoint no longer starts calls');
response=await bob.get('admin'); check(response.status===403,'member cannot open administration');
response=await alice.form('chat_api',{csrf:'wrong',chat_action:'text',peer:2,body:'Invalid token'}); check(response.status===400,'chat API rejects invalid CSRF');
const text='HTTP fixture 😀 '+Date.now()+' <script>alert(1)</script>';
response=await alice.form('chat_api',{csrf:alice.csrf,chat_action:'text',peer:2,body:text}); check(response.status===200,'chat text and emoji sent through HTTP');
response=await bob.get('chat_api',{peer:1}); let data=await response.json(); check(data.messages.some(message=>message.body===text),'recipient receives chat message');
response=await outsider.get('chat_api',{peer:1}); check(response.status===400,'third party cannot poll a private conversation');
response=await alice.form('chat_api',{csrf:alice.csrf,chat_action:'text',peer:2,body:text}); check(response.status===400,'repeated HTTP message blocked as spam');
response=await alice.form('chat_api',{csrf:alice.csrf,chat_action:'audio',peer:2},ogg); let uploaded=await response.json(); check(response.status===200 && uploaded.ok,'synthetic Ogg voice message upload');
response=await bob.get('chat_api',{peer:1}); data=await response.json(); const voice=data.messages.findLast(message=>message.kind==='audio'); check(!!voice?.media_url,'voice message has protected media endpoint');
response=await bob.request(new URL(voice.media_url,base),{headers:{Range:'bytes=0-9'}}); check(response.status===206 && (await response.arrayBuffer()).byteLength===10,'authorized audio byte range playback');
response=await outsider.request(new URL(voice.media_url,base)); check(response.status===400,'unrelated member cannot download voice recording');
response=await guest.request(new URL(voice.media_url,base)); check(response.status===401,'guest cannot download voice recording');
response=await alice.form('chat_api',{csrf:alice.csrf,chat_action:'audio',peer:2},Buffer.from('<?php echo "not audio";')); check(response.status===400,'disguised executable rejected as audio');
response=await bob.get('messages',{id:1}); const conversation=await response.text(); check(!conversation.includes('<script>alert(1)</script>') && conversation.includes('&lt;script&gt;'),'chat HTML escapes script payloads');
const priorSession=new Client(); await priorSession.login('jonas');
const changedPassword='Changed-Disposable-2026!';
response=await outsider.form('settings',{csrf:outsider.csrf,action:'password',current_password:password,password:changedPassword}); check(response.status===303,'authenticated password change succeeds');
response=await priorSession.get('chat_api'); check(response.status===401,'password change revokes another existing session');
response=await outsider.get('chat_api'); check(response.status===200,'password-changing session remains authenticated');
response=await outsider.get('settings'); const currentSettings=await response.text();
outsider.csrf=/name="csrf" value="([a-f0-9]+)"/.exec(currentSettings)?.[1];
response=await outsider.form('settings',{csrf:outsider.csrf,action:'password',current_password:changedPassword,password}); assert.equal(response.status,303);
console.log(`\n${checks} HTTP checks passed. Synthetic audio only; real device audio is a separate acceptance test.`);
