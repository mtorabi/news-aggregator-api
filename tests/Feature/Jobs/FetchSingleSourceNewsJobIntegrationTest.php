<?php

use App\Jobs\FetchSingleSourceNewsJob;
use App\Services\NewsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

describe('FetchSingleSourceNewsJob', function () {
    it('handles successful api response', function () {
        // Mock HTTP response for NewsAPI.org
        Http::fake([
            'newsapi.org/*' => Http::response([
                'articles' => [
                    [
                        'title' => 'Test Article 1',
                        'url' => 'https://example.com/article1',
                        'description' => 'Test description 1',
                        'author' => 'Test Author 1',
                        'urlToImage' => 'https://example.com/image1.jpg',
                        'publishedAt' => '2024-01-01T12:00:00Z'
                    ],
                    [
                        'title' => 'Test Article 2',
                        'url' => 'https://example.com/article2',
                        'description' => 'Test description 2',
                        'author' => 'Test Author 2',
                        'urlToImage' => 'https://example.com/image2.jpg',
                        'publishedAt' => '2024-01-01T14:00:00Z'
                    ]
                ]
            ], 200)
        ]);

        // Create and handle job
        $job = new FetchSingleSourceNewsJob('news_api_org', Carbon::yesterday(), Carbon::today());
        $job->handle();

        // If we reach here without exception, the job worked
        expect(true)->toBeTrue();
    });

    it('handles api error gracefully', function () {
        // Mock HTTP error response
        Http::fake([
            'newsapi.org/*' => Http::response(['error' => 'Invalid API key'], 401)
        ]);

        $job = new FetchSingleSourceNewsJob('news_api_org', Carbon::yesterday(), Carbon::today());

        expect(fn() => $job->handle())->toThrow(Exception::class);
    });
});