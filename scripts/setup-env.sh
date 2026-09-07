#!/usr/bin/env bash
# engleza.wiki pod bootstrap — ensures PHP 8.3, PHP extensions, and MariaDB are ready.
set -e

install_needed() {
    ! command -v php8.3 >/dev/null 2>&1 || ! php8.3 -m | grep -q '^dom$' || ! command -v mysql >/dev/null 2>&1
}

if install_needed; then
    echo "[bootstrap] installing PHP 8.3 + MariaDB…"
    if [ ! -f /etc/apt/sources.list.d/sury-php.list ]; then
        curl -sSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /usr/share/keyrings/sury-php.gpg
        echo "deb [signed-by=/usr/share/keyrings/sury-php.gpg] https://packages.sury.org/php/ bookworm main" > /etc/apt/sources.list.d/sury-php.list
    fi
    # wait for any competing apt process (up to ~5 minutes)
    for i in $(seq 1 20); do
        pgrep -f 'apt-get' > /dev/null || break
        sleep 15
    done
    apt-get update -y >/dev/null 2>&1 || true
    apt-get install -y --no-install-recommends \
        php8.3 php8.3-cli php8.3-mbstring php8.3-xml php8.3-dom php8.3-mysql \
        php8.3-curl php8.3-zip php8.3-bcmath php8.3-gd php8.3-intl \
        php8.3-tokenizer php8.3-fileinfo \
        default-mysql-server default-mysql-client >/dev/null 2>&1 || true
fi

ln -sf /usr/bin/php8.3 /usr/bin/php

# Composer
if ! command -v composer >/dev/null 2>&1; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer >/dev/null 2>&1 || true
fi

# MariaDB
mkdir -p /var/run/mysqld
chown mysql:mysql /var/run/mysqld || true
service mariadb start >/dev/null 2>&1 || service mysql start >/dev/null 2>&1 || true
sleep 3
mysql -e "CREATE DATABASE IF NOT EXISTS engleza_wiki CHARACTER SET utf8mb4; \
          CREATE DATABASE IF NOT EXISTS engleza_wiki_test CHARACTER SET utf8mb4; \
          CREATE USER IF NOT EXISTS 'engleza'@'localhost' IDENTIFIED BY 'engleza_pass'; \
          GRANT ALL ON engleza_wiki.* TO 'engleza'@'localhost'; \
          GRANT ALL ON engleza_wiki_test.* TO 'engleza'@'localhost'; \
          FLUSH PRIVILEGES;" 2>/dev/null || true

# If the app database is empty, migrate & seed.
if [ -f /app/artisan ]; then
    HAS_USERS=$(mysql -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='engleza_wiki' AND table_name='users';" 2>/dev/null || echo 0)
    if [ "${HAS_USERS:-0}" = "0" ]; then
        cd /app && php artisan migrate --force --no-interaction >/dev/null 2>&1 || true
        cd /app && php artisan db:seed --force --no-interaction >/dev/null 2>&1 || true
    fi
fi

echo "[bootstrap] ready. PHP: $(php -v | head -1)"
