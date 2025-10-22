<?php

namespace App\Services;

use App\Http\Requests\ArticleIndexRequest;
use App\Models\Article;
use App\Services\Interfaces\IArticleService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ArticleService implements IArticleService
{
    /**
     * Get articles with search, filtering, and pagination
     */
    public function getArticles(ArticleIndexRequest $request): LengthAwarePaginator
    {
        $query = Article::query();

        // Apply search functionality
        $this->applySearch($query, $request);

        // Apply filters
        $this->applySourceFilter($query, $request);
        $this->applyCategoryFilter($query, $request);
        $this->applyAuthorFilter($query, $request);
        $this->applyDateFilter($query, $request);

        // Apply sorting
        $this->applySorting($query, $request);

        // Apply pagination
        $perPage = min($request->get('per_page', 15), 100); // Max 100 per page
        
        return $query->paginate($perPage);
    }

    /**
     * Get a specific article by ID
     */
    public function getArticleById(int $id): ?Article
    {
        return Article::find($id);
    }

    /**
     * Get unique sources for filter dropdown
     */
    public function getUniqueSources(): Collection
    {
        return Article::select('source')
            ->distinct()
            ->whereNotNull('source')
            ->orderBy('source')
            ->pluck('source');
    }

    /**
     * Get unique categories for filter dropdown
     */
    public function getUniqueCategories(): Collection
    {
        return Article::select('category')
            ->distinct()
            ->whereNotNull('category')
            ->orderBy('category')
            ->pluck('category');
    }

    /**
     * Get unique authors for filter dropdown
     */
    public function getUniqueAuthors(): Collection
    {
        return Article::select('author')
            ->distinct()
            ->whereNotNull('author')
            ->where('author', '!=', '')
            ->orderBy('author')
            ->pluck('author');
    }

    /**
     * Apply search functionality to the query
     */
    protected function applySearch(Builder $query, ArticleIndexRequest $request): void
    {
        if ($request->filled('search')) {
            $query->search($request->get('search'));
        }
    }

    /**
     * Apply source filter to the query
     */
    protected function applySourceFilter(Builder $query, ArticleIndexRequest $request): void
    {
        if ($request->filled('source')) {
            $sources = is_array($request->get('source')) 
                ? $request->get('source') 
                : [$request->get('source')];
            $query->fromSources($sources);
        }
    }

    /**
     * Apply category filter to the query
     */
    protected function applyCategoryFilter(Builder $query, ArticleIndexRequest $request): void
    {
        if ($request->filled('category')) {
            $categories = is_array($request->get('category')) 
                ? $request->get('category') 
                : [$request->get('category')];
            $query->inCategories($categories);
        }
    }

    /**
     * Apply author filter to the query
     */
    protected function applyAuthorFilter(Builder $query, ArticleIndexRequest $request): void
    {
        if ($request->filled('author')) {
            $authors = is_array($request->get('author')) 
                ? $request->get('author') 
                : [$request->get('author')];
            $query->byAuthors($authors);
        }
    }

    /**
     * Apply date filter to the query
     */
    protected function applyDateFilter(Builder $query, ArticleIndexRequest $request): void
    {
        $dateFrom = $request->filled('date_from') 
            ? Carbon::parse($request->get('date_from'))->startOfDay() 
            : null;
        $dateTo = $request->filled('date_to') 
            ? Carbon::parse($request->get('date_to'))->endOfDay() 
            : null;
        
        $query->publishedBetween($dateFrom, $dateTo);
    }

    /**
     * Apply sorting to the query
     */
    protected function applySorting(Builder $query, ArticleIndexRequest $request): void
    {
        $sortBy = $request->get('sort_by', 'published_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
    }
}