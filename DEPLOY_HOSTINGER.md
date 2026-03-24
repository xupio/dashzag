# Hostinger Deploy Checklist

Use this flow for ZagChain updates.

## Server Paths

- App folder: `/home/u723968965/laravel-app`
- Domain root: `/home/u723968965/domains/zagchain.net/public_html`
- Domain: `https://zagchain.net`

## Local Update Flow

Run locally before deployment:

```powershell
cd "C:\xampp\htdocs\zag2 - Copy\example-app"
git status
git add .
git commit -m "Your update message"
git push origin main
```

## Hostinger Update Flow

SSH into Hostinger, then run:

```bash
cd /home/u723968965/laravel-app
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
cp -a /home/u723968965/laravel-app/public/. /home/u723968965/domains/zagchain.net/public_html/
```

## Public Index Path

File:

- `/home/u723968965/domains/zagchain.net/public_html/index.php`

These lines must point to the app folder:

```php
require __DIR__.'/../../../laravel-app/vendor/autoload.php';
$app = require_once __DIR__.'/../../../laravel-app/bootstrap/app.php';
```

## Production .env Basics

File:

- `/home/u723968965/laravel-app/.env`

Minimum settings:

```env
APP_NAME=ZagChain
APP_ENV=production
APP_DEBUG=false
APP_URL=https://zagchain.net

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=YOUR_DB_NAME
DB_USERNAME=YOUR_DB_USER
DB_PASSWORD=YOUR_DB_PASSWORD

MAIL_MAILER=log
```

Switch back to SMTP only after the site is stable.

## Storage Link

If needed:

```bash
ln -s /home/u723968965/laravel-app/storage/app/public /home/u723968965/domains/zagchain.net/public_html/storage
```

## Quick Checks

Open after each deployment:

- `https://zagchain.net`
- `https://zagchain.net/login`
- `https://zagchain.net/how-it-works`

If something breaks:

```bash
cd /home/u723968965/laravel-app
tail -n 120 storage/logs/laravel.log
```

## Notes

- Do not rely on Hostinger Git wizard for this app unless it exposes a real app folder you can manage by SSH.
- The stable flow is: local push -> SSH `git pull` -> copy `public/` -> artisan cache commands.
