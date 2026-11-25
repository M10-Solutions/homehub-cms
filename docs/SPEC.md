# HomeHub CMS – Funktionsspezifikation

**Stand:** 25.11.2026 · HomeHub 1.0-20261124; private Betreiberfreigabe, öffentliche Freigabe offen.
Diese Datei ist die konsolidierte Soll-/Ist-Beschreibung des aktuellen Produkts; ältere Abnahmedokumente sind historische Nachweise. SEARCH-001 und die vorangegangenen Erweiterungen sind laut Betreiber abgenommen. CLEANUP-001 / SDD-002: Betreiber bestätigt 24/24 Tests und manuelle Funktionstests. QA-002: NAS-Abnahme mit 25/25 Tests bestätigt. VERSION-001: 26/26 Regressionen bestätigt; Footer nicht separat bestätigt. UI-011: 27/27 Regressionen und manuelle Funktionstests bestätigt.

## Verbindliche SDD-Grundsätze (25.11.2026)

**Architektur und Abhängigkeiten:** HomeHub CMS verwendet möglichst wenige externe Abhängigkeiten. Bei funktional gleichwertigen Lösungen werden native PHP-Komponenten mit klarer Lizenzierung, einfacher Wartbarkeit und geringer technischer Komplexität bevorzugt. Bewährte Drittkomponenten werden nicht ohne konkreten Nutzen durch Eigenentwicklungen ersetzt. Herkunft, Version und Lizenz neuer Drittkomponenten sind nachvollziehbar zu dokumentieren.

**Betriebsverantwortung und Sicherheitsabgrenzung:** HomeHub CMS ist ein Anwendungssoftwareprojekt. Installation, Konfiguration, Absicherung, Überwachung und Wartung der individuellen Docker- und Betriebsumgebung liegen beim Betreiber. Die bereitgestellte Docker-Konfiguration ist eine Referenzinstallation, keine umfassend sicherheitsgeprüfte oder gehärtete Betriebsplattform. Das Projekt verantwortet den eigenen Anwendungscode, die bereitgestellte Referenzkonfiguration, den verantwortungsvollen Einsatz externer Abhängigkeiten und die Dokumentation bekannter Einschränkungen. Eine umfassende Prüfung und Behebung von Schwachstellen der individuellen Betriebsplattform ist ausserhalb des Projektumfangs. Bekannte unmittelbar anwendungsrelevante Risiken bleiben zu berücksichtigen. Ein CVE-freies Basisimage ist kein Freigabekriterium.

**Verhältnismässigkeit und Qualitätssicherung:** HomeHub CMS ist ein nicht kommerziell ausgerichtetes Open-Source-Projekt. Umfang und Tiefe der Qualitätssicherung orientieren sich am vorgesehenen Einsatzzweck, am tatsächlichen Risiko und am konkreten Nutzen für das Projekt. Prüfungen konzentrieren sich auf funktionale Korrektheit, Anwendungssicherheit, rechtliche Anforderungen und zuverlässige Nutzung. Zusätzliche Prüfungen, Nachweise und Optimierungen erfolgen nur bei nachvollziehbarem Mehrwert oder konkretem Risiko. Kommerzielle oder unternehmensspezifische Auditstandards sind nicht Bestandteil des Projektumfangs. Gesetzliche Anforderungen und Lizenzverpflichtungen bleiben unberührt.

**Dokumentation und Wissensmanagement:** Die Projektdokumentation konzentriert sich auf den aktuellen, verbindlichen Stand der Software. Anforderungen, Architekturentscheidungen und betriebsrelevante Informationen werden möglichst redundanzfrei gepflegt. Historische Entwicklungsprotokolle, Zwischenabnahmen und abgeschlossene Arbeitspakete werden nach ihrer Konsolidierung privat archiviert und nicht dauerhaft in der aktiven Projektdokumentation mitgeführt.

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
- Reader liest veröffentlichte berechtigte Inhalte; Editor verwaltet berechtigte Seiten und Entwürfe inklusive Erstellung, Publikation, Rückzug, Verschiebung und Löschung; Admin verwaltet zusätzlich Benutzer und Seitenberechtigungen. Die verbindliche Rollenmatrix und `published`/`nav`-Regeln stehen in **ACL-001** unten. Seitenlinks, Suche und Exporte prüfen Leserechte serverseitig. Seitenberechtigungen schützen nicht separat ausgelieferte Medien-URLs: deren Kenntnis genügt derzeit für den Abruf.

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

## MEDIA-001–004 / MEDIA-006 – Medienbibliothek
- Manuelle verschachtelte Ordner unter `data/upload`, passender bestehender Ordner kann im Picker vorgeschlagen werden. Liste/Raster, Vorschaubilder, Dateinamensfilter, Ordnerbaum und Breadcrumbs. Picker erlaubt Upload, Ordneranlage, Einfügen mit Alternativtext. Rollen und CSRF schützen API-Operationen.
- JPEG, PNG, WebP, GIF, PDF, TXT; MIME/Endung/Upload prüfen; je Datei höchstens 10 MiB. Admin-Upload bis 20 Dateien und 10 MiB insgesamt pro Anfrage; Mehrfachwahl/Drag-and-drop, Dateiicons und Namen, Löschen der Auswahl/Hochladen innerhalb der Zone; neue Auswahl ersetzt bisherige. Teilfehler werden gemeldet.
- Admin: Auswahl sichtbarer Medien im aktuellen Ordner, Alle/Keine, zentrale Sammelaktionen Löschen/Verschieben, Zielordnerdialog, Unterordnerdialog, pro Karte Markdown kopieren (HTTP-Fallback) und Umbenennen. Kein Überschreiben bestehender Zieldateien. IDs bleiben bei Move/Rename erhalten.
- `data/config/media-index.json` registriert Pfade unter 32-stelligen hexadezimalen IDs (ohne `m_`-Präfix). `/media.php?id=…`; Legacy `/media.php?file=…` bleibt unterstützt. Keine Referenzprüfung beim produktiven Move/Delete; Löschen kann Links ungültig machen. Offline-Migration via `bin/migrate-media-links.php --check/--apply`, Hashmanifest und Backup, eindeutige Alt-URLs in Markdown/HTML-Bildreferenzen; siehe README.md.

**Abnahme:** Mehrfachupload, Auswahl/Filter, Move/Rename und Delete; ID-Link nach Move unverändert erreichbar; Legacy-Links separat prüfen.

## USER-001 – Anmeldung und Benutzerverwaltung
- JSON-Konten `data/config/users.json` mit Passwort-Hash, Benutzername, stabiler `u_<32 hex>`-ID, Rolle, Aktivstatus und optional Vorname, Name, E-Mail. Legacy-Rollen `member`/`visitor` werden auf `editor`/`reader` normalisiert. Alte IDs werden nachregistriert. Login über Wiki-Dialog oder Admin, Session-Cookies HttpOnly/SameSite Strict, Secure bei HTTPS, CSRF und Login-Verzögerung.
- Admin-Tabelle mit Einzelauswahl, Suche, Rollen-/Statusfilter, Sortierung, Neu/Bearbeiten/Löschen, Doppelklick. Benutzername 3–50 ASCII-Zeichen (Buchstaben/Ziffern/Punkt/_/-), eindeutiger Name ignoriert Gross-/Kleinschreibung; Passwort mindestens 12 Zeichen, beim Bearbeiten optional. Profilfelder validiert. Letzter aktiver Admin geschützt; eigenes Konto nicht löschbar über UI/Route. Kein E-Mail-Versand.
- Erstinstallation (UAT-002f): Bei einer frischen Installation ohne `data/config/users.json` und ohne Initialisierungsmarker führt der erste Browseraufruf zur Web-Ersteinrichtung `/setup.php`. Das Formular erstellt den ersten Benutzer zwingend mit Admin-Rolle; Benutzername 3–50 Zeichen und Passwort mindestens 12 Zeichen, inklusive Passwortbestätigung. CSRF-/Origin-Prüfung und eine exklusive Sperre schützen die Erstellung. Nach Erfolg folgt die reguläre Anmeldung (kein automatisches Login). Der persistente Marker `.homehub-initialized` verhindert eine erneute Einrichtung auch bei später fehlender Benutzerdatei; leere/beschädigte Benutzerdateien dürfen kein Setup auslösen. Bereits bestehende Installationen bleiben erhalten. Das interaktive `bin/create-admin.php` bleibt als optionaler CLI-Bootstrap bestehen; bestehende Konten werden nicht überschrieben. Alte Passwortdatei ist kein Login-Speicher mehr.

**Abnahme:** Anmeldung/Abmeldung, aktives/inaktives Konto, Profiländerung, stabile ID und letzter-Admin-Schutz. UAT-002f: Browser-Setup auf isolierter frischer NAS-Instanz, Anmeldung und persistente Inhalte, Sicherheitsfälle S1–S6 (Setup nach Einrichtung gesperrt, ungültige Passwörter, Passwortbestätigung, parallele Versuche, fehlende Benutzerdatei, bestehende Installation) sowie 30/30 QA-Regressionen laut Betreiber am 23.11.2026 bestanden. UAT-INS-03 war am 22.11.2026 bestanden; Release-Update und Restore am 24.11.2026 laut Betreiber bestanden.

## TAG-001–004 – Schlagwörter
- Zentraler Katalog `data/config/tags.json` als Liste `{id,name}`, ID `t_<32 hex>`; YAML `labels` speichert IDs. Namen bleiben case-sensitive, nur exakt gleich geschriebene Namen sind identisch. Maximal 20 Zuordnungen pro Seite, Name 1–40 Zeichen ohne Komma/Zeilenumbruch/reservierte ID.
- Seiteneinstellungen: case-insensitive Vorschlagsuche, Pills mit X, neue Namen eingeben, Tastaturauswahl und Speichern; Abbrechen persistiert nichts. Entfernen aus Seite löscht nicht den Katalogeintrag.
- Admin-Unterseite Schlagwörter mit Suchfilter, Einzelauswahl, Doppelklick, Neu/Bearbeiten/Löschen und Anzahl zugeordneter Seiten (auch Entwürfe). Umbenennung auch bei Verwendung erlaubt; dabei ändern sich nur Katalogdaten. Löschen verwendeter IDs gesperrt. Katalogänderungen unter Sperren, atomar. Lesbare Legacy-Labels werden beim expliziten Speichern der Seiteneinstellungen zugeordnet; keine automatische Massenmigration. Inhaltsbearbeitung bewahrt vorhandene Zuordnungen.

**Abnahme:** Ein Tag auf zwei Seiten, zentral umbenennen, beide zeigen neuen Namen ohne Änderung der Markdown-Dateien; Suche mit abweichender Schreibweise, ZIP enthält Namen; Delete bei Verwendung blockiert.

## SEARCH-001 – Volltext- und Schlagwortsuche
- Suchfeld im Wiki-Header, Enter/Lupe, GET `/?q=…`; maximal 160 Unicode-Zeichen, normalisierte Leerzeichen, case-insensitive Teilstrings. Alle Begriffe müssen in mindestens einem Feld vorkommen (UND); pro Begriff Titel 8, Schlagwort 5, Beschreibung 3, Inhalt 1 Punkte. Sortierung nach Relevanz, Titel, Slug. Link-/Bildbeschriftungen statt Ziel-URLs, keine Tag-IDs als Suchnamen.
- Ergebnisliste im Content-Bereich, Trefferzahl, verlinkter Titel (ID bevorzugt), Pfad, Textauszug und passende Pills; eigene Hinweise für leer/kein Treffer. Suchbegriff bleibt im Header. Nur veröffentlichte, lesbare Seiten inkl. versteckter Navigationseinträge; kein Suchindex, Antwort privat/no-store. Navigationsfilter bleibt unabhängig.

**Abnahme:** Titel-, Text-, Tag- und Mehrwortsuche, leere Suche, keine Treffer, geschützte/unsichtbare Seiten, aktuelle Tag-Namen, Link nach Move.

## EXPORT-001–003 – PDF, ODT und ZIP
- Einzelne Seite PDF/ODT und Kapitelexport PDF/ODT/ZIP; Umfang Seite oder mit Unterseiten, nur veröffentlichte lesbare Seiten inklusive `nav: false`. Kapitelreihenfolge anhand Hierarchie/Sortierung. PDF/ODT optional Deckblatt, Inhaltsverzeichnis und Nummerierung beim Kapitelexport. Pandoc/WeasyPrint, ODT-Referenzvorlage `docs/templates/reference.odt` und editierbarer ODT-Index.
- PDF-Bilder innerhalb Seitenbreite; ODT-Bildrahmen proportional auf nutzbare Standard-Seitenbreite (Ränder abgezogen), konservativer Fallback 16 cm; kein Upscaling.
- ZIP: Markdown mit YAML in `pages/`, referenzierte lokale Medien in `media/`, dedupliziert, relative Verweise auf enthaltene Ziele, Tag-Namen statt IDs; fehlende lokale Referenzen protokollieren (`EXPORT-HINWEISE.txt`). Keine externen Bilder herunterladen, Originaldaten unverändert. PDF-/ODT-Optionen gelten nicht für ZIP.

**Abnahme:** Einzelseite/Kapitel in allen angebotenen Formaten exportieren; Berechtigungen, Bilder, relative Referenzen und Tag-Namen prüfen.

## UI-001–010 – Gemeinsames Erscheinungsbild
- Fester dunkler Hub-Header (`--hub-header-bg`), gemeinsames Logo/Titel/Untertitel, responsive Navigation. Admin rechts «Zum Wiki», Wiki mit Admin-Link für Administratoren und Suchfeld. Profil unten links in beiden Bereichen: Vor-/Nachname (Fallback Benutzername), zweite Zeile Benutzername, Rollenicon mit Tooltip.
- Gemeinsame Dialog-Header in Hub-Farbe mit weissem Titel/Schliessen-Icon, Aktionsbuttons mit Icons, primär blau `#4263eb`, Gefahr dunkelrot `#9c2434`, neutraler Rahmen, Fokus/Disabled-Zustände. Native Browser-Bestätigungen unverändert. Seiten-Löschbutton dunkelrot.

**Abnahme:** Admin, Wiki, mobile Darstellung und alle Dialoge visuell prüfen.

## QA-001 / CLEANUP-001 – Qualitätsanforderungen und Grenzen
- Kein Überschreiben produktiver `data/`-Dateien bei Code-Patches, keine Datenmigration im Cleanup. PHP 8.3, Composer, Apache, Docker Compose; Details in ARCHITECTURE und README.
- Regressionstests arbeiten temporär; NAS-/Browser-Abnahme bleibt zusätzlich erforderlich. Keine automatisierte Browser-Gesamtsuite und kein vollständiger Export-End-to-End-Test.
- Bewusst nicht implementiert: Medien-Verwendungsprüfung vor Löschung, automatisches Umschreiben alter Pfadlinks beim Move, Tag-Zusammenführen/erzwungenes Löschen verwendeter Tags, Editor-Paste/Drop von Bildern, persistenter Suchindex, separate Medien-ACLs. Keine neue Infrastruktur im Cleanup.

## QA-002 – Isolierte Testumgebung
- Compose-Profil `qa`: kurzlebiger CLI-Container mit gemeinsamem HomeHub-Image, ohne produktiven Datenmount, Netzwerk oder Webserver; Quellcode und Root-Dateisystem schreibgeschützt, `/tmp` beschreibbar.
- Strikter Preflight prüft PHP, Erweiterungen, Composer-Klassen und temporären Speicher. Fehlende Voraussetzungen verhindern den Lauf.
- Bericht pro Test mit Ausgabe, Status, Laufzeit und Gesamtbilanz. Exit-Codes 0 (Erfolg), 1 (Testfehler), 2 (Voraussetzungen fehlen). Lokaler nicht-strikter Modus bleibt für Teilprüfungen erhalten.
- Abnahme: `docker compose --profile qa run --rm --no-deps --build qa` liefert 25/25 und Exit 0, ohne den produktiven Container oder dessen Daten zu verändern. Browser- und vollständige PDF-Export-Abnahme bleiben separat.

## VERSION-001 – App-Version
- Zentraler String `version` in `src/config/app.php`, initial `0.8-20261122`. Format `Major.Minor-YYYYMMDD`; Major für grössere Änderungen, Minor für kleinere Erweiterungen und Bugfix-Releases, Datum für die letzte Revision. Reine Bugfixes können nur das Datum ändern. Keine automatische Datumsänderung.
- Anzeige als HTML-escaped Text rechts im Content-Footer auf allen Wiki-Ansichten, auch ohne Anmeldung und bei der Suche. Admin-Footer unverändert.

**Abnahme:** Version in Config ändern, Wiki neu laden und Anzeige rechts im Footer prüfen; auch mobile Darstellung und Suchergebnisse prüfen.

## UI-011: Light-/Dark-Mode
- Standard ist das bisherige Light-Design. Ein gemeinsamer Header-Schalter mit Sonne/Mond und `role=switch` wechselt ohne Neuladen zwischen Light und Dark, auch vor Anmeldung.
- Die Auswahl wird unter `homehub.theme` im Local Storage pro Browser und Origin gespeichert, nicht pro Benutzer. Bei deaktiviertem Speicher funktioniert der Wechsel für die aktuelle Seite. Andere offene Tabs derselben Origin werden synchronisiert.
- Content, Navigation, Suche, Markdown, Inline-Editor, Dialoge und Administration verwenden dieselben Dark-Farbrollen. Primär-/Gefahr-Aktionen behalten ihre semantischen Farben. Bildinhalte und Syntaxhighlighting werden nicht invertiert. Exportformatierung bleibt unverändert.
- Abnahme: beide Modi, Reload, Login/Logout, Wechsel Wiki/Admin, Tastatur, schmale Viewports, alle Dialoge, Export und 27 Regressionstests.

## UAT-001 / SDD-003 – Release-Abnahme
- Feature Freeze auf `0.9-20261122`; UAT-Konzept und Testkatalog siehe TESTING.md. UAT-001 durchgeführt (58/59); UAT-002a–f und UI-012 danach abgenommen, zuletzt 35/35 NAS-Regressionen. Endgültige Freigabe siehe TESTING.md. BACKLOG.md enthält unverbindliche Ideen, keine aktuelle Funktionsanforderung.

## UAT-002a – Einheitliche Mitteilungen und Bestätigungen
- In Wiki und Administration stehen gemeinsame `HomeHubFeedback.notify()`- und `HomeHubFeedback.confirm()`-Funktionen zur Verfügung.
- Erfolg/Info: automatisch nach fünf Sekunden ausgeblendet, manuell schliessbar. Fehler: standardmässig persistent und manuell schliessbar.
- Seiten- und Medienlöschung sowie das Verwerfen ungespeicherter Inline-Änderungen verlangen eine modale Bestätigung im HomeHub-Design. Abbrechen, Escape und Schliessen ohne Bestätigung lösen keine Aktion aus.
- Servermeldungen der Admin-Verwaltung werden nach dem Laden als Notifications angezeigt; bei deaktiviertem JavaScript bleiben sie als bisherige Hinweise sichtbar.
- Neue Komponenten verwenden die Light-/Dark-Farbvariablen; keine Änderungen an Rechten, Exporten oder Speicherformat.
- Weiterführende dialoginterne Validierung und Berechtigungs-UX bleiben separate UAT-FIX-007/008-Aufgaben.
- Testanleitung: `TESTING.md`.

## UAT-002c – Historie der redaktionellen Navigation
Die ursprüngliche UAT-002c-Admin-Entwurfsansicht wurde durch UAT-002c.1 und UAT-002c.2 **abgelöst**. Aktuell sehen Admin und berechtigte Editoren Entwürfe standardmässig, unabhängig von `nav`. Die Kapitelvorauswahl bei der Seitenerstellung bleibt erhalten. **Verbindliche Regeln siehe ACL-001.**

## UAT-002c.2 – Editor-Inhaltsverwaltung (0.13-20261123)
Editoren dürfen berechtigte Seiten erstellen (als Draft unter berechtigtem Kapitel), veröffentlichen, bearbeiten, verschieben und löschen; Benutzer- und ACL-Verwaltung bleibt Admin-Aufgabe. Kein Page Owner. Details: `TESTING.md`.


## ACL-001 – Verbindliches Rollen-, Veröffentlichungs- und Navigationsmodell (SDD-004)

**Geltung:** Diese Regeln ersetzen widersprechende ältere Formulierungen in NAV-001, EDIT-001/ADMIN-001 sowie historischen UAT-002c-Abschnitten. Keine Page Ownership und kein Genehmigungsworkflow. `published`, `nav` und die ACL sind getrennte Eigenschaften.

### Rollen und Aktionen

| Aktion | Admin | Editor | Reader / anonym |
| --- | --- | --- | --- |
| Veröffentlichte Seite lesen | Ja | Nur mit Leserecht | Nur mit Leserecht |
| Draft in redaktioneller Navigation sehen/lesen | Alle | Nur mit eigener und geerbter ACL | Nein |
| Berechtigte Seite / Draft inhaltlich bearbeiten | Ja | Ja | Nein |
| Neue Seite erstellen | Ja | Als Draft unter bestehendem berechtigtem Kapitel | Nein |
| Titel/Beschreibung/Tags/Sortierung/`published`/`nav` bearbeiten | Ja | Auf berechtigter Seite | Nein |
| Seite veröffentlichen oder zurückziehen | Ja | Auf berechtigter Seite | Nein |
| Seite verschieben/umbenennen | Ja | Nur bei berechtigter Quelle **und** berechtigtem Zielkapitel | Nein |
| Seite löschen | Ja | Nur bei Berechtigung auf die Seite | Nein |
| `visibility`/`allowed_roles`/`allowed_users` ändern | Ja | Nein | Nein |
| Benutzer und administrative Konfiguration verwalten | Ja | Nein | Nein |

Für Editoren gelten die eigene Seiten-ACL und sämtliche existierenden Markdown-Vorfahrenregeln. Verzeichnisse ohne Markdown besitzen keine eigene ACL. Neue Editor-Seiten starten als Draft und erben die geltenden Kapiteleinschränkungen; Editoren können sie nicht durch eigene ACL-Werte erweitern. Auch POST/API-Mutationen müssen diese Grenzen serverseitig prüfen. Ein Admin hat administrativen Seitenzugriff. Die Rollenbezeichnung allein hebt für Editoren keine Kapitelsperre auf.

### `published` × `nav` – vier Fälle

| `published` | `nav` | Redaktionelle Navigation (Admin / berechtigter Editor) | Reader-Navigation | Reguläre Suche, Direktlink, Export |
| --- | --- | --- | --- | --- |
| `true` | `true` | Normaler Eintrag | Bei Leserecht sichtbar | Bei Leserecht verfügbar |
| `true` | `false` | Kein Eintrag | Kein Eintrag | Bei Leserecht verfügbar |
| `false` | `true` | *Kursiv*, Label **Draft** | Unsichtbar | Nicht verfügbar |
| `false` | `false` | *Kursiv*, **Draft**, Auge durchgestrichen | Unsichtbar | Nicht verfügbar |

`published` steuert die Veröffentlichung, **nicht** die Kapitel-ACL; es wird nicht von Vorfahren geerbt. `nav` steuert die normale Navigation veröffentlichter Seiten, **nicht** ihre Leseberechtigung. Bei Drafts wird `nav` nur als künftige Navigationspräferenz gespeichert; die redaktionelle Entwurfsnavigation zeigt sie unabhängig davon. Ein Draft kann von berechtigten redaktionellen Rollen über den gesonderten Draft-Zugriff geöffnet werden, nicht über die reguläre öffentliche Route. Reguläre Suche, Seitenpicker und PDF/ODT/ZIP-Exporte enthalten nur veröffentlichte und lesbare Seiten. Auch bei `nav: false` bleiben veröffentlichte Seiten über Suche und Direktlink erreichbar, sofern die ACL es erlaubt.

### Abnahmekriterien

1. Alle vier Kombinationen von `published`/`nav` mit Admin, berechtigtem Editor, gesperrtem Editor, Reader und anonym prüfen; Navigation, direkte öffentliche Route, Draft-Route, Suche, Picker und Export vergleichen.
2. Editor erstellt unter berechtigtem bestehendem Kapitel einen Draft; Veröffentlichung, Rückzug, Umbenennung, Verschiebung und Löschung sind dort möglich; in gesperrtem Kapitel nicht.
3. Verschiebung in ein nicht berechtigtes Zielkapitel scheitert serverseitig ohne Dateiveränderung.
4. Manipulierte Requests ändern keine ACL-Felder durch Editoren; Reader kann keine Mutationen ausführen.
5. Veröffentlichung oder `nav`-Änderung allein umgeht keine eigene oder geerbte ACL. Medien-URL-Zugriff bleibt ein separat dokumentiertes Sicherheitsrisiko.



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

## UAT-002b – Responsive Navigation

- .1 Desktop oberhalb 1024 CSS-Pixel: bisherige Navigation, Wiki-Klappzustand und Breite unverändert.
- .2 Bis einschliesslich 1024 CSS-Pixel: Navigation initial geschlossen, Content ohne Sidebar-Margin.
- .3 Gemeinsamer Header-Button öffnet die links fixierte Navigation mit Backdrop; X, Backdrop und Escape schliessen sie. Bei geöffnetem Drawer ist der Hauptinhalt inert und die Seitenscrollbewegung gesperrt.
- .4 Normale Linkauswahl schliesst die Navigation; modifizierte Klicks bleiben unverändert. Kontextaktionen schliessen den Drawer ebenfalls.
- .5 Bestehende Filter, Kapitelstruktur, Kontextaktionen und Benutzerprofil bleiben erhalten; Touch-Ziele werden vergrössert.
- .6 Wiki und angemeldete Verwaltung nutzen dasselbe Script und CSS. Admin-Login ohne Drawer.
- .7 Kompakter Header; auf kleinen Smartphones zweizeilige Wiki-Suche, Breadcrumbs ausgeblendet, Markenname bis 380px ausgeblendet.
- .8 Theme-Variablen für Light/Dark; Desktop- und Drucklayout nicht durch Drawer-Regeln verändert.
- .9 Mobile Sichtbarkeit wird nicht gespeichert; Desktop-localStorage bleibt unverändert. Wechsel des Breakpoints und pageshow schliessen den Drawer.
- Ohne JavaScript bleibt die bisherige Navigation als Fallback erhalten. Keine Änderungen an ACL oder serverseitiger Navigation.
- Status: abgenommen am 24.11.2026. Alle 11 NAS-Abnahmekriterien erfüllt, vollständige Regression 34/34 laut Betreiber. Regression: `tests/uat-002b.php`.

### NAS-Abnahmenachweis (24.11.2026)

Betreiber bestätigt Desktop-Navigation inklusive Breitenverstellung, Tablet-Hochformat, kompakten Smartphone-Header ohne horizontale Überläufe, Hamburger/X/Aussenklick, automatisches Schliessen bei Seitenauswahl, Filter/Kapitel/Kontextmenüs, Benutzerprofil, Verwaltung, Light-/Dark-Mode, Orientierungswechsel und Erhalt der Desktop-Einstellungen sowie 34/34 Regressionstests.


## SEC-1.0 – Metadaten-Hardening (24.11.2026)

- Gemeinsamer Parser für Wiki und Admin: ungültiges, unvollständiges oder typwidriges Front Matter wird abgewiesen. Fehlerhafte Seiten werden nicht veröffentlicht und liefern keinen Inhalt; die ACL-Vererbung bleibt für Nicht-Admins gesperrt. Gültige Legacy-Seiten ohne Front Matter behalten ihre bisherigen Defaults. Vollständig entfernte Metadaten sind nicht von Legacy-Seiten unterscheidbar.
- Admin-Ausnahme für gültige Nachfahrenseiten bleibt erhalten; beschädigte Seiten selbst sind auch für Admins nicht lesbar. Reparatur über gesicherten Dateizugriff.
- Neuer Integrationstest `security-metadata.php`; lokale Regression 32/35, drei Tests wegen fehlendem Composer bzw. ZipArchive nicht ausführbar. NAS-Abnahme am 24.11.2026 laut Betreiber: 35/35 bestanden.
- Medien-ACL unverändert: Variante A am 24.11.2026 ausdrücklich akzeptiert. Keine vertraulichen Medien über die bisherige öffentliche Medien-URL bereitstellen. Release freigegeben.

## PUB-001 – Publikationsanforderungen
GPL-3.0-or-later; Drittkomponenten gemäss THIRD-PARTY-NOTICES.md. Reproduzierbarer Composer-Lockfile-Build, optionale .env-Konfiguration und GitHub-QA. Keine produktiven Daten im Repository. Aktuelle Freigabekriterien siehe PROJECT.md; Prüfungen siehe TESTING.md.
