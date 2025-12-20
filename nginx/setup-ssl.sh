#!/bin/bash
# OvertimeStaff SSL Certificate Setup Script
# Run this on your production server

set -e

DOMAIN="overtimestaff.com"
EMAIL="admin@overtimestaff.com"

echo "============================================"
echo "OvertimeStaff SSL Certificate Setup"
echo "============================================"

# Check if certbot is installed
if ! command -v certbot &> /dev/null; then
    echo "Installing Certbot..."
    sudo apt update
    sudo apt install -y certbot python3-certbot-nginx
fi

# Create webroot directory for ACME challenges
sudo mkdir -p /var/www/certbot

# Option 1: Obtain certificate with wildcard (requires DNS validation)
echo ""
echo "Choose SSL certificate type:"
echo "1) Wildcard certificate (*.overtimestaff.com) - Recommended for white-label subdomains"
echo "2) Standard certificate (www.overtimestaff.com only)"
read -p "Enter choice [1/2]: " choice

if [ "$choice" == "1" ]; then
    echo ""
    echo "============================================"
    echo "Wildcard Certificate (DNS Validation)"
    echo "============================================"
    echo ""
    echo "This will require you to add a DNS TXT record."
    echo "Make sure you have access to your DNS provider."
    echo ""

    sudo certbot certonly \
        --manual \
        --preferred-challenges dns \
        -d "$DOMAIN" \
        -d "*.$DOMAIN" \
        --email "$EMAIL" \
        --agree-tos \
        --no-eff-email

    echo ""
    echo "Wildcard certificate obtained!"
    echo "Certificate covers: $DOMAIN, *.$DOMAIN"

else
    echo ""
    echo "============================================"
    echo "Standard Certificate (HTTP Validation)"
    echo "============================================"

    sudo certbot certonly \
        --nginx \
        -d "www.$DOMAIN" \
        -d "$DOMAIN" \
        --email "$EMAIL" \
        --agree-tos \
        --no-eff-email

    echo ""
    echo "Standard certificate obtained!"
    echo "Certificate covers: $DOMAIN, www.$DOMAIN"
fi

# Generate DH parameters if not exists
if [ ! -f /etc/letsencrypt/ssl-dhparams.pem ]; then
    echo ""
    echo "Generating DH parameters (this may take a few minutes)..."
    sudo openssl dhparam -out /etc/letsencrypt/ssl-dhparams.pem 2048
fi

# Create Let's Encrypt options file if not exists
if [ ! -f /etc/letsencrypt/options-ssl-nginx.conf ]; then
    echo ""
    echo "Creating SSL options file..."
    sudo tee /etc/letsencrypt/options-ssl-nginx.conf > /dev/null <<EOF
ssl_session_cache shared:le_nginx_SSL:10m;
ssl_session_timeout 1440m;
ssl_session_tickets off;
ssl_protocols TLSv1.2 TLSv1.3;
ssl_prefer_server_ciphers off;
ssl_ciphers "ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384";
EOF
fi

# Setup auto-renewal cron job
echo ""
echo "Setting up auto-renewal..."
(crontab -l 2>/dev/null | grep -v "certbot renew"; echo "0 3 * * * /usr/bin/certbot renew --quiet --post-hook 'systemctl reload nginx'") | crontab -

echo ""
echo "============================================"
echo "SSL Setup Complete!"
echo "============================================"
echo ""
echo "Next steps:"
echo "1. Copy nginx config: sudo cp nginx/overtimestaff.conf /etc/nginx/sites-available/"
echo "2. Enable site: sudo ln -sf /etc/nginx/sites-available/overtimestaff.conf /etc/nginx/sites-enabled/"
echo "3. Test config: sudo nginx -t"
echo "4. Reload nginx: sudo systemctl reload nginx"
echo ""
echo "Certificate location: /etc/letsencrypt/live/$DOMAIN/"
echo "Auto-renewal: Enabled (runs daily at 3 AM)"
