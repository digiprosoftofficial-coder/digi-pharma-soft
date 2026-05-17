<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/v1/user', function (Request $request) {
    return $request->user();
});
