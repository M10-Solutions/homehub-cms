# HomeHub CMS – Architektur und Entscheidungen

**Stand:** 26.11.2026 · Release 1.1.0 (Anwendung 1.1-20261126); abschliessend abgenommen und zur Veröffentlichung freigegeben; GitHub-Publikation noch nicht bestätigt.

## Verbindliche SDD-Grundsätze (25.11.2026)

**Architektur und Abhängigkeiten:** HomeHub CMS verwendet möglichst wenige externe Abhängigkeiten. Bei funktional gleichwertigen Lösungen werden native PHP-Komponenten mit klarer Lizenzierung, einfacher Wartbarkeit und geringer technischer Komplexität bevorzugt. Bewährte Drittkomponenten werden nicht ohne konkreten Nutzen durch Eigenentwicklungen ersetzt. Herkunft, Version und Lizenz neuer Drittkomponenten sind nachvollziehbar zu dokumentieren.

**Betriebsverantwortung und Sicherheitsabgrenzung:** HomeHub CMS ist ein Anwendungssoftwareprojekt. Installation, Konfiguration, Absicherung, Überwachung und Wartung der individuellen Docker- und Betriebsumgebung liegen beim Betreiber. Die bereitgestellte Docker-Konfiguration ist eine Referenzinstallation, keine umfassend sicherheitsgeprüfte oder gehärtete Betriebsplattform. Das Projekt verantwortet den eigenen Anwendungscode, die bereitgestellte Referenzkonfiguration, den verantwortungsvollen Einsatz externer Abhängigkeiten und die Dokumentation bekannter Einschränkungen. Eine umfassende Prüfung und Behebung von Schwachstellen der individuellen Betriebsplattform ist ausserhalb des Projektumfangs. Bekannte unmittelbar anwendungsrelevante Risiken bleiben zu berücksichtigen. Ein CVE-freies Basisimage ist kein Freigabekriterium.

**Verhältnismässigkeit und Qualitätssicherung:** HomeHub CMS ist ein nicht kommerziell ausgerichtetes Open-Source-Projekt. Umfang und Tiefe der Qualitätssicherung orientieren sich am vorgesehenen Einsatzzweck, am tatsächlichen Risiko und am konkreten Nutzen für das Projekt. Prüfungen konzentrieren sich auf funktionale Korrektheit, Anwendungssicherheit, rechtliche Anforderungen und zuverlässige Nutzung. Zusätzliche Prüfungen, Nachweise und Optimierungen erfolgen nur bei nachvollziehbarem Mehrwert oder konkretem Risiko. Kommerzielle oder unternehmensspezifische Auditstandards sind nicht Bestandteil des Projektumfangs. Gesetzliche Anforderungen und Lizenzverpflichtungen bleiben unberührt.

**Dokumentation und Wissensmanagement:** Die Projektdokumentation konzentriert sich auf den aktuellen, verbindlichen Stand der Software. Anforderungen, Architekturentscheidungen und betriebsrelevante Informationen werden möglichst redundanzfrei gepflegt. Historische Entwicklungsprotokolle, Zwischenabnahmen und abgeschlossene Arbeitspakete werden nach ihrer Konsolidierung privat archiviert und nicht dauerhaft in der aktiven Projektdokumentation mitgeführt.

## Prinzipien
Dateibasierte, portable Inhalte; keine Datenbank, kein persistenter Suchindex. Stabile Referenzen statt produktiver Massenumschreibungen. Möglichst wenige Abhängigkeiten; explizite CLI-Migration für ältere Links. SPEC-EDITOR.md und SPEC-ADMIN.md beschreibt verbindliches Verhalten, TASKS.md Status und offene Arbeiten.

## Laufzeit und Verzeichnisstruktur
PHP 8.3/Apache, Composer PSR-4 `HomeHub\` → `src/lib/`, CommonMark 2.x, Symfony YAML 7.x; PHP mbstring, zip, DOM. Pandoc und WeasyPrint für Dokumente. Compose publiziert Port 8011; `data/` beschreibbar, `src/`, `bin/`, `tests/` und `docs/templates/` read-only eingebunden. Apache-DocumentRoot `src/`, Zugriff auf lib/config gesperrt; Daten und Composer-Abhängigkeiten ausserhalb. UID/GID www-data über HOMEHUB_UID/GID (1000/1001), keine rekursive Besitzänderung am Anwendungscode. OPcache prüft Dateiänderungen. Dockerfile/Entrypoint-Änderungen erfordern Rebuild zur Übernahme in bestehende Images; Bind-Mount-Code nicht.

## Einstiegspunkte
| Datei | Verantwortung |
|---|---|
| src/index.php | Wiki, Login/Logout, berechtigungsgefilterte Navigation, Suche, Inline-Editor und Seiteneinstellungen |
| src/inline.php | JSON Preview/Content-Save mit CSRF, Zugriff und Revision |
| src/page-actions.php | Admin-Seitenoperationen, CSRF und Revision bei Settings/Move |
| src/page.php | Seiten-ID-Resolver mit Zugriffskontrolle |
| src/page-picker-api.php | lesbare veröffentlichte Seiten mit ID |
| src/admin.php | Medien-, Benutzer- und Schlagwortverwaltung |
| src/media-api.php | rollen-/CSRF-geschützte Medienliste und Mutationen für Picker |
| src/media.php | Auslieferung per ID oder Legacy-Pfad mit MED-SEC-Kontext |
| src/export.php / chapter-export.php | Einzel-/Kapitelexport mit Leserechteprüfung |

## Domänenklassen und Daten
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

`data/content/**/*.md`: YAML mit Seiten-ID `p_` + 32 Hexzeichen und `labels` (Tag-IDs). Kein separater Seitenindex. `data/config/users.json`: stabile `u_`-IDs; `media-index.json`: versionierte Zuordnung von 32 Hexzeichen (ohne Präfix) zu Medienpfaden unter `data/upload/`; `tags.json`: Liste aus ID (`t_` + 32 Hexzeichen) und Name. Namen werden zur Laufzeit aufgelöst. Die Katalog-Umbenennung verändert keine Seiten. Berechtigungen über eigene und vererbte Markdown-Regeln; Medienabruf über MediaAccess mit Seiten- oder Bibliothekskontext.

## Konsistenz und Sperren
`MediaOperationLock` verwendet `data/config/media-operations.lock`. Seitenmutationen, MediaMover/Deleter und Tag-Administration verwenden diese Sperre; darunter ggf. eigene Registry-/Tag-/User-Locks. JSON-Mutationen lesen den aktuellen Stand unter separater Lockdatei und schreiben über temporäre Datei/rename. Seiten schreiben atomar pro Datei; Move prüft die Revision innerhalb der Sperre. Inline- und Settings-Revisionsprüfung liegt derzeit vor der eigentlichen Save-Sperre; dies ist keine vollständige Transaktion gegen parallele Updates. Batch-Operationen sind keine globale Transaktion. Keine neuen Sperren im Cleanup ausser dem bestehenden Benutzerlock für Bootstrap.

## UI und Assets
Wiki und Admin nutzen gemeinsamen Header, Sidebar-Account-View, Dialog-Header und Action-Buttons. CSS ist funktionsbezogen aufgeteilt, `layout.css` enthält gemeinsamen Rahmen, `app.css` und `admin.css` die Grundstile. Admin-Benutzer und Tags teilen Tabellen-/Dialogstile. Native Dialoge und Vanilla-JS, Font Awesome lokal, Prism lokal; keine Frontend-Buildkette. Admin-Navigation dauerhaft sichtbar. Drittanbieter-Assets bewusst unverändert.

## Export und Wartung
Kapitelexport sammelt freigegebene Seiten hierarchisch; PDF/ODT über Pandoc, PDF via WeasyPrint, ODT mit `docs/templates/reference.odt` und DOM/ZipArchive-Nachbearbeitung. OdtImageFitter ermittelt Breite aus Standard-Master-Page/Page-Layout, 16 cm Fallback. ZIP schreibt Markdown/Medien und relative Referenzen, Tag-Namen. Temporäre Exportdateien werden bereinigt.

CLI unter `bin/`: `create-admin.php` (nur leerer Benutzerspeicher; bestehende Authenticator-Klasse), `register-page-ids.php` (Dry-Run/--apply), `migrate-page-links.php` und `migrate-media-links.php` (--check/--apply mit Hashmanifest/Backups), `run-regression-tests.php`. Historischer Registrierungsaufruf unter tests/ ist nur ein Weiterleiter. Keine automatische Tag-Migration. Legacy-Medien-/Seitenlinks und Rollen bleiben aus Kompatibilitätsgründen unterstützt.

## Entwicklungsworkflow
Aktuelle vollständige ZIP prüfen → SPEC/Abnahmekriterien → Änderung → Syntax/Regression → PROJECT/ARCHITECTURE/TASKS aktualisieren → Patch ohne Nutzdaten und Vollarchiv → NAS-Abnahme durch Betreiber. Historische Abnahme-/Migrationsdokumente werden nicht als aktueller Funktionsstand interpretiert.

## QA-002
Das Compose-Profil `qa` verwendet dasselbe Image und dieselbe Build-Definition wie `homehub`, jedoch ohne dessen Datenmount oder Startskript. Read-only Root und Code-Mounts, Netzwerk `none`, Benutzer `www-data` und ein temporäres `/tmp` begrenzen Schreibzugriffe. Der PHP-Runner prüft Voraussetzungen im strikten Modus und führt jeden Test als separaten Prozess aus; die gemeinsame temporäre Ausgabedatei vermeidet Pipe-Deadlocks. Keine zusätzliche Bibliothek.

## VERSION-001
Die bestehende Anwendungskonfiguration `src/config/app.php` enthält den einzigen Versionswert. `src/index.php` rendert ihn escaped im gemeinsamen Wiki-Footer; CSS nutzt dessen bestehendes Flexlayout. Keine zusätzliche Klasse, Nutzdatendatei oder Abhängigkeit.

## UI-011: Theme
`src/css/theme.css` definiert semantische Dark-Tokens nur auf `:root[data-theme="dark"]`; Komponenten verwenden `var(--theme-…, bisheriger Farbwert)` und behalten ihre Light-Fallbacks. `src/js/theme.js` setzt das Attribut vor dem Rendern, aktualisiert den gemeinsamen `theme-toggle-view.php` und synchronisiert Local Storage. Native Formulare folgen `color-scheme`. Keine Session-, Auth- oder Datenänderung, keine neue Bibliothek. Export-CSS bleibt separat.

## SEC-1.0
PageMetadata zentralisiert die restriktive Front-Matter-Validierung. PageRepository und PermissionService berücksichtigen die Gültigkeit; Legacy ohne Front Matter bleibt kompatibel. Medienabruf prüft Seiten-ACL oder globale Bibliotheksberechtigung (MED-SEC). Siehe SPEC und TESTING.md.

## PUB-001 – Publikationsanforderungen
GPL-3.0-or-later; Drittkomponenten gemäss THIRD-PARTY-NOTICES.md. Reproduzierbarer Composer-Lockfile-Build, optionale .env-Konfiguration und GitHub-QA. Keine produktiven Daten im Repository. Aktuelle Freigabekriterien siehe PROJECT.md; Prüfungen siehe TESTING.md.

EXP-SEC: DocumentExporter rendert jede freigegebene Seite separat mit AlignedMarkdown; MediaAccess validiert Bildressourcen vor Pandoc (HTML-Eingabe). ChapterZipExporter verwendet dieselbe Referenzsemantik zur Medienauswahl. Markdown-Quelldateien bleiben unverändert.

## Papierkorb (TRASH-001)
PageTrash nutzt MediaOperationLock und PageEditLease. Originaldateien werden ohne Umschreiben verschoben; bei regulärem Verschiebefehler wird ein Rollback versucht. Kein persistenter Index. Papierkorb unter data/trash ist nicht Teil der aktiven Seitenauflösung. Admin-Endpunkt prüft Rolle und CSRF; Seiten-Endpunkt prüft ACL je betroffener Seite innerhalb der Operationssperre. Kein Schutz gegen alle Prozessabbrüche oder externe parallele Dateisystemänderungen.
