<?php

use App\Models\User;

test('security headers are added to web responses', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    $response->assertHeader('Content-Security-Policy');
    expect($response->headers->get('Content-Security-Policy'))->toContain("default-src 'self'");
    expect($response->headers->get('Content-Security-Policy'))->toContain("object-src 'none'");
});
