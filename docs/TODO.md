# Entwicklungsplan und To-do

Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer.

**Ursprünglicher Umfang vom 17. Oktober 2026, erweitert um die ausdrücklich gewünschten 0.3-Funktionen:** Der aktuelle Ausbau umfasst die Community-Grundlage, Chat/Sprachraum, Sicherheit und die nachfolgend ergänzten Feed-/Profil-/Admin-Funktionen. Die nachfolgenden Etappen 2 und 3 sind ausschließlich Ideen für Community-Beiträge, keine weitere zugesagte Eigenentwicklung. Noch offen bleiben Abnahme und Veröffentlichungsvorbereitung.

- [x] Chat mit automatischer Aktualisierung und Emoji-Auswahl.
- [x] Sprachnachrichten: Aufnahme, Vorschau, Verwerfen, Versand, geschützte Wiedergabe.
- [x] Gemeinsamer WebRTC-Sprachraum für sechs Mitglieder mit Beitreten, Stummschalten, Verlassen und STUN-/TURN-Konfiguration; Gegenseitanrufe entfernt.
- [x] HTTPS-Anforderung und Browser-Mikrofonfreigabe in README dokumentiert.
- [x] Bot-Köder, zeitgeprüfte Registrierung, Duplikat-, Link- und Versandlimits.
- [x] Audio-Upload- und Speicherlimits, Sitzungswiderruf bei Passwortänderung und Kontosperre.
- [x] 56 Kernprüfungen, 34 Sprachraumprüfungen und 36 HTTP-Prüfungen bestanden.
- [x] Erste Namensrecherche in Web, GitHub und DPMAregister dokumentiert.
- [ ] Aufnahme und echtes gleichzeitiges Sprechen zwischen mehreren Geräten über unterschiedliche Netzwerke abnehmen.

Ziel: Ein frei verfügbares, eigenständiges Friends-SNCMS mit einem ähnlichen Kernumfang wie das analysierte Altprodukt. PHP 8.4, MySQL 5.7, Deutsch/Englisch, integrierte Erweiterungen und eigener moderner Oberfläche. Öffentliche Projekteinstiegsseite: README mit persönlichem Profil und dezentem Guided-CMS-Hinweis. Noch kein GitHub-Repository angelegt oder veröffentlicht.

## Etappe 1 · Neue Community-Grundlage

Entwicklungsstand 0.3.1: Schema 3, frische Installation oder gesichertes additives Update von 0.2. Keine verpflichtenden Bezahl-APIs oder Abos; GitHub-Regeln und Mitwirkenden-Anleitung sind lokal vorbereitet, aber noch nicht auf GitHub aktiviert.

- [x] ZIP-Struktur und Funktionsbereiche analysieren; Herkunft dokumentieren.
- [x] Eigenes PHP-/PDO-Fundament und Datenbankschema.
- [x] Klassischer Komplett-Upload mit automatischem Installer beim Öffnen der URL, dreistufigem MySQL-Setup, geschützten internen Dateien und Installationssperre.
- [x] Registrierung, Anmeldung, Abmeldung, Profil, Passwortwechsel.
- [x] Feed, Textbeiträge, Kommentare, Likes, eigene private Beiträge, Löschen, Melden.
- [x] Freundschaftsanfragen, Annahme, Entfernen.
- [x] Einfache Nachrichten bestätigter Freunde, Mitteilungen.
- [x] Öffentliche Gruppen mit Mitgliedschaften und Seiten mit Folgen-Funktion.
- [x] Admin-Grundlage: Einstellungen, Sperren, Meldungen, Protokoll.
- [x] Integrierte Plugin-Hooks und Manifest-Erkennung; Beispielplugin.
- [x] Integrierte Theme-Auswahl: Blue und Ember Orange, jeweils Hell/Dunkel.
- [x] DE/EN-Oberfläche und responsives Layout.
- [x] Autorenvermerk in Dateiköpfen, HTML-Metadaten, Fußzeile und Dokumentation.
- [x] MIT-Lizenz, README mit Autorenprofil, Architektur- und Installationsanleitung.
- [x] Automatische Integrationsprüfungen und erste Browserprüfung.
- [ ] Echte MySQL-5.7-Installation und sämtliche Abnahmetests durchführen.
- [ ] Fehlerfälle des finalen MySQL-Installationsschritts einschließlich Dateirechte und Parallelzugriff prüfen.

## Community-Ideen · Alltagstaugliches soziales Netzwerk

- [x] Sichere Avatar-, Header- und Feedbild-Uploads mit Größenlimits und Bild-Neukodierung.
- [ ] Beiträge/Kommentare bearbeiten, Kommentare löschen und separat paginieren.
- [ ] Teilen, Permalinks, Hashtags und Erwähnungen.
- [ ] Freunde-Feed und feinere Sichtbarkeitsrechte.
- [ ] Blockieren, Kontolöschung, Datenexport, Datenschutztexte.
- [ ] E-Mail-Verifikation, SMTP, Passwortwiederherstellung mit einmaligen ablaufenden Tokens.
- [x] Sicherheitsstempel für alle Sitzungen bei Passwortänderung.
- [ ] Nachrichtenverlauf paginieren, Lesestatus und Live-Aktualisierung.
- [ ] Gruppenrollen, private Gruppen, Einladungen und Verwaltung von Gruppen/Seiten.

## Community-Ideen · Vollständigeres Backend und Erweiterungen

- [ ] Rollen mit einzeln vergebenen Rechten und Moderationsoberfläche.
- [x] Mitglieder suchen und bearbeiten; Rollenänderung mit Passwortbestätigung.
- [ ] Beiträge, Gruppen und Seiten vollständig filtern und paginieren.
- [ ] Statistiken mit Zeitbereichen; Aufbewahrungsregeln für Auditdaten.
- [x] Statische Infoseiten ohne ungeprüftes HTML/JavaScript.
- [ ] Weitere Werbeverwaltung nur bei gesonderter Community-Anfrage.
- [x] Eigene CSS-Themes als prüfbare ZIP-Pakete installieren und aktivieren/deaktivieren.
- [ ] Austauschbare Template-Verträge über die gemeinsamen Core-Views hinaus.
- [ ] Plugin-Konfiguration, Abhängigkeiten, Migrationen und dokumentierter API-Lebenszyklus.
- [x] Additives gesichertes Core-Update von Schema 2 auf 3.
- [x] Plugin-ZIP-Upload mit Archivprüfung, Passwortbestätigung und deaktivierter Installation.
- [x] Feed-Emojis, Bilder, Dateien und serverseitige Text-Linkvorschauen.
- [x] Statische DE-/EN-Seiten mit öffentlichem Footer und Entwürfen.
- [x] E-Mail-Einstellungen, Kontaktformular und Opt-in-Newsletter mit manuellen Stapeln und Abmeldung.
- [ ] Künftige Versionen und Plugin-Migrationen separat planen.

## Etappe 4 · GitHub-Veröffentlichung

- [ ] MySQL-5.7/PHP-8.4-Testmatrix und automatisierte Prüfung aufsetzen.
- [ ] Sicherheitsprüfung, Lasttests, Barrierefreiheit und weitere Mobilgeräte prüfen.
- [ ] Installationsvideo und bebilderte DE/EN-Anleitungen.
- [x] Beitragsrichtlinien, Governance, Code of Conduct und Issue-/PR-Vorlagen vorbereiten.
- [ ] Sicherheitskontakt festlegen und GitHub-Schutzregeln nach dem Onlinetest aktivieren.
- [ ] Guided-CMS-Link ergänzen, sobald eine echte Zieladresse feststeht.
- [ ] Spätere Firma erst nach Festlegung von Name und Auftritt eintragen.
- [ ] GitHub-Repository und Release erstellen, sobald ausdrücklich beauftragt.

## Maßstab für kurze Implementierung

Gemeinsame Formulare, Datenbankzugriff, Übersetzungen und Komponenten vermeiden Wiederholung. Keine Code-Verschleierung, keine Minifizierung des Lerncodes und kein Weglassen von Rechten/Validierung zugunsten weniger Zeilen. Den zentralen Controller bei wachsendem Umfang in Fachmodule teilen.
