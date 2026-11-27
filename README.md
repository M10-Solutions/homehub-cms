# HomeHub CMS

[![HomeHub QA](https://github.com/M10-Solutions/homehub-cms/actions/workflows/qa.yml/badge.svg?branch=main)](https://github.com/M10-Solutions/homehub-cms/actions/workflows/qa.yml)

**Dein Zuhause auf einen Blick.**

Ein schlankes, dateibasiertes CMS für private Intranets, Haushalte und kleine Gemeinschaften. Inhalte werden als Markdown-Dateien gespeichert; eine Datenbank ist nicht erforderlich.

Release: `1.2.0` · Anwendungsversion: `1.2.0-20261127` · Lizenz: GPL-3.0-or-later

![HomeHub CMS mit Wiki-Navigation und integriertem Benutzerhandbuch](docs/images/homehub-screenshot.jpg)

## Funktionen

- Hierarchische Wiki-Navigation, Volltextsuche und Schlagwörter
- Markdown-Editor mit Vorschau, Split-Ansicht und Medienverwaltung
- Stabile Seiten- und Medienreferenzen, Rollen und Kapitelberechtigungen
- Bearbeitungssperren und Revisionsprüfung für mehrere Benutzer
- Responsive Oberfläche mit Light- und Dark-Mode
- PDF-, ODT- und Kapitel-ZIP-Export
- Papierkorb und rekursives Löschen mit administrativer Mehrfachauswahl und Wiederherstellung
- Typografisch optimierter ODT-Export mit dezent gestalteten Tabellen
- Browserbasierte Ersteinrichtung, Beispielseiten und integriertes Benutzerhandbuch

## Installation

Voraussetzungen: Docker mit Compose v2, ein unterstützter Linux-Container-Host und Netzwerkzugriff für den Image-Build. Das Projekt in einem separaten Verzeichnis entpacken bzw. klonen. Optional `.env.example` nach `.env` kopieren und UID/GID, Port und Zeitzone anpassen. Für einen lokalen Ersttest `HOMEHUB_BIND_ADDRESS=127.0.0.1` setzen.

```sh
docker compose config
docker compose up -d --build
```

Auf dem Docker-Host `http://localhost:8011/` öffnen (bei abweichendem Port anpassen). Bei einer NAS-Installation die Adresse des Hosts im vertrauenswürdigen Netz verwenden. Beim ersten Aufruf ein Administratorkonto mit einem individuellen Passwort von mindestens zwölf Zeichen erstellen. Das Kapitel HomeHub Benutzerhandbuch erklärt die Bedienung.

## Sicherheit und Daten

HomeHub ist **nicht für direkten öffentlichen Internetzugriff** freigegeben. Firewall/VPN und gegebenenfalls HTTPS mit Zugriffsbeschränkung verwenden. Medienzugriff gemäss MED-SEC; Editoren und Admins sehen alle Medien. Globale Medienrechte und Export-Risikogrenzen beachten. Die Ersteinrichtung nur in kontrollierter Umgebung durchführen. Weitere Grenzen: [SECURITY.md](SECURITY.md).

Produktive Daten unter `data/` regelmässig sichern. Vollarchive niemals über produktive Inhalte entpacken. Entwicklungsrepository und produktive Installation strikt trennen: Beispielseiten unter `data/content/` sind absichtlich versioniert.

## Entwicklung und Dokumentation

```sh
docker compose --profile qa run --rm --no-deps --build qa
```

- [Installation und Betrieb](docs/OPERATIONS.md)
- [Content-/Editor-Spezifikation](docs/SPEC-EDITOR.md), [Admin-Spezifikation](docs/SPEC-ADMIN.md) und [Architektur](docs/ARCHITECTURE.md)
- [Mitwirken](CONTRIBUTING.md), [Änderungen](CHANGELOG.md), [Drittkomponenten](THIRD-PARTY-NOTICES.md)

## Lizenz

Copyright (C) 2026 M10-Solutions. HomeHub-eigener Code steht unter der GNU General Public License Version 3 oder einer späteren Version ([LICENSE](LICENSE)). Drittkomponenten behalten ihre jeweiligen Lizenzen. Keine Gewährleistung gemäss Lizenzbedingungen.

## Betriebsverantwortung
Die Docker-Konfiguration ist eine Referenzinstallation, keine gehärtete Betriebsplattform. Installation, Absicherung, Aktualisierung und Überwachung der individuellen Docker-/Host-/Netzwerkumgebung liegen beim Betreiber. HomeHub verantwortet den eigenen Anwendungscode und die bereitgestellte Referenzkonfiguration; bekannte anwendungsrelevante Risiken werden berücksichtigt. Siehe [SECURITY.md](SECURITY.md).
