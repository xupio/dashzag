<div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, rgba(10, 42, 130, 0.08), rgba(101, 113, 255, 0.04));">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
      <div>
        <div class="text-uppercase small fw-semibold text-primary mb-2">Admin safety center</div>
        <h5 class="mb-1">Production security snapshot</h5>
        <p class="text-secondary mb-0">Quick checks before migrations, payment updates, and major production changes. Use the actions below when you actually need to do something.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary btn-sm">Open account security</a>
        <a href="{{ route('dashboard.operations') }}" class="btn btn-outline-secondary btn-sm">Open operations</a>
        <a href="{{ route('dashboard.settings') }}" class="btn btn-outline-dark btn-sm">Open platform settings</a>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-md-4">
        <div class="border rounded p-3 h-100 bg-white">
          <div class="text-secondary small mb-1">Admin 2FA</div>
          <div class="fw-semibold mb-2">{{ $adminSafetyViewer->hasAdminTwoFactorEnabled() ? 'Enabled' : 'Action needed' }}</div>
          <div class="small text-secondary">
            @if ($adminSafetyViewer->hasAdminTwoFactorEnabled())
              Your admin login challenge is active and protecting dashboard access.
            @else
              Enable Admin 2FA from Account Settings before handling wallets, payouts, or user approvals.
            @endif
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="border rounded p-3 h-100 bg-white">
          <div class="text-secondary small mb-1">Before running migrations</div>
          <div class="fw-semibold mb-2">Create a fresh DB backup</div>
          <div class="small text-secondary">Export the production database before `php artisan migrate --force`, payment changes, or wallet updates.</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="border rounded p-3 h-100 bg-white">
          <div class="text-secondary small mb-1">Deployment reminder</div>
          <div class="fw-semibold mb-2">Rebuild caches after deploy</div>
          <div class="small text-secondary">Run `optimize:clear`, rebuild caches, and confirm login, wallet, buy-shares, and operations pages load cleanly.</div>
        </div>
      </div>
    </div>

    <div class="alert alert-light border mt-3 mb-0">
      Emergency note: if the admin device is lost, use the recovery command in <span class="fw-semibold">BACKUP_AND_RECOVERY.md</span> to disable 2FA on the server and re-enroll it.
    </div>

    <div class="row g-3 mt-1">
      <div class="col-lg-6">
        <div class="border rounded p-3 h-100 bg-white">
          <div class="fw-semibold mb-2">What to do before a deploy</div>
          <div class="small text-secondary mb-2">Use this every time you push production changes that affect data, auth, payments, or wallets.</div>
          <ol class="small text-secondary ps-3 mb-0">
            <li>Create a fresh database backup.</li>
            <li>Deploy the new code to Hostinger.</li>
            <li>Run migrations only if the release includes a new migration.</li>
            <li>Clear and rebuild Laravel caches.</li>
            <li>Test login, buy shares, wallet, and operations pages.</li>
          </ol>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="border rounded p-3 h-100 bg-white">
          <div class="fw-semibold mb-2">Quick server commands</div>
          <div class="small text-secondary mb-2">Run these after a normal code deploy on Hostinger.</div>
          <pre class="bg-light border rounded p-3 small mb-0" style="white-space: pre-wrap;">cd /home/u723968965/laravel-app
git pull origin main
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache</pre>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="border rounded p-3 h-100 bg-white">
          <div class="fw-semibold mb-2">Lost admin device recovery</div>
          <div class="small text-secondary mb-2">If your authenticator phone is lost, disable 2FA on the server first, then re-enroll it from your profile.</div>
          <pre class="bg-light border rounded p-3 small mb-2" style="white-space: pre-wrap;">cd /home/u723968965/laravel-app
php -r "require 'vendor/autoload.php'; \$app=require 'bootstrap/app.php'; \$kernel=\$app->make(Illuminate\Contracts\Console\Kernel::class); \$kernel->bootstrap(); \$user=App\Models\User::where('email','xupio.dso1234@gmail.com')->first(); if (! \$user) { echo 'Admin not found'; exit(1); } \$user->forceFill(['admin_two_factor_secret'=>null,'admin_two_factor_confirmed_at'=>null])->save(); echo 'Admin 2FA disabled for '.$user->email;"</pre>
          <div class="small text-secondary mb-0">After that: log in, open Profile, generate a new authenticator setup, scan the QR code, and confirm the 6-digit code.</div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="border rounded p-3 h-100 bg-white">
          <div class="fw-semibold mb-2">Printable checklist file</div>
          <div class="small text-secondary mb-2">The short runbook lives in the project if you want to keep it beside Hostinger.</div>
          <div class="bg-light border rounded p-3 small fw-semibold">PRODUCTION_BACKUP_CHECKLIST.md</div>
          <div class="small text-secondary mt-2 mb-0">Use it for backup timing, after-deploy checks, and the admin-device recovery flow.</div>
        </div>
      </div>
    </div>
  </div>
</div>
