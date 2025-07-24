<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CsvProcessorController;

// Route to show the upload form
Route::get('/', [CsvProcessorController::class, 'showUploadForm']);

// Route to handle the form submission and file processing
Route::post('/process-csv', [CsvProcessorController::class, 'processCsv']);
