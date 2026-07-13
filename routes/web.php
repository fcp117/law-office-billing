<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MatterController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\FirmOverviewController;
use App\Http\Controllers\MatterListController;
use App\Http\Controllers\ClientListController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\TimeEntryController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

//Route::get('/dashboard', function () {
//  return Inertia::render('Dashboard');
//})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// The partner-only overview route
Route::get('/firm-overview', [FirmOverviewController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.overview');

Route::get('/matters/{id}', [MatterController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('matters.show');

Route::get('/matter-list', [MatterListController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('matter-list');

Route::post('/matters', [MatterController::class, 'store'])->name('matters.store');

Route::get('/client-list', [ClientListController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('client-list');

Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');

Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');

Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

Route::get('/clients/{id}/edit', [ClientListController::class, 'edit'])->name('clients.edit');

Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');

Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])
    ->name('payments.store');

// Creating them requires the Matter ID so the backend knows where to attach them
Route::post('/matters/{matter}/tasks', [App\Http\Controllers\TaskController::class, 'store'])->name('matters.tasks.store');
Route::post('/matters/{matter}/events', [App\Http\Controllers\EventController::class, 'store'])->name('matters.events.store');

// Updating and Deleting them only requires the ID of the specific Task/Event
Route::put('/tasks/{task}', [App\Http\Controllers\TaskController::class, 'update'])->name('tasks.update');
Route::delete('/tasks/{task}', [App\Http\Controllers\TaskController::class, 'destroy'])->name('tasks.destroy');

Route::put('/events/{event}', [App\Http\Controllers\EventController::class, 'update'])->name('events.update');
Route::delete('/events/{event}', [App\Http\Controllers\EventController::class, 'destroy'])->name('events.destroy');


Route::post('/matters/{matter}/time-entries', [TimeEntryController::class, 'store'])->name('matters.time-entries.store');
Route::put('/time-entries/{time_entry}', [TimeEntryController::class, 'update'])->name('time-entries.update');
Route::delete('/time-entries/{time_entry}', [TimeEntryController::class, 'destroy'])->name('time-entries.destroy');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
