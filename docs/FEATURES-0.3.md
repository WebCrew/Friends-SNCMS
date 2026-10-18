# Friends-SNCMS 0.3 – Funktionen und Bedienung

Andreas Holzer · @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer.

## Feed und Profile

Der Beitragseditor bietet Emoji-Auswahl, eine konfigurierbare Anzahl an Anhängen und automatische Vorschauen für URLs im Beitragstext. Beiträge können auch nur aus einem Anhang oder Link bestehen. Bilder: JPEG, PNG und WebP; Dateien: PDF, UTF-8-TXT und ZIP. Weitere Dateitypen werden bewusst nicht ungeprüft angenommen. ZIP-Anhänge werden nur heruntergeladen, niemals entpackt. Jede Datei unterliegt der Beitragssichtbarkeit; Gäste erhalten keinen Zugriff. Das Löschen eines Beitrags entfernt seine Anhänge.

Bilder werden mit GD dekodiert, auf höchstens 2.000 Pixel Breite verkleinert und neu als PNG gespeichert. Das entfernt Metadaten und angehängte Fremdinhalte. Maximal 12 Megapixel und 8.000 Pixel je Kante im Original; kein SVG/GIF. Animierte Bilder und EXIF-Ausrichtung werden nicht übernommen. Der Server transkodiert keine Dokumente und führt keinen Virenscan durch; unbekannte Dokumente nicht unbedacht öffnen.

Unter Einstellungen können Mitglieder Bio, bis zu sechs Homepage-/Social-Links, Avatar und Headerbild bearbeiten und Bilder wieder entfernen. Links je Zeile als `Bezeichnung | https://adresse`. Profile bleiben nur für angemeldete aktive Mitglieder sichtbar.

## Administration

Die Navigation enthält Übersicht, Community-Einstellungen, Feed & Uploads, Mitgliederverwaltung, statische/rechtliche Seiten, E-Mail/Newsletter, Plugins, Themes, Sprachraum, Meldungen und Protokoll.

- Feed & Uploads: Emoji-Auswahl, Bilder, Dateien, Linkvorschauen und Profilbilder einzeln schalten; frei wählbare positive MB je Datei und Anhänge pro Beitrag und 10–500 MB je Konto/Tag. Das Gesamtkontingent ist 250 MB je Konto. Neue Uploads lassen sich sperren, bestehende Inhalte bleiben sichtbar. PHP- und Webserverlimits müssen die gewählten Werte zulassen; empfohlen: `upload_max_filesize=10M`, `post_max_size=48M`. Bild-Neukodierung kann Dateien vergrößern.
- Mitglieder: nach Namen suchen, Anzeigename, E-Mail, Bio, Sprache, Rolle und Status bearbeiten. Der Benutzername bleibt stabil. Rollenänderungen erfordern das aktuelle Administratorpasswort. Bestehende Administratoren sind gegen Sperre/Herabstufung über dieses Formular geschützt. E-Mail-, Rollen- und Statusänderungen widerrufen Sitzungen. Eine geänderte E-Mail nimmt Newsletter-Einwilligungen zurück.
- Statische Seiten: Adresskürzel, DE-/EN-Titel und Inhalte erfassen, als Entwurf speichern oder veröffentlichen. Veröffentlichte Seiten stehen im Footer und sind ohne Anmeldung erreichbar. Zurücknehmen entfernt die öffentliche Darstellung, erhält den Entwurf. Klartext und HTTP(S)-Links; keine ungeprüften HTML-/Scriptblöcke. Eigene geeignete Rechtstexte liefern; das CMS erzeugt keine rechtliche Beratung.
- Plugins/Themes: siehe [Paketformat](PACKAGES.md).

## Linkvorschau

Vorschauen laden serverseitig ausschließlich Titel und Beschreibung, keine externen Bilder, Favicons, Videos oder Skripte. Ein Zielserver sieht dabei die IP des CMS-Servers. Nur HTTP(S) mit Standardport; keine Zugangsdaten, Redirects oder privaten/reservierten IPv4-Ziele. DNS wird geprüft und die Verbindung an eine geprüfte Adresse gebunden. Reine IPv6-Ziele und nicht abrufbare Seiten erhalten eine einfache Domain-Karte. Höchstens 256 KiB HTML, begrenzte cURL-Zeit und 30 Vorschauanfragen je Konto/Stunde. Keine Abrufe beim späteren Lesen des Feeds.

HTTPS-Zertifikate werden geprüft. Auf Windows wird bei unterstütztem cURL zusätzlich der native Zertifikatsspeicher genutzt ([cURL-Dokumentation](https://curl.se/libcurl/c/CURLOPT_SSL_OPTIONS.html)); auf anderen Systemen muss der Hoster gültige CA-Zertifikate für PHP bereitstellen.

## E-Mail, Kontaktformular und Newsletter

Es ist kein kostenpflichtiger Anbieter nötig. Versand über den vorhandenen PHP-Maildienst des Hosters oder ein eigenes SMTP-Konto. SMTP unterstützt STARTTLS und direktes TLS, beide mit Zertifikatsprüfung; keine unverschlüsselte Anmeldung. Hosting-Freigaben für ausgehende SMTP-Verbindungen sowie Absender-Domain, SPF/DKIM/DMARC und Hoster-Versandlimits selbst einrichten.

SMTP-Passwörter liegen in `smtp-secret.php` neben der privaten Installationseinstellung, im geschützten Storage-Verzeichnis. Leer lassen behält das Passwort, das separate Löschfeld entfernt es. Der Wert wird nicht in Formularen oder Datenbankeinstellungen angezeigt. Private Dateien nur für den PHP-Prozess lesbar halten und gesichert mitnehmen.

Der Kontaktformular-Empfänger ist eine fest konfigurierte Teamadresse. Benutzer können keine beliebigen Empfänger oder Mailheader bestimmen. CSRF, zeitgeprüfter Bot-Köder, Einwilligungsfeld und IP-Versandlimit schützen das öffentliche Formular. Das Formular speichert die Anfrage nicht zusätzlich in der CMS-Datenbank.

Newsletter:

1. Versandart, Absender und öffentliche HTTPS-Basis-URL konfigurieren; Basis-URL bezeichnet das Verzeichnis, das `index.php` enthält.
2. Newsletter aktivieren. Mitglieder können ihn ausdrücklich in ihren Einstellungen abonnieren; keine automatisch gesetzte Einwilligung und kein Fremdadressen-Import.
3. Betreff und Inhalt vorbereiten. Die Warteschlange enthält nur derzeit aktive Mitglieder mit Einwilligung.
4. Mit „Nächsten Stapel senden“ höchstens drei Empfänger je Klick bedienen. Vorbereitung allein versendet nichts.
5. Einwilligung wird vor jedem Versand erneut geprüft. Abmeldung ist über Einstellungen oder einen kryptografischen Link ohne Anmeldung möglich; ein Linkscanner allein meldet niemanden ab, weil erst ein POST bestätigt.

Es gibt keine automatische Massenversandschleife oder Cron-Pflicht. Wartende Empfänger lassen sich abbrechen. „Vom Mailserver angenommen“ ist keine garantierte Postfachzustellung. Fehlgeschlagene oder unterbrochene Zustellungen werden wegen möglicher Doppelzustellung nicht automatisch wiederholt. Bei „In Bearbeitung“ nach einem Abbruch zuerst Mailserverprotokolle prüfen. E-Mail ist kein Ende-zu-Ende-verschlüsselter Kanal.

## Update von 0.2

1. Wartungsfenster wählen und Datenbank, privaten Storage und eigene Erweiterungen sichern.
2. Die neuen Code-Dateien bereitstellen, ohne `storage/config.php`, vorhandene Dateien, SMTP-Geheimnisse oder eigene Erweiterungen zu überschreiben. Beim klassischen Upload müssen die mitgelieferten Schutzregeln aktiv bleiben; interne Verzeichnisse dürfen nicht direkt per HTTP erreichbar sein.
3. Als bisheriger Administrator anmelden. Das Updateformular für Schema 2 erscheint vor dem normalen Betrieb.
4. Sicherung bestätigen und Update ausführen. Sechs zusätzliche Tabellen entstehen; Konten, Beiträge und Chatdaten bleiben erhalten. Der Abschluss markiert Schema 3.

MySQL-DDL ist nicht automatisch rückrollbar. Die additiven Schritte sind wiederholbar, falls ein Lauf unterbrochen wird. Bei Problemen Serverprotokoll prüfen und gegebenenfalls Code **und** Daten aus derselben Sicherung wiederherstellen. Kein direkter Updatepfad von 0.1: dort weiterhin eine separate frische Testinstallation verwenden. Der MySQL-5.7-Abnahmetest steht noch aus.

## English quick reference

Feed: emoji picker, up to four private attachments (JPEG/PNG/WebP images, PDF/TXT/ZIP downloads), text-only server-fetched link cards. Profile: bio, six labeled social/homepage URLs, avatar/header upload/removal. Admin: dedicated section navigation, member editing, bilingual public static/legal pages, host mail or verified TLS SMTP, opt-in newsletter with manual batches and unsubscribe, disabled-by-default extension ZIP installation.

Required PHP extensions: PDO MySQL, Fileinfo, GD, cURL, ZIP, OpenSSL, DOM, Iconv and Sessions. No Composer/npm runtime dependencies or paid APIs. Back up database/private storage before an additive schema-2-to-3 update. Never replace private configuration with a release package. See [validation](VALIDATION.md) for tested coverage and remaining acceptance.
