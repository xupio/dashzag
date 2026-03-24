<section>
    <p class="muted" style="margin-bottom:1rem;">Upload a profile photo for the dashboard and account menu. Name and email are locked.</p>

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div class="d-flex align-items-center gap-3" style="margin-bottom:1rem;">
            <img
                src="{{ $user->profilePhotoUrl() }}"
                alt="{{ $user->name }}"
                style="width:72px; height:72px; object-fit:cover; border-radius:999px; border:1px solid rgba(0,0,0,.08);"
            >
            <div>
                <div style="font-weight:600;">{{ $user->name }}</div>
                <div class="muted">{{ $user->displayEmail() }}</div>
            </div>
        </div>

        <div class="form-group">
            <label class="label" for="profile_photo">Profile photo</label>
            <input id="profile_photo" name="profile_photo" type="file" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            <div class="muted" style="margin-top:.35rem;">Accepted: JPG, PNG, or WEBP. Maximum 2 MB.</div>
            @error('profile_photo')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div style="display:flex; align-items:center; gap:.65rem;">
            <button class="btn btn-primary" type="submit">Upload Photo</button>
            @if (session('status') === 'profile-updated')
                <span class="muted">Saved.</span>
            @endif
        </div>
    </form>
</section>
