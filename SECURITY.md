# Sicherheit / Security

## Unterstützter Stand
HomeHub CMS 1.1.0 und 1.1.1 (`1.1.1-20261127`) sind für vertrauenswürdige private Netze vorgesehen. Die öffentliche Bereitstellung des Quellcodes ist keine Freigabe für öffentlich erreichbare Installationen.

## Sicherer Betrieb
- Kein direktes Port-Forwarding aus dem Internet. Firewall, VPN und bei Zugriff über nicht vertrauenswürdige Netze HTTPS mit geeigneter Zugriffsbeschränkung verwenden.
- Ersteinrichtung in einem kontrollierten Netz durchführen; der erste Benutzer wird Administrator. Starke individuelle Passwörter verwenden.
- MED-SEC: Medienabruf mit Seiten-ACL und Referenzprüfung oder globaler Bibliotheksberechtigung für Editor/Admin. Öffentliche Referenzen veröffentlichen Medien. MED-SEC/EXP-SEC sind funktional abgenommen. Globale Medienrechte und Export-Risikogrenzen beachten.
- Die Anmeldefunktion hat keine nachgewiesene anwendungsweite Begrenzung von Fehlversuchen. Zugriff einschränken und gegebenenfalls Rate-Limiting am Reverse Proxy einsetzen.
- Regelmässig Daten sichern, Wiederherstellung testen und Abhängigkeiten sowie Container-Basisimage aktualisieren. Produktive Daten nie in Git einchecken.
- Fehlerhaftes Front Matter wird restriktiv behandelt; vollständig entfernte Metadaten sind nicht von öffentlichen Legacy-Seiten unterscheidbar. Dateizugriff auf vertrauenswürdige Administratoren beschränken.

## Schwachstellen melden
Nach Veröffentlichung bevorzugt die private Schwachstellenmeldung des GitHub-Repositories M10-Solutions/homehub-cms verwenden, sofern aktiviert. Keine Exploits, Zugangsdaten oder privaten Inhalte in öffentliche Issues einstellen. Der Maintainer muss die private Meldemöglichkeit vor Publikation aktivieren und testen.

## Prüfgrenzen
Kein vollständiger Penetrationstest. Composer-Audit und vollständige Lizenzprüfung sind vor Veröffentlichung nachzuweisen. Composer-Audit deckt Systempakete nicht ab. Die Docker-Konfiguration ist eine Referenzinstallation; umfassende Prüfung, Härtung und Wartung der individuellen Betriebsumgebung liegen beim Betreiber. Bekannte anwendungsrelevante Risiken und die bereitgestellte Referenzkonfiguration bleiben im Projektumfang. Ein CVE-freies Basisimage ist kein Freigabekriterium.

## Dokumentexport
Nur vertrauenswürdige schreibberechtigte Benutzer und Medien zulassen. Die vollständige Isolation externer und lokaler Ressourcen beim Pandoc-/WeasyPrint-Export ist nicht nachgewiesen. Siehe docs/ACCEPTANCE.md.
