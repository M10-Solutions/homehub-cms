# HomeHub CMS – Funktionsspezifikation Content und Editor

Geltungsstand: Release 1.2.0 (`1.2.0-20261127`). Architekturgrundsätze: [ARCHITECTURE.md](ARCHITECTURE.md). Rollen, Administration und gemeinsame UI: [SPEC-ADMIN.md](SPEC-ADMIN.md).

## Allgemeines

### CONTENT-0001 – Zugang und Oberfläche

Für Zugriff, Veröffentlichung und Navigation gelten ACL-0001–0004; für Seitenoperationen STRUCT-0001; für gemeinsame Oberfläche, Dialoge, responsive Navigation und Theme ADMIN-0002–0005. Anmeldung gemäss AUTH-0001. Der gemeinsame Header verwendet «Dein Zuhause auf einen Blick.».

### CONTENT-0002 – Versionsanzeige

Die zentrale Anwendungsversion gemäss VERSION-0001 erscheint HTML-escaped rechts im Content-Footer auf allen Wiki-Ansichten, auch ohne Anmeldung und bei der Suche. Admin-Footer unverändert.

## Seitendarstellung

### DOC-0001 – Seitenmodell

Markdown unter `data/content`, hierarchische Slugs aus Verzeichnissen und Dateinamen. Startseite, Titel, Untertitel und Zeitzone in `src/config/app.php`. YAML-Felder: `id`, `title`, `description`, `date-created`, `date-modified`, `labels`, `order`, `published`, `nav`, `visibility`, `allowed_roles`, `allowed_users`. Standard bei fehlenden Metadaten: veröffentlicht, Navigation sichtbar, Reihenfolge 100, öffentliche Lesbarkeit. Kategorie ergibt sich aus Pfad.

### DOC-0002 – Dokumentvorspann und Darstellung

Titel/Beschreibung stehen zusätzlich als lesbarer Markdown-Vorspann; Renderer und Editor entfernen den generierten Vorspann aus dem eigentlichen Inhalt. Kein doppelter Titel, auch bei leerem Inhalt. Keine statische Eyebrow «HOMEHUB · WISSENSBASIS» im Lese- oder Bearbeitungsmodus.

### DOC-0003 – Markdown-Rendering

Markdown mit Tabellen, Codeblöcken, Syntax-Highlighting (Prism), Ausrichtungsblöcken `::: left|center|right|justify`; HTML-Eingabe wird im Wiki- und Preview-Renderer entfernt, unsichere Links deaktiviert. Bilder proportional innerhalb der Inhaltsbreite, kein Upscaling.

## Navigation

### NAV-0001 – Baum und Filter

Hierarchischer Baum, Sortierung nach Reihenfolge/Titel, Breadcrumbs, aktive Seite und Zweige. Separater Navigationsfilter. Redaktionelle Entwurfsnavigation gemäss ACL-0003. Keine zusätzliche «INTRANET»-Überschrift; Navigationseinträge 14 px, Kapitelüberschriften dürfen abweichen. Ordnersymbole zeigen den geöffneten bzw. geschlossenen Zustand des jeweiligen Zweigs.

### NAV-0002 – Desktop-Navigation

Wiki-Navigation einklappbar, Breite 200–500 px einstellbar, Zustand im Browser gespeichert. Desktop-Navigationsbutton auf Mittelachse des Suchfelds, dessen Position unverändert bleibt; eingeklappt Mittelpunkt am linken Fensterrand. Responsive Verhalten gemäss ADMIN-0004; Admin-Navigation auf Desktop dauerhaft sichtbar.

## Seitenidentität und Verlinkung

### PAGE-0001 – Stabile Identität

ID `p_<32 hex>` serverseitig erzeugen, im YAML speichern und bei Bearbeitung/Move erhalten. Legacy-Seiten ohne ID bleiben lesbar; Registrierung per `bin/register-page-ids.php` (Dry-Run, `--apply`). Doppelte/ungültige IDs verhindern widersprüchliche Inventarisierung und relevante Mutationen.

### PAGE-0002 – Auflösung und Zugriff

`/page.php?id=…` löst auf aktuellen Slug auf, prüft Zugriff, meldet fehlende IDs (404), Fehler bei widersprüchlichen IDs. Alte Slug-Links bleiben nutzbar, können bei Move brechen. Normale Klicks auf interne stabile Seiten-ID-Links im Leseinhalt prüfen Zugriff über die bestehende Route. Bei 401/403 bleibt die aktuelle Seite sichtbar und eine HomeHub-Notification erscheint. Direkte Aufrufe und neue Tabs behalten die serverseitige Fehlerantwort.

### PAGE-0003 – Picker und Migration

Picker listet lesbare, veröffentlichte Seiten mit ID. `bin/migrate-page-links.php --check/--apply` migriert eindeutig erkennbare klassische `?page=`-Links, relative `.md`-Links und absolute Wiki-Pfade. Prüflauf mit Hashmanifest, unveränderte Ausgangsdaten erforderlich, Backups vor Anwendung; Code, YAML, unbekannte/mehrdeutige Ziele bleiben erhalten. Betriebsanleitung: [OPERATIONS.md](OPERATIONS.md).

## Editor

### EDIT-0001 – Seitenoperationen

Seitenoperationen ausschliesslich im Content-Bereich, kein klassischer Admin-Editor. Kapitelvorauswahl bei Seitenerstellung. Rechte und Validierung gemäss ACL-0001–0004 und STRUCT-0001; Löschung gemäss TRASH-0001–0003. Seiten speichern atomar (temporäre Datei/rename), Verschieben bleibt bytegleich.

### EDIT-0002 – Inline-Editor und Werkzeuge

Nach erfolgreicher Übernahme der exklusiven Bearbeitungssperre kompakter, viewportorientierter Editor im Content-Bereich. Editor automatisch sichtbar positionieren und Textfeld fokussieren. Edit-/Preview-/Split-Ansicht, Markdown-Toolbar, Bild- und Seitenlink-Picker; Auswahl an Cursor/Markierung einfügen. Formatierungswerkzeuge platzsparend; Toolbar-Tooltip und zugänglicher Name «Web-Bild».

### EDIT-0003 – Layout und Aktionen

Seitentitel bleibt sichtbar, ohne zusätzliche Bearbeiten-Pill. Editorhöhe passt sich dem sichtbaren Bereich unter dem Seitentitel an; Bearbeiten/Vorschau/Geteilt mit gleicher verfügbarer Panelhöhe und unabhängigen internen Scrollbereichen. Ansichtsschalter als Symbolbuttons links im Editorfuss, Speichern und Abbrechen rechts unmittelbar unter den Editorflächen sichtbar. Änderungsstatus rechts in der Markdown-Panelbeschriftung. Keine zusätzliche Sticky-Aktionsleiste.

### EDIT-0004 – Exklusive Bearbeitungslease

Pro Seite nur eine gültige Bearbeitungssitzung; separate Tabs desselben Kontos sind separate Sitzungen. Client erneuert Lease alle 30 Sekunden auch ohne Texteingaben; Verfall nach 120 Sekunden ohne bestätigten Heartbeat, ohne Hintergrunddienst. Abbrechen und erfolgreiches Speichern geben Lease frei; beim Verlassen Freigabe bestmöglich versuchen. Bei Netzwerkfehlern ausschliesslich zentrale HomeHub-Notification; nach Wiederverbindung Warnung entfernen. Bei ungültiger Lease kein Speichern, ungespeicherter Inhalt bleibt erhalten. Verschieben, Löschen und Sichtbarkeit respektieren aktive Inline-Leases. Keine eigenen Langzeit-Leases für Einstellungsdialoge.

### EDIT-0005 – Revision und Speichern

Speichern setzt gültigen Lease-Token und passende vollständige Seitenrevision voraus; Revisionsprüfung und Schreiboperation unter derselben Dateioperationssperre gemäss DATA-0002. Konfliktmeldung bei veraltetem Stand. Sichtbarkeitsdialog prüft Revision; Move prüft Revision unter gemeinsamer Operationssperre. Markdown-Speicherung fügt dem eigentlichen Inhalt keine Zeilenenden hinzu; vorhandene Leerzeilen und generierter Dokumentvorspann bleiben erhalten.

## Redaktionelle Metadaten

### META-0001 – Seitentitel

Titel direkt im page-heading mit identischer Titeltypografie zur normalen Seitendarstellung editieren.

### META-0002 – Beschreibung und Schlagworte

Beschreibung und Schlagworte unmittelbar darunter im standardmässig eingeklappten Bereich «Beschreibung & Schlagworte». Schlagwortzuordnung gemäss TAG-0001–0003.

### META-0003 – Gemeinsames Speichern

Metadaten gemeinsam mit Markdown speichern; Abbrechen verwirft alle Änderungen. Vollständige Seitenrevision und gültige Lease gemäss EDIT-0005. IDs und Pfade bleiben bei redaktioneller Bearbeitung unverändert.

### META-0004 – Sichtbarkeit

Sortierung, Publikation, Navigation und Admin-ACL werden getrennt im Dialog «Sichtbarkeit» verwaltet; Regeln gemäss ACL-0001–0004.

## Medieneinbindung

### IMAGE-0001 – Seitenkontext

Medienendpunkt verlangt Seitenkontext mit aktueller ACL und tatsächlich gerendertem Link/Bild. Stabile ID und optionaler Slug als Suchhinweis; Legacy-Seiten ohne ID über geprüften Slug. Kein Referenznachweis durch Code oder Klartext. Öffentliche Referenz veröffentlicht das Medium; berechtigte Benutzer können Inhalte speichern. Bibliothekskontext und allgemeine Zugriffsregeln gemäss MEDIA-0006.

### IMAGE-0002 – Referenzen und Picker

Gespeicherte ID-/Legacy-Dateilinks unverändert; Renderer ergänzt Kontext, Picker trennt Vorschau- und Markdown-URL. Medienverwaltung, Vorschau und Exporte müssen funktionieren. Picker, Upload und Medienverwaltung gemäss MEDIA-0001–0006.

## Suche

### SEARCH-0001 – Suchanfrage und Relevanz

Suchfeld im Wiki-Header, Enter/Lupe, GET `/?q=…`; maximal 160 Unicode-Zeichen, normalisierte Leerzeichen, case-insensitive Teilstrings. Alle Begriffe müssen in mindestens einem Feld vorkommen (UND); pro Begriff Titel 8, Schlagwort 5, Beschreibung 3, Inhalt 1 Punkte. Sortierung nach Relevanz, Titel, Slug. Link-/Bildbeschriftungen statt Ziel-URLs, keine Tag-IDs als Suchnamen. Kein Suchindex.

### SEARCH-0002 – Ergebnisse und Zugriff

Ergebnisliste im Content-Bereich, Trefferzahl, verlinkter Titel (ID bevorzugt), Pfad, Textauszug und passende Pills; eigene Hinweise für leer/kein Treffer. Suchbegriff bleibt im Header. Nur veröffentlichte, lesbare Seiten inkl. versteckter Navigationseinträge gemäss ACL-0003; Antwort privat/no-store. Navigationsfilter unabhängig.

## Export

### EXPORT-0001 – Formate und Umfang

Einzelseiten- und Kapitelexport PDF/ODT/ZIP für angemeldete Benutzer; Gäste nur Einzelseiten-PDF. Nur veröffentlichte lesbare Seiten inklusive `nav: false` gemäss ACL-0003. Kapitelreihenfolge anhand Hierarchie/Sortierung. Kapitel-Endpunkt erfordert Anmeldung für alle Formate und Umfänge; Einzelseiten-Endpunkt für PDF öffentlich und ODT angemeldet verfügbar.

### EXPORT-0002 – Bedienung

Gäste sehen nur PDF, angemeldete Benutzer nur «Kapitelstruktur exportieren». Dialog «Seite oder Kapitel exportieren»: Format zuerst (PDF Standard, ODT, ZIP), danach Umfang (Standard «Nur diese Seite»), danach Dokumentoptionen nur bei Kapitelumfang und PDF/ODT (Deckblatt, Inhaltsverzeichnis, Nummerierung). Ausgeblendete Optionen deaktiviert; beim erneuten Öffnen Standardwerte. Nur Abbrechen und Exportieren als Aktionsbuttons. Dialog schliesst bei gültigem nativem Exportstart; Browser meldet Downloadabschluss nicht, serverseitige Fehler bleiben in nativer Antwort sichtbar.

### EXPORT-0003 – PDF und ODT

Einzelseiten-PDF/ODT verwenden Einzelseitenformatierung; Kapiteloptionen nur für Kapitel-PDF/ODT. Pandoc/WeasyPrint, ODT-Referenzvorlage `docs/templates/reference.odt` und editierbarer ODT-Index. PDF-Bilder innerhalb Seitenbreite; ODT-Bildrahmen proportional auf nutzbare Standard-Seitenbreite (Ränder abgezogen), konservativer Fallback 16 cm; kein Upscaling. ODT-Tabellen: Pandoc-Zellstile mit feinen grauen Rahmen (0,5 pt, #C5CCD0) und vollflächigem blaugrauem Tabellenkopf (#E8EEF1), Einzel- und Kapitelexport.

### EXPORT-0004 – ZIP

Beide Umfänge; Markdown mit YAML in `pages/`, referenzierte lokale Medien in `media/`, dedupliziert, relative Verweise auf enthaltene Ziele, Tag-Namen statt IDs; fehlende lokale Referenzen protokollieren (`EXPORT-HINWEISE.txt`). Keine externen Bilder herunterladen, Originaldaten unverändert. PDF-/ODT-Optionen gelten nicht für ZIP.

### EXPORT-0005 – Sichere Aufbereitung

PDF/ODT verwenden pro freigegebener Seite sicheres Wiki-HTML statt ungeprüftem Pandoc-Markdown. Nur validierte lokale Rasterbilder als Bildressourcen; andere Bilder durch Alttext ersetzen. Titel/Beschreibung Klartext. ZIP übernimmt nur tatsächlich gerenderte Medienreferenzen aus freigegebenen Seiten. Technische Sicherheitsgrenzen gemäss SEC-0002.
