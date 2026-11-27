# HomeHub CMS – Erweiterungskatalog (Backlog)

Stand 27.11.2026 · Release 1.1.0 veröffentlicht; Release 1.1.1 freigegeben (Publikation ausstehend); IDEA-18 abgenommen; IDEA-02/17 abgenommen; ursprünglicher Katalog vom 22.11.2026. Ideen, keine Implementierungsaufträge und keine Voraussetzungen für 1.0. Prioritäten sind Vorschläge für eine spätere Entscheidung: H = hoher Nutzen, M = mittel, N = niedrig. Aufwand qualitativ, nicht geschätzt in Tagen. Jede Idee benötigt bei Beauftragung eine eigene SPEC und Tests. Dateibasierte Architektur, stabile IDs, Rechte und portable Inhalte bleiben Leitplanken.

| ID | Idee | Nutzen / Umfang | Prio | Aufwand | Risiken / Abgrenzung |
|---|---|---|---|---|---|
| IDEA-01 | Seitenhistorie | Frühere Stände anzeigen, vergleichen und wiederherstellen | H | M–H | Speicherbedarf, Aufbewahrung, Rechte, Restore mit gleicher ID |
| IDEA-02 | Papierkorb | Abgenommen (TRASH-001) | H | M | ID- und Pfadkollisionen, Rechte, endgültiges Löschen |
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
| IDEA-17 | Rekursives Löschen von Seitenstrukturen | Abgenommen (TRASH-001) | M | M | ACL, Sperren, Bestätigung, Papierkorb |
| IDEA-18 | Berechtigungsgeschützter Medienzugriff | Abgenommen (MED-SEC/EXP-SEC) | H | M–H | Siehe SPEC und TASKS |
| IDEA-19 | Ganze Kapitelstrukturen verschieben | Kapitel mitsamt allen Unterseiten an einen neuen Ort verschieben | M | M–H | ACL-Vererbung, Sperren, Pfadkollisionen, stabile IDs, keine Verschiebung in eigene Unterstruktur |
| IDEA-20 | Keine zusätzlichen Leerzeilen beim Speichern | Markdown-Inhalt ohne automatisch angefügte Leerzeilen speichern | H | N | Fehlerkorrektur; bewusst eingegebene Leerzeilen erhalten |
| IDEA-21 | Editor-Button «Web-Bild» | Beschriftung «Bild» in «Web-Bild» ändern | M | N | Nur Beschriftung; Funktion unverändert |
| IDEA-22 | Kontextabhängige Berechtigungseinstellungen | Nur zur Sichtbarkeit passende Auswahlfelder anzeigen | M | N | ACL unverändert; ausgeblendete Felder dürfen keine unbeabsichtigten Rechteänderungen verursachen |
| IDEA-23 | Redaktionelle Metadaten und Sichtbarkeit | Titel, Beschreibung und Schlagworte in den Editor verschieben; Seiteneinstellungen in «Sichtbarkeit» umbenennen | M | M | Gemeinsames Speichern, Sperren, ACL und stabile IDs erhalten; mit IDEA-22 abstimmen |
| IDEA-24 | Einzeilige Darstellung von Seitentiteln | Automatische Zeilenumbrüche bei langen Seitentiteln verhindern | M | N | Responsive Darstellung; vollständiger Titel bleibt erhalten; keine Kollision mit Tool-Buttons; diese bleiben sichtbar und bedienbar; zurückgestellt |
| IDEA-25 | Konsolidierung und Vereinfachung der Exportfunktionen | Exportaktionen nach Anmeldestatus; gemeinsamer Exportdialog mit Format-Dropdown als erstem Eingabefeld und Umfang | M | M | ACL und Sicherheitsprüfungen erhalten; Kapitelstruktur-Export nur angemeldet; PDF-Einzelseitenexport für Gäste |

## IDEA-10 – Drag-and-drop im Seitenbaum
Ziel: Admin kann eine Seite auf einen zulässigen Zielordner ziehen, statt den bestehenden Move-Dialog manuell zu bedienen. Variante für Umsortierung unter Geschwistern separat entscheiden; keine implizite Änderung der Sortierreihenfolge. Bestehender Move-Dialog bleibt als Tastatur-/Touch-Alternative erhalten. Vor dem Commit Zielpfad und mögliche Änderung geerbter Leserechte anzeigen, Bestätigung und Abbruch anbieten. Vorhandene serverseitige Move-Operation, Rollen-/CSRF-Prüfung, Revisionskontrolle, Sperre und Pfadvalidierung wiederverwenden. Keine Überschreibung, keine ungültigen Ziele, keine unbeabsichtigte Verschiebung eines gesamten Kapitels; Semantik von Unterseiten ausdrücklich spezifizieren. ID-Links und Medienreferenzen bleiben erhalten, Legacy-Pfadlinks können wie bisher brechen. Bei Fehler Baum aus Serverzustand aktualisieren; keine vorgetäuschte Erfolgsmeldung. Tests für Admin/Editor/Reader, Kollisionen, veraltete Revision, Abbruch, geerbte Rechte, Tastatur und schmale Ansichten. Noch nicht implementiert.

## IDEA-19 – Ganze Kapitelstrukturen verschieben
Ziel: Ein bestehendes Kapitel mitsamt sämtlichen Unterseiten an einen neuen zulässigen Ort verschieben. Hierarchie, stabile Seiten-IDs, Inhalte und Metadaten sollen erhalten bleiben. Bei einer späteren Spezifikation sind Berechtigungen am Quell- und Zielort, geerbte Rechte, Sperren, Pfadkollisionen, ungültige Ziele (insbesondere eigene Unterstrukturen), Legacy-Pfadlinks und Fehlerbehandlung zu klären. Bedienung über den bestehenden Move-Dialog prüfen; Drag-and-drop (IDEA-10) bleibt eine separate Idee. Noch nicht beauftragt oder implementiert.

## IDEA-20 – Keine zusätzlichen Leerzeilen beim Speichern
Beobachtung des Betreibers: Bei jedem Bearbeiten und Speichern einer Seite wird in der Markdown-Datei eine zusätzliche Leerzeile angefügt. Ziel: Keine automatisch angefügten Leerzeilen; bewusst eingegebene Leerzeilen bleiben erhalten. Wiederholtes Speichern ohne Inhaltsänderung darf keine weiteren Leerzeilen erzeugen. Ursache und Verhalten bei vorhandenen Zeilenenden bei Beauftragung prüfen. Abgenommen am 27.11.2026 laut Betreiber; siehe SPEC-EDITOR.md.


## IDEA-21 – Editor-Button «Web-Bild»
Ziel: Die Beschriftung des bestehenden Editor-Buttons «Bild» in «Web-Bild» ändern. Die bestehende Funktion bleibt unverändert. Abgenommen am 27.11.2026 laut Betreiber; siehe SPEC-EDITOR.md.


## IDEA-22 – Kontextabhängige Berechtigungseinstellungen
Ziel: Die Seiteneinstellungen zeigen abhängig von der gewählten Sichtbarkeit ausschliesslich die relevanten Auswahlfelder. Bei «Ausgewählte Rollen» nur die Rollenauswahl anzeigen, bei «Ausgewählte Benutzer» nur die Benutzerauswahl. Bei «Alle» und «Angemeldete Benutzer» keine zusätzlichen Auswahlfelder anzeigen. Die Anzeige soll unmittelbar beim Wechsel der Sichtbarkeit aktualisiert werden. Das bestehende ACL-Modell und die serverseitige Prüfung bleiben unverändert. Abgenommen am 27.11.2026 laut Betreiber; siehe SPEC-EDITOR.md.


## IDEA-23 – Trennung redaktioneller Metadaten von Seiteneinstellungen
Ziel: Redaktionelle Bearbeitung und Konfiguration von Veröffentlichung und Zugriff klar trennen. Titel, Beschreibung und Schlagworte in den Editor verschieben; Titel direkt im page-heading mit identischer Titeltypografie, Beschreibung und Schlagworte unmittelbar darunter, standardmässig eingeklappt. Metadaten und Markdown-Inhalt gemeinsam speichern; beim Abbrechen sämtliche ungespeicherten redaktionellen Änderungen verwerfen. Die bisherigen Seiteneinstellungen in «Sichtbarkeit» umbenennen; dort verbleiben Sortierung, Veröffentlicht, In Navigation anzeigen, Sichtbarkeit und die bestehenden Berechtigungseinstellungen. Kontextabhängige Auswahl gemäss IDEA-22 berücksichtigen. Keine zusätzlichen Registerkarten. Bestehende ACL, stabile Seiten-IDs und Speicherpfade erhalten; keine Erweiterung der Bearbeitungsrechte durch die Verlagerung. Bearbeitungssperren und serverseitige Prüfungen auch für Metadaten sicherstellen. Abgenommen am 27.11.2026 laut Betreiber; siehe SPEC-EDITOR.md.


## IDEA-24 – Einzeilige Darstellung von Seitentiteln
Beobachtung des Betreibers: Längere Seitentitel werden unschön umgebrochen. Ziel: Seitentitel grundsätzlich einzeilig darstellen, ohne automatische Zeilenumbrüche. Auch bei schmalen Ansichten dürfen andere Bedienelemente nicht verdrängt werden. Der Seitentitel darf nicht mit den Tool-Buttons kollidieren oder diese überdecken; die Tool-Buttons bleiben vollständig sichtbar und bedienbar. Bei Platzmangel hat deren Bedienbarkeit Vorrang vor der vollständigen optischen Darstellung des Titels. Gespeicherte Titel bleiben vollständig und unverändert. Bei Beauftragung den genauen Anzeigebereich und die Darstellung bei Platzmangel klären; optische Kürzung mit Auslassungspunkten bei weiterhin zugänglichem vollständigem Titel als Vorschlag prüfen. Responsive Darstellung testen. Noch nicht beauftragt oder implementiert.

Zurückgestellt auf Betreiberwunsch; nicht beauftragt.

## IDEA-25 – Konsolidierung und Vereinfachung der Exportfunktionen
Ziel: Alle Exportfunktionen im Content-Bereich für angemeldete Benutzer in «Kapitelstruktur exportieren» zusammenführen. Diesen Button ausschliesslich angemeldeten Benutzern anzeigen; den Button «Als PDF herunterladen» ausschliesslich nicht angemeldeten Benutzern anzeigen. Weitere separate Export-Buttons entfallen. Für Gäste bleibt der PDF-Export der zugriffsberechtigten Einzelseite erhalten.

Dialogtitel neu «Seite oder Kapitel exportieren». «Nur diese Seite» ist standardmässig ausgewählt. Bei «Diese Seite mit allen Unterseiten» zusätzlich «Dokumentoptionen (PDF / ODT)» anzeigen; bei «Nur diese Seite» ausblenden. Exportformat per Dropdown (PDF, ODT oder ZIP) auswählen. Das Exportformat steht als erstes Eingabefeld unmittelbar unter dem Dialogtitel, vor dem Exportumfang und den gegebenenfalls eingeblendeten Dokumentoptionen. Dialog-Buttons auf «Abbrechen» und «Exportieren» reduzieren. «Exportieren» startet den Export im gewählten Format und Umfang; «Abbrechen» schliesst den Dialog ohne Export. PDF ist Standardformat. Dokumentoptionen bei ZIP ausblenden und deaktivieren; bei Einzelseiten serverseitig ignorieren.

Bestehende ACL und Sicherheitsmechanismen erhalten. Beschränkung des Kapitelstruktur-Exports auf angemeldete Benutzer auch serverseitig durchsetzen; keine Offenlegung nicht zugriffsberechtigter oder nach bestehendem Exportkonzept ausgeschlossener Inhalte. Bestehende Dokumentoptionen und Exportfunktionen im zulässigen Umfang erhalten. Tests für angemeldete und nicht angemeldete Benutzer, direkte Exportaufrufe, beide Exportumfänge, alle drei Formate, Reihenfolge der Dialogelemente, dynamische Anzeige der Dokumentoptionen und Abbruch vorsehen. Am 27.11.2026 laut Betreiber abgenommen; Regression erfolgreich. Verbindliche Details siehe SPEC-EDITOR.md.

## Nicht im aktuellen Zielbild
WYSIWYG, Datenbank, Plugin-System, Echtzeit-Kollaboration, KI und komplexe Freigabeworkflows bleiben ausserhalb des geplanten Umfangs. Technische Härtung, Backup/Restore, Browser-/Export-End-to-End-Tests sowie parallele Schreibzugriffe sind Qualitätsaufgaben (TASKS.md), keine optionalen Produktfeatures. Keine Priorisierung oder Umsetzung ohne Betreiberentscheidung.

## Weitere Sicherheitsoption
Individuelle Medien-ACLs können später separat spezifiziert werden. Variante A bleibt für 1.0 akzeptiert; keine Umsetzung beauftragt.
