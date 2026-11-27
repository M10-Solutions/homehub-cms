# HomeHub CMS – Tests und Abnahmen

## Allgemeines Testdrehbuch

### TEST-0001 – Regression (QA-002)

Im Projektverzeichnis auf dem NAS einmalig bzw. nach Änderungen am Dockerfile oder den Composer-Abhängigkeiten:

```sh
docker compose --profile qa run --rm --no-deps --build qa
```

Für Folgeläufe bei bereits gebautem Image genügt:

```sh
docker compose --profile qa run --rm --no-deps qa
```

Die Compose-Datei definiert `homehub-local:latest` als gemeinsames Image für Wiki und QA. Der erste QA-Build erstellt dieses Image; ein bereits laufender Wiki-Container wird nicht neu gestartet. Ein späterer regulärer Compose-Neubau kann das gemeinsame Image übernehmen. Der Build benötigt gegebenenfalls Internetzugang; der Testcontainer selbst hat kein Netzwerk. Composer-Versionen können bei einem Neubau ohne Lockdatei von einem älteren laufenden Container abweichen.

### Isolation
- Kein Mount von `./data`, keine Ports, kein Apache, kein produktives Entrypoint-Skript.
- Read-only Root-Dateisystem und Code-/Test-Mounts, Benutzer www-data.
- Temporäre Fixtures und Testausgabe in `/tmp` (tmpfs); Container wird durch `--rm` entfernt.
- Keine Änderungen an der produktiven Anwendung oder Nutzdaten durch den Testlauf.

### Bericht und Status
Der Preflight prüft PHP >=8.3, proc_open, beschreibbares temporäres Verzeichnis, dom/zip/mbstring/fileinfo sowie Composer-Autoload, CommonMark und Symfony YAML. Im strikten Modus wird bei fehlenden Voraussetzungen kein Test gestartet. Jeder Test erhält Status, Laufzeit und Ausgabe; am Ende folgen Gesamtbilanz und fehlgeschlagene Tests.

- 0: alle Tests bestanden
- 1: mindestens ein Test fehlgeschlagen
- 2: Preflight oder temporäre Logdatei fehlgeschlagen

Lokale Teilprüfung weiterhin mit `php bin/run-regression-tests.php` möglich; fehlende Voraussetzungen werden angezeigt. Nicht-strikte Ergebnisse sind keine vollständige technische Abnahme.


### TEST-0002 – Manuelle Prüfung

Manuelle Stichproben nach betroffenen Änderungen: Erstinstallation auf isolierter Instanz (`bin/prepare-fresh-install-test.sh`), Admin-Anlage und Login, Setup-Marker nach Verlust der Benutzerdatei, Update/Restore; Navigation Desktop/Tablet/Mobil, Theme; Seiten erstellen, bearbeiten, verschieben, löschen, Draft/published/nav und Rollen gemäss SPEC-ADMIN.md / ACL-0001–0004; parallele Tabs, Lease-Ablauf, Revisionskonflikte; Medien, Tags, Suche; Syntax Highlighting, Kopieren und Live-Vorschau; PDF/ODT/ZIP für Einzelpage und Kapitel, Bilder, Tabellen, Code, Deckblatt, Inhaltsverzeichnis und Nummerierung. Historischer detaillierter UAT-Katalog bleibt privat archiviert; keine vollständige Browser-E2E-Suite.

### TEST-0003 – Release-Abnahme

Backup, Update, Versionsanzeige, vollständige strikte Regression, betroffene Browserfunktionen und tatsächliche Exportdownloads prüfen; Ergebnisse und ausdrückliche Betreiberfreigabe dokumentieren. Architektur: QA-0001–0002 und RELEASE-0002.

## Release 1.1.1 – 27.11.2026

### IDEA-25 – abgenommen
- Gast: nur PDF-Button, PDF einer veröffentlichten lesbaren Seite funktioniert; direkte Aufrufe chapter-export.php (alle Formate/Umfänge) und export.php mit ODT ergeben 401.
- Reader, Editor, Admin: nur Export-Button; Dialogtitel, Format zuerst, PDF und Einzelseite als Standard; Abbrechen ohne Download; erneutes Öffnen setzt Standardwerte.
- Alle sechs Format-/Umfangskombinationen herunterladen und öffnen. Dokumentoptionen nur für Kapitel-PDF/ODT sichtbar und aktiv; Wechsel zu ZIP oder Einzelseite übermittelt keine Optionen. Einzelseiten-PDF/ODT behalten bisherige Formatierung.
- Gesperrte Seiten, Entwürfe und nicht lesbare Unterseiten auch bei direkten URLs ausgeschlossen; versteckte veröffentlichte lesbare Unterseiten weiterhin enthalten. Light/Dark, Tastatur und mobile Ansicht prüfen.
- UI-009 prüft Quellstruktur; kein automatisierter Browser- oder HTTP-End-to-End-Nachweis. NAS-QA 39/39 ohne Warnungen erforderlich.

### IDEA-20 / IDEA-21 – abgenommen
Markdown-Speicherung fügt dem eigentlichen Inhalt keine Zeilenenden hinzu; vorhandene Leerzeilen bleiben erhalten. Der generierte Dokumentvorspann bleibt erhalten. Toolbar-Tooltip und zugänglicher Name: «Web-Bild», Funktion unverändert. IDEA-24 bleibt zurückgestellt.
IDEA-25: manuelle Abnahme und Regression am 27.11.2026 laut Betreiber erfolgreich. IDEA-20/21: wiederholt speichern mit und ohne abschliessende sowie führende Leerzeilen, leeren Inhalt und Beschreibung prüfen; Tooltip und Bildfunktion prüfen. UAT-EDT-01 enthält Roundtrip- und Quellprüfungen.

### IDEA-22 / IDEA-23 – abgenommen

Redaktionelle Metadaten im Inline-Editor; Sichtbarkeit mit kontextabhängigen ACL-Feldern. Sortierung bleibt im Dialog Sichtbarkeit. Gemeinsame Speicherung mit vollständiger Revision und gültiger Lease; ACL und Publikation bleiben serverseitig geschützt. IDEA-22/23: manuelle Abnahme und Regression laut Betreiber erfolgreich. IDEA-24 zurückgestellt.


IDEA-22/23: bisherige Funktion laut Betreiber erfolgreich getestet. UX-Nachbesserung IDEA-23: redaktionelle Felder direkt im page-heading, Titeltypografie wie Lesemodus, Beschreibung und Schlagworte darunter ausklappbar (beim Editorstart eingeklappt). Gemeinsame Speicherung und ACL unverändert. Titelgestaltung und erneut ausklappbarer Bereich laut Betreiber erfolgreich abgenommen.

### Page-Heading – UX-Bereinigung
Die statische Eyebrow «HOMEHUB · WISSENSBASIS» entfällt im Lese- und Bearbeitungsmodus ersatzlos. Titel und übrige Funktionen bleiben unverändert. Visuelle Betreiberabnahme erfolgreich; UAT-EDT-01 prüft die Abwesenheit des Markups im page-heading. Die CSS-Abwesenheitsprüfung entfällt nach einem Fehlschlag auf dem NAS.

Die redundante Bearbeiten-Pill wurde aus HTML, CSS und JavaScript entfernt. UAT-EDT-01 prüft ihre Abwesenheit; visuelle Betreiberabnahme erfolgreich. Vollständige NAS-Regression nach Testkorrektur laut Betreiber erfolgreich.

### Konsolidierung vor Versionierung
CSS- und Dokumentationsbereinigung: visuelle Abnahme und vollständige NAS-Regression am 27.11.2026 laut Betreiber erfolgreich.

### Release 1.1.1 – Betreiberfreigabe
Anwendung 1.1.1-20261127. Finale visuelle NAS-Prüfung und vollständige Regression nach Versionspflege laut Betreiber erfolgreich. Endgültige Betreiberfreigabe am 27.11.2026 ausdrücklich erteilt. Kein separater Testlog oder Einzelprotokoll der Backup-/Update-Prüfung vorliegend. GitHub-Commit und Release am 27.11.2026 laut Betreiber erfolgreich; CI und Audit nicht separat bestätigt.
Lokale Teilprüfung nach Versionspflege: VERSION-001 auf das neue dreiteilige Versionsformat angepasst und bestanden; 33/39 Regressionen erfolgreich, sechs bekannte Fehlschläge wegen fehlendem Composer-Autoload bzw. ZipArchive. Keine finale NAS-Freigabe in der lokalen Umgebung; anschliessende Betreiberfreigabe siehe oben.

## Release 1.1.0 – 26.11.2026

### MED-SEC – vollständig abgenommen
HTTP-Tests: anonym/Reader/Editor/Admin, eigene und geerbte ACL, Draft, nav=false, manipulierte ID/Slug, fehlender Kontext, falsche Referenz, Codeblock, Legacy-Dateilink, Logout/Rechteentzug, Cache, Picker/Vorschau, PDF/ODT/ZIP und direkter Dateipfad. Betreiber bestätigt Funktionstests und 37/37 Regressionen. EXP-SEC-01–03: neuer Test export-security.php prüft Bildressourcen und ZIP-Auswahl; vollständige Konverterisolation nicht nachgewiesen. Betreiber bestätigt 38/38 ohne PHP-Warnungen und sämtliche manuellen Abnahmekriterien.

Lokaler EXP-SEC-Lauf: PHP 8.4.24, 32/38 erfolgreich; page-link-id, export-003-zip, security-metadata, media-sec und export-security mangels Composer-Autoload, export-002-odt mangels ZipArchive nicht ausführbar. PHP-Syntaxprüfung erfolgreich. Keine dynamische Konverter- oder HTTP-Abnahme.

### TRASH-001
Neuer Test trash-001.php: Einzel-/Teilbaumlöschung, Wiederherstellung, Pfadkonflikt, ACL-Ablehnung, Lease, endgültiges Löschen und Leeren. Lokal PHP 8.4.24: 33/39 bestanden; sechs bestehende umgebungsbedingte Fehlschläge (Composer/ZipArchive). Vorgesehene NAS-Prüfpunkte: Admin-only, CSRF, ID-Kollision, fehlendes Elternkapitel, geschütztes Kapitel, Navigation/Suche/Export/Medien, aktuelle ACL und responsive UI prüfen.

UI-Nachbesserung: PHP-Syntax und JavaScript-Syntax erfolgreich, trash-001.php bestanden; vollständige lokale Regression 33/39 (sechs bekannte fehlende Abhängigkeiten). NAS-Prüfpunkte: Checkbox, erzwungene Teilbaumlöschung, Auswahl/Alle, Toolbar, Abbruch/Bestätigung, Mehrfachaktionen mit Konflikten, Light/Dark und responsive Darstellung prüfen.

Betreiberabnahme TRASH-001 einschliesslich UI-Nachbesserung am 25.11.2026 bestätigt: alle manuellen Abnahmekriterien erfüllt, 39/39 Regressionstests bestanden, keine PHP-Warnungen. Keine erneute unabhängige Prüfung bei der Dokumentationspflege.

### ODT-Tabellengestaltung (abgenommen)
- Einzel- und Kapitelexport mit Tabellen: graue Rahmen, vollflächiger Tabellenkopf, unveränderte Inhalte und Bildskalierung prüfen. PDF/ZIP unverändert.
- EXPORT-002 prüft zusätzlich die generierten Zellstile anhand eines echten Pandoc-Exports.

Betreiberabnahme am 25.11.2026: alle manuellen Kriterien erfüllt, 39/39 Regressionstests bestanden, keine PHP-Warnungen. Release 1.1.0: erneute strikte NAS-QA nach Versionspflege am 26.11.2026 laut Betreiber erfolgreich: alle Abnahmekriterien erfüllt, 39/39 Regressionstests bestanden, keine PHP-Warnungen.

Lokale Release-Teilprüfung am 26.11.2026 nach Versionspflege: 33/39 erfolgreich; sechs bekannte umgebungsbedingte Fehlschläge (fehlender Composer-Autoload bzw. ZipArchive). PHP-Syntax der Versionskonfiguration erfolgreich. Keine vollständige Release-Abnahme in der lokalen Prüfumgebung; anschliessende NAS-Betreiberabnahme erfolgreich (siehe oben).


GitHub Actions und Publikationskriterien laut Betreiber erfolgreich.

## Release 1.0.1 – 25.11.2026

Wartung QA-002 (25.11.2026, nach Release 1.0.0): Cleanup in `tests/user-001.php` und `tests/cleanup-001.php` erfasst mit `scandir()` auch versteckte Dateien wie `.homehub-initialized`. Beide lokalen Einzeltests unter PHP 8.4.24 ohne PHP-Warnungen und mit vollständig entfernten temporären Testverzeichnissen; CommonMark-Teilprüfung lokal mangels Composer-Autoload nicht ausgeführt. Strikte NAS-Regression (35/35, warnungsfrei) und Betreiberabnahme am 25.11.2026 bestätigt. Keine Änderung am Anwendungscode.

UI-013: lokale Syntax- und Regressionstests; NAS-Abnahme am 25.11.2026 laut Betreiber erfolgreich: alle manuellen Abnahmekriterien erfüllt, 36/36 Regressionen ohne PHP-Warnungen. Manuell: Header, Navigation offen/geschlossen (auch nach Reload), mobile Navigation, Light/Dark, Admin-Logout mit öffentlicher/geschützter Startseite, PDF/ODT/ZIP-Export, Seitenerstellung mit bestehendem Pfad (auch Draft), neuem Pfad, fehlender Kapitelberechtigung und Konflikt beim Absenden; Dialog-Icons einschliesslich dynamischer Titel. Nativer Download meldet den Abschluss nicht an JavaScript.

Lokaler UI-013-Lauf: PHP 8.4.24, 33/36 Tests erfolgreich; page-link-id und security-metadata mangels Composer-Autoload, export-002-odt mangels ZipArchive nicht ausführbar. UI-013 Icon-Teilprüfung erfolgreich; dynamische Pfadprüfung lokal mangels CommonMark ausgelassen. PHP-/JS-Syntax der geänderten Dateien geprüft. Keine Browser- oder NAS-Abnahme in der lokalen Testumgebung durchgeführt. Die anschliessende Betreiberabnahme war erfolgreich (siehe oben). Die abschliessende Regression nach Versionspflege für 1.0.1 wurde vom Betreiber bestätigt.


GitHub CI und Publikation laut Betreiber bestätigt.

## Release 1.0.0 – 25.11.2026

Historische vollständige Regression für 1.0.0: 35/35 (NAS und CI #1). Schritt 1 und 2 laut Betreiber erfolgreich getestet; keine separaten neuen Logs. Abschliessende GitHub-QA und Repository-Kontrolle am 25.11.2026 laut Maintainer erfolgreich; kein separater Abschlusslog vorliegend. Lokale Einzeltests: `php tests/<datei>.php`.

### Sicherheits- und Lizenzgrenzen
Zwei Trivy-Scans am 25.11.2026 laut Betreiber: 171 Systembefunde (162 HIGH, 9 CRITICAL), keine Fixed Version ausgewiesen; Composer 0 HIGH/CRITICAL. Keine Einzelanalyse oder unabhängige Wiederholung. Kein Null-CVE-Freigabekriterium. Kein vollständiger Penetrationstest. Medien-ACL Variante A akzeptiert; keine vertraulichen Medien, kein direkter Internetbetrieb.

Spätere Exportprüfung (siehe Release 1.1.0): EXP-SEC ersetzt die bisherige Regex-Bildauswertung durch sicheres Wiki-HTML und validierte lokale Rasterbilder. Keine explizite Netzwerk-/Dateisystem-Sandbox des Konverters; nur vertrauenswürdige schreibberechtigte Benutzer und Medien zulassen. Betreiberabnahme bestätigt; vollständige Konverterisolation nicht nachgewiesen.

Prism-Quellen und Lizenz sowie ODT-Herkunft: THIRD-PARTY-NOTICES.md. Kein zusätzlicher unabhängiger Bytevergleich oder Composer-Lizenztextabgleich beauftragt. Originalhinweise erhalten.



### Frühere Prüfkriterien und Nachweise

- **Abnahme:** Anmeldung/Abmeldung, aktives/inaktives Konto, Profiländerung, stabile ID und letzter-Admin-Schutz. UAT-002f: Browser-Setup auf isolierter frischer NAS-Instanz, Anmeldung und persistente Inhalte, Sicherheitsfälle S1–S6 (Setup nach Einrichtung gesperrt, ungültige Passwörter, Passwortbestätigung, parallele Versuche, fehlende Benutzerdatei, bestehende Installation) sowie 30/30 QA-Regressionen laut Betreiber am 23.11.2026 bestanden. UAT-INS-03 war am 22.11.2026 bestanden; Release-Update und Restore am 24.11.2026 laut Betreiber bestanden.
- **Abnahme:** Admin, Wiki, mobile Darstellung und alle Dialoge visuell prüfen.
- Feature Freeze auf `0.9-20261122`; UAT-Konzept und Testkatalog historisch geführt. UAT-001 durchgeführt (58/59); UAT-002a–f und UI-012 danach abgenommen, zuletzt 35/35 NAS-Regressionen. 
- Status: abgenommen am 24.11.2026. Alle 11 NAS-Abnahmekriterien erfüllt, vollständige Regression 34/34 laut Betreiber. Regression: `tests/uat-002b.php`.
- Betreiber bestätigt Desktop-Navigation inklusive Breitenverstellung, Tablet-Hochformat, kompakten Smartphone-Header ohne horizontale Überläufe, Hamburger/X/Aussenklick, automatisches Schliessen bei Seitenauswahl, Filter/Kapitel/Kontextmenüs, Benutzerprofil, Verwaltung, Light-/Dark-Mode, Orientierungswechsel und Erhalt der Desktop-Einstellungen sowie 34/34 Regressionstests.
- Neuer Integrationstest `security-metadata.php`; lokale Regression 32/35, drei Tests wegen fehlendem Composer bzw. ZipArchive nicht ausführbar. NAS-Abnahme am 24.11.2026 laut Betreiber: 35/35 bestanden.
- **Abnahme:** Mehrfachupload, Auswahl/Filter, Move/Rename und Delete; ID-Link nach Move unverändert erreichbar; Legacy-Links separat prüfen.
- **Abnahme:** Ein Tag auf zwei Seiten, zentral umbenennen, beide zeigen neuen Namen ohne Änderung der Markdown-Dateien; Suche mit abweichender Schreibweise, ZIP enthält Namen; Delete bei Verwendung blockiert.
- **Abnahme:** Leere und komplexere Seite korrekt anzeigen, bearbeiten und als Markdown öffnen; breites Bild bleibt innerhalb der Fläche.
- **Abnahme:** Freigegebene und gesperrte Seite samt Unterseite mit jeder Rolle und ohne Login prüfen; Navigation, direkte Links, Suche, Picker und Export vergleichen.
- **Abnahme:** Seite erstellen, Inhalt/Metadaten ändern, Rollen-/Nutzerfreigabe prüfen, umbenennen/verschieben und löschen; Konflikt und unveränderte ID kontrollieren.
- **Abnahme:** ID-Link bleibt nach Move gültig; unbekannte ID/gesperrtes Ziel nicht auflösen; zweiter Migrationscheck meldet keine bereits migrierten Ziele.
- **Abnahme:** Titel-, Text-, Tag- und Mehrwortsuche, leere Suche, keine Treffer, geschützte/unsichtbare Seiten, aktuelle Tag-Namen, Link nach Move.
- **Abnahme:** Einzelseite/Kapitel in allen angebotenen Formaten exportieren; Berechtigungen, Bilder, relative Referenzen und Tag-Namen prüfen.
- **Abnahme:** Version in Config ändern, Wiki neu laden und Anzeige rechts im Footer prüfen; auch mobile Darstellung und Suchergebnisse prüfen.
- Betreiberabnahme UAT-002d und UI-012 am 24.11.2026 erfolgreich; vollständige NAS-Regression 33/33 bestanden.

1. Alle vier Kombinationen von `published`/`nav` mit Admin, berechtigtem Editor, gesperrtem Editor, Reader und anonym prüfen; Navigation, direkte öffentliche Route, Draft-Route, Suche, Picker und Export vergleichen.
2. Editor erstellt unter berechtigtem bestehendem Kapitel einen Draft; Veröffentlichung, Rückzug, Umbenennung, Verschiebung und Löschung sind dort möglich; in gesperrtem Kapitel nicht.
3. Verschiebung in ein nicht berechtigtes Zielkapitel scheitert serverseitig ohne Dateiveränderung.
4. Manipulierte Requests ändern keine ACL-Felder durch Editoren; Reader kann keine Mutationen ausführen.
5. Veröffentlichung oder `nav`-Änderung allein umgeht keine eigene oder geerbte ACL. Medienzugriff gemäss MED-SEC.

Vor Release 1.0.0: UAT-002a–f und UI-012 abgenommen; NAS 34/34 auf 0.13-20261123, finale NAS-QA 35/35 am 24.11.2026. CI #1 35/35 und Composer-Audit ohne Meldungen; nach Checkout-Update CI ohne Warnungen laut Betreiber. Keine zusätzliche ausdrückliche Einzelabnahme der Export-Risikogrenzen dokumentiert.
