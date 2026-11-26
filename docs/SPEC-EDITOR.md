# HomeHub CMS – Funktionsspezifikation Content und Editor

**Stand:** 26.11.2026 · Release 1.1.0 (Anwendung 1.1-20261126); abschliessend abgenommen und zur Veröffentlichung freigegeben; GitHub-Publikation noch nicht bestätigt.
Diese Datei ist die konsolidierte Soll-/Ist-Beschreibung des aktuellen Produkts; ältere Abnahmedokumente sind historische Nachweise. SEARCH-001 und die vorangegangenen Erweiterungen sind laut Betreiber abgenommen. CLEANUP-001 / SDD-002: Betreiber bestätigt 24/24 Tests und manuelle Funktionstests. QA-002: NAS-Abnahme mit 25/25 Tests bestätigt. VERSION-001: 26/26 Regressionen bestätigt; Footer nicht separat bestätigt. UI-011: 27/27 Regressionen und manuelle Funktionstests bestätigt.


Gemeinsame verbindliche Regeln (SDD, ACL-001, UI, QA und Administration): [SPEC-ADMIN.md](SPEC-ADMIN.md). Die folgenden gemischten Abschnitte enthalten auch administrative Teilanforderungen; sie gelten bereichsübergreifend.

## DOC-001 – Seitenmodell und Darstellung
- Markdown unter `data/content`, hierarchische Slugs aus Verzeichnissen und Dateinamen. Startseite, Titel, Untertitel und Zeitzone in `src/config/app.php`.
- YAML-Felder: `id`, `title`, `description`, `date-created`, `date-modified`, `labels`, `order`, `published`, `nav`, `visibility`, `allowed_roles`, `allowed_users`. Titel/Beschreibung stehen zusätzlich als lesbarer Markdown-Vorspann; der Renderer und Editor entfernen den generierten Vorspann aus dem eigentlichen Inhalt. Kein doppelter Titel, auch bei leerem Inhalt.
- Standard bei fehlenden Metadaten: veröffentlicht, Navigation sichtbar, Reihenfolge 100, öffentliche Lesbarkeit. Kategorie ergibt sich aus Pfad.
- Markdown mit Tabellen, Codeblöcken, Syntax-Highlighting (Prism), Ausrichtungsblöcken `::: left|center|right|justify`; HTML-Eingabe wird im Wiki- und Preview-Renderer entfernt, unsichere Links deaktiviert. Bilder proportional innerhalb der Inhaltsbreite, kein Upscaling (IMG-001).

**Abnahme:** Leere und komplexere Seite korrekt anzeigen, bearbeiten und als Markdown öffnen; breites Bild bleibt innerhalb der Fläche.

## NAV-001 – Navigation und Zugriff
- Hierarchischer Baum, Sortierung nach Reihenfolge/Titel, Breadcrumbs, aktive Seite und Zweige. Wiki-Navigation einklappbar, Breite 200–500 px einstellbar, Zustand im Browser gespeichert. Separater Navigationsfilter. Admin-Navigation auf Desktop dauerhaft sichtbar; bis 1024 CSS-Pixel gemeinsame Off-Canvas-Navigation (UAT-002b).
- `nav: false` blendet den Navigationseintrag aus, nicht den direkten Zugriff, Such- oder Exporttreffer. `published: false` verhindert öffentliche Auslieferung; auch Admins sehen Entwürfe nicht über die normalen Wiki-/Such-/Export-Routen. Veröffentlichung ist pro Seite, nicht geerbt.
- Rollen `reader`, `editor`, `admin`; ohne Login öffentliche Seiten. Sichtbarkeit `all`, `authenticated`, `roles` oder `users`. Für Nicht-Admins müssen eigene und alle vorhandenen Markdown-Vorfahrenregeln erfüllt sein (auch unveröffentlichte Vorfahren). Admins dürfen alle veröffentlichten Seiten lesen. Verzeichnis ohne Markdown hat keine eigene Regel.
- Reader liest veröffentlichte berechtigte Inhalte; Editor verwaltet berechtigte Seiten und Entwürfe inklusive Erstellung, Publikation, Rückzug, Verschiebung und Löschung; Admin verwaltet zusätzlich Benutzer und Seitenberechtigungen. Die verbindliche Rollenmatrix und `published`/`nav`-Regeln stehen in **ACL-001** unten. Seitenlinks, Suche und Exporte prüfen Leserechte serverseitig. Medienzugriff gemäss MED-SEC unten.

**Abnahme:** Freigegebene und gesperrte Seite samt Unterseite mit jeder Rolle und ohne Login prüfen; Navigation, direkte Links, Suche, Picker und Export vergleichen.

## EDIT-001 / ADMIN-001 – Seiten bearbeiten und organisieren
- **UAT-EDT-01 (implementiert und am 24.11.2026 vom Betreiber abgenommen):** Nach erfolgreicher Übernahme der exklusiven Bearbeitungssperre zeigt HomeHub einen kompakten, viewportorientierten Editor im Content-Bereich. Der Seitentitel bleibt während der Bearbeitung sichtbar und erhält eine kompakte Bearbeiten-Pill, der Editor automatisch sichtbar positioniert und das Textfeld fokussiert. Die Ansichten Bearbeiten/Vorschau/Geteilt werden als Symbolbuttons links im Editorfuss dargestellt. Der Änderungsstatus erscheint rechts in der Markdown-Panelbeschriftung. Die Formatierungswerkzeuge sind platzsparend angeordnet; Speichern und Abbrechen stehen rechts unmittelbar unter den intern scrollbaren Editorflächen. Keine zusätzliche Sticky-Aktionsleiste. Bestehende Sperr-, Vorschau- und Speicherlogik bleiben erhalten.
- Seitenoperationen ausschliesslich im Content-Bereich, nicht im klassischen Admin-Editor (entfernt). Inline-Editor mit Edit-/Preview-/Split-Ansicht, Markdown-Toolbar, Bild- und Seitenlink-Picker; Auswahl an Cursor/Markierung einfügen. Revision beim Inhaltsspeichern, Konfliktmeldung bei veraltetem Stand.
- Admin verwaltet sämtliche Seiten inklusive ACL-Felder. Editor darf berechtigte Seiten erstellen (als Draft in einem bestehenden berechtigten Kapitel), inhaltlich und redaktionell bearbeiten, veröffentlichen/zurückziehen, verschieben/umbenennen und löschen. ACL-Felder (`visibility`, `allowed_roles`, `allowed_users`) bleiben Admin-only; serverseitige Prüfung auch bei manipulierten Formularen. Geerbte Freigaben werden angezeigt. Seiteneinstellungen prüfen Revision; Move prüft Revision unter gemeinsamer Operationssperre. Details und Einschränkungen siehe **ACL-001**.
- Pfade: Kleinbuchstaben a–z, Zahlen, `_`, `-`, Unterordner; maximal 180 Zeichen. Titel 1–160 Zeichen, Beschreibung bis 500 Zeichen, Inhalt bis 1 MiB, Sortierung −99999 bis 99999. Zielpfad darf nicht existieren; Schreibpfade ohne Symlinks. Move-Dialog wählt bestehenden Ordner und Namen, zeigt resultierenden Pfad; keine Ordnerneuanlage im Move-Dialog.
- Seiten speichern atomar (temporäre Datei/rename), Verschieben bleibt bytegleich. Keine produktive Massenaktualisierung alter Referenzen.

**Abnahme:** Seite erstellen, Inhalt/Metadaten ändern, Rollen-/Nutzerfreigabe prüfen, umbenennen/verschieben und löschen; Konflikt und unveränderte ID kontrollieren.

## PAGE-001–004 – Stabile Seitenidentität und Links
- ID `p_<32 hex>` wird serverseitig erzeugt, im YAML gespeichert und bei Bearbeitung/Move erhalten. Legacy-Seiten ohne ID bleiben lesbar; Registrierung per `bin/register-page-ids.php` (Dry-Run, `--apply`). Doppelte/ungültige IDs verhindern widersprüchliche Inventarisierung und relevante Mutationen.
- `/page.php?id=…` löst auf aktuellen Slug auf, prüft Zugriff, meldet fehlende IDs (404), Fehler bei widersprüchlichen IDs. Picker listet lesbare, veröffentlichte Seiten mit ID. Alte Slug-Links bleiben nutzbar, können bei Move brechen.
- `bin/migrate-page-links.php --check/--apply` migriert eindeutig erkennbare klassische `?page=`-Links, relative `.md`-Links und absolute Wiki-Pfade. Prüflauf mit Hashmanifest, unveränderte Ausgangsdaten erforderlich, Backups vor Anwendung; Code, YAML, unbekannte/mehrdeutige Ziele bleiben erhalten. Details: README.md.

**Abnahme:** ID-Link bleibt nach Move gültig; unbekannte ID/gesperrtes Ziel nicht auflösen; zweiter Migrationscheck meldet keine bereits migrierten Ziele.

## SEARCH-001 – Volltext- und Schlagwortsuche
- Suchfeld im Wiki-Header, Enter/Lupe, GET `/?q=…`; maximal 160 Unicode-Zeichen, normalisierte Leerzeichen, case-insensitive Teilstrings. Alle Begriffe müssen in mindestens einem Feld vorkommen (UND); pro Begriff Titel 8, Schlagwort 5, Beschreibung 3, Inhalt 1 Punkte. Sortierung nach Relevanz, Titel, Slug. Link-/Bildbeschriftungen statt Ziel-URLs, keine Tag-IDs als Suchnamen.
- Ergebnisliste im Content-Bereich, Trefferzahl, verlinkter Titel (ID bevorzugt), Pfad, Textauszug und passende Pills; eigene Hinweise für leer/kein Treffer. Suchbegriff bleibt im Header. Nur veröffentlichte, lesbare Seiten inkl. versteckter Navigationseinträge; kein Suchindex, Antwort privat/no-store. Navigationsfilter bleibt unabhängig.

**Abnahme:** Titel-, Text-, Tag- und Mehrwortsuche, leere Suche, keine Treffer, geschützte/unsichtbare Seiten, aktuelle Tag-Namen, Link nach Move.

## EXPORT-001–003 – PDF, ODT und ZIP
- Einzelne Seite PDF/ODT und Kapitelexport PDF/ODT/ZIP; Umfang Seite oder mit Unterseiten, nur veröffentlichte lesbare Seiten inklusive `nav: false`. Kapitelreihenfolge anhand Hierarchie/Sortierung. PDF/ODT optional Deckblatt, Inhaltsverzeichnis und Nummerierung beim Kapitelexport. Pandoc/WeasyPrint, ODT-Referenzvorlage `docs/templates/reference.odt` und editierbarer ODT-Index.
- PDF-Bilder innerhalb Seitenbreite; ODT-Bildrahmen proportional auf nutzbare Standard-Seitenbreite (Ränder abgezogen), konservativer Fallback 16 cm; kein Upscaling.
- ODT-Tabellen: Pandoc-Zellstile erhalten feine graue Rahmen (0,5 pt, #C5CCD0) und einen vollflächig blaugrauen Tabellenkopf (#E8EEF1); Einzel- und Kapitelexport.
- ZIP: Markdown mit YAML in `pages/`, referenzierte lokale Medien in `media/`, dedupliziert, relative Verweise auf enthaltene Ziele, Tag-Namen statt IDs; fehlende lokale Referenzen protokollieren (`EXPORT-HINWEISE.txt`). Keine externen Bilder herunterladen, Originaldaten unverändert. PDF-/ODT-Optionen gelten nicht für ZIP.

**Abnahme:** Einzelseite/Kapitel in allen angebotenen Formaten exportieren; Berechtigungen, Bilder, relative Referenzen und Tag-Namen prüfen.

## VERSION-001 – App-Version
- Zentraler String `version` in `src/config/app.php`, initial `0.8-20261122`. Format `Major.Minor-YYYYMMDD`; Major für grössere Änderungen, Minor für kleinere Erweiterungen und Bugfix-Releases, Datum für die letzte Revision. Reine Bugfixes können nur das Datum ändern. Keine automatische Datumsänderung.
- Anzeige als HTML-escaped Text rechts im Content-Footer auf allen Wiki-Ansichten, auch ohne Anmeldung und bei der Suche. Admin-Footer unverändert.

**Abnahme:** Version in Config ändern, Wiki neu laden und Anzeige rechts im Footer prüfen; auch mobile Darstellung und Suchergebnisse prüfen.

## UAT-002c – Historie der redaktionellen Navigation
Die ursprüngliche UAT-002c-Admin-Entwurfsansicht wurde durch UAT-002c.1 und UAT-002c.2 **abgelöst**. Aktuell sehen Admin und berechtigte Editoren Entwürfe standardmässig, unabhängig von `nav`. Die Kapitelvorauswahl bei der Seitenerstellung bleibt erhalten. **Verbindliche Regeln siehe ACL-001.**

## UAT-002c.2 – Editor-Inhaltsverwaltung (0.13-20261123)
Editoren dürfen berechtigte Seiten erstellen (als Draft unter berechtigtem Kapitel), veröffentlichen, bearbeiten, verschieben und löschen; Benutzer- und ACL-Verwaltung bleibt Admin-Aufgabe. Kein Page Owner. Details: `TESTING.md`.


## UAT-002e – Exklusive Inline-Bearbeitung (abgenommen 23.11.2026)

Beim Öffnen des Inline-Editors muss eine exklusive seitenbezogene Bearbeitungslease erworben werden. Pro Seite darf nur eine gültige Bearbeitungssitzung bestehen; separate Tabs desselben Kontos sind separate Sitzungen. Der Client erneuert die Lease alle 30 Sekunden auch ohne Texteingaben; sie verfällt nach 120 Sekunden ohne bestätigten Heartbeat, ohne Hintergrunddienst. Speichern setzt einen gültigen Lease-Token und die passende Inhaltsrevision voraus; Revisionsprüfung und Schreiboperation erfolgen unter derselben Dateioperationssperre. Abbrechen und erfolgreiches Speichern geben die Lease frei; beim Verlassen wird die Freigabe bestmöglich versucht. Bei Netzwerkfehlern informiert ausschliesslich die zentrale HomeHub-Notification; nach Wiederverbindung wird die Warnung entfernt. Bei ungültiger Lease darf nicht gespeichert werden, ungespeicherter Inhalt bleibt erhalten. Verschieben, Löschen und Seiteneinstellungen respektieren aktive Inline-Leases. Eigene Langzeit-Leases für Einstellungsdialoge sind nicht Teil dieser Anforderung. Siehe `TESTING.md`.


### UAT-EDT-01 – Layout-Nachbesserung (24.11.2026)
Der Seitentitel bleibt im Bearbeitungsmodus sichtbar. Der Editor passt seine Höhe an den sichtbaren Bereich unter dem Seitentitel an; Speichern und Abbrechen bleiben rechts im Editorfuss sichtbar. Bearbeiten, Vorschau und Geteilt verwenden dieselbe verfügbare Panelhöhe und interne Scrollbereiche. NAS-Sichtprüfung und vollständige Regression am 24.11.2026 laut Betreiber erfolgreich; Redesign abgenommen.

**UAT-EDT-01 Feinschliff (24.11.2026):** Seitentitel bleibt mit Bearbeiten-Pill sichtbar; Ansichtsschalter im Editorfuss links, Speichern/Abbrechen rechts. Status im Markdown-Panelkopf. Editorhöhe und unabhängig scrollbare Vorschau bleiben erhalten. NAS-Sichtprüfung und vollständige Regression laut Betreiber am 24.11.2026 erfolgreich; abgenommen.

### UI-012 – Navigationsdarstellung und Theme-Aktion
Die Wiki-Seitennavigation zeigt keine zusätzliche „INTRANET“-Überschrift. In der Administration gibt es nur die Abschnittsüberschrift „Verwaltung“, keine vorangestellte „Navigation“-Überschrift. Navigationseinträge verwenden in beiden Bereichen 14 px Schriftgrösse; Kapitelüberschriften dürfen zur Hierarchiebetonung abweichen. Der gemeinsame Theme-Button zeigt im hellen Modus den Mond (Aktion: Dark Mode aktivieren) und im dunklen Modus die Sonne (Aktion: Light Mode aktivieren). Die bestehende Theme-Persistenz und Tab-Synchronisierung bleiben erhalten.

### UAT-002d – Editor und Medien (Nachbesserung)
- Normale Klicks auf interne stabile Seiten-ID-Links im Leseinhalt prüfen den Zugriff über die bestehende Route. Bei 401/403 bleibt die aktuelle Seite sichtbar und eine HomeHub-Notification erscheint. Direkte Aufrufe und neue Tabs behalten die serverseitige Fehlerantwort.
- Einzelne Dateien lassen sich vor dem Upload aus der tatsächlichen Dateiauswahl entfernen; Anzahl und Validierung werden neu berechnet.
- Medien werden über einen zentralen Dialog umbenannt; CSRF, Rechteprüfung und stabile IDs bleiben unverändert.
- MED-02: Inline-Validierung bleibt bis zur Korrektur sichtbar; serverseitige Notifications bleiben unverändert.
- Betreiberabnahme UAT-002d und UI-012 am 24.11.2026 erfolgreich; vollständige NAS-Regression 33/33 bestanden.

## UI-013 – Benutzerführung und Dialogkonsistenz

- a: Gemeinsamer Header: «Dein Zuhause auf einen Blick.»
- b/c: Desktop-Navigationsbutton auf Mittelachse des Suchfelds, dessen Position unverändert bleibt; eingeklappt Mittelpunkt am linken Fensterrand. Mobile Navigation unverändert.
- d/e: Admin-Logout nur Icon mit zugänglicher Beschriftung; nach Logout Startseite, bei anonym verweigertem Zugriff modaler Login.
- f: Kapitel-Exportdialog schliesst bei gültigem nativem Exportstart (PDF/ODT/ZIP); Browser meldet Downloadabschluss nicht, serverseitige Fehler bleiben in der nativen Antwort sichtbar.
- g: Seitenerstellung prüft den tatsächlichen Seitenpfad nach 300 ms Eingabepause serverseitig mit bestehender ACL und Pfadlogik; Konflikte im Dialog, Eingaben bleiben erhalten. Autoritative Prüfung beim Schreiben unter bestehender Sperre bleibt erhalten.
- h: Alle modalen Dialogheader mit funktionsbezogenem Font-Awesome-Icon links vom Titel, auch dynamische Dialoge.

Abnahme: alle Punkte manuell prüfen, Light/Dark und responsive Darstellung erhalten, Regressionen warnungsfrei, keine neuen Abhängigkeiten oder Migrationen.

## MED-SEC – Berechtigungsgeschützter Medienzugriff (IDEA-18)

- MED-SEC-01–03: Medienendpunkt verlangt Seitenkontext mit aktueller ACL und tatsächlich gerendertem Link/Bild. Stabile ID und optionaler Slug als Suchhinweis; Legacy-Seiten ohne ID über geprüften Slug. Kein Referenznachweis durch Code oder Klartext.
- MED-SEC-04–06: Bibliothekskontext nur für `media.read` (Editor/Admin), unabhängig von Kapitel-ACL; kontextlose Anfragen 404; kein HTTP-Referer.
- MED-SEC-07–10: Gespeicherte ID-/Legacy-Dateilinks unverändert; Renderer ergänzt Kontext, Picker trennt Vorschau- und Markdown-URL. Medienverwaltung, Vorschau und Exporte müssen funktionieren. `private, no-store`; keine neuen Abhängigkeiten oder Indizes.
- Öffentliche Referenz veröffentlicht das Medium; berechtigte Benutzer können Inhalte speichern. Editor/Admin sind für alle Medien vertrauenswürdig. MED-SEC und EXP-SEC sind im vereinbarten Prüfumfang abgenommen; Restrisiken siehe TESTING.md.

- EXP-SEC-01–03: PDF/ODT verwenden pro freigegebener Seite das sichere Wiki-HTML statt ungeprüftem Pandoc-Markdown. Nur validierte lokale Rasterbilder werden als Bildressourcen übergeben; andere Bilder werden durch Alttext ersetzt. Titel/Beschreibung sind Klartext. ZIP übernimmt nur tatsächlich gerenderte Medienreferenzen aus freigegebenen Seiten. Keine vollständige Konverter-Sandbox zugesichert.


## Bereichsübergreifende Funktionen

Siehe [SPEC-ADMIN.md](SPEC-ADMIN.md): MEDIA-001–004 / MEDIA-006 (Medienpicker und Bibliothek), TAG-001–004 (Schlagwörter und Seiteneinstellungen), TRASH-001 (Löschdialog, rekursives Löschen und Papierkorb), USER-001 (Anmeldung).
