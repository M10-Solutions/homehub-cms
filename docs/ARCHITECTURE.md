# HomeHub CMS – Architektur und Grundsätze

Geltungsstand: Release 1.2.0. Test- und Abnahmenachweise: [ACCEPTANCE.md](ACCEPTANCE.md).

## Entwicklungsmethodik

### SDD-0001 – Architektur und Abhängigkeiten

HomeHub CMS verwendet möglichst wenige externe Abhängigkeiten. Bei funktional gleichwertigen Lösungen werden native PHP-Komponenten mit klarer Lizenzierung, einfacher Wartbarkeit und geringer technischer Komplexität bevorzugt. Bewährte Drittkomponenten werden nicht ohne konkreten Nutzen durch Eigenentwicklungen ersetzt. Herkunft, Version und Lizenz neuer Drittkomponenten sind nachvollziehbar zu dokumentieren.

### SDD-0002 – Betriebsverantwortung und Sicherheitsabgrenzung

HomeHub CMS ist ein Anwendungssoftwareprojekt. Installation, Konfiguration, Absicherung, Überwachung und Wartung der individuellen Docker- und Betriebsumgebung liegen beim Betreiber. Die bereitgestellte Docker-Konfiguration ist eine Referenzinstallation, keine umfassend sicherheitsgeprüfte oder gehärtete Betriebsplattform. Das Projekt verantwortet den eigenen Anwendungscode, die bereitgestellte Referenzkonfiguration, den verantwortungsvollen Einsatz externer Abhängigkeiten und die Dokumentation bekannter Einschränkungen. Eine umfassende Prüfung und Behebung von Schwachstellen der individuellen Betriebsplattform ist ausserhalb des Projektumfangs. Bekannte unmittelbar anwendungsrelevante Risiken bleiben zu berücksichtigen. Ein CVE-freies Basisimage ist kein Freigabekriterium.

### SDD-0003 – Verhältnismässigkeit und Qualitätssicherung

HomeHub CMS ist ein nicht kommerziell ausgerichtetes Open-Source-Projekt. Umfang und Tiefe der Qualitätssicherung orientieren sich am vorgesehenen Einsatzzweck, am tatsächlichen Risiko und am konkreten Nutzen für das Projekt. Prüfungen konzentrieren sich auf funktionale Korrektheit, Anwendungssicherheit, rechtliche Anforderungen und zuverlässige Nutzung. Zusätzliche Prüfungen, Nachweise und Optimierungen erfolgen nur bei nachvollziehbarem Mehrwert oder konkretem Risiko. Kommerzielle oder unternehmensspezifische Auditstandards sind nicht Bestandteil des Projektumfangs. Gesetzliche Anforderungen und Lizenzverpflichtungen bleiben unberührt.

### SDD-0004 – Dokumentation und Wissensmanagement

Die Projektdokumentation konzentriert sich auf den aktuellen, verbindlichen Stand der Software. Anforderungen, Architekturentscheidungen und betriebsrelevante Informationen werden möglichst redundanzfrei gepflegt. Historische Entwicklungsprotokolle und Zwischenunterlagen werden privat archiviert; konsolidierte Entwicklungshistorie und Abnahmen bleiben in den dafür vorgesehenen Dokumenten erhalten.

### SDD-0005 – Massgeblicher Ablageort

Jede Information besitzt genau einen massgeblichen Ablageort.

### SDD-0006 – Geltendes Sollverhalten

Spezifikationen beschreiben ausschliesslich das aktuell geltende Sollverhalten.

### SDD-0007 – Stabile Kennungen

Anforderungen und Architekturgrundsätze besitzen eindeutige, dauerhafte Kennungen nach THEMA-nnnn bzw. FUNKTION-nnnn. Aufgehobene Kennungen werden nicht erneut vergeben; bestehende Testkennungen bleiben erhalten.

### SDD-0008 – Referenzen

Dokumentübergreifende Zusammenhänge werden durch Referenzen statt Wiederholungen beschrieben.

### SDD-0009 – Historie und Abnahme

Entwicklungshistorie und Abnahmen werden getrennt von normativen Anforderungen in CHANGELOG.md bzw. ACCEPTANCE.md geführt.

### SDD-0010 – Backlog

Nicht umgesetzte Anforderungen werden im BACKLOG.md geführt.

### SDD-0011 – Pendenzen

Konkrete offene Arbeitsaufträge und Pendenzen werden in TASKS.md mit Status und Datum geführt.

### SDD-0012 – Historische Nachweise

Historische Testergebnisse werden nicht nachträglich überschrieben; Ergänzungen werden als solche gekennzeichnet.

### SDD-0013 – Entwicklungsworkflow

Aktuelle vollständige ZIP prüfen → Spezifikation und Abnahmekriterien → Änderung → Syntax/Regression → betroffene Dokumentation aktualisieren → Patch ohne produktive Nutzdaten und Vollarchiv → NAS-Abnahme durch Betreiber.

## Design

### DESIGN-0001 – Gemeinsame Oberfläche und Assets

Wiki und Admin nutzen gemeinsamen Header, Sidebar-Account-View, Dialog-Header und Action-Buttons. CSS ist funktionsbezogen aufgeteilt, `layout.css` enthält gemeinsamen Rahmen, `app.css` und `admin.css` die Grundstile. Admin-Benutzer und Tags teilen Tabellen-/Dialogstile. Native Dialoge und Vanilla-JS, Font Awesome lokal, Prism lokal; keine Frontend-Buildkette. Admin-Navigation dauerhaft sichtbar. Drittanbieter-Assets bewusst unverändert.

### DESIGN-0002 – Theme-Architektur

`src/css/theme.css` definiert semantische Dark-Tokens nur auf `:root[data-theme="dark"]`; Komponenten verwenden `var(--theme-…, bisheriger Farbwert)` und behalten ihre Light-Fallbacks. `src/js/theme.js` setzt das Attribut vor dem Rendern, aktualisiert den gemeinsamen `theme-toggle-view.php` und synchronisiert Local Storage. Native Formulare folgen `color-scheme`. Keine Session-, Auth- oder Datenänderung, keine neue Bibliothek. Export-CSS bleibt separat.

## Systemarchitektur

### ARCH-0001 – Laufzeit und Verzeichnisstruktur

PHP 8.3/Apache, Composer PSR-4 `HomeHub\` → `src/lib/`, CommonMark 2.x, Symfony YAML 7.x; PHP mbstring, zip, DOM. Pandoc und WeasyPrint für Dokumente. Compose publiziert Port 8011; `data/` beschreibbar, `src/`, `bin/`, `tests/` und `docs/templates/` read-only eingebunden. Apache-DocumentRoot `src/`, Zugriff auf lib/config gesperrt; Daten und Composer-Abhängigkeiten ausserhalb. UID/GID www-data über HOMEHUB_UID/GID (1000/1001), keine rekursive Besitzänderung am Anwendungscode. OPcache prüft Dateiänderungen. Dockerfile/Entrypoint-Änderungen erfordern Rebuild zur Übernahme in bestehende Images; Bind-Mount-Code nicht.

### ARCH-0002 – Einstiegspunkte

| Datei | Verantwortung |
|---|---|
| src/index.php | Wiki, Login/Logout, berechtigungsgefilterte Navigation, Suche, Inline-Editor und Dialog Sichtbarkeit |
| src/inline.php | JSON Preview/Content-Save mit CSRF, Zugriff und Revision |
| src/page-actions.php | Admin-Seitenoperationen, CSRF und Revision bei Settings/Move |
| src/page.php | Seiten-ID-Resolver mit Zugriffskontrolle |
| src/page-picker-api.php | lesbare veröffentlichte Seiten mit ID |
| src/admin.php | Medien-, Benutzer- und Schlagwortverwaltung |
| src/media-api.php | rollen-/CSRF-geschützte Medienliste und Mutationen für Picker |
| src/media.php | Auslieferung per ID oder Legacy-Pfad mit MED-SEC-Kontext |
| src/export.php / chapter-export.php | Einzel-/Kapitelexport mit Leserechteprüfung |

### ARCH-0003 – Domänenklassen

| Klasse | Verantwortung |
|---|---|
| Page / PageRepository | Lesemodell, YAML, publizierte Seiten, Tag-Auflösung, Renderer |
| Admin | Validierung, atomare Seitenspeicherung, Move/Delete, Preview; kein eigener klassischer Editor |
| DocumentBody / AlignedMarkdown | portabler Titelvorspann, gemeinsame sichere CommonMark-Konfiguration und Ausrichtungsblöcke |
| PageIdentity / PageFolders / NavigationTree | ID-Inventur, sichere Ordnerliste, hierarchische Navigation |
| PermissionService / Session / JSONAuthenticator | Zugriffsregeln, Session-Cookies, JSON-Konten und atomare Benutzeränderungen |
| MediaLibrary / MediaRegistry | sichere Datei-/Ordneroperationen, persistente Medien-ID-Pfad-Zuordnung |
| MediaMover / MediaDeleter / MediaBatch | gesperrte Medienoperationen, Teilerfolg bei Batch |
| MediaOperationLock | gemeinsame Sperre für Seiten-/Medienmutation, Tag-Administration, Migration und ZIP-Export |
| TagRepository / TagAdministration | zentraler ID-Namenskatalog, Schreibsperre, Zuordnungszählung und Löschschutz |
| PageSearch | zustandslose Suche über bereits zugriffsgefilterte publizierte Seiten |
| DocumentExporter / OdtImageFitter / ChapterZipExporter | PDF/ODT, proportionale ODT-Bildrahmen, portables Markdown-ZIP |

### ARCH-0004 – Export und Konverter

Kapitelexport sammelt freigegebene Seiten hierarchisch; PDF/ODT über Pandoc, PDF via WeasyPrint, ODT mit `docs/templates/reference.odt` und DOM/ZipArchive-Nachbearbeitung. OdtImageFitter ermittelt Breite aus Standard-Master-Page/Page-Layout, 16 cm Fallback. ZIP schreibt Markdown/Medien und relative Referenzen, Tag-Namen. Temporäre Exportdateien werden bereinigt.

### ARCH-0005 – Wartungswerkzeuge

CLI unter `bin/`: `create-admin.php` (nur leerer Benutzerspeicher; bestehende Authenticator-Klasse), `register-page-ids.php` (Dry-Run/--apply), `migrate-page-links.php` und `migrate-media-links.php` (--check/--apply mit Hashmanifest/Backups), `run-regression-tests.php`. Historischer Registrierungsaufruf unter tests/ ist nur ein Weiterleiter. Keine automatische Tag-Migration. Legacy-Medien-/Seitenlinks und Rollen bleiben aus Kompatibilitätsgründen unterstützt.

## Datenhaltung

### DATA-0001 – Dateibasierte Persistenz

Dateibasierte, portable Inhalte; keine Datenbank, kein persistenter Suchindex. Stabile Referenzen statt produktiver Massenumschreibungen; explizite CLI-Migration für ältere Links.

`data/content/**/*.md`: YAML mit Seiten-ID `p_` + 32 Hexzeichen und `labels` (Tag-IDs). Kein separater Seitenindex. `data/config/users.json`: stabile `u_`-IDs; `media-index.json`: versionierte Zuordnung von 32 Hexzeichen (ohne Präfix) zu Medienpfaden unter `data/upload/`; `tags.json`: Liste aus ID (`t_` + 32 Hexzeichen) und Name. Namen werden zur Laufzeit aufgelöst. Die Katalog-Umbenennung verändert keine Seiten. Berechtigungen über eigene und vererbte Markdown-Regeln; Medienabruf über MediaAccess mit Seiten- oder Bibliothekskontext.

### DATA-0002 – Konsistenz und Sperren

`MediaOperationLock` verwendet `data/config/media-operations.lock`. Seitenmutationen, MediaMover/Deleter und Tag-Administration verwenden diese Sperre; darunter ggf. eigene Registry-/Tag-/User-Locks. JSON-Mutationen lesen den aktuellen Stand unter separater Lockdatei und schreiben über temporäre Datei/rename. Seiten schreiben atomar pro Datei; Move prüft die Revision innerhalb der Sperre. Inline- und Settings-Speicherung prüfen die erwartete vollständige Revision innerhalb der Operationssperre; Settings prüft zusätzlich vorab. Inline prüft dort auch den Lease-Eigentümer, Settings die Verfügbarkeit der Lease. Dies ist keine globale Transaktion. Batch-Operationen sind keine globale Transaktion.

### DATA-0003 – Papierkorb und Fehlergrenzen

PageTrash nutzt MediaOperationLock und PageEditLease. Originaldateien werden ohne Umschreiben verschoben; bei regulärem Verschiebefehler wird ein Rollback versucht. Kein persistenter Index. Papierkorb unter data/trash ist nicht Teil der aktiven Seitenauflösung. Admin-Endpunkt prüft Rolle und CSRF; Seiten-Endpunkt prüft ACL je betroffener Seite innerhalb der Operationssperre. Kein Schutz gegen alle Prozessabbrüche oder externe parallele Dateisystemänderungen.

## Sicherheit

### SEC-0001 – Metadatenvalidierung

PageMetadata zentralisiert die restriktive Front-Matter-Validierung. PageRepository und PermissionService berücksichtigen die Gültigkeit; Legacy ohne Front Matter bleibt kompatibel. Medienabruf prüft Seiten-ACL oder globale Bibliotheksberechtigung (MED-SEC). Funktionale Zugriffsregeln siehe SPEC-ADMIN.md und SPEC-EDITOR.md.

### SEC-0002 – Exportressourcen

DocumentExporter rendert jede freigegebene Seite separat mit AlignedMarkdown; MediaAccess validiert Bildressourcen vor Pandoc (HTML-Eingabe). ChapterZipExporter verwendet dieselbe Referenzsemantik zur Medienauswahl. Markdown-Quelldateien bleiben unverändert. Keine explizite Netzwerk-/Dateisystem-Sandbox des Konverters; nur vertrauenswürdige schreibberechtigte Benutzer und Medien zulassen.

### SEC-0003 – Betriebsgrenzen

Nur in einem vertrauenswürdigen Netz betreiben, kein direkter Internetzugriff. Bei Zugriff über nicht vertrauenswürdige Netze HTTPS und geeignete Zugriffsbeschränkungen vorsehen. Editor/Admin haben globalen Medienzugriff. Vollständig entfernte Metadaten sind nicht von Legacy-Seiten ohne Front Matter unterscheidbar.

## Qualitätssicherung

### QA-0001 – Isolierte Regression

Das Compose-Profil `qa` verwendet dasselbe Image und dieselbe Build-Definition wie `homehub`, jedoch ohne dessen Datenmount oder Startskript. Read-only Root und Code-Mounts, Netzwerk `none`, Benutzer `www-data` und ein temporäres `/tmp` begrenzen Schreibzugriffe. Der PHP-Runner prüft Voraussetzungen im strikten Modus und führt jeden Test als separaten Prozess aus; die gemeinsame temporäre Ausgabedatei vermeidet Pipe-Deadlocks. Keine zusätzliche Bibliothek.

### QA-0002 – Prüfumfang

Strikter Preflight und vollständige Regression ergänzen die manuelle NAS-/Browser-Abnahme. Keine vollständige automatisierte Browser-Gesamtsuite, kein vollständiger Export-End-to-End-Test und kein vollständiger Penetrationstest. Prüfdrehbuch und Ergebnisse werden separat geführt.

## Versionierung

### VERSION-0001 – Versionsquelle und Format

Die bestehende Anwendungskonfiguration `src/config/app.php` enthält den einzigen Versionswert. `src/index.php` rendert ihn escaped im gemeinsamen Wiki-Footer; CSS nutzt dessen bestehendes Flexlayout. Keine zusätzliche Klasse, Nutzdatendatei oder Abhängigkeit. Format `Major.Minor.Patch-YYYYMMDD`: Major für grössere Änderungen, Minor für Erweiterungen, Patch für Wartungsreleases, Datum für die letzte Revision. Keine automatische Datumsänderung.

## Veröffentlichung

### RELEASE-0001 – Lizenz und Paketierung

GPL-3.0-or-later; Drittkomponenten gemäss [THIRD-PARTY-NOTICES.md](../THIRD-PARTY-NOTICES.md). Reproduzierbarer Composer-Lockfile-Build, optionale .env-Konfiguration und GitHub-QA. Keine produktiven Daten oder privaten Screenshots im Repository; kein fertiges Docker-Image verteilen.

### RELEASE-0002 – Freigabe

Funktionsumfang vor der abschliessenden Release-Abnahme einfrieren. Veröffentlichung erst nach ausdrücklicher Betreiberfreigabe. Historische Freigaben und Testergebnisse separat dokumentieren.
