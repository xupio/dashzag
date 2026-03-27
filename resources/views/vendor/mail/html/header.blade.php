@props(['url'])
<tr>
<td class="header" style="padding: 28px 0 20px;">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<img src="{{ asset('branding/zagchain-logo-auth.png') }}" alt="{{ config('app.name', 'ZagChain') }}" style="max-width: 180px; width: 100%; height: auto; display: block; margin: 0 auto 10px;">
<div style="font-size: 18px; font-weight: 700; color: #0f172a; text-align: center;">
{{ config('app.name', 'ZagChain') }}
</div>
</a>
</td>
</tr>
