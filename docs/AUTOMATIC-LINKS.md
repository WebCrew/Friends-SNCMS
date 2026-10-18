# Automatische Linkkarten / Automatic link cards · 0.3.6

Andreas Holzer @WebCrew (GitHub) · Copyright © 2026.

Eine vollständige HTTP- oder HTTPS-Adresse direkt in den Beitragstext einfügen. Nach einer kurzen Tipp-Pause erscheint automatisch eine Vorschau. Das zusätzliche Link-Eingabefeld und dessen Schaltfläche entfallen. Pro Beitrag wird die erste gültige URL als Karte verwendet. Weitere URLs bleiben im Text anklickbar. Einfache Satzzeichen am URL-Ende werden bei der Kartenerkennung entfernt; ausgeglichene Klammern in einer Adresse bleiben erhalten.

Beim Veröffentlichen erkennt der Server die URL erneut und speichert die Karte gemeinsam mit dem Beitrag. Das funktioniert auch ohne JavaScript oder beim Absenden, bevor die Vorschau geladen ist. Der Browser übermittelt keine vertrauenswürdigen Titel oder Beschreibungen; Metadaten kommen ausschließlich vom Server. Eine entfernte oder geänderte erste URL aktualisiert die Vorschau. Ältere, bereits geöffnete Formulare mit dem separaten Feld bleiben übergangsweise kompatibel; die aktuelle URL im Text hat Vorrang.

Admin → Feed & Uploads → Linkvorschauen steuert sowohl Anzeige als auch Erzeugung neuer Karten. Ist die Option aus, sind normale Textbeiträge mit anklickbaren URLs weiterhin möglich. Alte gespeicherte Karten bleiben erhalten. Bereits bestehende reine Textbeiträge werden nicht nachträglich automatisch um Karten ergänzt.

Der Server liest öffentliche Seitentitel und Beschreibungen, lädt aber keine externen Vorschaubilder, Cookies oder Skripte. Die bestehenden IP-/DNS-Prüfungen, Verbot von Weiterleitungen, TLS-Prüfung, Größen- und Zeitlimits bleiben wirksam. Es werden weiterhin höchstens 30 Vorschauabrufe pro Mitglied und Stunde erlaubt. Bis zu zehn Metadaten-Ergebnisse werden in der Sitzung 15 Minuten zwischengespeichert. Fehlen Metadaten oder ist das Abruflimit erreicht, bleibt eine sichere Domain-Karte erhalten; der Beitrag wird deshalb nicht verworfen.

Automatische Karten folgen der Sichtbarkeit des Beitrags. Private Beiträge und ihre Karten erscheinen nur beim Autor. Der Zugriff auf die Vorschau-API erfordert weiterhin Anmeldung und gültigen CSRF-Schutz. Keine Datenbankmigration nötig.

## English

Paste a complete HTTP(S) URL directly into post text. The first valid URL generates a preview after a short typing pause. Publishing independently extracts and stores that card on the server, including when JavaScript is unavailable or the preview has not finished loading. Changed or removed URLs update the browser preview; stale requests are ignored. No separate Add Link field is needed. The admin preview switch remains available. Metadata is fetched only on the server using existing SSRF/TLS/size/time protections, cached briefly and rate-limited. A safe domain card is used when details are unavailable. Post privacy applies unchanged; no schema migration or retroactive backfill is performed.
