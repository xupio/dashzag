<aside class="sidebar">
    <div class="sidebar-brand">{{ config('app.name', 'Laravel') }}</div>

    <ul class="sidebar-nav">
        <li>
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
        </li>
        <li>
            <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}">Profile Settings</a>
        </li>
    </ul>
</aside>

