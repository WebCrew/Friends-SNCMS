# Live-Installationsprüfung · 17. Oktober 2026

Andreas Holzer · @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer.

Testinstallation: https://test-sso.a-holzer.de/

## Ergebnis

Die Installation auf dem Webspace wurde erfolgreich abgeschlossen:

- PHP-/Serverprüfung bestanden.
- Verbindung zur vorgesehenen Datenbank und Leerheitsprüfung bestanden.
- Datenbankschema und erstes Administratorkonto angelegt.
- Private Konfiguration und Installationssperre geschrieben.
- Administrator-Anmeldung, Feed und Verwaltungsübersicht funktionieren.
- Installationsaufrufe ohne Anmeldung leiten zur Anmeldung weiter.
- Direkte HTTP-Zugriffe auf Konfiguration, internes Bootstrap und SQL-Schema wurden mit 403 abgewiesen.

Der ursprüngliche Verbindungsfehler war MySQL 1045: Der Server lehnte die Anmeldung ab. Die gleichen Angaben funktionierten auch bei phpMyAdmin nicht. Nach einer durch den Betreiber vorgenommenen Passwortkorrektur war die Installation möglich. Der Installer ändert keine Datenbankbenutzer-Passwörter beim Hoster.

Die Fehlerbehandlung unterscheidet nun Anmeldung, Berechtigungen, fehlende Datenbank, nicht erreichbaren Host/Port, DNS und TLS. Eine geschützte Diagnosedatei enthält nur Zeitpunkt, SQLSTATE und numerischen Treiberfehler, keine Zugangsdaten; sie wurde nach erfolgreicher Einrichtung automatisch entfernt.

## Grenzen

Dies ist der Nachweis der Installation und erster Lese-/Anmeldeabläufe auf diesem Webspace. Die lokale Entwicklung besitzt weiterhin keinen eigenen MySQL-Testserver. Der genaue Datenbank-Versionsstring wurde hier nicht gespeichert; daraus folgt keine vollständige Zertifizierung aller MySQL-Versionen oder Hosting-Varianten. Live-Mailzustellung, reale Mikrofon-/Netzwerktests und eine unabhängige Sicherheitsprüfung sind weiterhin separat durchzuführen.

Zugangsdaten und eine konfigurierte Installation sind nicht Bestandteil des Downloadpakets.
