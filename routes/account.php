<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;




Route::middleware(['auth'])->group(function () {
    // Route::redirect('accounts-types', 'account-types/create-account-type');

    Volt::route('account/create', 'account.create')->name('account.create');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});
