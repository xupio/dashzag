<section>
    <p class="muted" style="margin-bottom:1rem;">
        Once your account is deleted, all resources and data will be permanently deleted.
    </p>

    @if ($errors->userDeletion->any())
        <div class="alert alert-danger">
            {{ $errors->userDeletion->first('password') }}
        </div>
    @endif

    <form method="POST" action="{{ route('profile.destroy') }}" style="max-width:420px;">
        @csrf
        @method('delete')

        <div class="form-group">
            <label class="label" for="delete_password">Confirm your password to delete account</label>
            <input id="delete_password" name="password" type="password" class="form-control" placeholder="Password">
        </div>

        <button class="btn btn-danger" type="submit" onclick="return confirm('Are you sure you want to delete your account?');">
            Delete Account
        </button>
    </form>
</section>
