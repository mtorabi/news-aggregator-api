<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

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