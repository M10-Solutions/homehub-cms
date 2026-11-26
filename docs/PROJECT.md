# HomeHub CMS – Projektstatus

Stand 26.11.2026. Release 1.1.0 abgenommen und zur Veröffentlichung freigegeben, Anwendung 1.1-20261126; letzte bestätigte GitHub-Veröffentlichung 1.0.1. Maintainer M10-Solutions, GPL-3.0-or-later. Repository M10-Solutions/homehub-cms ist öffentlich; Release 1.0.0 am 25.11.2026 laut Maintainer veröffentlicht.

## Arbeitsweise
SPEC-EDITOR.md und SPEC-ADMIN.md definieren Anforderungen, ARCHITECTURE die vier SDD-Grundsätze und technische Entscheidungen, TASKS aktuelle Aufträge, BACKLOG unverbindliche Ideen, TESTING die Prüfungen und README den Betrieb. Änderungen erst spezifizieren, dann implementieren, testen und dokumentieren. Historische Unterlagen privat archivieren.

## Abnahmen
- Betreiberfreigabe 1.0 am 24.11.2026, NAS-QA 35/35; UAT-002a–f, UI-012, Update und Restore abgenommen.
- GitHub CI #1 erfolgreich, 35/35 und Composer-Audit ohne Meldungen laut Betreiber; nach Checkout-Update CI ohne Warnungen bestätigt.
- Prism 1.30.0 und neue LibreOffice-ODT-Vorlage laut Betreiber erfolgreich getestet (Schritte 1 und 2). Zusätzlicher unabhängiger Prism-Bytevergleich bewusst nicht durchgeführt. Composer-Lizenzdateien vorhanden laut Betreiber; kein separater Textabgleich.
- PDF, Docker und Composer bleiben unverändert. Systemscan und Export-Risikogrenzen siehe TESTING.md.

- QA-002: NAS-Regression 35/35 ohne Warnungen; UI-013a–h: alle manuellen Kriterien und 36/36 Regressionen ohne Warnungen am 25.11.2026 laut Betreiber bestätigt.
- Release 1.0.1: gebündelte Publikation freigegeben; abschliessende Regression 36/36 ohne Warnungen und GitHub CI sowie Publikation laut Betreiber bestätigt.

## PUB-001 – abgeschlossen
- Lizenzierung, Lockfile, Prism und eigene ODT-Vorlage vorbereitet und funktional abgenommen.
- Vier SDD-Grundsätze und Dokumentationskonsolidierung umgesetzt.
- Neues unabhängiges Repository mit sauberem Initialstand erstellt; abschliessende QA und Repository-Kontrolle laut Maintainer erfolgreich.
- Öffentliche Freigabe und Release `1.0.0` am 25.11.2026 vom Maintainer bestätigt. Export-Risikogrenzen bleiben gemäss TESTING.md bestehen; keine zusätzliche ausdrückliche Einzelabnahme dokumentiert.
- README redaktionell aktualisiert und freigegebener Beispiel-Screenshot ergänzt; Übernahme im gebündelten Release 1.0.1 laut Betreiber bestätigt; kein unabhängiger GitHub-Abgleich.
- Private Vulnerability Reporting: Aktivierung nicht bestätigt. Historisches Entwicklungsrepository: Löschung nicht bestätigt.

Keine produktiven Daten oder privaten Screenshots veröffentlichen. Kein fertiges Docker-Image verteilen. Historische Dokumentation privat aufbewahren. Keine neuen Entwicklungsaufträge.

- MED-SEC (IDEA-18): Medienendpunkt/UI funktional abgenommen, 37/37 Regressionen laut Betreiber. EXP-SEC-01–03 umgesetzt; abschliessende Exportabnahme erfolgreich (38/38, keine PHP-Warnungen). IDEA-18 abgeschlossen; Bestandteil von 1.1.0.

TRASH-001 (IDEA-02/17) einschliesslich UI-Nachbesserung: Betreiberabnahme am 25.11.2026 erfolgreich; alle manuellen Abnahmekriterien erfüllt, 39/39 Regressionstests ohne PHP-Warnungen. Keine aktiven Entwicklungsaufträge oder offenen funktionalen Abnahmen. MED-SEC/EXP-SEC und TRASH-001 sind Bestandteil von 1.1.0.

ODT-Gestaltung: sämtliche manuellen Kriterien und 39/39 Regressionstests ohne PHP-Warnungen am 25.11.2026 laut Betreiber bestätigt. Spezifikationsaufteilung abgeschlossen. IDEA-19 bleibt unbeauftragt. Paketierung und abschliessende Betreiberabnahme am 26.11.2026 bestätigt: alle Kriterien erfüllt, 39/39 Regressionstests ohne PHP-Warnungen. Veröffentlichung freigegeben; GitHub-Publikation noch nicht bestätigt.
