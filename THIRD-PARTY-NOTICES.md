# Drittkomponenten / Third-party notices

HomeHub-eigener Code: GPL-3.0-or-later. Drittkomponenten behalten ihre Lizenzen.

- Prism 1.30.0: MIT, Copyright 2012 Lea Verou; `docs/PRISM-LICENSE.txt`, Originalmodule und Hashmanifest unter `docs/vendor/prism-1.30.0/`. Lokales npm-Paket, unveränderte Module zusammengefügt. Quelle: https://registry.npmjs.org/prismjs/-/prismjs-1.30.0.tgz ; https://github.com/PrismJS/prism/tree/v1.30.0 . Unabhängiger Bytevergleich bewusst nicht durchgeführt; funktional abgenommen.
- Font Awesome Free 6.7.2: MIT (Code), SIL OFL 1.1 (Fonts), CC BY 4.0 (Icons); `src/vendor/fontawesome/LICENSE.txt`.
- ODT: eigene LibreOffice-Vorlage, GPL-3.0-or-later, Copyright 2026 M10-Solutions; `docs/templates/README.md`. Funktional abgenommen.
- Systempakete (inkl. Pandoc und WeasyPrint) und Basisimage behalten ihre Lizenzen. Kein fertiges Image zur Weitergabe vorgesehen.

## Fixierte Composer-Abhängigkeiten

| Paket | Version | Lizenz laut Paketmetadaten |
|---|---|---|
| dflydev/dot-access-data | v3.0.3 | MIT |
| league/commonmark | 2.10.8 | BSD-3-Clause |
| league/config | v1.2.0 | BSD-3-Clause |
| nette/schema | v1.4.0 | BSD-3-Clause, GPL-2.0-only, GPL-3.0-only |
| nette/utils | v4.1.6 | BSD-3-Clause, GPL-2.0-only, GPL-3.0-only |
| psr/event-dispatcher | 1.0.0 | MIT |
| symfony/deprecation-contracts | v3.8.1 | MIT |
| symfony/polyfill-ctype | v1.37.0 | MIT |
| symfony/polyfill-php80 | v1.43.0 | MIT |
| symfony/yaml | v7.4.23 | MIT |

Inventar: `docs/composer-licenses.json`, Versionen in `composer.lock`. Original-Lizenzdateien laut Betreiber vorhanden; kein separater Textabgleich. Keine offensichtliche GPLv3-Inkompatibilität laut Metadaten; Nette BSD-3-Clause als kompatible Lizenzoption. Originalhinweise erhalten. Audit im CI #1 laut Betreiber ohne Meldungen.
