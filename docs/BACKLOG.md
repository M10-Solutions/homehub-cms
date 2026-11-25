# HomeHub CMS – Erweiterungskatalog (Backlog)

Stand 24.11.2026 · Release 1.0 freigegeben; keine neuen Aufträge; ursprünglicher Katalog vom 22.11.2026. Ideen, keine Implementierungsaufträge und keine Voraussetzungen für 1.0. Prioritäten sind Vorschläge für eine spätere Entscheidung: H = hoher Nutzen, M = mittel, N = niedrig. Aufwand qualitativ, nicht geschätzt in Tagen. Jede Idee benötigt bei Beauftragung eine eigene SPEC und Tests. Dateibasierte Architektur, stabile IDs, Rechte und portable Inhalte bleiben Leitplanken.

| ID | Idee | Nutzen / Umfang | Prio | Aufwand | Risiken / Abgrenzung |
|---|---|---|---|---|---|
| IDEA-01 | Seitenhistorie | Frühere Stände anzeigen, vergleichen und wiederherstellen | H | M–H | Speicherbedarf, Aufbewahrung, Rechte, Restore mit gleicher ID |
| IDEA-02 | Papierkorb | Versehentlich gelöschte Seiten wiederherstellen | H | M | ID- und Pfadkollisionen, Rechte, endgültiges Löschen |
| IDEA-03 | Systemintegritätsprüfung | Offline-/Admin-Prüfung defekter Links, IDs, verwaister Medien | H | M | Zunächst nur lesend; keine automatische Löschung; kein produktiver Usage-Scanner |
| IDEA-04 | Backlinks | Eingehende Seitenlinks anzeigen | M | M | Nur lesbare Seiten anzeigen; Legacy-Links berücksichtigen |
| IDEA-05 | Seitenvorlagen | Wiederkehrende Markdown-Strukturen | M | N–M | Keine kopierten IDs oder ungewollten Freigaben |
| IDEA-06 | Inhaltsverzeichnis | Sprunglinks aus Überschriften für lange Seiten | M | N–M | Eindeutige Anker, Vorschau und Export abgrenzen |
| IDEA-07 | Favoriten | Schnellzugriff auf häufig verwendete Seiten | N | N–M | Stabile IDs, aktuelle Rechte, Speicherung festlegen |
| IDEA-08 | Zuletzt besuchte Seiten | Persönlicher Navigationsverlauf | N | N–M | Datenschutz, Löschung, aktuelle Rechte |
| IDEA-09 | Benutzereinstellungen | Theme und weitere Präferenzen pro Konto | N | M | Browserpräferenz/Migration, Verhalten ohne Login |
| IDEA-10 | Seiten per Drag-and-drop verschieben | Seiten direkt im Navigationsbaum reorganisieren | M | M–H | Rechte, Kollisionen, Vererbung, Bestätigung, stabile IDs |
| IDEA-11 / MEDIA-005 | Bilder direkt im Editor einfügen (Drop/Paste) | Weniger Bedienwege | M | M | Uploadvalidierung, Ordnerwahl, Dateilimits |
| IDEA-12 / TAG-005 | Tags zusammenführen | Katalogpflege bei Dubletten | N | M–H | Atomare Zuordnungsbereinigung, Backup |
| IDEA-14 | Vollbildeditor | Mehr Platz für umfangreiche Markdown-Bearbeitung | N | M | Fokus, mobile Ansichten und bestehende Sperrverwaltung berücksichtigen |
| IDEA-15 | Alternative Positionen der Editor-Aktionsleiste | Wahlweise untere oder doppelte Aktionsleiste | N | N–M | Keine verdeckten Inhalte, mobile Bedienbarkeit |
| IDEA-16 | Erweiterte Editor-Vorschau | Verbesserte Navigation und Darstellung bei langen Inhalten | N | M | Preview/Markdown-Semantik und Performance erhalten |
| IDEA-13 | Suchindex | Schnellere Suche bei grossen Beständen | N | M–H | Nur bei gemessenem Bedarf; Aktualität und ACL |

## IDEA-10 – Drag-and-drop im Seitenbaum
Ziel: Admin kann eine Seite auf einen zulässigen Zielordner ziehen, statt den bestehenden Move-Dialog manuell zu bedienen. Variante für Umsortierung unter Geschwistern separat entscheiden; keine implizite Änderung der Sortierreihenfolge. Bestehender Move-Dialog bleibt als Tastatur-/Touch-Alternative erhalten. Vor dem Commit Zielpfad und mögliche Änderung geerbter Leserechte anzeigen, Bestätigung und Abbruch anbieten. Vorhandene serverseitige Move-Operation, Rollen-/CSRF-Prüfung, Revisionskontrolle, Sperre und Pfadvalidierung wiederverwenden. Keine Überschreibung, keine ungültigen Ziele, keine unbeabsichtigte Verschiebung eines gesamten Kapitels; Semantik von Unterseiten ausdrücklich spezifizieren. ID-Links und Medienreferenzen bleiben erhalten, Legacy-Pfadlinks können wie bisher brechen. Bei Fehler Baum aus Serverzustand aktualisieren; keine vorgetäuschte Erfolgsmeldung. Tests für Admin/Editor/Reader, Kollisionen, veraltete Revision, Abbruch, geerbte Rechte, Tastatur und schmale Ansichten. Noch nicht implementiert.

## Nicht im aktuellen Zielbild
WYSIWYG, Datenbank, Plugin-System, Echtzeit-Kollaboration, KI und komplexe Freigabeworkflows bleiben ausserhalb des geplanten Umfangs. Technische Härtung, Backup/Restore, Browser-/Export-End-to-End-Tests sowie parallele Schreibzugriffe sind Qualitätsaufgaben (TASKS.md), keine optionalen Produktfeatures. Keine Priorisierung oder Umsetzung ohne Betreiberentscheidung.

## Weitere Sicherheitsoption
Individuelle Medien-ACLs können später separat spezifiziert werden. Variante A bleibt für 1.0 akzeptiert; keine Umsetzung beauftragt.
