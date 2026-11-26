# QA-002 – Isolierte Regressionstests

Im Projektverzeichnis auf dem NAS einmalig bzw. nach Änderungen am Dockerfile oder den Composer-Abhängigkeiten:

```sh
docker compose --profile qa run --rm --no-deps --build qa
```

Für Folgeläufe bei bereits gebautem Image genügt:

```sh
docker compose --profile qa run --rm --no-deps qa
```

Die Compose-Datei definiert `homehub-local:latest` als gemeinsames Image für Wiki und QA. Der erste QA-Build erstellt dieses Image; ein bereits laufender Wiki-Container wird nicht neu gestartet. Ein späterer regulärer Compose-Neubau kann das gemeinsame Image übernehmen. Der Build benötigt gegebenenfalls Internetzugang; der Testcontainer selbst hat kein Netzwerk. Composer-Versionen können bei einem Neubau ohne Lockdatei von einem älteren laufenden Container abweichen.

## Isolation
- Kein Mount von `./data`, keine Ports, kein Apache, kein produktives Entrypoint-Skript.
- Read-only Root-Dateisystem und Code-/Test-Mounts, Benutzer www-data.
- Temporäre Fixtures und Testausgabe in `/tmp` (tmpfs); Container wird durch `--rm` entfernt.
- Keine Änderungen an der produktiven Anwendung oder Nutzdaten durch den Testlauf.

## Bericht und Status
Der Preflight prüft PHP >=8.3, proc_open, beschreibbares temporäres Verzeichnis, dom/zip/mbstring/fileinfo sowie Composer-Autoload, CommonMark und Symfony YAML. Im strikten Modus wird bei fehlenden Voraussetzungen kein Test gestartet. Jeder Test erhält Status, Laufzeit und Ausgabe; am Ende folgen Gesamtbilanz und fehlgeschlagene Tests.

- 0: alle Tests bestanden
- 1: mindestens ein Test fehlgeschlagen
- 2: Preflight oder temporäre Logdatei fehlgeschlagen

Lokale Teilprüfung weiterhin mit `php bin/run-regression-tests.php` möglich; fehlende Voraussetzungen werden angezeigt. Nicht-strikte Ergebnisse sind keine vollständige technische Abnahme.


## Aktuelle Abnahme und manuelle Regression

Wartung QA-002 (25.11.2026, nach Release 1.0.0): Cleanup in `tests/user-001.php` und `tests/cleanup-001.php` erfasst mit `scandir()` auch versteckte Dateien wie `.homehub-initialized`. Beide lokalen Einzeltests unter PHP 8.4.24 ohne PHP-Warnungen und mit vollständig entfernten temporären Testverzeichnissen; CommonMark-Teilprüfung lokal mangels Composer-Autoload nicht ausgeführt. Strikte NAS-Regression (35/35, warnungsfrei) und Betreiberabnahme am 25.11.2026 bestätigt. Keine Änderung am Anwendungscode.

Historische vollständige Regression für 1.0.0: 35/35 (NAS und CI #1). Schritt 1 und 2 laut Betreiber erfolgreich getestet; keine separaten neuen Logs. Abschliessende GitHub-QA und Repository-Kontrolle am 25.11.2026 laut Maintainer erfolgreich; kein separater Abschlusslog vorliegend. Lokale Einzeltests: `php tests/<datei>.php`.

Manuelle Stichproben nach betroffenen Änderungen: Erstinstallation auf isolierter Instanz (`bin/prepare-fresh-install-test.sh`), Admin-Anlage und Login, Setup-Marker nach Verlust der Benutzerdatei, Update/Restore; Navigation Desktop/Tablet/Mobil, Theme; Seiten erstellen, bearbeiten, verschieben, löschen, Draft/published/nav und Rollen gemäss SPEC/ACL-001; parallele Tabs, Lease-Ablauf, Revisionskonflikte; Medien, Tags, Suche; Syntax Highlighting, Kopieren und Live-Vorschau; PDF/ODT/ZIP für Einzelpage und Kapitel, Bilder, Tabellen, Code, Deckblatt, Inhaltsverzeichnis und Nummerierung. Historischer detaillierter UAT-Katalog bleibt privat archiviert; keine vollständige Browser-E2E-Suite.

## Sicherheits- und Lizenzgrenzen
Zwei Trivy-Scans am 25.11.2026 laut Betreiber: 171 Systembefunde (162 HIGH, 9 CRITICAL), keine Fixed Version ausgewiesen; Composer 0 HIGH/CRITICAL. Keine Einzelanalyse oder unabhängige Wiederholung. Kein Null-CVE-Freigabekriterium. Kein vollständiger Penetrationstest. Medien-ACL Variante A akzeptiert; keine vertraulichen Medien, kein direkter Internetbetrieb.

Gezielte Exportprüfung: EXP-SEC ersetzt die bisherige Regex-Bildauswertung durch sicheres Wiki-HTML und validierte lokale Rasterbilder. Keine explizite Netzwerk-/Dateisystem-Sandbox des Konverters; nur vertrauenswürdige schreibberechtigte Benutzer und Medien zulassen. Betreiberabnahme bestätigt; vollständige Konverterisolation nicht nachgewiesen.

Prism-Quellen und Lizenz sowie ODT-Herkunft: THIRD-PARTY-NOTICES.md. Kein zusätzlicher unabhängiger Bytevergleich oder Composer-Lizenztextabgleich beauftragt. Originalhinweise erhalten.


UI-013: lokale Syntax- und Regressionstests; NAS-Abnahme am 25.11.2026 laut Betreiber erfolgreich: alle manuellen Abnahmekriterien erfüllt, 36/36 Regressionen ohne PHP-Warnungen. Manuell: Header, Navigation offen/geschlossen (auch nach Reload), mobile Navigation, Light/Dark, Admin-Logout mit öffentlicher/geschützter Startseite, PDF/ODT/ZIP-Export, Seitenerstellung mit bestehendem Pfad (auch Draft), neuem Pfad, fehlender Kapitelberechtigung und Konflikt beim Absenden; Dialog-Icons einschliesslich dynamischer Titel. Nativer Download meldet den Abschluss nicht an JavaScript.

Lokaler UI-013-Lauf: PHP 8.4.24, 33/36 Tests erfolgreich; page-link-id und security-metadata mangels Composer-Autoload, export-002-odt mangels ZipArchive nicht ausführbar. UI-013 Icon-Teilprüfung erfolgreich; dynamische Pfadprüfung lokal mangels CommonMark ausgelassen. PHP-/JS-Syntax der geänderten Dateien geprüft. Keine Browser- oder NAS-Abnahme in der lokalen Testumgebung durchgeführt. Die anschliessende Betreiberabnahme war erfolgreich (siehe oben). Die abschliessende Regression nach Versionspflege für 1.0.1 wurde vom Betreiber bestätigt.

## MED-SEC – vollständig abgenommen
HTTP-Tests: anonym/Reader/Editor/Admin, eigene und geerbte ACL, Draft, nav=false, manipulierte ID/Slug, fehlender Kontext, falsche Referenz, Codeblock, Legacy-Dateilink, Logout/Rechteentzug, Cache, Picker/Vorschau, PDF/ODT/ZIP und direkter Dateipfad. Betreiber bestätigt Funktionstests und 37/37 Regressionen. EXP-SEC-01–03: neuer Test export-security.php prüft Bildressourcen und ZIP-Auswahl; vollständige Konverterisolation nicht nachgewiesen. Betreiber bestätigt 38/38 ohne PHP-Warnungen und sämtliche manuellen Abnahmekriterien.

Lokaler EXP-SEC-Lauf: PHP 8.4.24, 32/38 erfolgreich; page-link-id, export-003-zip, security-metadata, media-sec und export-security mangels Composer-Autoload, export-002-odt mangels ZipArchive nicht ausführbar. PHP-Syntaxprüfung erfolgreich. Keine dynamische Konverter- oder HTTP-Abnahme.

## TRASH-001
Neuer Test trash-001.php: Einzel-/Teilbaumlöschung, Wiederherstellung, Pfadkonflikt, ACL-Ablehnung, Lease, endgültiges Löschen und Leeren. Lokal PHP 8.4.24: 33/39 bestanden; sechs bestehende umgebungsbedingte Fehlschläge (Composer/ZipArchive). Vorgesehene NAS-Prüfpunkte: Admin-only, CSRF, ID-Kollision, fehlendes Elternkapitel, geschütztes Kapitel, Navigation/Suche/Export/Medien, aktuelle ACL und responsive UI prüfen.

UI-Nachbesserung: PHP-Syntax und JavaScript-Syntax erfolgreich, trash-001.php bestanden; vollständige lokale Regression 33/39 (sechs bekannte fehlende Abhängigkeiten). NAS-Prüfpunkte: Checkbox, erzwungene Teilbaumlöschung, Auswahl/Alle, Toolbar, Abbruch/Bestätigung, Mehrfachaktionen mit Konflikten, Light/Dark und responsive Darstellung prüfen.

Betreiberabnahme TRASH-001 einschliesslich UI-Nachbesserung am 25.11.2026 bestätigt: alle manuellen Abnahmekriterien erfüllt, 39/39 Regressionstests bestanden, keine PHP-Warnungen. Keine erneute unabhängige Prüfung bei der Dokumentationspflege.

### ODT-Tabellengestaltung (abgenommen)
- Einzel- und Kapitelexport mit Tabellen: graue Rahmen, vollflächiger Tabellenkopf, unveränderte Inhalte und Bildskalierung prüfen. PDF/ZIP unverändert.
- EXPORT-002 prüft zusätzlich die generierten Zellstile anhand eines echten Pandoc-Exports.

Betreiberabnahme am 25.11.2026: alle manuellen Kriterien erfüllt, 39/39 Regressionstests bestanden, keine PHP-Warnungen. Release 1.1.0: erneute strikte NAS-QA nach Versionspflege am 26.11.2026 laut Betreiber erfolgreich: alle Abnahmekriterien erfüllt, 39/39 Regressionstests bestanden, keine PHP-Warnungen.

Lokale Release-Teilprüfung am 26.11.2026 nach Versionspflege: 33/39 erfolgreich; sechs bekannte umgebungsbedingte Fehlschläge (fehlender Composer-Autoload bzw. ZipArchive). PHP-Syntax der Versionskonfiguration erfolgreich. Keine vollständige Release-Abnahme in der lokalen Prüfumgebung; anschliessende NAS-Betreiberabnahme erfolgreich (siehe oben).
