# Deployment Guide for SmallTime v3

This guide will help you deploy SmallTime v3 to your AWS EC2 server at `donsmoore.com/timeclock/v3`.

## Prerequisites

- AWS EC2 instance running Ubuntu/Debian
- Apache 2 web server
- PHP 8.3 or higher (8.3.6 recommended)
- Composer installed
- Node.js 20.19+ or 22.12+ (required for Vite 7)
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

# Install Node.js and npm (version 20.x LTS)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Verify Node.js version (should be 20.19+ or 22.12+)
node --version
npm --version

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

# Fix Git ownership issue (if you get "dubious ownership" errors)
git config --global --add safe.directory /var/www/html/donsmoore.com/timeclock/v3

# Or if using SSH:
# git clone git@github.com:donsmoore/small-time-v3.git .
# git config --global --add safe.directory /var/www/html/donsmoore.com/timeclock/v3
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
# Enable required Apache modules
sudo a2enmod rewrite
sudo a2enmod headers  # Optional: for security headers

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

# Check migration status first
php artisan migrate:status

# If tables already exist, you have two options:

# Option 1: Mark migrations as run without executing them (if tables already exist)
# php artisan migrate --pretend  # See what would run
# Then manually mark migrations as run in the migrations table

# Option 2: Run migrations (will fail if tables exist, but safe to ignore if structure matches)
php artisan migrate --force

# If you get "table already exists" errors but the tables are correct, you can:
# 1. Check if migrations table exists and mark migrations as run manually, OR
# 2. Skip the migration step if your database structure is already correct
```

### 9. Generate Application Key (if needed)

```bash
# Make sure .env file is writable
sudo chown $USER:www-data /var/www/html/donsmoore.com/timeclock/v3/.env
chmod 664 /var/www/html/donsmoore.com/timeclock/v3/.env

# Generate application key
php artisan key:generate

# After generating, secure the .env file
chmod 640 /var/www/html/donsmoore.com/timeclock/v3/.env
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

### Git Ownership Issues
If you get "dubious ownership" or "insufficient permission" errors when running git commands:

```bash
cd /var/www/html/donsmoore.com/timeclock/v3

# Fix ownership of entire directory temporarily for git operations
CURRENT_USER=$(whoami)
sudo chown -R $CURRENT_USER:$CURRENT_USER .

# Add as safe directory
git config --global --add safe.directory /var/www/html/donsmoore.com/timeclock/v3

# Now git pull should work
git pull origin main

# After git operations, restore ownership for web files
sudo chown -R www-data:www-data .
sudo chown -R $CURRENT_USER:$CURRENT_USER .git  # Keep .git owned by your user
```

### Permission Issues
```bash
# Fix ownership of application files
sudo chown -R www-data:www-data /var/www/html/donsmoore.com/timeclock/v3

# Make storage and cache writable
sudo chmod -R 775 storage bootstrap/cache

# Make .env writable temporarily for key generation
sudo chown $USER:www-data /var/www/html/donsmoore.com/timeclock/v3/.env
chmod 664 /var/www/html/donsmoore.com/timeclock/v3/.env

# After key generation, secure .env
chmod 640 /var/www/html/donsmoore.com/timeclock/v3/.env
```

### Apache Not Serving Laravel Routes
- Ensure `mod_rewrite` is enabled: `sudo a2enmod rewrite`
- Check `.htaccess` exists in `public/` directory
- Verify Apache configuration allows `.htaccess` overrides

### Assets Not Loading
- Run `npm run build` to rebuild assets
- Check `public/build` directory exists and has files
- Verify file permissions

### Node.js Version Too Old
If you get an error that Node.js version is too old (Vite 7 requires 20.19+ or 22.12+):

**First, fix any broken apt repositories (if you get repository errors):**

```bash
# Remove broken certbot PPA if it exists
sudo rm -f /etc/apt/sources.list.d/certbot-ubuntu-certbot-*.list
sudo rm -f /etc/apt/sources.list.d/certbot-ubuntu-certbot-*.sources

# Update apt
sudo apt update
```

**Then upgrade Node.js:**

```bash
# Remove old Node.js version
sudo apt remove nodejs npm -y
sudo apt autoremove -y

# Install Node.js 20.x LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Verify the version (should be 20.19+ or 22.12+)
node --version
```

**If the NodeSource script still fails, use nvm instead:**

```bash
# Install nvm (Node Version Manager)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
source ~/.bashrc

# Install Node.js 20.x using nvm
nvm install 20
nvm use 20
nvm alias default 20

# Verify
node --version
```

### Database Migration Errors
If you get "table already exists" errors:

```bash
# Check migration status
php artisan migrate:status

# If tables already exist and have the correct structure, you can:
# Option 1: Mark specific migrations as run (if they match your existing tables)
# Access your database and insert records into the migrations table:
# INSERT INTO migrations (migration, batch) VALUES ('2025_11_06_093749_create_clock_groups_table', 1);
# INSERT INTO migrations (migration, batch) VALUES ('2025_11_06_093753_create_clock_users_table', 1);
# INSERT INTO migrations (migration, batch) VALUES ('2025_11_06_093800_create_clock_events_table', 1);

# Option 2: Skip migrations if your database structure is already correct
# The application will work fine if tables exist with the correct structure

# Option 3: Fresh start (WARNING: This will delete all data!)
# php artisan migrate:fresh --force
```

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

