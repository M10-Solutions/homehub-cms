# HomeHub CMS – Funktionsspezifikation Administration

**Stand:** 27.11.2026 · Release 1.1.1 (Anwendung 1.1.1-20261127); Betreiberfreigabe am 27.11.2026 erteilt.
Diese Datei ist die konsolidierte Soll-/Ist-Beschreibung des aktuellen Produkts; ältere Abnahmedokumente sind historische Nachweise. SEARCH-001 und die vorangegangenen Erweiterungen sind laut Betreiber abgenommen. CLEANUP-001 / SDD-002: Betreiber bestätigt 24/24 Tests und manuelle Funktionstests. QA-002: NAS-Abnahme mit 25/25 Tests bestätigt. VERSION-001: 26/26 Regressionen bestätigt; Footer nicht separat bestätigt. UI-011: 27/27 Regressionen und manuelle Funktionstests bestätigt.


Content- und Editor-Funktionen: [SPEC-EDITOR.md](SPEC-EDITOR.md).

## Verbindliche SDD-Grundsätze (25.11.2026)

**Architektur und Abhängigkeiten:** HomeHub CMS verwendet möglichst wenige externe Abhängigkeiten. Bei funktional gleichwertigen Lösungen werden native PHP-Komponenten mit klarer Lizenzierung, einfacher Wartbarkeit und geringer technischer Komplexität bevorzugt. Bewährte Drittkomponenten werden nicht ohne konkreten Nutzen durch Eigenentwicklungen ersetzt. Herkunft, Version und Lizenz neuer Drittkomponenten sind nachvollziehbar zu dokumentieren.

**Betriebsverantwortung und Sicherheitsabgrenzung:** HomeHub CMS ist ein Anwendungssoftwareprojekt. Installation, Konfiguration, Absicherung, Überwachung und Wartung der individuellen Docker- und Betriebsumgebung liegen beim Betreiber. Die bereitgestellte Docker-Konfiguration ist eine Referenzinstallation, keine umfassend sicherheitsgeprüfte oder gehärtete Betriebsplattform. Das Projekt verantwortet den eigenen Anwendungscode, die bereitgestellte Referenzkonfiguration, den verantwortungsvollen Einsatz externer Abhängigkeiten und die Dokumentation bekannter Einschränkungen. Eine umfassende Prüfung und Behebung von Schwachstellen der individuellen Betriebsplattform ist ausserhalb des Projektumfangs. Bekannte unmittelbar anwendungsrelevante Risiken bleiben zu berücksichtigen. Ein CVE-freies Basisimage ist kein Freigabekriterium.

**Verhältnismässigkeit und Qualitätssicherung:** HomeHub CMS ist ein nicht kommerziell ausgerichtetes Open-Source-Projekt. Umfang und Tiefe der Qualitätssicherung orientieren sich am vorgesehenen Einsatzzweck, am tatsächlichen Risiko und am konkreten Nutzen für das Projekt. Prüfungen konzentrieren sich auf funktionale Korrektheit, Anwendungssicherheit, rechtliche Anforderungen und zuverlässige Nutzung. Zusätzliche Prüfungen, Nachweise und Optimierungen erfolgen nur bei nachvollziehbarem Mehrwert oder konkretem Risiko. Kommerzielle oder unternehmensspezifische Auditstandards sind nicht Bestandteil des Projektumfangs. Gesetzliche Anforderungen und Lizenzverpflichtungen bleiben unberührt.

**Dokumentation und Wissensmanagement:** Die Projektdokumentation konzentriert sich auf den aktuellen, verbindlichen Stand der Software. Anforderungen, Architekturentscheidungen und betriebsrelevante Informationen werden möglichst redundanzfrei gepflegt. Historische Entwicklungsprotokolle, Zwischenabnahmen und abgeschlossene Arbeitspakete werden nach ihrer Konsolidierung privat archiviert und nicht dauerhaft in der aktiven Projektdokumentation mitgeführt.

## USER-001 – Anmeldung und Benutzerverwaltung
- JSON-Konten `data/config/users.json` mit Passwort-Hash, Benutzername, stabiler `u_<32 hex>`-ID, Rolle, Aktivstatus und optional Vorname, Name, E-Mail. Legacy-Rollen `member`/`visitor` werden auf `editor`/`reader` normalisiert. Alte IDs werden nachregistriert. Login über Wiki-Dialog oder Admin, Session-Cookies HttpOnly/SameSite Strict, Secure bei HTTPS, CSRF und Login-Verzögerung.
- Admin-Tabelle mit Einzelauswahl, Suche, Rollen-/Statusfilter, Sortierung, Neu/Bearbeiten/Löschen, Doppelklick. Benutzername 3–50 ASCII-Zeichen (Buchstaben/Ziffern/Punkt/_/-), eindeutiger Name ignoriert Gross-/Kleinschreibung; Passwort mindestens 12 Zeichen, beim Bearbeiten optional. Profilfelder validiert. Letzter aktiver Admin geschützt; eigenes Konto nicht löschbar über UI/Route. Kein E-Mail-Versand.
- Erstinstallation (UAT-002f): Bei einer frischen Installation ohne `data/config/users.json` und ohne Initialisierungsmarker führt der erste Browseraufruf zur Web-Ersteinrichtung `/setup.php`. Das Formular erstellt den ersten Benutzer zwingend mit Admin-Rolle; Benutzername 3–50 Zeichen und Passwort mindestens 12 Zeichen, inklusive Passwortbestätigung. CSRF-/Origin-Prüfung und eine exklusive Sperre schützen die Erstellung. Nach Erfolg folgt die reguläre Anmeldung (kein automatisches Login). Der persistente Marker `.homehub-initialized` verhindert eine erneute Einrichtung auch bei später fehlender Benutzerdatei; leere/beschädigte Benutzerdateien dürfen kein Setup auslösen. Bereits bestehende Installationen bleiben erhalten. Das interaktive `bin/create-admin.php` bleibt als optionaler CLI-Bootstrap bestehen; bestehende Konten werden nicht überschrieben. Alte Passwortdatei ist kein Login-Speicher mehr.

**Abnahme:** Anmeldung/Abmeldung, aktives/inaktives Konto, Profiländerung, stabile ID und letzter-Admin-Schutz. UAT-002f: Browser-Setup auf isolierter frischer NAS-Instanz, Anmeldung und persistente Inhalte, Sicherheitsfälle S1–S6 (Setup nach Einrichtung gesperrt, ungültige Passwörter, Passwortbestätigung, parallele Versuche, fehlende Benutzerdatei, bestehende Installation) sowie 30/30 QA-Regressionen laut Betreiber am 23.11.2026 bestanden. UAT-INS-03 war am 22.11.2026 bestanden; Release-Update und Restore am 24.11.2026 laut Betreiber bestanden.

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
5. Veröffentlichung oder `nav`-Änderung allein umgeht keine eigene oder geerbte ACL. Medienzugriff gemäss MED-SEC.



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
- Historischer Release 1.0: ungeschützter Medienabruf akzeptiert; durch MED-SEC im Entwicklungsstand abgelöst.

## PUB-001 – Publikationsanforderungen
GPL-3.0-or-later; Drittkomponenten gemäss THIRD-PARTY-NOTICES.md. Reproduzierbarer Composer-Lockfile-Build, optionale .env-Konfiguration und GitHub-QA. Keine produktiven Daten im Repository. Aktuelle Freigabekriterien siehe PROJECT.md; Prüfungen siehe TESTING.md.



## Administrative Teilanforderungen in der Content-Spezifikation

Die folgenden bestehenden Anforderungen werden dort gemeinsam mit ihren Content-Gegenstücken verbindlich gepflegt:

- UI-013 und UI-012: administrative Navigation und Dialoge.
- MED-SEC: Bibliothekskontext und Medienberechtigungen.
- EDIT-001 / ADMIN-001: administrative Seitenrechte im Content-Bereich.

## MEDIA-001–004 / MEDIA-006 – Medienbibliothek
- Manuelle verschachtelte Ordner unter `data/upload`, passender bestehender Ordner kann im Picker vorgeschlagen werden. Liste/Raster, Vorschaubilder, Dateinamensfilter, Ordnerbaum und Breadcrumbs. Picker erlaubt Upload, Ordneranlage, Einfügen mit Alternativtext. Rollen und CSRF schützen API-Operationen.
- JPEG, PNG, WebP, GIF, PDF, TXT; MIME/Endung/Upload prüfen; je Datei höchstens 10 MiB. Admin-Upload bis 20 Dateien und 10 MiB insgesamt pro Anfrage; Mehrfachwahl/Drag-and-drop, Dateiicons und Namen, Löschen der Auswahl/Hochladen innerhalb der Zone; neue Auswahl ersetzt bisherige. Teilfehler werden gemeldet.
- Admin: Auswahl sichtbarer Medien im aktuellen Ordner, Alle/Keine, zentrale Sammelaktionen Löschen/Verschieben, Zielordnerdialog, Unterordnerdialog, pro Karte Markdown kopieren (HTTP-Fallback) und Umbenennen. Kein Überschreiben bestehender Zieldateien. IDs bleiben bei Move/Rename erhalten.
- `data/config/media-index.json` registriert Pfade unter 32-stelligen hexadezimalen IDs (ohne `m_`-Präfix). `/media.php?id=…`; Legacy `/media.php?file=…` bleibt unterstützt. Keine Referenzprüfung beim produktiven Move/Delete; Löschen kann Links ungültig machen. Offline-Migration via `bin/migrate-media-links.php --check/--apply`, Hashmanifest und Backup, eindeutige Alt-URLs in Markdown/HTML-Bildreferenzen; siehe README.md.

**Abnahme:** Mehrfachupload, Auswahl/Filter, Move/Rename und Delete; ID-Link nach Move unverändert erreichbar; Legacy-Links separat prüfen.


## TAG-001–004 – Schlagwörter
- Zentraler Katalog `data/config/tags.json` als Liste `{id,name}`, ID `t_<32 hex>`; YAML `labels` speichert IDs. Namen bleiben case-sensitive, nur exakt gleich geschriebene Namen sind identisch. Maximal 20 Zuordnungen pro Seite, Name 1–40 Zeichen ohne Komma/Zeilenumbruch/reservierte ID.
- Inline-Editor: case-insensitive Vorschlagsuche, Pills mit X, neue Namen eingeben, Tastaturauswahl und Speichern; Abbrechen persistiert nichts. Entfernen aus Seite löscht nicht den Katalogeintrag.
- Admin-Unterseite Schlagwörter mit Suchfilter, Einzelauswahl, Doppelklick, Neu/Bearbeiten/Löschen und Anzahl zugeordneter Seiten (auch Entwürfe). Umbenennung auch bei Verwendung erlaubt; dabei ändern sich nur Katalogdaten. Löschen verwendeter IDs gesperrt. Katalogänderungen unter Sperren, atomar. Lesbare Legacy-Labels werden bei Änderung der Schlagwortzuordnung im Editor zugeordnet; keine automatische Massenmigration. Inhaltsbearbeitung ohne Änderung der Schlagworte bewahrt vorhandene Zuordnungen.

**Abnahme:** Ein Tag auf zwei Seiten, zentral umbenennen, beide zeigen neuen Namen ohne Änderung der Markdown-Dateien; Suche mit abweichender Schreibweise, ZIP enthält Namen; Delete bei Verwendung blockiert.


## TRASH-001 (IDEA-02/17) – abgenommen am 25.11.2026
Seiten werden einzeln oder als Teilbaum in `data/trash/<Eintrag-ID>/content` verschoben. JSON-Metadaten enthalten ursprüngliche Slugs, Löschzeit und Benutzer. Alle betroffenen Seiten müssen vor dem Verschieben die bestehende Verwaltungs-ACL und Lease-Prüfung bestehen. Geschützte Kapitel mit Unterseiten dürfen nicht einzeln gelöscht werden, da sonst geerbte ACL entfallen könnten. Admins allein verwalten den Papierkorb per CSRF-geschütztem POST. Wiederherstellung nur am ursprünglichen Ort ohne Pfad-/ID-Kollision und mit vorhandenem Elternkapitel; aktuelle geerbte ACL gelten und sind ausdrücklich zu bestätigen. Endgültiges Löschen und Leeren erfordern Bestätigung. Medien bleiben unverändert. Keine automatische Frist, keine neue Abhängigkeit. Papierkorb liegt ausserhalb des aktiven Bestands und Webroots. Keine garantierte Transaktion bei Prozessabbruch oder Dateisystemfehlern; Backup bleibt erforderlich.

Die Löschoption ist eine Checkbox (Standard: einzeln); bei geschützten Kapiteln mit Unterseiten ist für Admins die rekursive Option vorgegeben. Editoren erhalten keine Unterseitenanzahl; die serverseitige Schutzprüfung bleibt verbindlich. Papierkorb: Tabelle mit Mehrfachauswahl und zentraler Toolbar; Bestätigung im Dialog. Mehrfachaktionen werden je Eintrag ausgeführt und Konflikte einzeln gemeldet.
