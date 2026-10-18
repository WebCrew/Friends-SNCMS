# Text chat, voice messages and the live voice room

Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer.

## Requirements

**HTTPS with a valid certificate** is required for the public website. Browser microphone capture requires a secure context and the user's explicit microphone permission. Loopback development origins such as localhost/127.0.0.1 are the browser's local exception. Use a current browser with `getUserMedia`, `MediaRecorder` and WebRTC.

PHP requires Fileinfo, enabled uploads, `upload_max_filesize >= 8M`, `post_max_size >= 10M` and writable private storage. Configure STUN and, for dependable connections through restrictive networks, TURN. This CMS supplies signaling; it is not a TURN relay.

Primary references: [secure microphone capture](https://developer.mozilla.org/en-US/docs/Web/API/MediaDevices/getUserMedia), [MediaRecorder](https://developer.mozilla.org/en-US/docs/Web/API/MediaRecorder), [WebRTC and ICE](https://webrtc.org/getting-started/peer-connections).

## Use

For private text and recorded voice messages, become accepted friends first. Open Messages and select a friend. Conversations update by short polling. No WebSocket or Node server is required for the PHP application/signaling.

Record voice message → allow microphone → stop → preview → send or discard. Capture stops after two minutes in the interface. Limits: 8 MB per upload, 20 uploads per account per day, 100 MB total stored audio per sender. Browser formats: WebM/Opus, Ogg/Opus or MP4. The server checks container MIME/signatures; recordings are not transcoded or fully inspected. Opaque filenames live in private storage blocked from direct HTTP access by the server rules. Playback checks the authenticated participant and active friendship, including byte-range requests.

The **Live voice room** is a shared community lounge for up to **six active, signed-in members**, including people who are not private friends. Choose Join voice room, allow microphone access, talk simultaneously, mute/unmute or leave. There is no ringing, invitation or accept-call interface. Participation is visible to signed-in members; this is not a private conversation. Audio does not start merely by opening the page.

The browser establishes one WebRTC audio connection to each other participant. Leaving the messages page stops microphone tracks and sends a leave signal where supported. Heartbeats expire after 35 seconds; a new join receives a fresh session identifier/token, and old browser sessions cannot continue signaling. Concurrent tabs cannot join as the same user. Password/session revocation and suspension remove stale memberships. Background/mobile lifecycle behavior and physical audio remain subject to device acceptance.

## ICE configuration

Administration → Live voice room → STUN / TURN takes a standard JSON list. Default: `[]`; no public ICE provider is contacted. Supply your own server's or chosen provider's values, for example:

```json
[
  {"urls": "stun:stun.example.org:3478"},
  {"urls": ["turn:turn.example.org:3478?transport=udp", "turns:turn.example.org:5349?transport=tcp"], "username": "service-user", "credential": "limited-service-credential"}
]
```

These are placeholders. Browser ICE credentials are necessarily disclosed to signed-in browsers. Use scoped relay credentials and provider quotas, never unrelated administrator credentials. Automatic short-lived TURN credential issuance is not implemented.

WebRTC transports audio directly or through your TURN relay. Temporary signaling expires after three minutes when the voice API is used, and leaving removes related signaling. PHP does not store the live room's audio and there is no live-room recording feature. Stored voice messages are a separate feature with no automatic retention scheduler yet. Do not describe stored messages as end-to-end encrypted.

## No required paid service

There is **no mandatory subscription, paid API or commercial provider account**. Browser WebRTC is used directly; a free/open-source TURN server such as [coturn](https://github.com/coturn/coturn) can be self-hosted. Server resources and bandwidth can still cost money. No relay credentials or hosted service are bundled.

The CMS, text chat, recordings and PHP signaling can remain on compatible shared hosting with HTTPS. Running TURN itself usually needs a separate VPS/server or a hosting product that explicitly supports its long-running process and network ports. A relay can run separately from the CMS. With no suitable ICE configuration, direct connections may work on compatible networks but arbitrary cross-network voice is not guaranteed. Do not describe shared hosting alone as a universal live-audio solution.

## Development schema change

Version 0.2 introduced the shared-room schema. Version 0.3 preserves those room tables and supports an additive schema-2-to-3 administrator update after backup; see FEATURES-0.3.md. New installations use an empty database. There is no direct 0.1 upgrade.

## Deutsch

Private Text-/Sprachnachrichten sind nur zwischen bestätigten Freunden möglich. Im gemeinsamen **Live-Sprachraum** können bis zu sechs aktive Mitglieder gleichzeitig sprechen, ohne gegenseitige Anrufe. Beitritt erfolgt bewusst mit Mikrofonfreigabe; beim Verlassen der Seite endet die Teilnahme. Der öffentliche Betrieb benötigt **HTTPS**.

Ein Bezahl-API-Abo ist ausdrücklich nicht erforderlich. Eine frei verfügbare TURN-Lösung kann selbst betrieben werden, meistens auf einem VPS/Server zusätzlich zum Shared Hosting des CMS. Hosting und Traffic können Kosten verursachen. Für zuverlässige Verbindungen über beliebige Netze STUN/TURN passend konfigurieren; reine Shared-Hosting-Webspace-Rechte reichen normalerweise nicht zum Betrieb des TURN-Prozesses.

Die automatische Testreihe prüft Rechte, Raumteilnahme, Vermittlung und künstlich erzeugte Audio-Uploads. Echte Aufnahme und gleichzeitiges Sprechen zwischen mehreren Geräten sind separat abzunehmen. Für eine Erstinstallation eine frische leere Datenbank verwenden; ein gesichertes Update von 0.2 ist in FEATURES-0.3.md beschrieben.
