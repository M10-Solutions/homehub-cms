# Changelog

## 1.0.1 – Small UX improvements and bugfixes (25.11.2026)

Anwendungsversion `1.0-20261125`.

- QA-002: temporäre Testverzeichnisse einschliesslich versteckter Dateien bereinigen.
- UI-013: neuer Header-Untertitel, ausgerichteter Navigationstoggle und einheitlicher Admin-Logout mit Weiterleitung zur Startseite.
- Kapitel-Exportdialog schliesst bei gültigem Exportstart; Live-Pfadkonfliktprüfung bei neuen Wiki-Seiten mit Fehleranzeige im Dialog.
- Einheitliche Icons in modalen Dialogheadern.
- README, Screenshot und SDD-Dokumentation aktualisiert.
- Betreiberabnahme: 36/36 Regressionstests ohne PHP-Warnungen und alle manuellen Kriterien erfüllt. Abschliessende Regression nach Versionspflege noch ausstehend.
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
