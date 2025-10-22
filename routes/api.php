<?php

use App\Http\Controllers\API\ArticleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('articles', [ArticleController::class, 'index']);
    Route::get('articles/{id}', [ArticleController::class, 'show']);
    
    // Get unique sources, categories, and authors for filter options
    Route::get('filters/sources', [ArticleController::class, 'getSources']);
    Route::get('filters/categories', [ArticleController::class, 'getCategories']);
    Route::get('filters/authors', [ArticleController::class, 'getAuthors']);
});