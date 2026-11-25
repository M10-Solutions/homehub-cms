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

Letzte explizit bestätigte vollständige Regression: 35/35 (NAS und CI #1). Schritt 1 und 2 laut Betreiber erfolgreich getestet; keine separaten neuen Logs. Abschliessende GitHub-QA und Repository-Kontrolle am 25.11.2026 laut Maintainer erfolgreich; kein separater Abschlusslog vorliegend. Lokale Einzeltests: `php tests/<datei>.php`.

Manuelle Stichproben nach betroffenen Änderungen: Erstinstallation auf isolierter Instanz (`bin/prepare-fresh-install-test.sh`), Admin-Anlage und Login, Setup-Marker nach Verlust der Benutzerdatei, Update/Restore; Navigation Desktop/Tablet/Mobil, Theme; Seiten erstellen, bearbeiten, verschieben, löschen, Draft/published/nav und Rollen gemäss SPEC/ACL-001; parallele Tabs, Lease-Ablauf, Revisionskonflikte; Medien, Tags, Suche; Syntax Highlighting, Kopieren und Live-Vorschau; PDF/ODT/ZIP für Einzelpage und Kapitel, Bilder, Tabellen, Code, Deckblatt, Inhaltsverzeichnis und Nummerierung. Historischer detaillierter UAT-Katalog bleibt privat archiviert; keine vollständige Browser-E2E-Suite.

## Sicherheits- und Lizenzgrenzen
Zwei Trivy-Scans am 25.11.2026 laut Betreiber: 171 Systembefunde (162 HIGH, 9 CRITICAL), keine Fixed Version ausgewiesen; Composer 0 HIGH/CRITICAL. Keine Einzelanalyse oder unabhängige Wiederholung. Kein Null-CVE-Freigabekriterium. Kein vollständiger Penetrationstest. Medien-ACL Variante A akzeptiert; keine vertraulichen Medien, kein direkter Internetbetrieb.

Gezielte statische Exportprüfung: DocumentExporter deaktiviert raw_html für Pandoc und ersetzt direkt erkannte Markdown-Bilder durch validierte lokale Medienpfade oder Alttext. Die Regex erfasst nicht alle Markdown-Bildformen (z.B. Referenzdefinitionen); der Konverter hat keine explizite Netzwerkisolation. Daher ist eine vollständige Unterbindung externer oder lokaler Ressourcen nicht nachgewiesen. Nur vertrauenswürdige schreibberechtigte Benutzer und Medien zulassen; bei nicht vertrauenswürdigen Editoren Exportabsicherung vor Einsatz erforderlich. Keine dynamische Exploitprüfung durchgeführt. PDF bleibt unverändert.

Prism-Quellen und Lizenz sowie ODT-Herkunft: THIRD-PARTY-NOTICES.md. Kein zusätzlicher unabhängiger Bytevergleich oder Composer-Lizenztextabgleich beauftragt. Originalhinweise erhalten.


UI-013: lokale Syntax- und Regressionstests; NAS-Abnahme am 25.11.2026 laut Betreiber erfolgreich: alle manuellen Abnahmekriterien erfüllt, 36/36 Regressionen ohne PHP-Warnungen. Manuell: Header, Navigation offen/geschlossen (auch nach Reload), mobile Navigation, Light/Dark, Admin-Logout mit öffentlicher/geschützter Startseite, PDF/ODT/ZIP-Export, Seitenerstellung mit bestehendem Pfad (auch Draft), neuem Pfad, fehlender Kapitelberechtigung und Konflikt beim Absenden; Dialog-Icons einschliesslich dynamischer Titel. Nativer Download meldet den Abschluss nicht an JavaScript.

Lokaler UI-013-Lauf: PHP 8.4.24, 33/36 Tests erfolgreich; page-link-id und security-metadata mangels Composer-Autoload, export-002-odt mangels ZipArchive nicht ausführbar. UI-013 Icon-Teilprüfung erfolgreich; dynamische Pfadprüfung lokal mangels CommonMark ausgelassen. PHP-/JS-Syntax der geänderten Dateien geprüft. Keine Browser- oder NAS-Abnahme in der lokalen Testumgebung durchgeführt. Die anschliessende Betreiberabnahme war erfolgreich (siehe oben). Die abschliessende Regression nach Versionspflege für 1.0.1 steht noch aus.
