#!/usr/bin/env bash
set -e
exec 1>/proc/1/fd/1 2>/proc/1/fd/2

cd /var/www/deming

DB_HOST="${DB_HOST:-mysql}"

echo "Waiting for MySQL (${DB_HOST}) to be ready..."
until mysqladmin ping -h"${DB_HOST}" --silent 2>/dev/null; do
    echo "  Not ready, retrying in 3s..."
    sleep 3
done
echo "MySQL is ready."

# Permissions — toujours en root (avant le USER switch)
chown -R www-data:www-data /var/www/deming/storage /var/www/deming/bootstrap/cache
chmod -R 775 /var/www/deming/storage /var/www/deming/bootstrap/cache

# APP_KEY — générer seulement si absent
grep -q '^APP_KEY=base64:' .env || php artisan key:generate --no-interaction

# Initialisation DB
bash /etc/resetdb.sh
bash /etc/initialdb.sh

# Storage
php artisan storage:link --quiet

# Import référentiel et données de démo
bash /etc/uploadiso27001db.sh || echo "uploadiso27001db skipped"
bash /etc/userdemo.sh || echo "userdemo skipped"

# Passport v13 — régénérer uniquement les clés (passport:install --force recrée
# les oauth_clients à chaque démarrage, causant une erreur de clé dupliquée car
# l'id UUID est tronqué en entier 19 par MySQL sur le schéma bigIncrements)
echo "🔑 Generating Passport encryption keys..."
php artisan passport:keys --force || echo "Passport keys generation skipped"

# Créer les clients OAuth seulement s'ils n'existent pas encore
CLIENT_COUNT=$(mysql -h"${DB_HOST}" -u"${DB_USERNAME}" --password="${DB_PASSWORD}" "${DB_DATABASE}" \
    -se "SELECT COUNT(*) FROM oauth_clients;" 2>/dev/null || echo "1")
if [ "${CLIENT_COUNT}" = "0" ]; then
    php artisan passport:client --personal --no-interaction --name="${APP_NAME:-Laravel}" \
        || echo "Passport personal client creation skipped"
fi

if ls storage/oauth-*.key 2>/dev/null; then
    chown www-data:www-data storage/oauth-*.key
    chmod 600 storage/oauth-*.key
fi

echo "🔑 Passport installation complete"

# Services système
service cron start || true

# Copier le vhost nginx
rm -f /etc/nginx/sites-enabled/default

echo "✅ Deming initialization complete — starting services"

# Démarrer artisan serve en arrière-plan (port 8000 — cible du reverse proxy nginx)
php artisan serve --host 0.0.0.0 --port 8000 &

# Nginx en PID 1
exec nginx -g "daemon off;"
