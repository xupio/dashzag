<?php

use App\Models\User;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('admin can view the network admin page', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $sponsor = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $branchHead = User::factory()->create([
        'email_verified_at' => now(),
        'sponsor_user_id' => $sponsor->id,
        'name' => 'Branch Head',
        'email' => 'branch@example.com',
    ]);

    User::factory()->create([
        'email_verified_at' => now(),
        'sponsor_user_id' => $branchHead->id,
        'name' => 'Downline User',
        'email' => 'downline@example.com',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.network-admin'));

    $response->assertOk();
    $response->assertSee('Network Admin');
    $response->assertSee('Visual sponsor tree');
    $response->assertSee('Branch Head');
    $response->assertSee('downline@example.com');
    $response->assertSee('Click any node for branch details');
});

test('non admin cannot view the network admin page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'user',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.network-admin'))
        ->assertForbidden();
});

test('admin can focus the network tree on a selected branch', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $rootA = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Root Alpha',
        'email' => 'rootalpha@example.com',
    ]);

    $rootB = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Root Beta',
        'email' => 'rootbeta@example.com',
    ]);

    User::factory()->create([
        'email_verified_at' => now(),
        'sponsor_user_id' => $rootA->id,
        'name' => 'Alpha Child',
        'email' => 'alphachild@example.com',
    ]);

    User::factory()->create([
        'email_verified_at' => now(),
        'sponsor_user_id' => $rootB->id,
        'name' => 'Beta Child',
        'email' => 'betachild@example.com',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.network-admin', [
        'tree_focus' => $rootA->id,
        'tree_depth' => 3,
    ]));

    $response->assertOk();
    $response->assertSee('Focused on');
    $response->assertSee('Root Alpha');
    $response->assertSee('The chart now shows only this sponsor branch.');
    $response->assertSee('Depth 3');
});

test('admin can export the focused network branch', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $root = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Export Root',
        'email' => 'exportroot@example.com',
    ]);

    User::factory()->create([
        'email_verified_at' => now(),
        'sponsor_user_id' => $root->id,
        'name' => 'Export Child',
        'email' => 'exportchild@example.com',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.network-admin.export', [
        'tree_focus' => $root->id,
        'tree_depth' => 3,
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $csv = $response->streamedContent();
    expect($csv)->toContain('Focused branch');
    expect($csv)->toContain('exportroot@example.com');
    expect($csv)->toContain('Export Child');
});

test('admin can open a printable focused network branch summary', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $root = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Printable Root',
        'email' => 'printableroot@example.com',
    ]);

    User::factory()->create([
        'email_verified_at' => now(),
        'sponsor_user_id' => $root->id,
        'name' => 'Printable Child',
        'email' => 'printablechild@example.com',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.network-admin.print', [
        'tree_focus' => $root->id,
        'tree_depth' => 3,
    ]));

    $response->assertOk();
    $response->assertSee('Branch Summary');
    $response->assertSee('Network Admin Branch View');
    $response->assertSee('printableroot@example.com');
    $response->assertSee('Printable Child');
});

