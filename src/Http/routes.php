<?php

use Illuminate\Support\Facades\Route;
use Kwidoo\MultiAuth\Http\Controllers\OTPController;

/** login */
Route::group(
    [
        'as' => 'passport.',
        'prefix' => config('passport.path', '/api/oauth'),
    ],
    function () {
        Route::post('/otp', [
            'uses' => OTPController::class . '@create',
            'as' => 'otp',
            'middleware' => 'throttle',
        ]);
    }
);
