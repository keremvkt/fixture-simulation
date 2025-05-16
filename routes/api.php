<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\FixtureController;

Route::get('/teams', [TeamController::class, 'index']);
Route::post('/fixture', [FixtureController::class, 'create']);
Route::post('/play-week/{id}', [FixtureController::class, 'playWeek']);
Route::get('/played-weeks/{id}', [FixtureController::class, 'getPlayedWeeks']);