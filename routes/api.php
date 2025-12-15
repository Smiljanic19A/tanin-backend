<?php

declare(strict_types=1);

use App\Http\Controllers\BookingController;
use App\Http\Controllers\PrivateReservationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Reservation API endpoints for managing bookings and private reservations.
| All routes are prefixed with /api automatically.
|
*/

// ============================================================================
// BOOKINGS (Standard Table Reservations)
// ============================================================================

Route::prefix('bookings')->group(function (): void {
    // GET /api/bookings - List all bookings with optional filters
    Route::get('/', [BookingController::class, 'index']);

    // POST /api/bookings - Create a new booking
    Route::post('/', [BookingController::class, 'store']);

    // GET /api/bookings/{id} - Get a specific booking
    Route::get('/{id}', [BookingController::class, 'show'])->whereNumber('id');

    // PATCH /api/bookings/{id}/approve - Approve a pending booking
    Route::patch('/{id}/approve', [BookingController::class, 'approve'])->whereNumber('id');

    // PATCH /api/bookings/{id}/decline - Decline a pending booking
    Route::patch('/{id}/decline', [BookingController::class, 'decline'])->whereNumber('id');
});

// ============================================================================
// PRIVATE RESERVATIONS (Private Events)
// ============================================================================

Route::prefix('private-reservations')->group(function (): void {
    // GET /api/private-reservations - List all private reservations with optional filters
    Route::get('/', [PrivateReservationController::class, 'index']);

    // POST /api/private-reservations - Create a new private reservation
    Route::post('/', [PrivateReservationController::class, 'store']);

    // GET /api/private-reservations/{id} - Get a specific private reservation
    Route::get('/{id}', [PrivateReservationController::class, 'show'])->whereNumber('id');

    // PATCH /api/private-reservations/{id}/approve - Approve a pending private reservation
    Route::patch('/{id}/approve', [PrivateReservationController::class, 'approve'])->whereNumber('id');

    // PATCH /api/private-reservations/{id}/decline - Decline a pending private reservation
    Route::patch('/{id}/decline', [PrivateReservationController::class, 'decline'])->whereNumber('id');
});

