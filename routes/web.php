<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::group(['prefix' => 'email'], function () {
        Route::view('inbox', 'pages.email.inbox');
        Route::view('read', 'pages.email.read');
        Route::view('compose', 'pages.email.compose');
    });

    Route::group(['prefix' => 'apps'], function () {
        Route::view('chat', 'pages.apps.chat');
        Route::view('calendar', 'pages.apps.calendar');
    });

    Route::group(['prefix' => 'ui-components'], function () {
        Route::view('accordion', 'pages.ui-components.accordion');
        Route::view('alerts', 'pages.ui-components.alerts');
        Route::view('badges', 'pages.ui-components.badges');
        Route::view('breadcrumbs', 'pages.ui-components.breadcrumbs');
        Route::view('buttons', 'pages.ui-components.buttons');
        Route::view('button-group', 'pages.ui-components.button-group');
        Route::view('cards', 'pages.ui-components.cards');
        Route::view('carousel', 'pages.ui-components.carousel');
        Route::view('collapse', 'pages.ui-components.collapse');
        Route::view('dropdowns', 'pages.ui-components.dropdowns');
        Route::view('list-group', 'pages.ui-components.list-group');
        Route::view('media-object', 'pages.ui-components.media-object');
        Route::view('modal', 'pages.ui-components.modal');
        Route::view('navs', 'pages.ui-components.navs');
        Route::view('offcanvas', 'pages.ui-components.offcanvas');
        Route::view('pagination', 'pages.ui-components.pagination');
        Route::view('placeholders', 'pages.ui-components.placeholders');
        Route::view('popovers', 'pages.ui-components.popovers');
        Route::view('progress', 'pages.ui-components.progress');
        Route::view('scrollbar', 'pages.ui-components.scrollbar');
        Route::view('scrollspy', 'pages.ui-components.scrollspy');
        Route::view('spinners', 'pages.ui-components.spinners');
        Route::view('tabs', 'pages.ui-components.tabs');
        Route::view('toasts', 'pages.ui-components.toasts');
        Route::view('tooltips', 'pages.ui-components.tooltips');
    });

    Route::group(['prefix' => 'advanced-ui'], function () {
        Route::view('cropper', 'pages.advanced-ui.cropper');
        Route::view('owl-carousel', 'pages.advanced-ui.owl-carousel');
        Route::view('sortablejs', 'pages.advanced-ui.sortablejs');
        Route::view('sweet-alert', 'pages.advanced-ui.sweet-alert');
    });

    Route::group(['prefix' => 'forms'], function () {
        Route::view('basic-elements', 'pages.forms.basic-elements');
        Route::view('advanced-elements', 'pages.forms.advanced-elements');
        Route::view('editors', 'pages.forms.editors');
        Route::view('wizard', 'pages.forms.wizard');
    });

    Route::group(['prefix' => 'charts'], function () {
        Route::view('apex', 'pages.charts.apex');
        Route::view('chartjs', 'pages.charts.chartjs');
        Route::view('flot', 'pages.charts.flot');
        Route::view('peity', 'pages.charts.peity');
        Route::view('sparkline', 'pages.charts.sparkline');
    });

    Route::group(['prefix' => 'tables'], function () {
        Route::view('basic-tables', 'pages.tables.basic-tables');
        Route::view('data-table', 'pages.tables.data-table');
    });

    Route::group(['prefix' => 'icons'], function () {
        Route::view('lucide-icons', 'pages.icons.lucide-icons');
        Route::view('flag-icons', 'pages.icons.flag-icons');
        Route::view('mdi-icons', 'pages.icons.mdi-icons');
    });

    Route::group(['prefix' => 'general'], function () {
        Route::view('blank-page', 'pages.general.blank-page');
        Route::view('faq', 'pages.general.faq');
        Route::view('invoice', 'pages.general.invoice');
        Route::view('profile', 'pages.general.profile');
        Route::view('pricing', 'pages.general.pricing');
        Route::view('timeline', 'pages.general.timeline');
    });


    Route::group(['prefix' => 'error'], function () {
        Route::view('404', 'pages.error.404');
        Route::view('500', 'pages.error.500');
    });
});

require __DIR__.'/auth.php';

