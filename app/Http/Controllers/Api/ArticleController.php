<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArticleIndexRequest;
use App\Http\Resources\ArticleResource;
use App\Services\Interfaces\IArticleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArticleController extends Controller
{
    protected IArticleService $articleService;

    public function __construct(IArticleService $articleService)
    {
        $this->articleService = $articleService;
    }

    /**
     * Get articles with search, filtering, and pagination
     */
    public function index(ArticleIndexRequest $request): AnonymousResourceCollection
    {
        $articles = $this->articleService->getArticles($request);
        
        return ArticleResource::collection($articles);
    }

    /**
     * Get a specific article by ID
     */
    public function show(int $id): JsonResponse|ArticleResource
    {
        $article = $this->articleService->getArticleById($id);

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
        $sources = $this->articleService->getUniqueSources();

        return response()->json([
            'data' => $sources
        ]);
    }

    /**
     * Get unique categories for filter dropdown
     */
    public function getCategories(): JsonResponse
    {
        $categories = $this->articleService->getUniqueCategories();

        return response()->json([
            'data' => $categories
        ]);
    }

    /**
     * Get unique authors for filter dropdown
     */
    public function getAuthors(): JsonResponse
    {
        $authors = $this->articleService->getUniqueAuthors();

        return response()->json([
            'data' => $authors
        ]);
    }
}