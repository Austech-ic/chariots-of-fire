<?php

use App\Http\Controllers\Api\PublicApiController;
use Illuminate\Support\Facades\Route;

/*
| Read-only public API (v1) for future mobile apps or partners. Write
| operations (bookings, donations, forms) stay on the website for now.
*/
Route::prefix('v1')->middleware('throttle:60,1')->group(function () {
    Route::get('/sermons', [PublicApiController::class, 'sermons']);
    Route::get('/sermons/{sermon:slug}', [PublicApiController::class, 'sermon']);
    Route::get('/events', [PublicApiController::class, 'events']);
    Route::get('/events/{event:slug}', [PublicApiController::class, 'event']);
    Route::get('/livestreams/current', [PublicApiController::class, 'currentStream']);
});
