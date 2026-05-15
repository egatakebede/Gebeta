#!/bin/bash

# Gebeta Quick Deploy Script
# Run: bash quick-deploy.sh

echo "🚀 Gebeta Quick Deploy"
echo "======================"
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "⚠️  Please run as root: sudo bash quick-deploy.sh"
    exit
fi

# Update system
echo "📦 Updating system..."
apt update && apt upgrade -y

# Install LAMP stack
echo "🔧 Installing Apache, MySQL, PHP..."
apt install apache2 mysql-server php8.1 php8.1-mysql php8.1-curl php8.1-mbstring php8.1-xml -y

# Enable Apache modules
echo "⚙️  Configuring Apache..."
a2enmod rewrite
systemctl restart apache2

# Setup MySQL
echo "🗄️  Setting up database..."
read -p "Enter MySQL password for 'gebeta' user: " DB_PASS

mysql -e "CREATE DATABASE IF NOT EXISTS gebeta;"
mysql -e "CREATE USER IF NOT EXISTS 'gebeta'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -e "GRANT ALL PRIVILEGES ON gebeta.* TO 'gebeta'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Import database
if [ -f "gebeta.sql" ]; then
    echo "📥 Importing database..."
    mysql -u gebeta -p$DB_PASS gebeta < gebeta.sql
fi

# Copy files
echo "📁 Copying files..."
cp -r . /var/www/html/gebeta/

# Set permissions
echo "🔐 Setting permissions..."
chown -R www-data:www-data /var/www/html/gebeta
chmod -R 755 /var/www/html/gebeta
chmod -R 775 /var/www/html/gebeta/uploads

# Create .env if not exists
if [ ! -f "/var/www/html/gebeta/.env" ]; then
    echo "📝 Creating .env file..."
    cat > /var/www/html/gebeta/.env << EOF
DB_HOST=127.0.0.1
DB_NAME=gebeta
DB_USER=gebeta
DB_PASS=$DB_PASS

BREVO_API_KEY=your_brevo_api_key_here
BREVO_SENDER_EMAIL=noreply@yourdomain.com
EOF
fi

# Configure Apache
echo "🌐 Configuring Apache virtual host..."
cat > /etc/apache2/sites-available/gebeta.conf << 'EOF'
<VirtualHost *:80>
    ServerAdmin admin@localhost
    DocumentRoot /var/www/html/gebeta
    
    <Directory /var/www/html/gebeta>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/gebeta_error.log
    CustomLog ${APACHE_LOG_DIR}/gebeta_access.log combined
</VirtualHost>
EOF

a2ensite gebeta.conf
a2dissite 000-default.conf
systemctl reload apache2

# Setup firewall
echo "🔥 Configuring firewall..."
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 22/tcp
ufw --force enable

# Get server IP
SERVER_IP=$(curl -s ifconfig.me)

echo ""
echo "✅ Deployment Complete!"
echo "======================"
echo ""
echo "🌐 Your app is live at: http://$SERVER_IP"
echo ""
echo "📝 Next steps:"
echo "1. Update .env file: nano /var/www/html/gebeta/.env"
echo "2. Add your Brevo API key"
echo "3. Point your domain to: $SERVER_IP"
echo "4. Setup SSL: sudo certbot --apache"
echo ""
echo "🔑 Admin Login:"
echo "   Email: admin@gebeta.com"
echo "   Password: admin123"
echo ""
echo "📚 Full guide: /var/www/html/gebeta/DEPLOYMENT.md"
echo ""
