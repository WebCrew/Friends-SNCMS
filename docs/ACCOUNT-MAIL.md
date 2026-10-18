# Konto-E-Mails / Account email · 0.3.2

Autor: Andreas Holzer @WebCrew (GitHub) · Copyright © 2026.

## Einrichtung

Unter Administration → E-Mail-Einstellungen Versandart (Host-Mail oder SMTP), Absenderadresse und öffentliche HTTPS-Adresse konfigurieren. Bei Installation in einem Unterordner gehört dieser zur Adresse, etwa https://example.org/community. SMTP verwendet überprüftes TLS/STARTTLS. Eine Testmail an das Administratorkonto senden; beim Hoster gegebenenfalls SPF/DKIM einrichten.

Neue Registrierungen benötigen diese Konfiguration und bleiben bis zur Bestätigung im Status „E-Mail unbestätigt“. Der Link gilt 24 Stunden; die geöffnete Seite enthält eine Bestätigungsschaltfläche, damit automatische E-Mail-Prüfer kein Konto freischalten. Abgelaufene oder verlorene Links können auf der Anmeldeseite erneut angefordert werden. Bestehende Konten und das Installations-Administratorkonto bleiben aktiv. Administratoren sehen den neuen Status in der Nutzerverwaltung.

„Passwort vergessen“ sendet einen 30 Minuten gültigen Link. Das neue Passwort muss zweimal eingegeben werden und 12–72 Bytes lang sein. Die erfolgreiche Änderung entwertet sämtliche bisherigen Passwortlinks und bestehenden Sitzungen. Anschließend normal anmelden. Kein automatischer Login durch einen E-Mail-Link.

Versand und Neuanforderung sind begrenzt: zehn Konto-E-Mail-Aktionen pro IP in 15 Minuten, drei Versandanforderungen pro Adresse und Stunde. Bestätigungslinks und Passwortlinks sind zweckgebunden, zufällig und signiert; Änderungen am Passwort, an der Adresse, am Kontostatus oder an der Sitzungsversion entwerten alte Links. Parallel eintreffende Einlösungen werden durch eine bedingte Datenbankänderung abgefangen. Keine zusätzliche Tabelle oder manuelle SQL-Installation nötig. Alte noch unbestätigte Registrierungen werden nicht automatisch gelöscht.

Öffentliche Antworten auf Passwort- und Bestätigungslink-Anforderungen verraten nicht, ob eine Adresse registriert ist. Versandfehler werden ohne Zugangsdaten oder Link protokolliert. E-Mail-Links sind vertraulich; Webserver-Zugriffsprotokolle können URL-Parameter enthalten. Keine Links in öffentliche Fehlerberichte kopieren.

## Update und Prüfung

Vor einem Update Datenbank und privaten Storage sichern. 0.3/0.3.1 → 0.3.2 ändert das Datenbankschema nicht; Konfiguration, SMTP-Geheimnis und hochgeladene Medien behalten. Die Registrierung ist ohne konfigurierte E-Mail-Zustellung gesperrt. Paketdateien ersetzen niemals persönliche Konfigurationen.

Lokal: bin/check.ps1 enthält tests/account-mail.php (22 Prüfungen). tests/account-mail-http.mjs startet selbst kurzlebige lokale HTTP-/TLS-SMTP-Testserver und prüft Registrierung, gesperrten Login, scanner-sichere Bestätigung, Einmalnutzung, Passwortänderung und Sitzungswiderruf (13 Prüfungen, zwei lokale E-Mails). Benötigt PHP mit SQLite/OpenSSL, Node und OpenSSL-CLI; FRIENDS_PHP_EXE und FRIENDS_OPENSSL_EXE können absolute Programmpfade enthalten. Echte Zustellung auf dem Webspace gesondert mit der Testmail prüfen.

## English

Configure mail transport, sender and the canonical public HTTPS URL under Admin → Email settings and send a test email. New registrations require email verification (24 hours); existing users and the installation administrator remain active. Password reset links expire after 30 minutes. Successful reset invalidates previous links and all existing login sessions. Confirmation requires an explicit form submission to avoid email scanner activation. Resend is available from sign-in. There is no schema migration; preserve private configuration and runtime files on update. Local tests capture SMTP deliveries without contacting real recipients; live mail delivery depends on host configuration.
