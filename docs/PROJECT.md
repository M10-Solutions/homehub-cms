# HomeHub CMS – Projektstatus

Stand 25.11.2026. Anwendung 1.0-20261125; Release 1.0.1 zur Publikation vorbereitet. Maintainer M10-Solutions, GPL-3.0-or-later. Repository M10-Solutions/homehub-cms ist öffentlich; Release 1.0.0 am 25.11.2026 laut Maintainer veröffentlicht.

## Arbeitsweise
SPEC definiert Anforderungen, ARCHITECTURE die vier SDD-Grundsätze und technische Entscheidungen, TASKS aktuelle Aufträge, BACKLOG unverbindliche Ideen, TESTING die Prüfungen und README den Betrieb. Änderungen erst spezifizieren, dann implementieren, testen und dokumentieren. Historische Unterlagen privat archivieren.

## Abnahmen
- Betreiberfreigabe 1.0 am 24.11.2026, NAS-QA 35/35; UAT-002a–f, UI-012, Update und Restore abgenommen.
- GitHub CI #1 erfolgreich, 35/35 und Composer-Audit ohne Meldungen laut Betreiber; nach Checkout-Update CI ohne Warnungen bestätigt.
- Prism 1.30.0 und neue LibreOffice-ODT-Vorlage laut Betreiber erfolgreich getestet (Schritte 1 und 2). Zusätzlicher unabhängiger Prism-Bytevergleich bewusst nicht durchgeführt. Composer-Lizenzdateien vorhanden laut Betreiber; kein separater Textabgleich.
- PDF, Docker und Composer bleiben unverändert. Systemscan und Export-Risikogrenzen siehe TESTING.md.

- QA-002: NAS-Regression 35/35 ohne Warnungen; UI-013a–h: alle manuellen Kriterien und 36/36 Regressionen ohne Warnungen am 25.11.2026 laut Betreiber bestätigt.
- Release 1.0.1: gebündelte Publikation freigegeben; abschliessende Regression nach Versionspflege und GitHub-Publikation noch nicht bestätigt.

## PUB-001 – abgeschlossen
- Lizenzierung, Lockfile, Prism und eigene ODT-Vorlage vorbereitet und funktional abgenommen.
- Vier SDD-Grundsätze und Dokumentationskonsolidierung umgesetzt.
- Neues unabhängiges Repository mit sauberem Initialstand erstellt; abschliessende QA und Repository-Kontrolle laut Maintainer erfolgreich.
- Öffentliche Freigabe und Release `1.0.0` am 25.11.2026 vom Maintainer bestätigt. Export-Risikogrenzen bleiben gemäss TESTING.md bestehen; keine zusätzliche ausdrückliche Einzelabnahme dokumentiert.
- README redaktionell aktualisiert und freigegebener Beispiel-Screenshot ergänzt; Übernahme dieses letzten Patches auf GitHub nicht bestätigt.
- Private Vulnerability Reporting: Aktivierung nicht bestätigt. Historisches Entwicklungsrepository: Löschung nicht bestätigt.

Keine produktiven Daten oder privaten Screenshots veröffentlichen. Kein fertiges Docker-Image verteilen. Historische Dokumentation privat aufbewahren. Keine neuen Entwicklungsaufträge.
