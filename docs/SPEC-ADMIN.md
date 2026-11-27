# HomeHub CMS – Funktionsspezifikation Administration

Geltungsstand: Release 1.1.1 (`1.1.1-20261127`). Architekturgrundsätze: [ARCHITECTURE.md](ARCHITECTURE.md). Content-Funktionen: [SPEC-EDITOR.md](SPEC-EDITOR.md).

## Allgemeines

### ADMIN-0001 – Verwaltungszugang

Benutzer und administrative Konfiguration werden ausschliesslich durch Administratoren verwaltet. Seitenoperationen erfolgen im Content-Bereich. Das Rollenmodell ist unter ACL festgelegt.

### ADMIN-0002 – Gemeinsame Oberfläche

Die Oberfläche folgt DESIGN-0001 und DESIGN-0002. Der feste dunkle Hub-Header verwendet gemeinsames Logo, Titel und Untertitel; Admin rechts «Zum Wiki», Wiki mit Admin-Link für Administratoren und Suchfeld. Das Profil unten links zeigt Vor-/Nachname (Fallback Benutzername), darunter Benutzername und Rollenicon mit Tooltip. Admin-Logout als Icon mit zugänglicher Beschriftung; danach Startseite, bei anonym verweigertem Zugriff modaler Login.

### ADMIN-0003 – Dialoge und Rückmeldungen

Gemeinsame Dialogheader in Hub-Farbe mit weissem Titel, funktionsbezogenem Font-Awesome-Icon links und Schliessen-Icon. Aktionsbuttons mit Icons, primär blau `#4263eb`, Gefahr dunkelrot `#9c2434`, neutraler Rahmen, Fokus-/Disabled-Zustände. Seiten-Löschbutton dunkelrot. Native Bestätigungen bleiben ausserhalb der ausdrücklich modal geregelten Aktionen erhalten.

Wiki und Administration nutzen `HomeHubFeedback.notify()` und `HomeHubFeedback.confirm()`. Erfolg/Info verschwinden nach fünf Sekunden und sind manuell schliessbar; Fehler bleiben standardmässig sichtbar und manuell schliessbar. Seiten-/Medienlöschung und Verwerfen ungespeicherter Inline-Änderungen verlangen modale Bestätigung. Abbrechen, Escape und Schliessen ohne Bestätigung lösen keine Aktion aus. Admin-Servermeldungen erscheinen nach Laden als Notifications, ohne JavaScript als Hinweise.

### ADMIN-0004 – Responsive Navigation

Oberhalb 1024 CSS-Pixel bleibt die Desktop-Navigation mit Wiki-Klappzustand und Breite erhalten. Bis einschliesslich 1024 CSS-Pixel ist die Navigation initial geschlossen, Content ohne Sidebar-Margin. Gemeinsamer Header-Button öffnet links fixierten Drawer mit Backdrop; X, Backdrop und Escape schliessen ihn. Hauptinhalt ist inert, Seitenscroll gesperrt. Normale Linkauswahl und Kontextaktionen schliessen den Drawer; modifizierte Klicks bleiben unverändert. Filter, Kapitelstruktur, Kontextaktionen und Profil bleiben erhalten, Touch-Ziele werden vergrössert. Wiki und angemeldete Verwaltung teilen Script und CSS; Admin-Login ohne Drawer. Kompakter Header, auf kleinen Smartphones zweizeilige Wiki-Suche, Breadcrumbs ausgeblendet, Markenname bis 380px ausgeblendet. Mobile Sichtbarkeit wird nicht gespeichert; Desktop-localStorage bleibt erhalten. Breakpointwechsel und pageshow schliessen den Drawer. Ohne JavaScript bleibt die Navigation als Fallback erhalten. Desktop- und Drucklayout bleiben von Drawer-Regeln unberührt. Administration zeigt nur «Verwaltung» als Abschnittsüberschrift; Navigationseinträge 14 px, Kapitelüberschriften dürfen abweichen.

### ADMIN-0005 – Theme

Light ist Standard. Gemeinsamer Header-Schalter (`role=switch`) wechselt ohne Reload auch vor Anmeldung; Mond im Light-, Sonne im Dark-Modus. Auswahl unter `homehub.theme` pro Browser und Origin, nicht pro Benutzer. Ohne Speicher funktioniert der Wechsel auf der aktuellen Seite; Tabs derselben Origin werden synchronisiert. Content, Navigation, Suche, Markdown, Editor, Dialoge und Administration nutzen gemeinsame Dark-Farbrollen. Primär-/Gefahrfarben bleiben semantisch, Bilder und Syntaxhighlighting werden nicht invertiert, Exportformatierung bleibt unverändert.

## Anmeldung und Authentifizierung

### AUTH-0001 – Anmeldung

Login über Wiki-Dialog oder Admin; Session-Cookies HttpOnly/SameSite Strict, Secure bei HTTPS, CSRF und Login-Verzögerung. Nur aktive Konten können sich anmelden. Alte Passwortdatei ist kein Login-Speicher.

### AUTH-0002 – Erstinstallation

Bei einer frischen Installation ohne `data/config/users.json` und ohne Initialisierungsmarker führt der erste Browseraufruf zur Web-Ersteinrichtung `/setup.php`. Das Formular erstellt den ersten Benutzer zwingend mit Admin-Rolle; Benutzername 3–50 Zeichen und Passwort mindestens 12 Zeichen, inklusive Passwortbestätigung. CSRF-/Origin-Prüfung und eine exklusive Sperre schützen die Erstellung. Nach Erfolg folgt die reguläre Anmeldung (kein automatisches Login). Der persistente Marker `.homehub-initialized` verhindert eine erneute Einrichtung auch bei später fehlender Benutzerdatei; leere/beschädigte Benutzerdateien dürfen kein Setup auslösen. Bereits bestehende Installationen bleiben erhalten. Das interaktive `bin/create-admin.php` bleibt als optionaler CLI-Bootstrap bestehen; bestehende Konten werden nicht überschrieben. Alte Passwortdatei ist kein Login-Speicher mehr.

## Benutzerverwaltung

### USER-0001 – Konten

JSON-Konten `data/config/users.json` mit Passwort-Hash, Benutzername, stabiler `u_<32 hex>`-ID, Rolle, Aktivstatus und optional Vorname, Name, E-Mail. Legacy-Rollen `member`/`visitor` werden auf `editor`/`reader` normalisiert; alte IDs werden nachregistriert.

### USER-0002 – Verwaltung und Validierung

Admin-Tabelle mit Einzelauswahl, Suche, Rollen-/Statusfilter, Sortierung, Neu/Bearbeiten/Löschen, Doppelklick. Benutzername 3–50 ASCII-Zeichen (Buchstaben/Ziffern/Punkt/_/-), eindeutiger Name ignoriert Gross-/Kleinschreibung; Passwort mindestens 12 Zeichen, beim Bearbeiten optional. Profilfelder validiert. Letzter aktiver Admin geschützt; eigenes Konto nicht löschbar über UI/Route. Kein E-Mail-Versand.

## Rollen und Berechtigungen

### ACL-0001 – Rollen und Aktionen

Keine Page Ownership und kein Genehmigungsworkflow. `published`, `nav` und ACL sind getrennte Eigenschaften.

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

### ACL-0002 – Vererbung und serverseitige Prüfung

Für Editoren gelten die eigene Seiten-ACL und sämtliche existierenden Markdown-Vorfahrenregeln. Verzeichnisse ohne Markdown besitzen keine eigene ACL. Neue Editor-Seiten starten als Draft und erben die geltenden Kapiteleinschränkungen; Editoren können sie nicht durch eigene ACL-Werte erweitern. Auch POST/API-Mutationen müssen diese Grenzen serverseitig prüfen. Ein Admin hat administrativen Seitenzugriff. Die Rollenbezeichnung allein hebt für Editoren keine Kapitelsperre auf.

### ACL-0003 – Veröffentlichung und Navigation

| `published` | `nav` | Redaktionelle Navigation (Admin / berechtigter Editor) | Reader-Navigation | Reguläre Suche, Direktlink, Export |
| --- | --- | --- | --- | --- |
| `true` | `true` | Normaler Eintrag | Bei Leserecht sichtbar | Bei Leserecht verfügbar |
| `true` | `false` | Kein Eintrag | Kein Eintrag | Bei Leserecht verfügbar |
| `false` | `true` | *Kursiv*, Label **Draft** | Unsichtbar | Nicht verfügbar |
| `false` | `false` | *Kursiv*, **Draft**, Auge durchgestrichen | Unsichtbar | Nicht verfügbar |

`published` steuert die Veröffentlichung, **nicht** die Kapitel-ACL; es wird nicht von Vorfahren geerbt. `nav` steuert die normale Navigation veröffentlichter Seiten, **nicht** ihre Leseberechtigung. Bei Drafts wird `nav` nur als künftige Navigationspräferenz gespeichert; die redaktionelle Entwurfsnavigation zeigt sie unabhängig davon. Ein Draft kann von berechtigten redaktionellen Rollen über den gesonderten Draft-Zugriff geöffnet werden, nicht über die reguläre öffentliche Route. Reguläre Suche, Seitenpicker und PDF/ODT/ZIP-Exporte enthalten nur veröffentlichte und lesbare Seiten. Auch bei `nav: false` bleiben veröffentlichte Seiten über Suche und Direktlink erreichbar, sofern die ACL es erlaubt.


### ACL-0004 – Sichtbarkeitseinstellungen

Nur die zur Sichtbarkeit passende Rollen- oder Benutzerauswahl wird angezeigt und ausgewertet. Geerbte Freigaben werden angezeigt. ACL-Felder bleiben Admin-only.

## Seiten- und Strukturverwaltung

### STRUCT-0001 – Seitenoperationen

Seitenoperationen erfolgen im Content-Bereich gemäss ACL-0001–0004. Pfade: Kleinbuchstaben a–z, Zahlen, `_`, `-`, Unterordner; maximal 180 Zeichen. Titel 1–160 Zeichen, Beschreibung bis 500 Zeichen, Inhalt bis 1 MiB, Sortierung −99999 bis 99999. Zielpfad darf nicht existieren; Schreibpfade ohne Symlinks. Move-Dialog wählt bestehenden Ordner und Namen, zeigt resultierenden Pfad; keine Ordnerneuanlage im Move-Dialog. Keine produktive Massenaktualisierung alter Referenzen. Seitenerstellung prüft nach 300 ms Eingabepause serverseitig Pfad und ACL; Konflikte im Dialog, Eingaben bleiben erhalten. Autoritative Prüfung beim Schreiben unter Sperre. Revision und Lease gemäss DATA-0002 und Content-Spezifikation.

## Medienbibliothek

### MEDIA-0001 – Ordner und Picker

Manuelle verschachtelte Ordner unter `data/upload`, passender bestehender Ordner kann im Picker vorgeschlagen werden. Liste/Raster, Vorschaubilder, Dateinamensfilter, Ordnerbaum und Breadcrumbs. Picker erlaubt Upload, Ordneranlage, Einfügen mit Alternativtext. Rollen und CSRF schützen API-Operationen.

### MEDIA-0002 – Upload und Validierung

JPEG, PNG, WebP, GIF, PDF, TXT; MIME/Endung/Upload prüfen; je Datei höchstens 10 MiB. Admin-Upload bis 20 Dateien und 10 MiB insgesamt pro Anfrage; Mehrfachwahl/Drag-and-drop, Dateiicons und Namen, Löschen der Auswahl/Hochladen innerhalb der Zone; neue Auswahl ersetzt bisherige. Teilfehler werden gemeldet.

### MEDIA-0003 – Verwaltung und stabile Links

Admin: Auswahl sichtbarer Medien im aktuellen Ordner, Alle/Keine, zentrale Sammelaktionen Löschen/Verschieben, Zielordnerdialog, Unterordnerdialog, pro Karte Markdown kopieren (HTTP-Fallback) und Umbenennen. Kein Überschreiben bestehender Zieldateien. IDs bleiben bei Move/Rename erhalten.

### MEDIA-0004 – Medienidentität und Legacy-Links

`data/config/media-index.json` registriert Pfade unter 32-stelligen hexadezimalen IDs (ohne `m_`-Präfix). `/media.php?id=…`; Legacy `/media.php?file=…` bleibt unterstützt. Keine Referenzprüfung beim produktiven Move/Delete; Löschen kann Links ungültig machen. Offline-Migration via `bin/migrate-media-links.php --check/--apply`, Hashmanifest und Backup, eindeutige Alt-URLs in Markdown/HTML-Bildreferenzen; siehe Betriebsdokumentation (`OPERATIONS.md` in docs).

### MEDIA-0005 – Upload- und Dialogverhalten

Einzelne Dateien lassen sich vor Upload aus der tatsächlichen Auswahl entfernen; Anzahl und Validierung werden neu berechnet. Umbenennen über zentralen Dialog; Inline-Validierung bleibt bis zur Korrektur sichtbar. Serverseitige Notifications bleiben erhalten.

### MEDIA-0006 – Bibliothekszugriff

Bibliothekskontext nur für `media.read` (Editor/Admin), unabhängig von Kapitel-ACL. Editor/Admin sind für alle Medien vertrauenswürdig. Kontextlose Anfragen liefern 404; kein HTTP-Referer als Nachweis. `private, no-store`. Seitenkontext und gerenderte Referenzen sind in der Content-Spezifikation geregelt.

## Schlagwortverwaltung

### TAG-0001 – Katalog und Validierung

Zentraler Katalog `data/config/tags.json` als Liste `{id,name}`, ID `t_<32 hex>`; YAML `labels` speichert IDs. Namen bleiben case-sensitive, nur exakt gleich geschriebene Namen sind identisch. Maximal 20 Zuordnungen pro Seite, Name 1–40 Zeichen ohne Komma/Zeilenumbruch/reservierte ID.

### TAG-0002 – Redaktionelle Zuordnung

Inline-Editor: case-insensitive Vorschlagsuche, Pills mit X, neue Namen eingeben, Tastaturauswahl und Speichern; Abbrechen persistiert nichts. Entfernen aus Seite löscht nicht den Katalogeintrag.

### TAG-0003 – Administrative Verwaltung

Admin-Unterseite Schlagwörter mit Suchfilter, Einzelauswahl, Doppelklick, Neu/Bearbeiten/Löschen und Anzahl zugeordneter Seiten (auch Entwürfe). Umbenennung auch bei Verwendung erlaubt; dabei ändern sich nur Katalogdaten. Löschen verwendeter IDs gesperrt. Katalogänderungen unter Sperren, atomar. Lesbare Legacy-Labels werden bei Änderung der Schlagwortzuordnung im Editor zugeordnet; keine automatische Massenmigration. Inhaltsbearbeitung ohne Änderung der Schlagworte bewahrt vorhandene Zuordnungen.

## Papierkorb

### TRASH-0001 – Löschung und Schutz

Seiten werden einzeln oder als Teilbaum in `data/trash/<Eintrag-ID>/content` verschoben. JSON-Metadaten enthalten ursprüngliche Slugs, Löschzeit und Benutzer. Alle betroffenen Seiten müssen vor dem Verschieben die bestehende Verwaltungs-ACL und Lease-Prüfung bestehen. Geschützte Kapitel mit Unterseiten dürfen nicht einzeln gelöscht werden, da sonst geerbte ACL entfallen könnten. 

### TRASH-0002 – Verwaltung und Wiederherstellung

Admins allein verwalten den Papierkorb per CSRF-geschütztem POST. Wiederherstellung nur am ursprünglichen Ort ohne Pfad-/ID-Kollision und mit vorhandenem Elternkapitel; aktuelle geerbte ACL gelten und sind ausdrücklich zu bestätigen. Endgültiges Löschen und Leeren erfordern Bestätigung. Medien bleiben unverändert. Keine automatische Frist, keine neue Abhängigkeit. Papierkorb liegt ausserhalb des aktiven Bestands und Webroots. Keine garantierte Transaktion bei Prozessabbruch oder Dateisystemfehlern; Backup bleibt erforderlich.

### TRASH-0003 – Bedienung

Die Löschoption ist eine Checkbox (Standard: einzeln); bei geschützten Kapiteln mit Unterseiten ist für Admins die rekursive Option vorgegeben. Editoren erhalten keine Unterseitenanzahl; die serverseitige Schutzprüfung bleibt verbindlich. Papierkorb: Tabelle mit Mehrfachauswahl und zentraler Toolbar; Bestätigung im Dialog. Mehrfachaktionen werden je Eintrag ausgeführt und Konflikte einzeln gemeldet.
