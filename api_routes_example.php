<?php

// Add these routes to your routes/api.php file

use App\Http\Controllers\EnhancedDocumentController;
use Illuminate\Support\Facades\Route;

// Document generation and conversion routes
Route::group(['prefix' => 'documents'], function () {
    
    // Generate document (supports both DOCX and DOC output based on 'intent' parameter)
    Route::post('/generate', [EnhancedDocumentController::class, 'generateDocument'])
        ->name('documents.generate');
    
    // Check available conversion tools
    Route::get('/conversion-tools', [EnhancedDocumentController::class, 'checkConversionTools'])
        ->name('documents.conversion-tools');
    
    // Convert existing DOCX file to DOC
    Route::post('/convert', [EnhancedDocumentController::class, 'convertExistingFile'])
        ->name('documents.convert');
});

// Alternative route structure (if you prefer flat routes)
/*
Route::post('/generate-document', [EnhancedDocumentController::class, 'generateDocument']);
Route::get('/check-conversion-tools', [EnhancedDocumentController::class, 'checkConversionTools']);
Route::post('/convert-existing-file', [EnhancedDocumentController::class, 'convertExistingFile']);
*/

// Example usage in your existing routes:
/*
// If you already have a document generation route, you can modify it like this:

// Before (your existing route):
Route::post('/your-existing-endpoint', [YourController::class, 'generateDocument']);

// After (enhanced with DOC conversion):
Route::post('/your-existing-endpoint', function (Request $request) {
    // Add DOC conversion support to your existing endpoint
    $converter = app(\App\Services\DocumentConverter::class);
    
    // Your existing document generation logic...
    $docxPath = storage_path('app/public/generated-document.docx');
    
    // Check if DOC conversion is requested
    if ($request->input('intent') === 'doc' || $request->boolean('convert_to_doc')) {
        $docPath = str_replace('.docx', '.doc', $docxPath);
        
        $success = $converter->convertDocxToDoc($docxPath, $docPath, [
            'method' => $request->input('conversion_method', 'auto'),
            'clean_before_conversion' => $request->boolean('clean_before_conversion', true)
        ]);
        
        if ($success) {
            return response()->download($docPath)->deleteFileAfterSend(true);
        }
    }
    
    return response()->download($docxPath)->deleteFileAfterSend(true);
});
*/