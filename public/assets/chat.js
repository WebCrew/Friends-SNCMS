/* Friends-SNCMS · Author: Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
(() => {
  const root = document.querySelector('#chat-root');
  if (!root) return;
  const words = JSON.parse(root.dataset.strings);
  const uid = Number(root.dataset.user);
  function setup(panel, peer) {
  const list = panel.querySelector('.message-list');
  const form = panel.querySelector('.chat-form');
  const preview = panel.querySelector('.recording-preview');
  let after = Number(list?.lastElementChild?.dataset.message || 0);
  let polling = false, errors = 0, stopped = false, toastTimer, pollTimer;
  let ownsRecording = false;
  let recorder = null, recordingStream = null, recordingTimer, recordingUrl = '', recordedBlob = null, generation = 0;
  const say = key => words[key] || key;

  function toast(message) {
    const box = root.querySelector('.chat-toast');
    box.textContent = message; box.hidden = false;
    clearTimeout(toastTimer); toastTimer = setTimeout(() => { box.hidden = true; }, 7000);
  }

  async function request(action, data = {}, file = null) {
    const body = new FormData(); body.set('csrf', root.dataset.token); body.set('chat_action', action);
    for (const [key, value] of Object.entries(data)) body.set(key, String(value));
    if (file) body.set('audio', file, 'voice.' + (file.type.includes('ogg') ? 'ogg' : file.type.includes('mp4') ? 'm4a' : 'webm'));
    const response = await fetch(root.dataset.api, {method: 'POST', body, credentials: 'same-origin'});
    let result;
    try { result = await response.json(); } catch { throw new Error(say('network_error')); }
    if (!response.ok || result.error) throw new Error(result.error || say('network_error'));
    return result;
  }

  function append(messages) {
    if (!list) return;
    const nearBottom = list.scrollHeight - list.scrollTop - list.clientHeight < 100;
    for (const message of messages) {
      const id = Number(message.id);
      if (id <= after) continue;
      const bubble = document.createElement('div');
      bubble.className = 'bubble' + (Number(message.sender_id) === uid ? ' mine' : '');
      bubble.dataset.message = String(id);
      if (message.kind === 'audio') {
        const audio = document.createElement('audio');
        audio.controls = true; audio.preload = 'none'; audio.src = message.media_url; bubble.append(audio);
      } else {
        const text = document.createElement('p'); text.textContent = message.body; bubble.append(text);
      }
      const time = document.createElement('small'); time.textContent = message.created_at + ' UTC';
      bubble.append(time); list.append(bubble); after = id;
    }
    if (nearBottom) list.scrollTop = list.scrollHeight;
  }

  async function sync() {
    if (!peer || polling || stopped) return;
    polling = true;
    try {
      const url = new URL(root.dataset.api, location.href);
      url.searchParams.set('peer', String(peer)); url.searchParams.set('after', String(after)); if (!after) url.searchParams.set('history', '1');
      const response = await fetch(url, {credentials: 'same-origin', cache: 'no-store'});
      const data = await response.json();
      if (!response.ok || data.error) {
        if (response.status === 401) stopped = true;
        throw new Error(data.error || say('network_error'));
      }
      if (!stopped) append(data.messages); errors = 0;
    } catch (error) {
      if (errors++ === 0) toast(error.message || say('network_error'));
    } finally { polling = false; }
  }

  form?.addEventListener('submit', async event => {
    event.preventDefault();
    const input = form.elements.body, button = form.querySelector('.chat-toolbar>.button');
    const body = input.value.trim(); if (!body) return;
    button.disabled = true;
    try { await request('text', {peer, body}); input.value = ''; await sync(); }
    catch (error) { toast(error.message); }
    finally { button.disabled = false; }
  });

  panel.querySelectorAll('.emoji').forEach(button => button.addEventListener('click', () => {
    const input = form.elements.body;
    const next = input.value.slice(0, input.selectionStart) + button.textContent + input.value.slice(input.selectionEnd);
    if ([...next].length > 5000) return;
    input.setRangeText(button.textContent, input.selectionStart, input.selectionEnd, 'end'); input.focus();
    button.closest('details').open = false;
  }));

  function clearRecording() {
    generation++;
    if (recorder?.state === 'recording') recorder.stop();
    recordingStream?.getTracks().forEach(track => track.stop());
    recordingStream = null; recorder = null; recordedBlob = null; if (ownsRecording) root.dataset.recording = '0'; ownsRecording = false;
    clearInterval(recordingTimer);
    if (recordingUrl) URL.revokeObjectURL(recordingUrl);
    recordingUrl = '';
    if (preview) { preview.hidden = true; preview.querySelector('audio').removeAttribute('src'); }
  }

  panel.querySelector('.record-audio')?.addEventListener('click', async () => {
    if (root.dataset.voiceJoined === '1' || root.dataset.voiceJoining === '1' || root.dataset.recording === '1' || recordedBlob) {
      toast(say('finish_audio')); return;
    }
    if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) { toast(say('microphone_error')); return; }
    if (!window.MediaRecorder) { toast(say('audio_unsupported')); return; }
    const button = panel.querySelector('.record-audio'); button.disabled = true;
    const current = ++generation; root.dataset.recording = '1'; ownsRecording = true;
    preview.hidden = false; preview.querySelector('audio').hidden = true;
    preview.querySelector('.send-audio').hidden = true; preview.querySelector('.stop-recording').hidden = true;
    preview.querySelector('.recording-status').textContent = say('voice_requesting');
    try {
      const acquired = await navigator.mediaDevices.getUserMedia({audio: {echoCancellation: true, noiseSuppression: true}, video: false});
      if (current !== generation) { acquired.getTracks().forEach(track => track.stop()); return; }
      recordingStream = acquired;
      const mime = ['audio/webm;codecs=opus', 'audio/ogg;codecs=opus', 'audio/mp4'].find(type => MediaRecorder.isTypeSupported(type));
      if (!mime) throw new Error(say('audio_unsupported'));
      recorder = new MediaRecorder(recordingStream, {mimeType: mime, audioBitsPerSecond: 64000});
      const chunks = []; let seconds = 0, bytes = 0;
      preview.querySelector('.stop-recording').hidden = false;
      preview.querySelector('.recording-status').textContent = say('recording') + ' 0:00 / 2:00';
      recorder.ondataavailable = event => {
        if (event.data.size) {
          chunks.push(event.data); bytes += event.data.size;
          if (bytes > 8 * 1024 * 1024 && recorder?.state === 'recording') recorder.stop();
        }
      };
      recorder.onstop = () => {
        if (current !== generation) return;
        clearInterval(recordingTimer); recordingStream?.getTracks().forEach(track => track.stop());
        recordingStream = null; recorder = null; if (ownsRecording) root.dataset.recording = '0'; ownsRecording = false;
        recordedBlob = new Blob(chunks, {type: mime});
        if (recordedBlob.size > 8 * 1024 * 1024 || !recordedBlob.size) { clearRecording(); toast(say('audio_upload_error')); return; }
        recordingUrl = URL.createObjectURL(recordedBlob);
        const audio = preview.querySelector('audio'); audio.src = recordingUrl; audio.hidden = false;
        preview.querySelector('.stop-recording').hidden = true; preview.querySelector('.send-audio').hidden = false;
        preview.querySelector('.recording-status').textContent = say('send_audio');
      };
      recorder.start(1000);
      recordingTimer = setInterval(() => {
        seconds++;
        preview.querySelector('.recording-status').textContent = say('recording') + ' ' + Math.floor(seconds / 60) + ':' + String(seconds % 60).padStart(2, '0') + ' / 2:00';
        if (seconds >= 120 && recorder?.state === 'recording') recorder.stop();
      }, 1000);
    } catch (error) { clearRecording(); toast(error.name === 'NotAllowedError' ? say('microphone_error') : error.message); }
    finally { button.disabled = false; }
  });

  panel.querySelector('.stop-recording')?.addEventListener('click', () => { if (recorder?.state === 'recording') recorder.stop(); });
  panel.querySelector('.discard-audio')?.addEventListener('click', clearRecording);
  panel.querySelector('.send-audio')?.addEventListener('click', async event => {
    if (!recordedBlob) return;
    const button = event.currentTarget; button.disabled = true;
    try { await request('audio', {peer}, recordedBlob); clearRecording(); await sync(); }
    catch (error) { toast(error.message); }
    finally { button.disabled = false; }
  });
  const dispose = () => { stopped = true; clearTimeout(pollTimer); clearTimeout(toastTimer); clearRecording(); panel.querySelectorAll('audio').forEach(audio => audio.pause()); };
  window.addEventListener('pagehide', dispose, {once:true});
  if (list) list.scrollTop = list.scrollHeight;
  if (peer) {
    const poll = async () => { await sync(); if (!stopped) pollTimer = setTimeout(poll, Math.min(15000, (document.hidden ? 5000 : 1800) * 2 ** Math.min(errors, 3))); };
    poll();
  }
  return () => { dispose(); window.removeEventListener('pagehide', dispose); };
  }
  document.querySelectorAll('[data-chat-peer]').forEach(panel => { if (Number(panel.dataset.chatPeer)) setup(panel, Number(panel.dataset.chatPeer)); });
  const dock = document.querySelector('.chat-dock');
  if (!dock) return;
  const friends = dock.querySelector('.dock-friends'), windowPanel = dock.querySelector('.dock-window');
  const launcher = dock.querySelector('.chat-launcher'), title = dock.querySelector('.dock-title');
  const contacts = [...dock.querySelectorAll('.dock-contact,.dock-quick-contact')], searchable = contacts.filter(button => button.matches('.dock-contact')), drafts = new Map();
  const storageKey = 'friends.chat.' + uid;
  let dispose, selected = 0;
  const remember = () => { try { sessionStorage.setItem(storageKey, JSON.stringify({peer:selected, minimized:windowPanel.classList.contains('minimized')})); } catch {} };
  const toggleFriends = (open, focus = true) => { friends.hidden = !open; launcher.setAttribute('aria-expanded', String(open)); if (open && focus) dock.querySelector('.dock-filter').focus(); };
  launcher.addEventListener('click', () => toggleFriends(friends.hidden));
  dock.querySelector('.dock-friends-close').addEventListener('click', () => { toggleFriends(false); launcher.focus(); });
  dock.querySelector('.dock-filter').addEventListener('input', event => {
    const term = event.target.value.toLocaleLowerCase();
    searchable.forEach(button => { button.hidden = !button.textContent.toLocaleLowerCase().includes(term); });
    dock.querySelector('.dock-no-results').hidden = !searchable.length || searchable.some(button => !button.hidden);
  });
  const saveDraft = () => { const input = windowPanel.querySelector('[name=body]'); if (selected && input) drafts.set(selected, input.value); };
  const select = (button, focus = true) => {
    saveDraft(); dispose?.(); selected = Number(button.dataset.peer);
    windowPanel.querySelector('.dock-name').textContent = button.dataset.name;
    windowPanel.querySelector('.dock-profile-avatar').replaceChildren(button.querySelector('.avatar').cloneNode(true));
    const full = new URL(root.dataset.messages, location.href); full.searchParams.set('id', String(selected)); dock.querySelector('.dock-expand').href = full.href;
    const body = windowPanel.querySelector('.dock-body'); body.replaceChildren(document.querySelector('#dock-conversation').content.cloneNode(true));
    const panel = body.querySelector('.dock-conversation'); panel.querySelector('[name=id]').value = String(selected);
    panel.querySelector('[name=body]').value = drafts.get(selected) || '';
    windowPanel.hidden = false; windowPanel.classList.remove('minimized'); title.setAttribute('aria-expanded', 'true');
    contacts.forEach(contact => contact.classList.toggle('selected', contact.dataset.peer === button.dataset.peer));
    dispose = setup(panel, selected); toggleFriends(false, false); remember();
    if (focus) panel.querySelector('[name=body]').focus();
  };
  contacts.forEach(button => button.addEventListener('click', () => select(button)));
  const minimize = () => {
    if (root.dataset.recording === '1') { root.querySelector('.chat-toast').textContent = words.finish_audio; root.querySelector('.chat-toast').hidden = false; return; }
    windowPanel.classList.toggle('minimized'); title.setAttribute('aria-expanded', String(!windowPanel.classList.contains('minimized'))); remember();
  };
  dock.querySelector('.dock-minimize').addEventListener('click', minimize); title.addEventListener('click', minimize);
  dock.querySelector('.dock-close').addEventListener('click', () => { saveDraft(); dispose?.(); dispose = null; selected = 0; windowPanel.hidden = true; remember(); launcher.focus(); });
  dock.addEventListener('keydown', event => { if (event.key === 'Escape') { toggleFriends(false); if (!windowPanel.hidden && !windowPanel.classList.contains('minimized')) minimize(); launcher.focus(); } });
  try { const saved = JSON.parse(sessionStorage.getItem(storageKey) || '{}'), button = contacts.find(item => Number(item.dataset.peer) === saved.peer); if (button) { select(button, false); if (saved.minimized) minimize(); } } catch {}
})();
