<?php

use Illuminate\Support\Facades\Route;
use Jevo\JForms\Http\Controllers\SubmissionController;

Route::middleware(['web'])->post('/jforms/{form}', [SubmissionController::class, 'store'])
    ->where('form', '[A-Za-z0-9_-]+')
    ->name('jForms.submit');

Route::get('/jforms/assets/jforms.js', [SubmissionController::class, 'asset'])
    ->name('jForms.asset');

