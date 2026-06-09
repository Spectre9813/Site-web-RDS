#!/bin/bash
set -e

DOMAIN="stageflow.fr"
CERT="/etc/ssl/certs/${DOMAIN}.crt"
KEY="/etc/ssl/private/${DOMAIN}.key"
CONF="/etc/ssl/openssl-san.cnf"
PROD_DIR="/var/www/html/prod"

echo "? HTTPS entrypoint starting"

# ------------------------------------------------------------------
# 1. Generate self-signed certificate if missing
# ------------------------------------------------------------------
if [ ! -f "$CERT" ] || [ ! -f "$KEY" ]; then
    echo "? Generating self-signed SSL certificate"

    cat > "$CONF" <<'EOF'
[req]
default_bits = 2048
prompt = no
default_md = sha256
req_extensions = req_ext
distinguished_name = dn

[dn]
C = FR
ST = Meurthe-et-Moselle
L = Nancy
O = StageFlow
CN = stageflow.fr

[req_ext]
subjectAltName = @alt_names

[alt_names]
DNS.1 = stageflow.fr
DNS.2 = www.stageflow.fr
DNS.3 = prod.stageflow.fr
DNS.4 = cdn.stageflow.fr
EOF

    openssl req -x509 -nodes -days 365 \
        -newkey rsa:2048 \
        -keyout "$KEY" \
        -out "$CERT" \
        -config "$CONF"

    chmod 600 "$KEY"
else
    echo "? SSL certificate already exists"
fi

# ------------------------------------------------------------------
# 2. Composer dependencies inside container
# ------------------------------------------------------------------
if [ -f "$PROD_DIR/composer.json" ]; then
    echo "? Found composer.json in $PROD_DIR. Checking dependencies..."

    composer install \
        --working-dir="$PROD_DIR" \
        --no-interaction \
        --optimize-autoloader

    if [ -d "$PROD_DIR/vendor" ]; then
        chown -R www-data:www-data "$PROD_DIR/vendor"
    fi
else
    echo "? No composer.json found in $PROD_DIR, skipping installation."
fi

# ------------------------------------------------------------------
# 3. Ensure useful runtime folders exist
# ------------------------------------------------------------------
mkdir -p /var/log/apache2
chown -R www-data:www-data /var/log/apache2 || true

# Dossiers d'upload pour les candidatures (CV + lettres de motivation).
# ApplicationController::handleFileUpload() y écrit, ils doivent donc
# appartenir à www-data (l'utilisateur Apache).
mkdir -p /var/www/html/cdn/uploads/cv /var/www/html/cdn/uploads/lm
chown -R www-data:www-data /var/www/html/cdn/uploads
chmod -R 755 /var/www/html/cdn/uploads

# ------------------------------------------------------------------
# 4. Start Apache
# ------------------------------------------------------------------
echo "? Starting Apache..."
exec apache2-foreground
