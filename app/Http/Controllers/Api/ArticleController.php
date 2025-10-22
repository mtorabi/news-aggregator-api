<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArticleIndexRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ArticleController extends Controller
{
    /**
     * Get articles with search, filtering, and pagination
     */
    public function index(ArticleIndexRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $query = Article::query();

        // Search functionality
        if ($request->filled('search')) {
            $query->search($request->get('search'));
        }

        // Filter by source
        if ($request->filled('source')) {
            $sources = is_array($request->get('source')) 
                ? $request->get('source') 
                : [$request->get('source')];
            $query->fromSources($sources);
        }

        // Filter by category
        if ($request->filled('category')) {
            $categories = is_array($request->get('category')) 
                ? $request->get('category') 
                : [$request->get('category')];
            $query->inCategories($categories);
        }

        // Filter by author
        if ($request->filled('author')) {
            $authors = is_array($request->get('author')) 
                ? $request->get('author') 
                : [$request->get('author')];
            $query->byAuthors($authors);
        }

        // Date filtering
        $dateFrom = $request->filled('date_from') 
            ? Carbon::parse($request->get('date_from'))->startOfDay() 
            : null;
        $dateTo = $request->filled('date_to') 
            ? Carbon::parse($request->get('date_to'))->endOfDay() 
            : null;
        
        $query->publishedBetween($dateFrom, $dateTo);

        // Sorting
        $sortBy = $request->get('sort_by', 'published_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100); // Max 100 per page
        $articles = $query->paginate($perPage);

        return ArticleResource::collection($articles);
    }

    /**
     * Get a specific article by ID
     */
    public function show(int $id): JsonResponse|ArticleResource
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json([
                'error' => 'Article not found'
            ], 404);
        }

        return new ArticleResource($article);
    }

    /**
     * Get unique sources for filter dropdown
     */
    public function getSources(): JsonResponse
    {
        $sources = Article::select('source')
            ->distinct()
            ->whereNotNull('source')
            ->orderBy('source')
            ->pluck('source');

        return response()->json([
            'data' => $sources
        ]);
    }

    /**
     * Get unique categories for filter dropdown
     */
    public function getCategories(): JsonResponse
    {
        $categories = Article::select('category')
            ->distinct()
            ->whereNotNull('category')
            ->orderBy('category')
            ->pluck('category');

        return response()->json([
            'data' => $categories
        ]);
    }

    /**
     * Get unique authors for filter dropdown
     */
    public function getAuthors(): JsonResponse
    {
        $authors = Article::select('author')
            ->distinct()
            ->whereNotNull('author')
            ->where('author', '!=', '')
            ->orderBy('author')
            ->pluck('author');

        return response()->json([
            'data' => $authors
        ]);
    }
}