# Production Backup Checklist

Keep this beside Hostinger for quick production work.

## Before Every Important Deploy

1. Export the production database from Hostinger phpMyAdmin.
2. Save the file with a clear name like:
   - `zagchain-prod-YYYY-MM-DD-before-deploy.sql`
3. Confirm GitHub has the latest code.
4. Deploy on the server.
5. Run:

```bash
cd /home/u723968965/laravel-app
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## After Every Deploy

Open and test:

- `https://zagchain.net/login`
- `https://zagchain.net/dashboard`
- `https://zagchain.net/profile`
- `https://zagchain.net/dashboard/buy-shares`
- `https://zagchain.net/dashboard/wallet`
- `https://zagchain.net/dashboard/operations`

## If Something Breaks

Run:

```bash
cd /home/u723968965/laravel-app
tail -n 120 storage/logs/laravel.log
```

Then:

- fix code and redeploy if it is a code issue
- restore the SQL backup if it is a bad data or migration issue

## If Admin Phone Or Device Is Lost

### Server command

Run:

```bash
cd /home/u723968965/laravel-app
php -r "require 'vendor/autoload.php'; \$app=require 'bootstrap/app.php'; \$kernel=\$app->make(Illuminate\Contracts\Console\Kernel::class); \$kernel->bootstrap(); \$user=App\Models\User::where('email','xupio.dso1234@gmail.com')->first(); if (! \$user) { echo 'Admin not found'; exit(1); } \$user->forceFill(['admin_two_factor_secret'=>null,'admin_two_factor_confirmed_at'=>null])->save(); echo 'Admin 2FA disabled for '.$user->email;"
```

### Browser steps

1. Open:
   - `https://zagchain.net/login`
2. Log in with admin email and password.
3. Open:
   - `https://zagchain.net/profile`
4. In `Admin 2FA`:
   - enter current password
   - click `Generate authenticator setup`
   - scan the new QR code with the new phone
   - enter the 6-digit code
   - click `Enable admin 2FA`

## Good Habit

- Always take the backup first.
