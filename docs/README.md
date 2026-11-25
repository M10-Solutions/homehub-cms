# HomeHub CMS – Installation und Betrieb

## Struktur
`src/` Apache-Webroot und Anwendung; `src/lib/` PHP-Klassen; `data/content/` Markdown; `data/upload/` Medien; `data/config/` JSON-Kataloge, Benutzer und Sperren; `bin/` Wartungs-CLI; `tests/` Regressionen; `docker/` Laufzeit; `docs/` Dokumentation und ODT-Vorlage.

## Neue Installation
Im Projektverzeichnis:

```sh
docker compose up -d --build
```

HomeHub anschliessend im Browser öffnen. Bei einer neuen Installation ohne Benutzerkonto erscheint automatisch `/setup.php`. Dort Benutzername und Passwort (mindestens 12 Zeichen) samt Bestätigung eingeben und das erste Administratorkonto erstellen. Anschliessend regulär anmelden. Eine abgeschlossene Einrichtung kann nicht erneut aufgerufen werden; bei bestehender Installation erscheint kein Setup. Die frühere `admin-password.hash` ist kein gültiger Login-Speicher mehr.

Optionaler CLI-Bootstrap (nur falls noch kein Benutzerkonto angelegt wurde): `docker compose exec -u www-data homehub php bin/create-admin.php`. Die Passworteingabe ist dort sichtbar; nur in einem privaten Terminal verwenden. Für bestehende Installationen vor Updates `data/config` sichern; den Initialisierungsmarker nicht entfernen.

Aufruf: `http://homehub.local:8011/` (Hostnamen an Installation anpassen), Administration `/admin.php`. Dort Benutzer, Medien und Schlagwörter verwalten; Seiten im Wiki bearbeiten. Funktionsdetails siehe SPEC.md.

Für eine isolierte Erstinstallations-Abnahme auf dem NAS siehe `TESTING.md` und `bin/prepare-fresh-install-test.sh`.

## Aktualisierung / bestehende Installation
Vor Updates `data/` sichern. Patch über Projektverzeichnis entpacken und genannte Löschungen explizit durchführen. Vollarchive enthalten Beispielinhalte: niemals über produktive `data/content`, `data/config` oder `data/upload` schreiben. Bestehende IDs, JSON-Dateien und Passwort-Hashes nicht manuell ersetzen.

Compose bindet `src/`, `bin/`, `tests/`, `docs/templates/` read-only und `data/` beschreibbar ein. Änderungen an diesen Code-/Testdateien benötigen bei vorhandenem Mount keinen Neustart. OPcache prüft Zeitstempel; Browser ggf. mit Ctrl+F5 aktualisieren. Änderungen an Dockerfile, Apache/PHP-Konfiguration, Composer-Abhängigkeiten und Entrypoint werden erst durch Neubau/Neuerstellung des Containers wirksam:

```sh
docker compose up -d --build
```

Bei alten Installationen mit abweichender Struktur/Mounts zuerst die benötigten Daten und Rechte separat prüfen. Keine automatische Datenmigration beim Cleanup.

## NAS-Rechte
www-data standardmässig UID 1000 und GID 1001. Bei abweichenden Host-Kennungen Build-Argumente HOMEHUB_UID / HOMEHUB_GID anpassen und Image neu bauen. `docker compose exec homehub id www-data` prüft numerische Zuordnung. Entrypoint korrigiert Rechte unter data/, nicht im Anwendungscode. Codeverzeichnisse 0755/Dateien 0644 als Orientierung; Benutzerdatei vertraulich halten (0600). Keine privaten Daten im Webroot.

## Wartung
Seiten-IDs: `php bin/register-page-ids.php` (Vorschau) / `--apply`. Alte Links: `php bin/migrate-page-links.php --check` / `--apply`, entsprechend `migrate-media-links.php`. Im Container mit Schreibzugriff ausführen; vorher Backup, Prüfstand beachten. Details im folgenden Migrationsabschnitt. Die alte Tag-Katalogstruktur vor TAG-004 separat sichern; bei bereits migriertem Stand nicht erneut zurücksetzen.

## ODT und Export
`docs/templates/reference.odt` kann in LibreOffice Writer angepasst werden (Seitenränder, Formatvorlagen). Im Compose-Betrieb werden Änderungen bei neuen Exporten ohne Neustart wirksam. ODT-Bildbreite wird aus der Standard-Seitenvorlage berechnet. PDF nutzt `src/css/export.css` und bei Kapiteln `chapter-document.css`. ZIP enthält referenzierte lokale Medien statt kompletter Bibliothek.

## Tests
```sh
docker compose config
docker compose --profile qa run --rm --no-deps --build qa
```

Tests nutzen temporäre Dateien; zusätzlich Browser-/NAS-Abnahme und tatsächliche Exportdownloads prüfen. Für lokale Tests sind PHP 8.3, Composer-Abhängigkeiten und DOM/zip/mbstring erforderlich. Einzeltests und Grenzen siehe TESTING.md und TESTING.md. Prism und Font Awesome werden lokal ausgeliefert; Lizenzen in docs/PRISM-LICENSE.txt und src/vendor/fontawesome/LICENSE.txt.

## App-Version
Die Version wird in `src/config/app.php` unter `version` gepflegt (initial `0.8-20261122`). Major.Minor-YYYYMMDD: grössere Änderungen erhöhen Major, kleinere Erweiterungen und Bugfix-Releases Minor; reine Revisionen können nur das Datum ändern. Der Wiki-Footer übernimmt den Wert beim nächsten Seitenaufruf. Kein Neustart erforderlich. Bei eigenen Änderungen an der App-Config diese beim Patch-Einspielen übernehmen.

### Light-/Dark-Mode (UI-011)
Der Schalter im Wiki- und Admin-Header speichert die Wahl lokal im Browser. Standard: Light. Keine Migration oder zusätzliche Konfiguration erforderlich. App-Version: `0.9-20261122`.

## Fachliche Abnahme und Release
UAT-002a–f und UI-012 sind abgenommen; NAS-Regression 34/34 auf 0.13-20261123. Release 1.0-20261124 ist seit 24.11.2026 freigegeben; finale NAS-QA 35/35. Siehe TESTING.md für Prüfschritte und Risiken.

## Backup und Wiederherstellung
Vor einem Update Schreibzugriffe beenden (Wartungsfenster, `docker compose stop homehub`). Vollständiges Projekt einschliesslich `data/` und individueller Compose-/Konfigurationsdateien geschützt sichern. Sicherung ausserhalb des Projektverzeichnisses aufbewahren und auf Lesbarkeit prüfen. Patch ohne Nutzdaten einspielen; individuelle Konfiguration erhalten. Anschliessend `docker compose up -d --build`. Bei Rückkehr zum alten Stand Container stoppen und zusammengehörigen Code- und Datenstand aus der Sicherung wiederherstellen; seit dem Backup entstandene Änderungen vorher separat sichern. Restore zunächst auf isolierter Instanz mit eigenem Port und separaten Daten prüfen.

## Sicherheitsgrenzen
Nur in einem vertrauenswürdigen Netz betreiben, kein direkter Internetzugriff. Bei Zugriff über nicht vertrauenswürdige Netze HTTPS und geeignete Zugriffsbeschränkungen vorsehen. Medien-URLs sind nicht durch Seiten-ACL geschützt; keine vertraulichen Medien ablegen. Ungültiges Front Matter wird seit SEC-1.0 restriktiv behandelt; vollständig entfernte Metadaten bleiben von Legacy-Seiten ohne Front Matter nicht unterscheidbar. Medienrisiko (Variante A) ausdrücklich akzeptiert; siehe TESTING.md.

## Mitgelieferte Beispielinhalte

Neue Installationen enthalten eine Startseite, das Kapitel Unser Zuhause und ein integriertes Benutzerhandbuch (13 Markdown-Seiten). Alle Beispielseiten sind veröffentlicht und öffentlich lesbar; sie enthalten keine echten persönlichen Daten. Interne Links verwenden stabile Seiten-IDs. Beispiel-Schlagwörter werden als unterstützte Legacy-Labels mitgeliefert und beim expliziten Speichern der Seiteneinstellungen dem Katalog zugeordnet. Bestehende Installationen nicht überschreiben; das Handbuch kann nach Backup und Kollisionsprüfung separat übernommen werden.

## Portable Installation

Optional `.env.example` nach `.env` kopieren und Werte anpassen. Ohne `.env` gelten die bisherigen Standards. `HOMEHUB_BIND_ADDRESS=127.0.0.1` beschränkt den Port auf den Docker-Host. UID/GID müssen gültige, nicht mit anderen Konten im Image kollidierende numerische IDs sein. Zeitzone als IANA-Name angeben. Für weitere Instanzen getrennte Projektverzeichnisse, Ports, Container- und Image-Namen verwenden. `docker compose config` vor dem Build prüfen. Änderungen an UID/GID erfordern Rebuild; Zeitzone und Port Neuerstellung. Siehe auch ../SECURITY.md.

### Abhängigkeiten vor Publikation fixieren
Nur in einer sauberen Entwicklungskopie mit Docker und Netzwerkzugriff: `sh bin/prepare-composer-lock.sh`. Das Skript erzeugt bei fehlendem Lockfile ein echtes Lockfile im PHP-Laufzeitimage, prüft es und führt Composer-Audit aus; bei Fehlern bricht es ab. `docs/composer-licenses.json` manuell bewerten und beide Dateien versionieren. Anschliessend `docker compose --profile qa run --rm --no-deps --build qa`. Ohne Lockfile ist ein Übergangsbuild möglich, aber keine öffentliche Freigabe. GitHub Actions prüft Regression, Lockfile und Audit.

### Fixierte Composer-Abhängigkeiten
Das Auslieferungsarchiv enthält nun composer.lock und docs/composer-licenses.json. Beim nächsten Build werden die fixierten Paketversionen installiert. Danach strikte QA ausführen. Das Lockfile im Entwicklungsrepository versionieren.


## Migration älterer Links
### PAGE-003 – Seitenlinks migrieren

1. Vorab `data/content` sichern und die Seiten-ID-Registrierung abschliessen.
2. Im Container `php bin/migrate-page-links.php --check` ausführen. Jede Änderung und Warnung kontrollieren.
3. Danach ohne zwischenzeitliche Inhaltsänderungen `php bin/migrate-page-links.php --apply` ausführen.
4. Wiki-Links im Browser testen. Das Skript nennt den Pfad des zusätzlichen Backups unter `data/config/page-link-migration/`.

**Umfang:** Nur eindeutig auflösbare HomeHub-Links in Markdown-Inline-Links und Markdown-Referenzdefinitionen (`/?page=slug`, `/index.php?page=slug`, `?page=slug`). Andere Formate bleiben unverändert. Nicht gefundene Seiten und Links mit zusätzlichen URL-Parametern werden gemeldet. Die Migration wird bei Änderungen zwischen den beiden Phasen abgebrochen. Bei einem Schreibfehler während `--apply` können bereits verarbeitete Seiten geändert sein; in diesem Fall die angegebenen Backups verwenden und nicht blind erneut starten.

Das Verzeichnis `data/config/page-link-migration` gehört zu den Laufzeitdaten, nicht in einen Quellcode-Patch.

## Ergänzung: importierte Dokumentationen

Zusätzlich werden relative Markdown-Ziele wie `[Licht](beleuchtung.md)` anhand des
Verzeichnisses der verlinkenden Markdown-Datei aufgelöst sowie absolute Wiki-Pfade
wie `[Einführung](/dokumentation/ubuntu-home-server/virtualisierung/einfuhrung)`
gegen die vorhandenen Seiten-IDs abgeglichen. Bei `../`-Referenzen darf die
Auflösung das Inhaltsverzeichnis nicht verlassen. Die Zuordnung muss eindeutig
sein und eine registrierte Seiten-ID besitzen. Nicht gefundene Seiten werden
mit `WARN` gemeldet und bleiben unverändert.

Die Migration verändert weder Bilder, externe URLs, Codeblöcke noch YAML-Front-Matter.
Links mit Fragmenten oder zusätzlichen Query-Parametern werden aus Vorsicht nicht
migriert. Das Manifest wurde auf Version 2 angehoben: Nach dem Einspielen der
Erweiterung **erneut `--check`** ausführen, bevor `--apply` gestartet wird.

Regressionstest auf dem Entwicklungsrechner: `php tests/migrate-page-links.php`.

### MEDIA-004 – Migration von Medienreferenzen

## Vorgehen

1. Vor Änderungen eine vollständige Sicherung von `data/` anlegen.
2. Nach dem Einspielen des Patches **vor Medienverschiebungen** ausführen:

   `docker compose exec homehub php bin/migrate-media-links.php --check`

3. `CHANGE`-Zeilen und `WARN`-Zeilen kontrollieren. Unbekannte Ziele werden **nicht** geändert.
4. Erst nach Prüfung:

   `docker compose exec homehub php bin/migrate-media-links.php --apply`

5. Danach nochmals `--check` ausführen; erwartet sind 0 Dateien mit Änderungen.
6. Stichproben im Browser prüfen, insbesondere importierte Bilder und PDF-Links.

## Sicherheitsverhalten

- Prüflauf speichert `data/config/media-link-migration/check.json`.
- `--apply` vergleicht Datei-Hashes und den Hash von `media-index.json` mit dem Prüflauf.
- Backups liegen unter `data/config/media-link-migration/backup-.../`.
- Bei einem Fehler während des Schreibens kann ein Teil der Seiten bereits migriert sein; die Backups bleiben erhalten. Es gibt **keinen automatischen Rollback**.
- Nur explizite lokale Alt-URLs werden migriert: `/media.php?file=...`, `/data/upload/...`, `/upload/...`, `/uploads/...`. Externe URLs, beliebige relative Bildnamen und komplexe Markdown-Konstrukte werden nicht geraten.
- Referenzen in YAML, Codeblöcken und Inline-Code bleiben unverändert.
- Das Entfernen des Laufzeit-Scanners bedeutet: Medien können trotz bestehender Referenzen gelöscht werden. Nach dem Löschen fehlen diese Medien auf den Seiten.
- Wenn eine Datei noch nicht in `media-index.json` registriert ist, zuerst Medienverwaltung öffnen, um sie registrieren zu lassen.

## Wiederherstellung

Im Backup-Ordner liegt die ursprüngliche relative Markdown-Verzeichnisstruktur. Bei Bedarf einzelne Seiten aus dem Backup zurückkopieren. Vor dem Zurückspielen HomeHub-Änderungen an diesen Seiten sichern.
