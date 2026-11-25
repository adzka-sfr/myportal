<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Apps\DiscountController;
use App\Http\Controllers\Apps\ElectricityController;
use App\Http\Controllers\Apps\FuelController;
use App\Http\Controllers\Apps\HunianController;
use App\Http\Controllers\Apps\HunianLogController;
use App\Http\Controllers\Apps\HunianMasterController;
use App\Http\Controllers\Apps\HunianPaymentController;
use App\Http\Controllers\Apps\HunianResidentController;
use App\Http\Controllers\Apps\HunianTenantController;
use App\Http\Controllers\Apps\HunianTicketController;
use App\Http\Controllers\Apps\PenyewaController;
use App\Http\Controllers\Apps\PenyewaTicketController;
use App\Http\Controllers\Apps\ShoppingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [PortalController::class, 'dashboard'])->name('dashboard');

    Route::prefix('admin')->name('admin.')->middleware('superadmin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::prefix('apps/electricity')->name('apps.electricity')->middleware('application:electricity')->group(function () {
        Route::get('/', [ElectricityController::class, 'index'])->name('');
        Route::post('/calculate', [ElectricityController::class, 'calculate'])->middleware('feature:electricity,basic')->name('.calculate');
        Route::post('/breakdown', [ElectricityController::class, 'breakdown'])->middleware('feature:electricity,breakdown')->name('.breakdown');
    });

    Route::prefix('apps/discount')->name('apps.discount')->middleware('application:discount')->group(function () {
        Route::get('/', [DiscountController::class, 'index'])->name('');
        Route::post('/calculate', [DiscountController::class, 'calculate'])->middleware('feature:discount,basic')->name('.calculate');
        Route::post('/stacked', [DiscountController::class, 'stacked'])->middleware('feature:discount,stacked')->name('.stacked');
    });

    Route::prefix('apps/fuel')->name('apps.fuel')->middleware('application:fuel')->group(function () {
        Route::get('/', [FuelController::class, 'index'])->name('');
        Route::post('/calculate', [FuelController::class, 'calculate'])->middleware('feature:fuel,trip_cost')->name('.calculate');
        Route::post('/compare', [FuelController::class, 'compare'])->middleware('feature:fuel,compare_vehicles')->name('.compare');
    });

    Route::prefix('apps/shopping')->name('apps.shopping')->middleware('application:shopping')->group(function () {
        Route::get('/', [ShoppingController::class, 'index'])->name('');
        Route::post('/items', [ShoppingController::class, 'addItem'])->middleware('feature:shopping,checklist')->name('.items.store');
        Route::patch('/items/{item}/toggle', [ShoppingController::class, 'toggleItem'])->middleware('feature:shopping,checklist')->name('.items.toggle');
        Route::delete('/items/{item}', [ShoppingController::class, 'deleteItem'])->middleware('feature:shopping,checklist')->name('.items.destroy');
        Route::post('/lists', [ShoppingController::class, 'createList'])->middleware('feature:shopping,multiple_lists')->name('.lists.store');
        Route::delete('/lists/{list}', [ShoppingController::class, 'deleteList'])->middleware('feature:shopping,multiple_lists')->name('.lists.destroy');
    });

    Route::prefix('apps/hunian')->name('apps.hunian')->middleware(['superadmin', 'application:hunian'])->group(function () {
        Route::get('/', [HunianController::class, 'index'])->name('');

        // Monitoring & pergantian penghuni
        Route::post('/rooms/{room}/occupancies', [HunianController::class, 'occupy'])->name('.rooms.occupy');
        Route::put('/occupancies/{occupancy}', [HunianController::class, 'updateOccupancy'])->name('.occupancies.update');
        Route::post('/occupancies/{occupancy}/payments', [HunianController::class, 'pay'])->name('.occupancies.pay');
        Route::patch('/occupancies/{occupancy}/end', [HunianController::class, 'end'])->name('.occupancies.end');
        Route::post('/rooms/{room}/maintenance', [HunianController::class, 'startMaintenance'])->name('.rooms.maintenance.start');
        Route::patch('/maintenance/{maintenance}/finish', [HunianController::class, 'finishMaintenance'])->name('.maintenance.finish');

        // Verifikasi pembayaran
        Route::get('/payments', [HunianPaymentController::class, 'index'])->name('.payments');
        Route::patch('/payments/{payment}/match', [HunianPaymentController::class, 'match'])->name('.payments.match');
        Route::get('/payments/{payment}/proof', [HunianPaymentController::class, 'proof'])->name('.payments.proof');
        Route::delete('/payments/{payment}/proof', [HunianPaymentController::class, 'destroyProof'])->name('.payments.proof.destroy');

        // Master kamar & fasilitas
        Route::get('/master', [HunianMasterController::class, 'index'])->name('.master');
        Route::post('/master/rooms', [HunianMasterController::class, 'storeRoom'])->name('.master.rooms.store');
        Route::put('/master/rooms/{room}', [HunianMasterController::class, 'updateRoom'])->name('.master.rooms.update');
        Route::delete('/master/rooms/{room}', [HunianMasterController::class, 'destroyRoom'])->name('.master.rooms.destroy');
        Route::post('/master/facilities', [HunianMasterController::class, 'storeFacility'])->name('.master.facilities.store');
        Route::put('/master/facilities/{facility}', [HunianMasterController::class, 'updateFacility'])->name('.master.facilities.update');
        Route::delete('/master/facilities/{facility}', [HunianMasterController::class, 'destroyFacility'])->name('.master.facilities.destroy');

        // Log aktivitas
        Route::get('/residents', [HunianResidentController::class, 'index'])->name('.residents');
        Route::get('/logs', [HunianLogController::class, 'index'])->name('.logs');

        // Informasi penghuni (profil, KTP, status aktif)
        Route::get('/tenants', [HunianTenantController::class, 'index'])->name('.tenants');
        Route::post('/tenants', [HunianTenantController::class, 'store'])->name('.tenants.store');
        Route::put('/tenants/{tenant}', [HunianTenantController::class, 'update'])->name('.tenants.update');
        Route::patch('/tenants/{tenant}/active', [HunianTenantController::class, 'toggleActive'])->name('.tenants.active');
        Route::delete('/tenants/{tenant}', [HunianTenantController::class, 'destroy'])->name('.tenants.destroy');
        Route::get('/tenants/{tenant}/ktp', [HunianTenantController::class, 'ktp'])->name('.tenants.ktp');

        // Keluhan penghuni
        Route::get('/tickets', [HunianTicketController::class, 'index'])->name('.tickets');
        Route::get('/tickets/{ticket}', [HunianTicketController::class, 'show'])->name('.tickets.show');
        Route::post('/tickets/{ticket}/messages', [HunianTicketController::class, 'reply'])->middleware('throttle:hunian-chat')->name('.tickets.reply');
        Route::patch('/tickets/{ticket}/status', [HunianTicketController::class, 'updateStatus'])->name('.tickets.status');
        Route::get('/tickets/messages/{message}/image', [HunianTicketController::class, 'image'])->name('.tickets.image');
    });

    // Aplikasi untuk penghuni terdaftar: semua data dibatasi ke penghuni yang sedang login.
    Route::prefix('apps/penyewa')->name('apps.penyewa')->middleware('tenant')->group(function () {
        Route::get('/', [PenyewaController::class, 'home'])->name('');
        Route::get('/pembayaran', [PenyewaController::class, 'payments'])->name('.payments');
        Route::get('/profil', [PenyewaController::class, 'profile'])->name('.profile');
        Route::put('/profil/kata-sandi', [PenyewaController::class, 'updatePassword'])->middleware('throttle:hunian-password')->name('.password');

        Route::get('/keluhan', [PenyewaTicketController::class, 'index'])->name('.tickets');
        Route::post('/keluhan', [PenyewaTicketController::class, 'store'])->middleware('throttle:hunian-ticket')->name('.tickets.store');
        Route::get('/keluhan/{ticket}', [PenyewaTicketController::class, 'show'])->name('.tickets.show');
        Route::post('/keluhan/{ticket}/pesan', [PenyewaTicketController::class, 'reply'])->middleware('throttle:hunian-chat')->name('.tickets.reply');
        Route::get('/keluhan/pesan/{message}/foto', [PenyewaTicketController::class, 'image'])->name('.tickets.image');
    });
});
