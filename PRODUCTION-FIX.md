# Fix for 404 Asset Errors on Production

## Problem
Assets are being requested from `https://donsmoore.com/build/assets/...` instead of `https://donsmoore.com/timeclock/v3/build/assets/...`

## Solution

### Option 1: Rebuild Assets on Production (Recommended)

On your production server, navigate to the app directory and rebuild:

```bash
cd /var/www/html/donsmoore.com/timeclock/v3
npm run build
```

This will rebuild the assets with the correct base path from `vite.config.js` (`base: '/timeclock/v3/'`).

### Option 2: Set APP_URL in Production .env

If rebuilding doesn't work, ensure your production `.env` file has the correct URL:

```bash
APP_URL=https://donsmoore.com/timeclock/v3
```

Then clear Laravel's config cache:
```bash
php artisan config:clear
php artisan cache:clear
```

### Verify Assets Are Built Correctly

After rebuilding, check that the manifest.json references the correct paths:
```bash
cat public/build/manifest.json
```

The file paths in manifest.json should be relative (e.g., `assets/app-xxx.css`), and Laravel will automatically prepend `/timeclock/v3/build/` when generating URLs if the base path is configured correctly.

