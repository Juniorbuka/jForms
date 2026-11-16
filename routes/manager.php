<?php

use Illuminate\Support\Facades\Route;
use Jevo\JForms\Http\Controllers\ModuleController;

Route::name('jForms.')->group(function (): void {
    Route::get('/', [ModuleController::class, 'index'])->name('index');
    Route::get('/{form}', [ModuleController::class, 'show'])->name('show');
    Route::get('/{form}/responses/{response}', [ModuleController::class, 'response'])
        ->whereNumber('response')
        ->name('response');
    Route::delete('/responses/{id}', [ModuleController::class, 'destroy'])->whereNumber('id')->name('destroy');
});
