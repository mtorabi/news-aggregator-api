<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArticleIndexRequest;
use App\Http\Resources\ArticleResource;
use App\Services\Interfaces\IArticleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

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
    #[OA\Get(
        path: "/articles",
        summary: "Get articles with search, filtering, and pagination",
        tags: ["Articles"],
        parameters: [
            new OA\Parameter(
                name: "search",
                in: "query",
                description: "Search term to filter articles by title or content",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "source",
                in: "query",
                description: "Filter articles by source",
                required: false,
                schema: new OA\Schema(type: "array", items: new OA\Items(type: "string"))
            ),
            new OA\Parameter(
                name: "category",
                in: "query",
                description: "Filter articles by category",
                required: false,
                schema: new OA\Schema(type: "array", items: new OA\Items(type: "string"))
            ),
            new OA\Parameter(
                name: "author",
                in: "query",
                description: "Filter articles by author",
                required: false,
                schema: new OA\Schema(type: "array", items: new OA\Items(type: "string"))
            ),
            new OA\Parameter(
                name: "date_from",
                in: "query",
                description: "Filter articles from this date (Y-m-d format)",
                required: false,
                schema: new OA\Schema(type: "string", format: "date")
            ),
            new OA\Parameter(
                name: "date_to",
                in: "query",
                description: "Filter articles to this date (Y-m-d format)",
                required: false,
                schema: new OA\Schema(type: "string", format: "date")
            ),
            new OA\Parameter(
                name: "page",
                in: "query",
                description: "Page number for pagination",
                required: false,
                schema: new OA\Schema(type: "integer", minimum: 1)
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Number of articles per page",
                required: false,
                schema: new OA\Schema(type: "integer", minimum: 1, maximum: 100)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful response",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Article")),
                        new OA\Property(property: "links", type: "object"),
                        new OA\Property(property: "meta", type: "object")
                    ]
                )
            )
        ]
    )]
    public function index(ArticleIndexRequest $request): AnonymousResourceCollection
    {
        $articles = $this->articleService->getArticles($request);
        
        return ArticleResource::collection($articles);
    }

    /**
     * Get a specific article by ID
     */
    #[OA\Get(
        path: "/articles/{id}",
        summary: "Get a specific article by ID",
        tags: ["Articles"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "Article ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful response",
                content: new OA\JsonContent(ref: "#/components/schemas/Article")
            ),
            new OA\Response(
                response: 404,
                description: "Article not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Article not found")
                    ]
                )
            )
        ]
    )]
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
    #[OA\Get(
        path: "/filters/sources",
        summary: "Get unique sources for filtering",
        tags: ["Filters"],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of unique sources",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(type: "string")
                        )
                    ]
                )
            )
        ]
    )]
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
    #[OA\Get(
        path: "/filters/categories",
        summary: "Get unique categories for filtering",
        tags: ["Filters"],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of unique categories",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(type: "string")
                        )
                    ]
                )
            )
        ]
    )]
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
    #[OA\Get(
        path: "/filters/authors",
        summary: "Get unique authors for filtering",
        tags: ["Filters"],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of unique authors",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(type: "string")
                        )
                    ]
                )
            )
        ]
    )]
    public function getAuthors(): JsonResponse
    {
        $authors = $this->articleService->getUniqueAuthors();

        return response()->json([
            'data' => $authors
        ]);
    }
}