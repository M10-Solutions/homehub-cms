# HomeHub CMS – Projektstatus

Stand 25.11.2026. Anwendung 1.0-20261124; geplanter GitHub-Tag v1.0.0. Maintainer M10-Solutions, GPL-3.0-or-later. Repository M10-Solutions/homehub-cms ist privat; öffentliche Freigabe steht aus.

## Arbeitsweise
SPEC definiert Anforderungen, ARCHITECTURE die vier SDD-Grundsätze und technische Entscheidungen, TASKS aktuelle Aufträge, BACKLOG unverbindliche Ideen, TESTING die Prüfungen und README den Betrieb. Änderungen erst spezifizieren, dann implementieren, testen und dokumentieren. Historische Unterlagen privat archivieren.

## Abnahmen
- Betreiberfreigabe 1.0 am 24.11.2026, NAS-QA 35/35; UAT-002a–f, UI-012, Update und Restore abgenommen.
- GitHub CI #1 erfolgreich, 35/35 und Composer-Audit ohne Meldungen laut Betreiber; nach Checkout-Update CI ohne Warnungen bestätigt.
- Prism 1.30.0 und neue LibreOffice-ODT-Vorlage laut Betreiber erfolgreich getestet (Schritte 1 und 2). Zusätzlicher unabhängiger Prism-Bytevergleich bewusst nicht durchgeführt. Composer-Lizenzdateien vorhanden laut Betreiber; kein separater Textabgleich.
- PDF, Docker und Composer bleiben unverändert. Systemscan und Export-Risikogrenzen siehe TESTING.md.

## PUB-001 – Abschluss
- [x] Lizenzierung, Lockfile, öffentliche Basisdokumentation, Prism und ODT vorbereitet und funktional abgenommen.
- [x] Vier SDD-Grundsätze und Dokumentationskonsolidierung umgesetzt.
- [ ] Abschliessende QA/CI auf dem Publikationsstand.
- [ ] Tatsächlichen Git-Dateibestand und Historie auf private Daten und ersetzte Komponenten mit ungeklärten Weitergaberechten prüfen; bevorzugt sauberer öffentlicher Initialstand ohne alte private Historie, nach Sicherung und Maintainer-Entscheid.
- [ ] Export-Risikogrenzen gemäss TESTING.md ausdrücklich akzeptieren bzw. bei nicht vertrauenswürdigen Editoren vor Freigabe beheben.
- [ ] Maintainer-Freigabe, Public-Schaltung, private Schwachstellenmeldungen sobald verfügbar, Tag v1.0.0 und Release erstellen.

Keine produktiven Daten oder Screenshots veröffentlichen. Kein fertiges Docker-Image verteilen. Die private Betreiberfreigabe ist keine öffentliche Freigabe.
