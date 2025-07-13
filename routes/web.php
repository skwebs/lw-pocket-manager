<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('account', 'account')->name('account');
    Volt::route('accounts', 'accounts')->name('accounts');
    Volt::route('account-types', 'account-types')->name('account-types');

    Volt::route('transaction-types', 'transaction-types')->name('transaction-types');
    Volt::route('transactions', 'transactions')->name('transactions');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
