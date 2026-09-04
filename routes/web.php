<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| The active frontend is the React SPA in resources/js/main.tsx. Laravel keeps
| serving API routes separately, while browser routes are handed to the SPA.
| Keep this fallback small so API behavior remains controlled from routes/api.php.
|
*/

Route::get('/{path?}', function () {
    return view('spa');
})->where('path', '^(?!api(?:/|$)|build(?:/|$)|storage(?:/|$)|template(?:/|$)).*');
