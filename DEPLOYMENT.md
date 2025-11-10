# Deployment Guide for SmallTime v3

This guide will help you deploy SmallTime v3 to your AWS EC2 server at `donsmoore.com/timeclock/v3`.

## Prerequisites

- AWS EC2 instance running Ubuntu/Debian
- Apache 2 web server
- PHP 8.3 or higher (8.3.6 recommended)
- Composer installed
- Node.js and npm installed
- Git installed
- SSH access to your EC2 instance

## Initial Server Setup

### 1. Connect to your EC2 instance

```bash
ssh -i /path/to/your-key.pem ubuntu@your-ec2-ip
```

### 2. Install Required Software

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP and required extensions
sudo apt install -y php8.3 php8.3-cli php8.3-common php8.3-mysql php8.3-zip php8.3-gd php8.3-mbstring php8.3-curl php8.3-xml php8.3-bcmath

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js and npm
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install Apache and enable mod_rewrite
sudo apt install -y apache2
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 3. Clone the Repository

```bash
# Create directory structure
sudo mkdir -p /var/www/html/donsmoore.com/timeclock/v3
sudo chown -R $USER:$USER /var/www/html/donsmoore.com/timeclock/v3

# Clone the repository
cd /var/www/html/donsmoore.com/timeclock/v3
git clone https://github.com/donsmoore/small-time-v3.git .

# Or if using SSH:
# git clone git@github.com:donsmoore/small-time-v3.git .
```

### 4. Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Edit .env file with your production settings
nano .env
```

Update the following in `.env`:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://donsmoore.com/timeclock/v3` (or your domain)
- Database credentials
- Any other environment-specific settings

### 5. Install Dependencies

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies
npm ci

# Build frontend assets
npm run build
```

### 6. Set Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/html/donsmoore.com/timeclock/v3

# Set permissions
sudo chmod -R 775 storage bootstrap/cache
```

### 7. Configure Apache

```bash
# Copy the Apache configuration
sudo cp apache-config.conf /etc/apache2/sites-available/donsmoore-timeclock-v3.conf

# Edit the configuration if needed
sudo nano /etc/apache2/sites-available/donsmoore-timeclock-v3.conf

# Enable the site
sudo a2ensite donsmoore-timeclock-v3.conf

# Disable default site (optional)
sudo a2dissite 000-default.conf

# Test Apache configuration
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2
```

### 8. Run Database Migrations

```bash
cd /var/www/html/donsmoore.com/timeclock/v3
php artisan migrate --force
```

### 9. Generate Application Key (if needed)

```bash
php artisan key:generate
```

### 10. Cache Configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Deployment Script

A deployment script (`deploy.sh`) is included for easy updates. Make it executable and run it:

```bash
chmod +x deploy.sh
./deploy.sh
```

Or run it manually:

```bash
cd /var/www/html/donsmoore.com/timeclock/v3
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## Updating the Application

To update the application after making changes:

1. **On your local machine:**
   ```bash
   git add .
   git commit -m "Your commit message"
   git push origin main
   ```

2. **On the server:**
   ```bash
   cd /var/www/html/donsmoore.com/timeclock/v3
   ./deploy.sh
   ```

## Troubleshooting

### Permission Issues
```bash
sudo chown -R www-data:www-data /var/www/html/donsmoore.com/timeclock/v3
sudo chmod -R 775 storage bootstrap/cache
```

### Apache Not Serving Laravel Routes
- Ensure `mod_rewrite` is enabled: `sudo a2enmod rewrite`
- Check `.htaccess` exists in `public/` directory
- Verify Apache configuration allows `.htaccess` overrides

### Assets Not Loading
- Run `npm run build` to rebuild assets
- Check `public/build` directory exists and has files
- Verify file permissions

### Database Connection Issues
- Check `.env` file has correct database credentials
- Ensure database server is running
- Verify database user has proper permissions

## Security Considerations

1. **Keep `.env` secure** - Never commit `.env` to git
2. **Set `APP_DEBUG=false`** in production
3. **Use HTTPS** - Configure SSL certificate
4. **Regular updates** - Keep dependencies updated
5. **Firewall** - Configure AWS Security Groups appropriately
6. **Backups** - Set up regular database backups

## SSL/HTTPS Setup (Recommended)

1. Install Certbot:
   ```bash
   sudo apt install certbot python3-certbot-apache
   ```

2. Obtain SSL certificate:
   ```bash
   sudo certbot --apache -d donsmoore.com -d www.donsmoore.com
   ```

3. Auto-renewal is set up automatically

## Support

For issues or questions, please open an issue on GitHub: https://github.com/donsmoore/small-time-v3

