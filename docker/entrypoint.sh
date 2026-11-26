#!/bin/sh
set -eu

# Validate the IANA timezone before writing PHP configuration.
php -r '$tz = getenv("TZ") ?: "Europe/Zurich"; if (!in_array($tz, timezone_identifiers_list(), true)) { fwrite(STDERR, "Invalid HOMEHUB_TZ\n"); exit(1); } file_put_contents(getenv("PHP_INI_DIR") . "/conf.d/zz-homehub-timezone.ini", "date.timezone=" . $tz . "\n") !== false or exit(1);'

for folder in /var/www/homehub/data/config /var/www/homehub/data/content /var/www/homehub/data/upload /var/www/homehub/data/trash; do
  mkdir -p "$folder"
  # Only writable application data is adjusted; application code is untouched.
  chown -R -h www-data:www-data "$folder"
  find "$folder" -type d -exec chmod 775 {} +
done

exec docker-php-entrypoint "$@"
