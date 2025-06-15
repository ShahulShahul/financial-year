<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FinancialYearController;

Route::get('/', [FinancialYearController::class, 'index']);
Route::get('/get-years', [FinancialYearController::class, 'getYears']);
Route::post('/financial-data', [FinancialYearController::class, 'financialData']);
