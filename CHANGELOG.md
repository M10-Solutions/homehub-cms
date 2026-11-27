# Changelog

## 1.1.1 – UX-Optimierungen und Wartung (27.11.2026)

Anwendungsversion `1.1.1-20261127`. GitHub-Commit und Release am 27.11.2026 laut Betreiber erfolgreich; Tag `v1.1.1`, Paket `homehub-1.1.1-final.zip`.

- Nach Veröffentlichung: SDD-Dokumentationskonsolidierung vorbereitet (ohne Versionsänderung).

- IDEA-20/21: Markdown-Leerzeilen erhalten; Web-Bild-Tooltip präzisiert.
- IDEA-22/23: kontextabhängige ACL-Auswahl, redaktionelle Metadaten im Seitenkopf, ausklappbare Beschreibung und Schlagworte, gemeinsame Speicherung mit Revisions- und Lease-Prüfung.
- IDEA-25: gemeinsamer Exportdialog für angemeldete Benutzer, PDF-Einzelseitenexport für Gäste.
- Redundante Eyebrow und Bearbeiten-Pill entfernt; CSS, SDD und Beispielhandbuch konsolidiert.
- Keine neuen Abhängigkeiten oder erforderlichen Datenmigrationen. IDEA-24 zurückgestellt.

## 1.1.0 – Papierkorb, Sicherheit und ODT-Gestaltung (26.11.2026)

Anwendungsversion `1.1-20261126`. Veröffentlichung am 26.11.2026 laut Betreiber.

- ODT: typografisch überarbeitete Referenzvorlage und nachträgliche Formatierung der Pandoc-Tabellenzellen.

- MED-SEC / EXP-SEC: seitengebundener Medienzugriff und einheitliche Referenzprüfung für Exporte.
- TRASH-001: Papierkorb, rekursives Löschen, Admin-Toolbar und Mehrfachaktionen.
- SDD: Aufteilung in SPEC-EDITOR.md und SPEC-ADMIN.md.
- Keine neuen Abhängigkeiten oder erforderlichen Datenmigrationen. IDEA-19 bleibt im Backlog.

## 1.0.1 – Small UX improvements and bugfixes (25.11.2026)

Anwendungsversion `1.0-20261125`.

- QA-002: temporäre Testverzeichnisse einschliesslich versteckter Dateien bereinigen.
- UI-013: neuer Header-Untertitel, ausgerichteter Navigationstoggle und einheitlicher Admin-Logout mit Weiterleitung zur Startseite.
- Kapitel-Exportdialog schliesst bei gültigem Exportstart; Live-Pfadkonfliktprüfung bei neuen Wiki-Seiten mit Fehleranzeige im Dialog.
- Einheitliche Icons in modalen Dialogheadern.
- README, Screenshot und SDD-Dokumentation aktualisiert.
- Keine neuen Abhängigkeiten oder Datenmigrationen.

## 1.0.0 – erster öffentlicher Release (25.11.2026)

Anwendungsversion `1.0-20261124`. Öffentliche Veröffentlichung am 25.11.2026 laut Betreiber.

- Rollenbezeichnungen member/visitor durch editor/reader ersetzt; Legacy-Kompatibilität erhalten. Redaktionelle Entwurfsnavigation UAT-002c durch UAT-002c.1/.2 abgelöst.
- Markdown-Wiki, Editor, Medien, Suche, Schlagwörter und Kapitelberechtigungen.
- Mehrbenutzerbetrieb mit Bearbeitungssperren, responsive Navigation, Light-/Dark-Mode.
- PDF-, ODT- und ZIP-Export, browserbasierte Ersteinrichtung.
- Restriktive Behandlung ungültiger Seitenmetadaten.
- Beispielstruktur und integriertes Benutzerhandbuch.
- GPLv3+, Copyright M10-Solutions, portable Docker-Konfiguration, fixierte Composer-Abhängigkeiten und vorbereitete GitHub-QA.

- Prism 1.30.0 mit dokumentierten Quellen; eigene LibreOffice-ODT-Vorlage.
- Vier SDD-Grundsätze und konsolidierte Projektdokumentation.


Test- und Freigabenachweise: [ACCEPTANCE.md](docs/ACCEPTANCE.md).
