<?php

namespace App\Services\Interfaces;

use App\Http\Requests\ArticleIndexRequest;
use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Interface for Article Service
 * Core functionalities for managing articles 
 */
interface IArticleService
{
    /**
     * Get articles with search, filtering, and pagination
     */
    public function getArticles(ArticleIndexRequest $request): LengthAwarePaginator;

    /**
     * Get a specific article by ID
     */
    public function getArticleById(int $id): ?Article;

    /**
     * Get unique sources for filter dropdown
     */
    public function getUniqueSources(): Collection;

    /**
     * Get unique categories for filter dropdown
     */
    public function getUniqueCategories(): Collection;

    /**
     * Get unique authors for filter dropdown
     */
    public function getUniqueAuthors(): Collection;
}