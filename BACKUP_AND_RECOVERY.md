# Backup And Recovery Runbook

Use this checklist for ZagChain production safety on Hostinger.

## Server Paths

- App folder: `/home/u723968965/laravel-app`
- Domain root: `/home/u723968965/domains/zagchain.net/public_html`
- Logs: `/home/u723968965/laravel-app/storage/logs/laravel.log`

## Database Backup

Before major changes, export the production database from Hostinger phpMyAdmin or use a command line dump if your plan allows it.

Recommended backup naming:

- `zagchain-prod-YYYY-MM-DD-before-deploy.sql`

Minimum rule:

- take a fresh backup before every deployment that changes migrations, payment logic, wallets, or admin auth

## App File Backup

Keep safe offline copies of:

- production `.env`
- payment wallet addresses and network notes
- current admin account email list

Never commit `.env` to Git.

## Secret Handling

Keep these only in production `.env` or a private password manager:

- `APP_KEY`
- database password
- SMTP password
- wallet-related admin notes

Do not store:

- seed phrases
- exchange recovery codes
- authenticator backup codes

inside the Laravel database or the Git repo.

## Wallet Operations Safety

For now, while using a personal wallet setup:

- use a wallet dedicated only to ZagChain
- use one BTC address
- use one USDT address on one network only
- test every new destination with a very small amount first

Operational rule:

- any wallet address change must be documented in admin settings and verified by a small test transfer before clients use it

## After Deploy Verification

Run on the server:

```bash
cd /home/u723968965/laravel-app
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Then verify:

- homepage loads
- login works
- admin profile loads
- buy-shares page loads
- wallet page loads
- admin operations page loads

## Emergency Log Check

If production fails:

```bash
cd /home/u723968965/laravel-app
tail -n 120 storage/logs/laravel.log
```

## Admin 2FA Recovery

If the admin loses the authenticator device, disable admin 2FA directly on the server.

Run:

```bash
cd /home/u723968965/laravel-app
php -r "require 'vendor/autoload.php'; \$app=require 'bootstrap/app.php'; \$kernel=\$app->make(Illuminate\Contracts\Console\Kernel::class); \$kernel->bootstrap(); \$user=App\Models\User::where('email','xupio.dso1234@gmail.com')->first(); if (! \$user) { echo 'Admin not found'; exit(1); } \$user->forceFill(['admin_two_factor_secret'=>null,'admin_two_factor_confirmed_at'=>null])->save(); echo 'Admin 2FA disabled for '.$user->email;"
```

Then log in normally and re-enable 2FA from:

- `https://zagchain.net/profile`

## Emergency Admin Password Reset

If needed, reset the admin password from the server:

```bash
cd /home/u723968965/laravel-app
php -r "require 'vendor/autoload.php'; \$app=require 'bootstrap/app.php'; \$kernel=\$app->make(Illuminate\Contracts\Console\Kernel::class); \$kernel->bootstrap(); \$user=App\Models\User::where('email','xupio.dso1234@gmail.com')->first(); if (! \$user) { echo 'Admin not found'; exit(1); } \$user->password=bcrypt('CHANGE-THIS-IMMEDIATELY'); \$user->save(); echo 'Password reset for '.$user->email;"
```

After using this:

- log in immediately
- change the password again from Account Settings
- verify 2FA is enabled again

## Recovery Priority Order

If something goes wrong in production:

1. Check `laravel.log`
2. Confirm `.env` values
3. Confirm the latest migration ran
4. Confirm `public_html/index.php` points to `laravel-app`
5. Rebuild Laravel caches
6. Restore database backup only if the issue is data corruption or a bad migration

## Good Habits

- deploy in small changes
- back up before migrations
- keep only one admin account for critical operations
- test login, payments, and wallet flows after every production deploy
