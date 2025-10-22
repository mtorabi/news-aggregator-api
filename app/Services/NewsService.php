<?php

namespace App\Services;

use App\Models\Article;
use App\Services\Interfaces\INewsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Throwable;

class NewsService implements INewsService
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('news.resources');
    }

    /**
     * Fetch articles from a specific news source
     * @param array $sourceConfig Configuration for the news source containing:
     *   - 'name': Name of the news source
     *   - 'url': Closure that builds API URL with from/to dates
     *   - 'items_path': Dot notation path to articles array in response
     *   - 'mapping': Array mapping API response fields to {@link Article} fields
     * @param Carbon $from Start date for fetching articles
     * @param Carbon $to End date for fetching articles
     * @return array List of mapped articles
     */
    public function fetchFromSource(array $sourceConfig, Carbon $from, Carbon $to): array
    {
        // Build the URL using the closure
        $urlBuilder = $sourceConfig['url'];
        $url = $urlBuilder($from, $to);

        // Make HTTP request
        $response = Http::timeout(30)->retry(3, 100, function ($exception, $request) {
            // Retry on connection exceptions or server errors
            if ($exception instanceof Throwable) {
                return true;
            }
            return false;
        })->get($url);

        if (!$response->successful()) {
            throw new \Exception("HTTP request failed with status: " . $response->status());
        }

        $data = $response->json();

        // Extract articles using items_path
        $articles = Arr::get($data, $sourceConfig['items_path'], []);

        if (empty($articles)) {
            return [];
        }

        // Map each article using the mapping configuration
        return array_map(function ($item) use ($sourceConfig) {
            return $this->mapArticleData($item, $sourceConfig['mapping']);
        }, $articles);
    }

    /**
     * Map raw article data to our standard format
     */
    protected function mapArticleData(array $rawData, array $mapping): array
    {
        $mappedData = [];

        foreach ($mapping as $ourField => $apiField) {
            if (empty($apiField)) {
                // Handle empty mappings (like category for NewsAPI)
                $mappedData[$ourField] = null;
                continue;
            }

            // Special case (like external_id) which may be a closure
            if ($apiField instanceof \Closure) {
                $value = $apiField($rawData);
            } else {
                // Try to get the field from raw data first
                $value = Arr::get($rawData, $apiField);
                
                // If the field doesn't exist in the raw data, treat the apiField as a static value
                // This handles cases like 'source' => 'NewsAPI.Org' which the mapping uses a static string instead of a field name
                if ($value === null && !Arr::has($rawData, $apiField)) {
                    $value = $apiField;
                }
            }

            // Special handling for different field types
            $mappedData[$ourField] = $this->transformFieldValue($ourField, $value);
        }

        return $mappedData;
    }

    /**
     * Transform field values based on field type
     */
    protected function transformFieldValue(string $fieldName, $value)
    {
        if ($value === null) {
            return null;
        }

        switch ($fieldName) {
            case 'published_at':
                return $this->parseDate($value);
            case 'image_url':
                return is_string($value) ? $value : null;
            case 'body':
                return $this->cleanText($value);
            case 'title':
                return $this->cleanText($value);
            default:
                return is_string($value) ? trim($value) : $value;
        }
    }

    /**
     * Parse various date formats to Carbon instance
     */
    protected function parseDate($dateValue): ?Carbon
    {
        if (empty($dateValue)) {
            return null;
        }

        try {
            return Carbon::parse($dateValue);
        } catch (\Exception $e) {
            Log::warning("Failed to parse date: " . $dateValue);
            return null;
        }
    }

    /**
     * Clean text content
     */
    protected function cleanText(?string $text): ?string
    {
        if (empty($text)) {
            return null;
        }

        // Remove HTML tags and decode entities
        $cleaned = html_entity_decode(strip_tags($text));

        // Trim and normalize whitespace
        return trim(preg_replace('/\s+/', ' ', $cleaned));
    }

    /**
     * Save articles to database, avoiding duplicates
     */
    public function saveArticles(array $articles): int
    {
        $savedCount = 0;

        foreach ($articles as $articleData) {
            try {
                // Skip if external_id is empty
                if (empty($articleData['external_id'])) {
                    continue;
                }

                // Use updateOrCreate to avoid duplicates based on external_id
                $whereConditions = ['external_id' => $articleData['external_id']];

                $article = Article::updateOrCreate(
                    $whereConditions,
                    $articleData
                );

                if ($article->wasRecentlyCreated) {
                    $savedCount++;
                }
            } catch (\Exception $e) {
                Log::error("Failed to save article: " . $e->getMessage(), ['article' => $articleData]);
            }
        }

        return $savedCount;
    }
}
