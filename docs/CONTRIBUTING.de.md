# Regeln für Gastentwickler

Projektleitung: **Andreas Holzer · [@WebCrew](https://github.com/WebCrew)**.
Copyright © 2026 Andreas Holzer. Beiträge erscheinen unter der vorhandenen MIT-Lizenz.

[English](../CONTRIBUTING.md) · [Architektur](ARCHITECTURE.md) · [Projektführung](../GOVERNANCE.md) · [Sicherheit](../SECURITY.md)

Diese Regeln gelten auch für KI-unterstützte Beiträge. Sie regeln die Annahme eines Beitrags; ein Vorschlag begründet keinen Anspruch auf Übernahme. Das CMS ist eine frühe Entwicklungsversion. Vorhandene Einschränkungen stehen in `TODO.md` und `VALIDATION.md`.

## 1. Zweck vorher abstimmen

Fehler mit reproduzierbaren Schritten melden. Neue Funktionen, Abhängigkeiten, Datenbankänderungen, öffentliche Erweiterungsschnittstellen und Architekturänderungen zuerst als Issue vorschlagen und eine ausdrückliche Maintainer-Zustimmung abwarten. Ein offenes Issue oder eine positive Reaktion ist keine Freigabe. Kleine Dokumentations- und Übersetzungskorrekturen dürfen direkt als Pull Request kommen.

Der Kern bleibt ein kleines selbst gehostetes Community-CMS mit Text-, Emoji- und Audio-Kommunikation. Große optionale Funktionen gehören möglichst in Erweiterungen. Die Projektleitung darf Vorschläge wegen unpassendem Umfang oder unverhältnismäßigem Wartungsaufwand ablehnen.

## 2. Ein Pull Request, ein nachvollziehbarer Zweck

In einem Fork und einem eigenen Themenbranch auf Basis des aktuellen Hauptbranches arbeiten. Keine Mischung aus Fehlerbehebung, globaler Formatierung, Umbenennungen, Paketupdates und vorsorglichem Umbau. Große Änderungen vorher in prüfbare Schritte zerlegen. Komplexe Arbeit früh als Entwurf zeigen.

Problem, Ergebnis, zugehöriges Issue, Prüfungen und Einschränkungen beschreiben. Bei sichtbaren Änderungen Vorher-/Nachher-Bilder ergänzen. Nur fiktive Daten verwenden; keine Zugangsdaten oder privaten Inhalte in Screenshots und Protokollen.

## 3. Technischen Rahmen einhalten

- PHP 8.4 und MySQL 5.7 bleiben Zielsysteme. Keine Funktionen einführen, die erst PHP 8.5 oder MySQL 8 benötigen. InnoDB, utf8mb4 und Datenbeziehungen erhalten.
- Neue Laufzeitabhängigkeiten benötigen eine ausdrückliche Freigabe samt Begründung, Alternativen, Lizenz, Sicherheits-/Updatefolgen und Versionsvorgaben.
- Kein verpflichtendes Bezahl-API-Abo und kein notwendiges proprietäres Anbieterkonto. Für jede Kernfunktion muss es einen dokumentierten Weg mit kostenloser, frei verfügbarer und selbst betreibbarer Software geben. Hosting und Traffic können Kosten verursachen; Infrastrukturbedarf offen nennen. Optionale kommerzielle Dienste dürfen nicht nachträglich zur Pflicht werden.
- PHP/PDO, lokale Styles und kleine JavaScript-Module beibehalten. Kein unangekündigter Frameworkwechsel, Build-Zwang, CDN, Tracking, Lizenzserver oder verpflichtender externer Dienst.
- Beim klassischen Upload dürfen nur die beiden Einstiegspunkte und öffentlichen Assets per HTTP erreichbar sein; Server-Schutzregeln und Startprüfung erhalten. Konfiguration, Uploads und Laufzeitdaten bleiben privat. Änderungen der Erweiterungsschnittstelle brauchen einen geprüften Kompatibilitätsplan.
- Vor Änderungen an Routing, Konten, Speicherung, Themes oder Plugins die Architektur lesen.

## 4. Lesbarkeit vor Zeilenzahl

Neue PHP-Dateien nutzen strikte Typisierung und möglichst klare Parameter-/Rückgabetypen. PHP: vier Leerzeichen; JavaScript/CSS/JSON: zwei. UTF-8, LF und `.editorconfig` beachten. Konkrete Namen und kleine, klar zuständige Funktionen verwenden. Ungewöhnliche Entscheidungen und Grenzen erklären.

Keine Verschleierung, Minifizierung, unnötigen Einzeiler, duplizierten Geschäftsregeln, Debugausgaben oder toten Code. Weniger Zeilen allein sind kein Qualitätsbeweis. Neue Abfragen und Rechteprüfungen gehören in die Anwendungsschicht. Bestehende kompakte Abschnitte rechtfertigen keinen schwerer lesbaren neuen Code; nur den betroffenen Bereich passend formatieren. Eine automatisierte Formatkontrolle ist noch nicht eingerichtet.

## 5. Sicherheitsregeln nicht aufweichen

- Typen, Längen und erlaubte Werte serverseitig prüfen. SQL-Werte parametrisieren; dynamische Bezeichner nur aus einer erlaubten Liste.
- Ausgabe passend zum jeweiligen Kontext maskieren. Anmeldung, Rolle, Eigentum, Sichtbarkeit sowie Privatchat-Berechtigung sowie Sprachraum-Sitzung an jedem betroffenen Endpunkt prüfen.
- Änderungen benötigen die vorgesehene Anfragemethode und CSRF-Schutz. Sichere Sitzungen, Passwort-Hashes, Sitzungswiderruf und Rate-Limits erhalten.
- Dateityp-, Größen-, Speicher- und Medienzugriffsprüfungen einschließlich Teilabrufen erhalten. Dateiendung oder Browser-MIME allein reichen nicht.
- Mikrofon nur nach bewusster Benutzeraktion und Browserfreigabe nutzen. Aufnahme/Tracks bei Stoppen, Abbruch, Fehler und Navigation freigeben. Den Live-Sprachraum nie heimlich aufzeichnen.
- Keine beliebige Codeausführung, unsicheren Plugin-Uploads, öffentlichen Laufzeitdateien, Geheimnisse im Browser/Protokoll oder unangekündigten externen Verbindungen.

Ausnutzbare Lücken gemäß `SECURITY.md` privat melden. Sicherheitsrelevante Änderungen benötigen ausdrückliche Prüfung durch einen Maintainer und Tests verweigerter Zugriffe.

## 6. Daten und bestehende Installationen schützen

Keine Konfigurationen, Datenbanken, echten Medien, Zugangsdaten oder `storage/`-Inhalte einchecken. Ausschließlich künstliche Testdaten verwenden. Destruktive Tests niemals gegen eine echte Community ausführen.

Schemaänderungen brauchen einen Test der Neuinstallation und einen Upgrade-/Wiederherstellungsplan samt Backup und Grenzen der Rücknahme. MySQL-DDL lässt sich nicht insgesamt als Transaktion zurückrollen. Ein allgemeiner Updater ist noch nicht vorhanden; dessen Aufbau muss vor entsprechenden Änderungen abgestimmt werden. Keine stillen Datenlöschungen oder Installer, die eine belegte Datenbank überschreiben.

## 7. Deutsch, Englisch und Zugänglichkeit pflegen

Beide Sprachdateien gemeinsam pflegen. Keine neuen fest eingebauten Bedien-/Fehlermeldungen; Marken und technische Kennungen sind Ausnahmen. Benutzerinhalte nicht ohne abgestimmte Funktion automatisch übersetzen.

Tastaturbedienung, Fokusmarkierung, Beschriftungen, Kontraste, Smartphone-Ansicht, beide Themes und Hell-/Dunkelmodus erhalten. Zustände nie ausschließlich durch Farbe ausdrücken. Bewegungsreduktion respektieren.

## 8. Nachweise liefern

`bin/check.ps1` ausführen. Verhalten mit sinnvollen Regressionstests absichern, einschließlich Ablehnung und Fehlerfällen. Tatsächliche Umgebung, Befehle und Ergebnisse nennen; nicht mögliche Prüfungen offen aufführen. Keine Tests nur zum Bestätigen des aktuellen Implementierungsdetails.

SQL-Änderungen müssen mit echtem MySQL 5.7 geprüft werden. SQLite genügt dafür nicht. Installer-/Speicheränderungen benötigen Neuinstallations- und Fehlerfalltests. Audioänderungen benötigen passende Geräte-/Browserprüfungen; künstliche Uploads und Vermittlungstests beweisen keinen echten Internetanruf. Sichtbare Änderungen in DE/EN, Hell/Dunkel und auf schmalen/breiten Ansichten prüfen. Reine Textkorrekturen brauchen normalerweise Inhalts-/Linkkontrolle statt erfundener Testläufe.

Das lokale Prüfskript startet keine destruktive HTTP-Testreihe, installiert nichts und richtet noch keine GitHub-CI ein. Es ist keine Produktivfreigabe.

## 9. Herkunft und Autoren respektieren

Nur Material beitragen, das rechtmäßig unter der Projektlizenz weitergegeben werden darf. Kein proprietärer Referenzcode und keine unklaren kopierten Assets/Bibliotheken. Copyright- und Lizenzvermerke erhalten; neue Autoren korrekt nennen. Fremde Beiträge nicht nachträglich als alleinige Arbeit der Projektleitung ausgeben.

Wesentliche KI-Unterstützung im Pull Request offenlegen, jede geänderte Zeile lesen und Verantwortung für Herkunft, Verhalten und Prüfungen übernehmen. Private Prompts oder Kontodaten müssen nicht veröffentlicht werden. KI-Code wird genauso geprüft wie jeder andere Beitrag.

## 10. Review und Freigabe

Rückmeldungen mit gezielten Änderungen beantworten. Unstimmigkeiten sachlich erklären. Gespräche nicht nur zum Entfernen eines Hindernisses als erledigt markieren. Wesentliche neue Änderungen benötigen ein erneutes Review. Nur autorisierte Maintainer führen Änderungen zusammen; niemand genehmigt den eigenen Pull Request.

Sicherheit, Datenbankschema/Migrationen, Anmeldung, Erweiterungs-API und Abhängigkeiten benötigen die ausdrückliche Freigabe der Projektleitung. Fehlt für eine eigene Änderung des Projektleiters ein unabhängiger Reviewer, wartet sie. Ausnahmen für Sicherheitsnotfälle regelt `GOVERNANCE.md`.

Die technische Durchsetzung über GitHub-Regeln folgt erst mit der Einrichtung des Repositorys. Bitte auch den [Verhaltenskodex](../CODE_OF_CONDUCT.md) beachten. Ablehnungen sollen kurz begründet werden; es gibt keinen Anspruch auf eine bestimmte Reaktionszeit.


## Voluntary support / Freiwillige Unterstützung

Business operators and commercial theme/plugin publishers are especially encouraged to [support the project voluntarily](https://ko-fi.com/A0A66XI3A). No payment is required to use the MIT-licensed CMS, contribute, submit extensions or report issues. Donations never buy review or merge priority.

