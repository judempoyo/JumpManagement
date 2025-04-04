<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;

use App\Models\PurchaseOrder;
use App\Models\Invoice;

Route::any('/',function(){
    return redirect ("/admin");
})->name('home');
Route::get('/purchase-orders/{purchaseOrder}/pdf', function (PurchaseOrder $purchaseOrder) {
    return view('purchase_orders.show', compact('purchaseOrder'));
})->name('purchase-orders.pdf')->middleware(['auth', 'verified']);
Route::get('/invoices/{invoice}/pdf', function (Invoice $invoice) {
    return view('invoices.show', compact('invoice'));
})->name('invoices.pdf') ->middleware(['auth', 'verified']);
/* 
Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

    Route::get('/purchase-orders/{purchaseOrder}/pdf', function (PurchaseOrder $purchaseOrder) {
        return view('purchase_orders.show', compact('purchaseOrder'));
    })->name('purchase-orders.pdf');
    Route::get('/invoices/{invoice}/pdf', function (Invoice $invoice) {
        return view('invoices.show', compact('invoice'));
    })->name('invoices.pdf');
Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
}); */

require __DIR__.'/auth.php';
