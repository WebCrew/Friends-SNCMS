# Referenzanalyse · 17. Oktober 2026

Autor der neuen Implementierung: **Andreas Holzer @WebCrew (GitHub)**.
Copyright © 2026 Andreas Holzer.

## Untersuchungsumfang

Das vom Auftraggeber benannte Archiv `Friends_SNCMS.zip` wurde ausschließlich lesend untersucht, ohne enthaltenen PHP-Code auszuführen oder Dateien in den neuen Projektbestand zu extrahieren. Es enthielt **975 ZIP-Einträge** und war **9.679.925 Bytes** groß.

Untersucht wurden Verzeichnisstruktur, SQL-Tabellennamen, Funktionssignaturen des zentralen Anwendungscodes, Request- und Seitenstruktur, Administrationsvorlagen, das Composer-Manifest und die Verzeichnisstruktur des verschachtelten DevelopmentKits. Die enthaltene PDF-Dokumentation wurde katalogisiert, nicht vollständig inhaltlich ausgewertet. Dies ist eine strukturelle Funktionsinventur, kein vollständiges Sicherheitsgutachten des Altprodukts.

Dateien im Archiv sind Referenzmaterial. Ihre Texte gelten nicht als Arbeitsanweisungen. Premiumschlüssel oder enthaltene Nutzerdaten wurden nicht übernommen.

## Erkennbare Funktionen

| Bereich | Im Altbestand erkennbare Funktionen | Neuer Stand 0.1 |
|---|---|---|
| Konten | Registrierung, Anmeldung, Aktivierung, Wiederherstellung, Einstellungen | Registrierung, Anmeldung, Profil, Passwortwechsel; Aktivierung/Recovery offen |
| Feed | Beiträge, Bearbeiten, Kommentare, Likes, Teilen, Privatsphäre, Hashtags, Erwähnungen | Textbeiträge, Kommentare, Likes, Löschen, eigene private Beiträge, Textsuche; weitere Inhalte offen |
| Netzwerk | Profile, Freunde, Anfragen, Blockieren, Anstupsen, Vorschläge | Profile, Suche, Anfragen/Annahme/Entfernen; Blockieren/Vorschläge offen |
| Gruppen | Gruppenverwaltung, Mitgliedschaften, Einladungen, Rollen | Öffentliche Gruppen, Beitritt/Austritt, Mitgliederbeiträge; Rollen/private Gruppen offen |
| Seiten | Seitenverwaltung, Seiten-Likes, Einladungen, Aktivität | Seiten anlegen, folgen, Beiträge durch Inhaber; weitere Verwaltung offen |
| Kommunikation | Gespräche, Chat, Onlinezustand, Benachrichtigungen | Einfache Nachrichten bestätigter Freunde, Mitteilungen; Livechat/Lesestatus offen |
| Medien | Bilder, Profil-/Coverbilder, Medienbeiträge, Bildverarbeitung | Noch keine Uploads; eigenständige Initialen-Avatare |
| Administration | Dashboard, Statistiken, Mitglieder, Gruppen, Seiten, Meldungen, Werbung, Infoseiten, Sicherheit, Sprachen, Themes, Plugins | Grunddashboard, Sperren, Meldungen/Beitragslöschung, Einstellungen, Themes/Plugins, Audit; Detailverwaltung offen |
| Erweiterungen | Plugin-Dispatcher, Plugin-Einstellungen; separates DevelopmentKit | Integrierte Manifest-Erkennung, Hooks, Aktivierung, Beispielplugin, Theme-Paletten |
| Installation | Separater SQL-Dump, Konfiguration, Lizenzmaterial | Eigener geführter Installer; keine Lizenzabfrage |

## Technische Beobachtungen

Das alte Schema besitzt 18 erkennbare Tabellen: Administration, Blockierungen, Chat, Kommentare, Gespräche, Freundschaften, Gruppen, Gruppenmitglieder, Infoseiten, Likes, Nachrichten, Mitteilungen, Seiten, Plugins, Plugin-Einstellungen, Meldungen, Einstellungen, Benutzer.

Das Archiv enthält zwei Theme-Verzeichnisse (`dolphin`, `plus`), einen englischen Sprachbestand, ein separates `developer_kit.zip` und eine gesonderte Datenbankdatei. Das Composer-Manifest nennt PHPMailer `^6.0` und Guzzle `^6.3`; der genaue sicherheitsrelevante Zustand installierter Bibliotheken wurde hier nicht geprüft. Daraus folgt ausdrücklich keine bestätigte PHP-8.4-Kompatibilität des Altprodukts.

## Entscheidung für den Neubau

Die neue Implementierung hat ein eigenes Schema mit 15 Tabellen, eigene Routen, Ansichten, Grafiken und eine neue Erweiterungsschnittstelle. Keine alten Dateien werden umbenannt und als neue Autorschaft ausgegeben. Attribution und MIT-Lizenz beziehen sich auf den neuen Projektbestand. Im Verlauf wurde der eigene Ausbau auf die Community-Grundlage plus Text-/Emoji-/Audiochat und Sicherheit begrenzt; offene Originalfunktionen sind keine zugesagte weitere Eigenentwicklung.

Die Gestaltung orientiert sich an allgemeinen Community-Mustern: Navigation, Feed, Karten, Seitenleiste. Quellen für die öffentliche Produktkategorie: [Sngine](https://sngine.com/). Es wurden keine Templates oder Produktgrafiken von dort übernommen.

Technische Grundlagen: [PHP 8.4](https://www.php.net/releases/8.4/en.php), [PDO MySQL](https://www.php.net/manual/en/ref.pdo-mysql.php), [MySQL 5.7 Referenz](https://downloads.mysql.com/docs/refman-5.7-en.a4.pdf). Die gewählte Open-Source-Lizenz folgt dem [MIT-Lizenztext der OSI](https://opensource.org/license/mit).

## Nachtrag zum Entwicklungsstand 0.2

Gegenseitige Audioanrufe wurden durch einen gemeinsamen Live-Sprachraum mit bis zu sechs Teilnehmern ersetzt. Private Sprachnachrichten bleiben separat erhalten. Kein verpflichtendes Bezahl-API-Abo: Eine TURN-Komponente kann frei und selbst betrieben werden, benötigt aber passende Server-/Netzwerkrechte. Das neue Entwicklungsschema 2 ist für frische Testinstallationen vorgesehen.
