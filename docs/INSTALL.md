# Installation / Installieren

Andreas Holzer · @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer.

## Der normale Weg

1. ZIP am PC entpacken und den Ordner **Friends-SNCMS** öffnen.
2. **Den gesamten Inhalt dieses Ordners** in das gewünschte Webspace-Verzeichnis hochladen – Dateien und Unterordner gemeinsam, einschließlich der versteckten Datei **.htaccess**. Dort müssen index.php und install.php direkt liegen.
3. Die URL dieses Verzeichnisses öffnen, etwa **https://deine-domain.de/** oder **https://deine-domain.de/community/**. Der Installer startet automatisch.
4. Den drei Schritten folgen: Server prüfen, leere Datenbank verbinden, Community und Administratorkonto einrichten.

**Nicht nur public hochladen. Keine Ordner verschieben. Die Domain muss nicht auf public zeigen.** install.php ist zusätzlich ein direkter Einstieg in denselben Installer.

Die leere Datenbank samt Zugangsdaten legt man wie üblich im Hoster-Kundenbereich an. Die Tabellen erzeugt das CMS selbst. Kein manueller SQL-Import, kein Lizenzschlüssel. Nach erfolgreicher Einrichtung öffnet dieselbe URL das CMS. Ein erneuter Aufruf von install.php erlaubt keine Neuinstallation über die bestehende Konfiguration.

## Webspace-Voraussetzungen

PHP 8.4+, PDO MySQL, Fileinfo, GD, cURL, ZIP, OpenSSL, DOM, Iconv und Sessions; HTTPS. MySQL 5.7 mit InnoDB/utf8mb4 bleibt das noch separat abzunehmende Kompatibilitätsziel. Der Installer prüft die PHP-Module und die Schreibrechte für Storage. Für Uploads empfohlen: upload_max_filesize=10M und post_max_size=48M.

Der klassische Upload setzt **Apache 2.4 oder kompatiblen LiteSpeed mit aktiven .htaccess-/Rewrite-Regeln** voraus. Die mitgelieferte Regeldatei erlaubt nur index.php, install.php, öffentliche Assets und ACME-Prüfdateien. Direkte Zugriffe auf Code, Konfiguration, Uploads, SQL, Dokumentation und Erweiterungen werden gesperrt. PHP prüft vor dem Start, dass die Serverregeln aktiv sind. Fehlt die Schutzdatei oder ignoriert der Server sie, startet die Installation nicht ungeschützt.

Für reine Nginx-/IIS-Server gelten .htaccess-Dateien nicht automatisch. Ein Betreiber muss dort gleichwertige Regeln konfigurieren; das ist keine zusätzliche Aufgabe im normalen unterstützten Shared-Hosting-Ablauf. Die bislang mögliche public-only-Konfiguration bleibt für erfahrene Betreiber verfügbar, wenn ausschließlich public/ als DocumentRoot freigegeben ist. Sie ist für den klassischen Upload nicht erforderlich.

## Wenn etwas hakt

Der Installer unterscheidet Datenbankfehler, ohne Zugangsdaten in der Fehlermeldung auszugeben:

- **1045:** Server erreichbar, Anmeldung abgewiesen. Datenbankbenutzer, Passwort und ausgewählte Datenbankinstanz prüfen; dieselben Angaben bei phpMyAdmin testen.
- **1044:** Benutzer darf die angegebene Datenbank nicht verwenden; Zuordnung und Rechte beim Hoster prüfen.
- **1049:** Datenbankname auf diesem Server nicht vorhanden.
- **2002/2003:** Host, Port oder lokaler Socket nicht erreichbar.
- **2005:** Hostname kann nicht aufgelöst werden.

Ein kleiner geschützter Diagnoseeintrag im privaten Storage enthält nur Zeit, SQLSTATE und numerischen Treiberfehler. Keine Hostnamen, Datenbanknamen, Benutzernamen oder Passwörter werden darin gespeichert. Der letzte Eintrag wird überschrieben und nach erfolgreicher Installation entfernt. Zugangsdaten gehören nicht in öffentliche Fehlermeldungen oder GitHub-Issues.

- **Schutzregeln nicht aktiv:** Beim FTP-Upload versteckte Dateien anzeigen und .htaccess ebenfalls übertragen. Bleibt die Meldung, den Hoster nach aktivem Apache-kompatiblem mod_rewrite und erlaubten .htaccess-Regeln fragen. Die Schutzprüfung nicht entfernen.
- **HTTP 500 sofort nach Upload:** Der Hoster verbietet möglicherweise eine .htaccess-Direktive oder mod_rewrite ist nicht aktiv. Serverprotokoll prüfen bzw. dem Hoster die Regeldatei geben.
- **Storage nicht beschreibbar:** Schreibrechte für den PHP-Prozess beim Hoster korrigieren; keine pauschalen 777-Rechte vergeben.
- **Uploadfehler:** PHP-Größenlimits und beschreibbares Upload-Temporärverzeichnis prüfen. Details unter FEATURES-0.3.md.

## Bestehende Daten erhalten

Neue Installationen gehören in ein leeres Zielverzeichnis und eine leere Datenbank. Für Updates zuerst Datenbank, privaten Storage und eigene Erweiterungen sichern. Niemals Konfigurationsdateien oder Nutzerdaten durch ein frisches Paket ersetzen. **0.3 → 0.3.1** benötigt keine Schemaänderung. **0.2 → 0.3.1** nutzt nach Anmeldung als bisheriger Administrator den additiven Schema-2-auf-3-Updateweg; siehe [Updateanleitung](FEATURES-0.3.md#update-von-02). Kein direkter Updatepfad von 0.1.

MySQL-DDL kann nicht als eine Transaktion zurückgerollt werden. Nach einer fehlgeschlagenen Erstinstallation bereits vorhandene Community-Daten nicht löschen. Für eine ausdrücklich verworfene Testinstallation eine neue leere Datenbank verwenden und die private Fehlerursache prüfen. Bei gescheitertem Konfigurationsschreiben nach Tabellenerzeugung kann ein erfahrener Betreiber die private Konfiguration fachgerecht wiederherstellen. Nie Zugangsdaten veröffentlichen.

## Lokale Vorschau

Unter Windows bin/preview.ps1 im entpackten Ordner starten. Die Vorschau legt einen getrennten SQLite-Datensatz an und zeigt dessen generierte Zugangsdaten an. bin/serve.ps1 startet stattdessen den echten Installer. Beide nutzen einen lokalen Router, weil der PHP-Entwicklungsserver .htaccess nicht verarbeitet. Der Router ist nur für lokale Entwicklung, nicht für den Webspace.

## English

Extract the ZIP, open Friends-SNCMS and upload **all its contents**, including .htaccess, to your chosen web directory. Open its URL; the guided installer starts automatically. install.php is an optional direct entry. Do not upload only public/ and do not change the domain document root for this standard installation. Requires compatible Apache/LiteSpeed protection, PHP 8.4 modules and an empty MySQL database. Back up configuration, runtime files and database before updating.

## Konto-E-Mails ab 0.3.2

Vor der ersten öffentlichen Registrierung Versandart, Absender und öffentliche HTTPS-Adresse unter Administration → E-Mail-Einstellungen speichern und eine Testmail senden. Neue Konten werden erst nach E-Mail-Bestätigung freigeschaltet. „Passwort vergessen“ nutzt denselben Versand. Das Update von 0.3.1 benötigt keine Datenbankänderung. Siehe [Konto-E-Mails](ACCOUNT-MAIL.md).
