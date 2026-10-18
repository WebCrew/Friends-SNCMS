/* Friends-SNCMS · Author: Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
(() => {
  const root = document.querySelector('#chat-root'), room = document.querySelector('.voice-room');
  if (!root || !room) return;
  const words = JSON.parse(root.dataset.strings), uid = Number(root.dataset.user);
  const join = room.querySelector('.voice-join'), leave = room.querySelector('.voice-leave');
  const mute = room.querySelector('.voice-mute'), status = room.querySelector('.voice-status');
  const membersNode = room.querySelector('.voice-members'), audioNode = room.querySelector('.voice-audio');
  const peers = new Map();
  let stream = null, token = '', session = '', iceServers = [], after = 0;
  let generation = 0, joining = false, syncing = false, timer, lastSuccess = 0, lobbyTimer, disposed = false;
  const say = key => words[key] || key;

  async function request(action, data = {}, specificToken = token) {
    const body = new FormData();
    body.set('csrf', root.dataset.token); body.set('voice_action', action);
    if (specificToken) body.set('voice_token', specificToken);
    for (const [key, value] of Object.entries(data)) body.set(key, String(value));
    const response = await fetch(root.dataset.voiceApi, {method: 'POST', body, credentials: 'same-origin', cache: 'no-store'});
    let result;
    try { result = await response.json(); } catch { throw new Error(say('network_error')); }
    if (!response.ok || result.error) {
      const error = new Error(result.error || say('network_error')); error.status = response.status; throw error;
    }
    return result;
  }

  function closePeer(id) {
    const peer = peers.get(id); if (!peer) return;
    peers.delete(id); peer.connection.close(); peer.holder.remove();
  }

  function reset() {
    generation++; clearTimeout(timer);
    for (const id of [...peers.keys()]) closePeer(id);
    stream?.getTracks().forEach(track => track.stop()); stream = null;
    token = ''; session = ''; after = 0; joining = false;
    root.dataset.voiceJoined = '0'; root.dataset.voiceJoining = '0';
    join.hidden = false; join.disabled = false; mute.hidden = true; leave.hidden = true;
    mute.textContent = say('mute'); status.textContent = say('voice_not_joined');
  }

  function signal(target, kind, payload) {
    return request('signal', {target_session: target, kind, payload: JSON.stringify(payload)});
  }

  function createPeer(member) {
    const id = member.session_id;
    if (peers.has(id)) return peers.get(id);
    const connection = new RTCPeerConnection({iceServers});
    stream.getTracks().forEach(track => connection.addTrack(track, stream));
    const holder = document.createElement('div'); holder.className = 'voice-listener';
    const audio = document.createElement('audio'); audio.autoplay = true;
    audio.setAttribute('aria-label', member.name);
    holder.append(audio); audioNode.append(holder);
    const peer = {connection, holder, audio, pending: [], offered: false}; peers.set(id, peer);
    connection.onicecandidate = event => {
      if (event.candidate && event.candidate.candidate && token && peers.has(id)) {
        signal(id, 'candidate', event.candidate.toJSON()).catch(() => {
          if (token && peers.has(id)) status.textContent = say('voice_failed');
        });
      }
    };
    connection.ontrack = event => {
      audio.srcObject = event.streams[0] || new MediaStream([event.track]);
      audio.play().catch(() => {
        if (holder.querySelector('button')) return;
        const play = document.createElement('button'); play.type = 'button'; play.className = 'button soft';
        play.textContent = say('voice_reconnect_audio') + ' · ' + member.name;
        play.addEventListener('click', async () => {
          try { await audio.play(); play.remove(); } catch { status.textContent = say('voice_failed'); }
        });
        holder.append(play);
      });
    };
    connection.onconnectionstatechange = () => {
      if (!peers.has(id)) return;
      if (connection.connectionState === 'failed') status.textContent = say('voice_failed');
    };
    return peer;
  }

  function showMembers(members) {
    membersNode.replaceChildren();
    for (const member of members) {
      const badge = document.createElement('span'); badge.className = 'voice-member';
      badge.textContent = member.name + (Number(member.user_id) === uid ? ' (' + say('voice_you') + ')' : '') + (Number(member.muted) ? ' · ' + say('voice_muted') : '');
      membersNode.append(badge);
    }
    if (!members.length) membersNode.textContent = say('voice_empty');
  }

  async function reconcile(members, current) {
    showMembers(members);
    const active = new Set(members.map(member => member.session_id));
    for (const id of [...peers.keys()]) if (!active.has(id)) closePeer(id);
    for (const member of members) {
      if (current !== generation || !stream) return;
      if (member.session_id === session) continue;
      const peer = createPeer(member);
      // Exactly one side offers, including simultaneous joins. This avoids negotiation glare.
      if (uid < Number(member.user_id) && !peer.offered) {
        peer.offered = true;
        try {
          await peer.connection.setLocalDescription(await peer.connection.createOffer());
          if (current !== generation || !peers.has(member.session_id)) return;
          await signal(member.session_id, 'offer', peer.connection.localDescription.toJSON());
        } catch {
          if (current === generation) { closePeer(member.session_id); status.textContent = say('voice_failed'); }
        }
      }
    }
  }

  async function consume(signals, members, current) {
    for (const item of signals) {
      if (current !== generation || !stream) return;
      const member = members.find(person => person.session_id === item.sender_session);
      if (!member) { after = Math.max(after, Number(item.id)); continue; }
      try {
        const peer = createPeer(member), description = JSON.parse(item.payload);
        if (item.kind === 'candidate') peer.pending.push(description);
        else if (item.kind === 'offer') {
          await peer.connection.setRemoteDescription(description);
          await peer.connection.setLocalDescription(await peer.connection.createAnswer());
          if (current !== generation) return;
          await signal(member.session_id, 'answer', peer.connection.localDescription.toJSON());
        } else if (item.kind === 'answer' && !peer.connection.remoteDescription) {
          await peer.connection.setRemoteDescription(description);
        }
        while (peer.connection.remoteDescription && peer.pending.length) await peer.connection.addIceCandidate(peer.pending.shift());
      } catch {
        // A malformed or departed peer must not block signaling for everyone else.
        if (current === generation) { closePeer(member.session_id); status.textContent = say('voice_failed'); }
      } finally {
        if (current === generation) after = Math.max(after, Number(item.id));
      }
    }
  }

  async function sync(current = generation) {
    if (!token || syncing || current !== generation) return;
    syncing = true;
    try {
      const result = await request('sync', {after, muted: stream?.getAudioTracks()[0]?.enabled ? 0 : 1});
      if (current !== generation) return;
      lastSuccess = Date.now();
      await reconcile(result.members, current);
      await consume(result.signals, result.members, current);
    } catch (error) {
      if (current !== generation) return;
      status.textContent = error.message || say('network_error');
      if (error.status === 400 || error.status === 401 || Date.now() - lastSuccess > 12000) {
        const message = error.message; await exitRoom(); status.textContent = message || say('voice_failed');
      }
    } finally {
      syncing = false;
      if (token && current === generation) timer = setTimeout(() => sync(current), 1800);
    }
  }

  async function exitRoom() {
    const previous = token; reset();
    if (previous) { try { await request('leave', {}, previous); } catch {} }
    request('state').then(result => { if (!token && !joining) showMembers(result.members); }).catch(() => {});
  }

  join.addEventListener('click', async () => {
    if (joining || token) return;
    if (root.dataset.recording === '1') { status.textContent = say('finish_audio'); return; }
    if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) { status.textContent = say('microphone_error'); return; }
    if (!window.RTCPeerConnection) { status.textContent = say('audio_unsupported'); return; }
    joining = true; root.dataset.voiceJoining = '1';
    join.hidden = true; leave.hidden = false; status.textContent = say('voice_requesting');
    const current = ++generation;
    try {
      const acquired = await navigator.mediaDevices.getUserMedia({audio: {echoCancellation: true, noiseSuppression: true}, video: false});
      if (current !== generation) { acquired.getTracks().forEach(track => track.stop()); return; }
      stream = acquired;
      const result = await request('join');
      if (current !== generation) { await request('leave', {}, result.voice_token); return; }
      token = result.voice_token; session = result.session_id; iceServers = result.ice_servers;
      lastSuccess = Date.now(); root.dataset.voiceJoined = '1'; root.dataset.voiceJoining = '0';
      mute.hidden = false; status.textContent = say('voice_connected');
      await reconcile(result.members, current);
      await sync(current);
    } catch (error) {
      if (current !== generation) return;
      const message = error.name === 'NotAllowedError' ? say('microphone_error') : error.message;
      await exitRoom(); status.textContent = message || say('voice_failed');
    } finally { if (current === generation) joining = false; }
  });

  leave.addEventListener('click', exitRoom);
  mute.addEventListener('click', () => {
    const track = stream?.getAudioTracks()[0]; if (!track) return;
    track.enabled = !track.enabled; mute.textContent = say(track.enabled ? 'mute' : 'unmute');
  });
  window.addEventListener('pagehide', () => {
    disposed = true; clearTimeout(lobbyTimer);
    if (token) {
      const body = new FormData(); body.set('csrf', root.dataset.token); body.set('voice_action', 'leave'); body.set('voice_token', token);
      navigator.sendBeacon(root.dataset.voiceApi, body);
    }
    reset();
  });
  async function refreshLobby() {
    try {
      if (!token && !joining && !disposed) {
        const result = await request('state');
        if (!token && !joining && !disposed) showMembers(result.members);
      }
    } catch (error) { if (!token && !disposed) status.textContent = error.message; }
    finally { if (!disposed) lobbyTimer = setTimeout(refreshLobby, 12000); }
  }
  refreshLobby();
})();
