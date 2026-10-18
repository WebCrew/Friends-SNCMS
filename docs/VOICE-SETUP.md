# Sprachraum einrichten / Voice-room setup

Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 · Stand 18.10.2026.

Unter Administration → Live-Sprachraum gibt es vier Möglichkeiten:

1. Direkt: keine externen Dienste; zunächst im gleichen Netzwerk testen.
2. Kostenlose Verbindungshilfe STUN: Cloudflare STUN wird erst nach bewusster Auswahl und Speichern verwendet. Beim Beitritt wird die öffentliche Netzwerkadresse ermittelt; es wird kein Audio über STUN vermittelt. Nicht jede Router-/Firewall-Konstellation funktioniert damit.
3. Eigener TURN-Server: Hostname, Port, UDP/TCP/TLS und ein eingeschränktes TURN-Konto eintragen. Das CMS erzeugt daraus die ICE-Liste. TLS benötigt ein gültiges Zertifikat. Dieser Modus installiert keine Server-Software im PHP-Webspace.
4. Erweitert: die bestehende JSON-Liste für mehrere Server verwenden. Bereits vorhandene Listen werden als erweiterte Konfiguration beibehalten.

Nach einer Änderung den Sprachraum verlassen und erneut beitreten. Vorher gemeinsam mit zwei Konten, Kopfhörern und unterschiedlichen Netzwerken testen. HTTPS und Mikrofonfreigabe bleiben erforderlich. Normale Sprachnachrichten brauchen kein STUN/TURN.

## Kosten und Software

Cloudflare beschreibt den öffentlichen STUN-Dienst als kostenlos. Die Einstellung nutzt ausschließlich stun:stun.cloudflare.com:3478, kein kostenpflichtiges TURN-Angebot und keine Registrierung. Die Teilnehmer geben dabei Netzwerkadressen an den Anbieter weiter. Quelle: [offizielle Cloudflare-FAQ](https://developers.cloudflare.com/realtime/turn/faq/).

Als eigene kostenlose Server-Software stehen [coturn](https://github.com/coturn/coturn) und [eturnal](https://eturnal.net/doc/) zur Verfügung. Beide unterstützen STUN/TURN. Ein Betreiber mit Serverzugriff installiert und pflegt die Software nach der offiziellen Anleitung. Die Programme verlangen kein Software-Abo; Servermiete und Datenverkehr können Kosten verursachen. Normales PHP-Shared-Hosting reicht für den Serverbetrieb in der Regel nicht. Der CMS-Webspace kann weiterhin Shared Hosting nutzen und sich mit einem anderen TURN-Server verbinden.

Serverbetreiber richten öffentliche Adresse, passende Listener und Relay-Ports, Firewall, gültiges TLS-Zertifikat bei TLS-Nutzung, Authentifizierung und Bandbreitenlimits ein. Keine offenen Relays anbieten. Das hier unterstützte statische TURN-Konto ist ausschließlich für diese Community und mit eingeschränktem Kontingent anzulegen. Die Browser der Sprachraumteilnehmer benötigen seine Daten. Niemals Server-, Hosting- oder CMS-Administratorzugangsdaten verwenden. Das TURN-Shared-Secret für temporäre REST-Zugangsdaten gehört nicht in diese Felder; automatisierte temporäre TURN-Zugangsdaten sind nicht Teil dieser Version.

Die Server-Anleitungen bleiben beim jeweiligen Projekt, damit keine veralteten Paketnamen oder unpassenden Installationsbefehle in die Oberfläche eingebaut werden. Das CMS installiert keine Pakete und öffnet keine Server-Ports automatisch.

## English

Choose Direct, optional free Cloudflare STUN, your own TURN server or a validated advanced ICE list under Admin → Live voice room. Changes apply on rejoin. coturn and eturnal are free server software, but hosting and bandwidth can cost money. Normal PHP shared hosting generally cannot run these services; it can still host the CMS while a separate server handles TURN. Use restricted community-specific TURN credentials, never an administrator account. Client browsers receive these credentials. Physical microphone and cross-network relay acceptance should be performed on your own deployment.
