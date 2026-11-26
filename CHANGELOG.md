# Changelog

## 1.1.0 – Papierkorb, Sicherheit und ODT-Gestaltung (26.11.2026)

Anwendungsversion `1.1-20261126`.

- ODT: typografisch überarbeitete Referenzvorlage und nachträgliche Formatierung der Pandoc-Tabellenzellen; Betreiberabnahme erfolgreich.

- MED-SEC / EXP-SEC: seitengebundener Medienzugriff und einheitliche Referenzprüfung für Exporte.
- TRASH-001: Papierkorb, rekursives Löschen, Admin-Toolbar und Mehrfachaktionen.
- SDD: Aufteilung in SPEC-EDITOR.md und SPEC-ADMIN.md.
- Betreiberabnahme am 25.11.2026: alle manuellen Kriterien erfüllt, 39/39 Regressionstests ohne PHP-Warnungen vor Versionspflege.
- Keine neuen Abhängigkeiten oder erforderlichen Datenmigrationen. IDEA-19 bleibt im Backlog.
- Paketierung und abschliessende Betreiberabnahme am 26.11.2026: alle Kriterien erfüllt, 39/39 Regressionstests ohne PHP-Warnungen. Veröffentlichung freigegeben; GitHub-Publikation noch nicht bestätigt.

## 1.0.1 – Small UX improvements and bugfixes (25.11.2026)

Anwendungsversion `1.0-20261125`.

- QA-002: temporäre Testverzeichnisse einschliesslich versteckter Dateien bereinigen.
- UI-013: neuer Header-Untertitel, ausgerichteter Navigationstoggle und einheitlicher Admin-Logout mit Weiterleitung zur Startseite.
- Kapitel-Exportdialog schliesst bei gültigem Exportstart; Live-Pfadkonfliktprüfung bei neuen Wiki-Seiten mit Fehleranzeige im Dialog.
- Einheitliche Icons in modalen Dialogheadern.
- README, Screenshot und SDD-Dokumentation aktualisiert.
- Betreiberabnahme: 36/36 Regressionstests ohne PHP-Warnungen und alle manuellen Kriterien erfüllt. Abschliessende Regression nach Versionspflege und Publikation laut Betreiber bestätigt.
- Keine neuen Abhängigkeiten oder Datenmigrationen.

## 1.0.0 – erster öffentlicher Release (25.11.2026)

Anwendungsversion `1.0-20261124`. Betreiberfreigabe des privaten Releases am 24.11.2026; öffentliche Freigabe am 25.11.2026 bestätigt.

- Markdown-Wiki, Editor, Medien, Suche, Schlagwörter und Kapitelberechtigungen.
- Mehrbenutzerbetrieb mit Bearbeitungssperren, responsive Navigation, Light-/Dark-Mode.
- PDF-, ODT- und ZIP-Export, browserbasierte Ersteinrichtung.
- Restriktive Behandlung ungültiger Seitenmetadaten.
- Beispielstruktur und integriertes Benutzerhandbuch.
- GPLv3+, Copyright M10-Solutions, portable Docker-Konfiguration, fixierte Composer-Abhängigkeiten und vorbereitete GitHub-QA.

- Prism 1.30.0 mit dokumentierten Quellen; eigene LibreOffice-ODT-Vorlage.
- Vier SDD-Grundsätze und konsolidierte Projektdokumentation.

Abnahmen und Einschränkungen: docs/TESTING.md; Publikationsstatus: docs/PROJECT.md.
