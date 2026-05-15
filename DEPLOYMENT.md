# 🚀 Gebeta Deployment Guide

## Quick Deployment Options

### Option 1: Deploy to Shared Hosting (Easiest)
**Best for: Beginners, Low traffic**

#### Recommended Hosts:
- **Hostinger** ($2-5/month) - hostinger.com
- **Namecheap** ($3-8/month) - namecheap.com
- **Bluehost** ($3-10/month) - bluehost.com

#### Steps:

1. **Purchase Hosting + Domain**
   - Get PHP hosting with MySQL
   - Register domain (e.g., gebeta.com)

2. **Upload Files via FTP**
   ```bash
   # Use FileZilla or cPanel File Manager
   # Upload all files to public_html/
   ```

3. **Create MySQL Database**
   - Go to cPanel → MySQL Databases
   - Create database: `gebeta_db`
   - Create user: `gebeta_user`
   - Import `gebeta.sql`

4. **Update .env File**
   ```env
   DB_HOST=localhost
   DB_NAME=gebeta_db
   DB_USER=gebeta_user
   DB_PASS=your_password
   
   BREVO_API_KEY=your_brevo_key
   BREVO_SENDER_EMAIL=noreply@yourdomain.com
   ```

5. **Set Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/menu/
   chmod 755 uploads/restaurants/
   ```

6. **Done!** Visit: https://yourdomain.com

---

### Option 2: Deploy to VPS (Recommended)
**Best for: Production, Scalability**

#### Recommended Providers:
- **DigitalOcean** ($6/month) - digitalocean.com
- **Linode** ($5/month) - linode.com
- **Vultr** ($6/month) - vultr.com
- **AWS Lightsail** ($5/month) - aws.amazon.com/lightsail

#### Steps:

1. **Create Ubuntu 22.04 Server**
   ```bash
   # SSH into your server
   ssh root@your_server_ip
   ```

2. **Install LAMP Stack**
   ```bash
   # Update system
   sudo apt update && sudo apt upgrade -y
   
   # Install Apache
   sudo apt install apache2 -y
   
   # Install MySQL
   sudo apt install mysql-server -y
   
   # Install PHP 8.1+
   sudo apt install php8.1 php8.1-mysql php8.1-curl php8.1-mbstring php8.1-xml -y
   
   # Enable Apache modules
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

3. **Setup MySQL Database**
   ```bash
   sudo mysql
   ```
   ```sql
   CREATE DATABASE gebeta;
   CREATE USER 'gebeta'@'localhost' IDENTIFIED BY 'strong_password_here';
   GRANT ALL PRIVILEGES ON gebeta.* TO 'gebeta'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ```

4. **Upload Your Code**
   ```bash
   # On your local machine
   scp -r /home/e/Gebeta/* root@your_server_ip:/var/www/html/
   
   # Or use Git
   cd /var/www/html/
   git clone https://github.com/yourusername/gebeta.git
   cd gebeta
   ```

5. **Import Database**
   ```bash
   mysql -u gebeta -p gebeta < gebeta.sql
   ```

6. **Configure Apache**
   ```bash
   sudo nano /etc/apache2/sites-available/gebeta.conf
   ```
   
   Add:
   ```apache
   <VirtualHost *:80>
       ServerName yourdomain.com
       ServerAlias www.yourdomain.com
       DocumentRoot /var/www/html/gebeta
       
       <Directory /var/www/html/gebeta>
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
       
       ErrorLog ${APACHE_LOG_DIR}/gebeta_error.log
       CustomLog ${APACHE_LOG_DIR}/gebeta_access.log combined
   </VirtualHost>
   ```
   
   ```bash
   sudo a2ensite gebeta.conf
   sudo systemctl reload apache2
   ```

7. **Set Permissions**
   ```bash
   sudo chown -R www-data:www-data /var/www/html/gebeta
   sudo chmod -R 755 /var/www/html/gebeta
   sudo chmod -R 775 /var/www/html/gebeta/uploads
   ```

8. **Setup SSL (Free with Let's Encrypt)**
   ```bash
   sudo apt install certbot python3-certbot-apache -y
   sudo certbot --apache -d yourdomain.com -d www.yourdomain.com
   ```

9. **Update .env**
   ```bash
   nano /var/www/html/gebeta/.env
   ```

10. **Done!** Visit: https://yourdomain.com

---

### Option 3: Deploy to Heroku (Free Tier Available)
**Best for: Quick testing, Free hosting**

1. **Install Heroku CLI**
   ```bash
   curl https://cli-assets.heroku.com/install.sh | sh
   heroku login
   ```

2. **Prepare Your App**
   ```bash
   cd /home/e/Gebeta
   
   # Create Procfile
   echo "web: vendor/bin/heroku-php-apache2" > Procfile
   
   # Create composer.json if not exists
   composer init
   ```

3. **Deploy**
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   
   heroku create gebeta-app
   heroku addons:create cleardb:ignite
   
   git push heroku main
   ```

4. **Import Database**
   ```bash
   heroku config | grep CLEARDB_DATABASE_URL
   # Use the URL to import your SQL file
   ```

---

### Option 4: Deploy to AWS (Professional)
**Best for: Enterprise, High traffic**

#### Use AWS Elastic Beanstalk:

1. **Install EB CLI**
   ```bash
   pip install awsebcli
   ```

2. **Initialize**
   ```bash
   cd /home/e/Gebeta
   eb init -p php-8.1 gebeta-app --region us-east-1
   ```

3. **Create Environment**
   ```bash
   eb create gebeta-production
   ```

4. **Deploy**
   ```bash
   eb deploy
   ```

5. **Setup RDS Database**
   - Go to AWS Console → RDS
   - Create MySQL database
   - Update .env with RDS credentials

---

## Pre-Deployment Checklist

### 1. Security
- [ ] Change all default passwords
- [ ] Update admin credentials
- [ ] Set strong DB password
- [ ] Enable HTTPS/SSL
- [ ] Update BREVO_API_KEY
- [ ] Remove debug mode

### 2. Configuration
- [ ] Update .env file
- [ ] Set correct BASE_URL
- [ ] Configure email settings
- [ ] Update Google OAuth credentials
- [ ] Set proper file permissions

### 3. Database
- [ ] Import gebeta.sql
- [ ] Update sample data
- [ ] Create admin account
- [ ] Test database connection

### 4. Testing
- [ ] Test registration
- [ ] Test login
- [ ] Test ordering flow
- [ ] Test payment methods
- [ ] Test email delivery
- [ ] Test on mobile devices

### 5. Performance
- [ ] Enable PHP OPcache
- [ ] Enable Gzip compression
- [ ] Optimize images
- [ ] Setup CDN (optional)
- [ ] Enable browser caching

---

## Post-Deployment

### 1. Setup Monitoring
```bash
# Install monitoring tools
sudo apt install htop -y

# Setup log rotation
sudo nano /etc/logrotate.d/gebeta
```

### 2. Setup Backups
```bash
# Daily database backup
crontab -e

# Add:
0 2 * * * mysqldump -u gebeta -p gebeta > /backups/gebeta_$(date +\%Y\%m\%d).sql
```

### 3. Setup Firewall
```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp
sudo ufw enable
```

### 4. Performance Tuning
```bash
# Enable PHP OPcache
sudo nano /etc/php/8.1/apache2/php.ini

# Add:
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

---

## Domain Setup

### 1. Point Domain to Server
Add these DNS records:

```
Type    Name    Value               TTL
A       @       your_server_ip      3600
A       www     your_server_ip      3600
```

### 2. Wait for DNS Propagation (5-48 hours)

### 3. Test
```bash
ping yourdomain.com
```

---

## Troubleshooting

### Issue: 500 Internal Server Error
```bash
# Check Apache error logs
sudo tail -f /var/log/apache2/error.log

# Check PHP errors
sudo tail -f /var/log/apache2/gebeta_error.log
```

### Issue: Database Connection Failed
```bash
# Test MySQL connection
mysql -u gebeta -p

# Check MySQL status
sudo systemctl status mysql
```

### Issue: File Upload Not Working
```bash
# Fix permissions
sudo chmod -R 775 /var/www/html/gebeta/uploads
sudo chown -R www-data:www-data /var/www/html/gebeta/uploads
```

### Issue: Email Not Sending
- Check BREVO_API_KEY in .env
- Whitelist server IP in Brevo dashboard
- Check spam folder

---

## Recommended: Quick Deploy Script

Save as `deploy.sh`:

```bash
#!/bin/bash

echo "🚀 Deploying Gebeta..."

# Pull latest code
git pull origin main

# Set permissions
chmod -R 755 .
chmod -R 775 uploads/

# Clear cache (if you add caching later)
# php artisan cache:clear

# Restart Apache
sudo systemctl restart apache2

echo "✅ Deployment complete!"
```

Run: `bash deploy.sh`

---

## Cost Estimate

### Shared Hosting
- **Monthly**: $3-10
- **Yearly**: $36-120
- **Best for**: 0-1000 users

### VPS
- **Monthly**: $5-20
- **Yearly**: $60-240
- **Best for**: 1000-10000 users

### AWS/Cloud
- **Monthly**: $20-200+
- **Yearly**: $240-2400+
- **Best for**: 10000+ users

---

## Support

Need help? Contact:
- Email: support@gebeta.com
- GitHub: github.com/yourusername/gebeta

---

**Good luck with your deployment! 🔥**
