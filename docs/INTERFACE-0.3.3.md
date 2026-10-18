# Friends-SNCMS 0.3.3 · Bedienung / Interface

Andreas Holzer @WebCrew (GitHub) · Copyright © 2026.

## Beiträge

Unter dem Textfeld liegen Emojis, Bilder/Dateien und Links in einer kompakten Werkzeugleiste. Emojis und Link-Eingabe werden erst beim Öffnen gezeigt. Ausgewählte Dateien erscheinen als kleine Vorschau. Einstellungen unter Admin → Feed & Uploads bleiben wirksam. Die Sichtbarkeit nutzt im Day- und Night-Modus passende native Auswahlfelder. Escape schließt offene Werkzeugfelder.

## Chat

Auf großen Bildschirmen ab 1200 Pixeln gibt es rechts eine schmale Kontaktleiste mit Avataren für den direkten Chat-Zugriff. Unten rechts öffnet „Nachrichten“ eine durchsuchbare Liste bestätigter aktiver Freunde. Ein Klick öffnet ein kleines Chatfenster direkt auf der aktuellen Seite. Text, Emojis und aufgezeichnete Sprachnachrichten nutzen dieselben geschützten Endpunkte wie die große Nachrichtenansicht. Das Fenster lässt sich minimieren, wieder öffnen oder schließen. Der Pfeil öffnet die große Ansicht samt Live-Sprachraum.

Die gewählte Unterhaltung und der Minimierungszustand werden pro Mitglied in sessionStorage für diesen Browser-Tab gemerkt. Nachrichteninhalte werden dort nicht gespeichert. Ungesendete Textentwürfe bleiben beim Wechsel zwischen Kontakten innerhalb derselben Seite im Arbeitsspeicher und gehen bei einem Seitenwechsel verloren. Beim Schließen oder Wechseln einer Unterhaltung werden Mikrofonaufnahme und Audiowiedergabe beendet. Während einer laufenden Aufnahme wird das Minimieren verhindert. Der Chat bleibt auf eine gleichzeitig geöffnete kleine Unterhaltung beschränkt.

## Seiten und Gruppen

Auf der jeweiligen Seite „Logo und Titelbild bearbeiten“ öffnen. Eigentümer und Administratoren können je ein Bild hochladen, ersetzen oder entfernen. Es gelten die vorhandenen Bildfreigaben unter Admin → Feed & Uploads, Größen-/Tageskontingente, GD-Neucodierung und die geschützte Medienauslieferung nur an angemeldete Mitglieder. Ein fehlerhafter zweiter Upload lässt die bisherigen Bilder unverändert. Ersetzte Dateien werden nach erfolgreicher Speicherung entfernt.

Die Bildverweise liegen unter community.ID.avatar und community.ID.header in f_settings; die Dateien bleiben im vorhandenen privaten Medien-Storage. Dadurch benötigt 0.3.2 → 0.3.3 keine neue Tabelle oder manuelle SQL-Installation. Persönliche Konfiguration, Laufzeitdaten und eigene Erweiterungen beim Update erhalten.

## English

The composer now groups emoji, upload and link controls in a compact toolbar. The audience selector follows dark mode. The floating messenger lists accepted friends, opens one compact conversation, supports text/emoji/voice messages and can be minimized or expanded to the existing full messenger. Only selected peer/minimized state persists in sessionStorage; drafts remain in page memory. Group and page owners or administrators can manage separate logos and covers. Existing upload limits, protected delivery and image processing apply. No database schema migration is needed.
