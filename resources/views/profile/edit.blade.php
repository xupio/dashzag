<x-app-layout>
    <x-slot name="header">
        Account Settings
    </x-slot>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">Profile Photo</div>
                <div class="card-body">
                    @include('profile.partials.update-profile-photo-form')
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">Update Password</div>
                <div class="card-body">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

