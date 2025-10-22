<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Article",
    title: "Article",
    description: "News article model",
    properties: [
        new OA\Property(property: "id", type: "integer", description: "Article ID"),
        new OA\Property(property: "external_id", type: "string", description: "External API article ID"),
        new OA\Property(property: "title", type: "string", description: "Article title"),
        new OA\Property(property: "url", type: "string", format: "uri", description: "Article URL"),
        new OA\Property(property: "body", type: "string", description: "Article content"),
        new OA\Property(property: "source", type: "string", description: "Article source"),
        new OA\Property(property: "author", type: "string", description: "Article author"),
        new OA\Property(property: "image_url", type: "string", format: "uri", nullable: true, description: "Article image URL"),
        new OA\Property(property: "category", type: "string", description: "Article category"),
        new OA\Property(property: "published_at", type: "string", format: "date-time", description: "Article publication date"),
        new OA\Property(property: "created_at", type: "string", format: "date-time", description: "Record creation date"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", description: "Record update date")
    ]
)]
class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'title',
        'url',
        'body',
        'source',
        'author',
        'image_url',
        'category',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Scope to search articles by term
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'LIKE', "%{$term}%")
              ->orWhere('body', 'LIKE', "%{$term}%")
              ->orWhere('author', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Scope to filter by sources
     */
    public function scopeFromSources(Builder $query, array $sources): Builder
    {
        return $query->whereIn('source', $sources);
    }

    /**
     * Scope to filter by categories
     */
    public function scopeInCategories(Builder $query, array $categories): Builder
    {
        return $query->whereIn('category', $categories);
    }

    /**
     * Scope to filter by authors
     */
    public function scopeByAuthors(Builder $query, array $authors): Builder
    {
        return $query->whereIn('author', $authors);
    }

    /**
     * Scope to filter by date range
     */
    public function scopePublishedBetween(Builder $query, $from = null, $to = null): Builder
    {
        if ($from) {
            $query->where('published_at', '>=', $from);
        }
        
        if ($to) {
            $query->where('published_at', '<=', $to);
        }

        return $query;
    }
}