<?php

use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('public marketing pages render successfully', function () {
    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('We build mining growth')
        ->assertDontSee('ZagChain system');

    $this->get(route('marketing.about'))
        ->assertOk()
        ->assertSee('How It Works')
        ->assertSee('ZagChain system')
        ->assertSee('A simple client journey');

    $this->get(route('marketing.how-it-works'))
        ->assertOk()
        ->assertSee('Reward structure');

    $this->get('/packages')->assertRedirect('/');
    $this->get('/media')->assertRedirect('/');
    $this->get('/references')->assertRedirect('/');
});

