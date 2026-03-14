<?php

use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('public marketing pages render successfully', function () {
    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('Cloud mining subscriptions')
        ->assertSee('Alpha One');

    $this->get(route('marketing.about'))
        ->assertOk()
        ->assertSee('About the business');

    $this->get(route('marketing.packages'))
        ->assertOk()
        ->assertSee('Subscription products');

    $this->get(route('marketing.media'))
        ->assertOk()
        ->assertSee('Media library');

    $this->get(route('marketing.references'))
        ->assertOk()
        ->assertSee('Trust anchors for the public website');
});

