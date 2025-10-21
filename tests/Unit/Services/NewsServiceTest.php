<?php

use App\Models\Article;
use App\Services\NewsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('NewsService', function () {
    beforeEach(function () {
        $this->service = new NewsService();
    });

    describe('fetchFromSource with string mappings only', function () {
        it('successfully fetches and maps articles from a source', function () {
            $from = Carbon::parse('2024-01-01');
            $to = Carbon::parse('2024-01-02');
            
            $mockResponse = [
                'articles' => [
                    [
                        'id' => 'test_id_1',
                        'headline' => 'Test Article 1',
                        'link' => 'https://test.com/article1',
                        'description' => 'This is test article 1',
                        'author_name' => 'John Doe',
                        'image' => 'https://test.com/image1.jpg',
                        'publish_date' => '2024-01-01T10:00:00Z',
                    ],
                ],
            ];
            
            Http::fake([
                'https://api.test.com/articles*' => Http::response($mockResponse, 200),
            ]);
            
            $sourceConfig = [
                'name' => 'Test Source',
                'url' => function ($from, $to) {
                    return 'https://api.test.com/articles?from=' . $from->format('Y-m-d') . '&to=' . $to->format('Y-m-d');
                },
                'items_path' => 'articles',
                'mapping' => [
                    'external_id' => 'id',
                    'title' => 'headline',
                    'url' => 'link',
                    'body' => 'description',
                    'author' => 'author_name',
                    'image_url' => 'image',
                    'published_at' => 'publish_date',
                ],
            ];
            
            $result = $this->service->fetchFromSource($sourceConfig, $from, $to);
            
            expect($result)->toHaveCount(1);
            expect($result[0]['external_id'])->toBe('test_id_1');
            expect($result[0]['title'])->toBe('Test Article 1');
            expect($result[0]['published_at'])->toBeInstanceOf(Carbon::class);
        });
    });

    describe('saveArticles', function () {
        it('saves new articles and returns count', function () {
            $articles = [
                [
                    'external_id' => 'test_1',
                    'title' => 'Test Article 1',
                    'url' => 'https://test.com/1',
                    'body' => 'Test body 1',
                    'source' => 'Test Source',
                    'author' => 'John Doe',
                    'image_url' => 'https://test.com/image1.jpg',
                    'category' => 'Tech',
                    'published_at' => Carbon::now(),
                ],
                [
                    'external_id' => 'test_2',
                    'title' => 'Test Article 2',
                    'url' => 'https://test.com/2',
                    'body' => 'Test body 2',
                    'source' => 'Test Source',
                    'author' => 'Jane Smith',
                    'image_url' => 'https://test.com/image2.jpg',
                    'category' => 'Sports',
                    'published_at' => Carbon::now(),
                ],
            ];
            
            $count = $this->service->saveArticles($articles);
            
            expect($count)->toBe(2);
            expect(Article::count())->toBe(2);
            
            $savedArticle = Article::where('external_id', 'test_1')->first();
            expect($savedArticle->title)->toBe('Test Article 1');
            expect($savedArticle->author)->toBe('John Doe');
        });

        it('updates existing articles instead of creating duplicates', function () {
            // Create an existing article
            Article::create([
                'external_id' => 'test_1',
                'title' => 'Old Title',
                'url' => 'https://test.com/1',
                'body' => 'Old body',
                'source' => 'Test Source',
                'author' => 'Old Author',
                'published_at' => Carbon::now(),
            ]);
            
            $articles = [
                [
                    'external_id' => 'test_1',
                    'title' => 'Updated Title',
                    'url' => 'https://test.com/1',
                    'body' => 'Updated body',
                    'source' => 'Test Source',
                    'author' => 'New Author',
                    'published_at' => Carbon::now(),
                ],
            ];
            
            $count = $this->service->saveArticles($articles);
            
            expect($count)->toBe(0); // No new articles created
            expect(Article::count())->toBe(1); // Still only one article
            
            $updatedArticle = Article::where('external_id', 'test_1')->first();
            expect($updatedArticle->title)->toBe('Updated Title');
            expect($updatedArticle->author)->toBe('New Author');
        });

        it('skips articles with empty external_id', function () {
            $articles = [
                [
                    'external_id' => '',
                    'title' => 'Test Article',
                    'url' => 'https://test.com/1',
                    'source' => 'Test Source',
                ],
                [
                    'external_id' => null,
                    'title' => 'Test Article 2',
                    'url' => 'https://test.com/2',
                    'source' => 'Test Source',
                ],
                [
                    'external_id' => 'valid_id',
                    'title' => 'Valid Article',
                    'url' => 'https://test.com/3',
                    'source' => 'Test Source',
                    'published_at' => Carbon::now(),
                ],
            ];
            
            $count = $this->service->saveArticles($articles);
            
            expect($count)->toBe(1);
            expect(Article::count())->toBe(1);
            expect(Article::first()->external_id)->toBe('valid_id');
        });
    });

    describe('field transformation', function () {
        it('transforms published_at to Carbon instance', function () {
            Http::fake([
                'https://test.com' => Http::response([
                    'items' => [['date' => '2024-01-01T10:00:00Z']]
                ], 200),
            ]);
            
            $result = $this->service->fetchFromSource([
                'name' => 'Test',
                'url' => fn() => 'https://test.com',
                'items_path' => 'items',
                'mapping' => ['published_at' => 'date'],
            ], Carbon::now(), Carbon::now());
            
            expect($result[0]['published_at'])->toBeInstanceOf(Carbon::class);
            expect($result[0]['published_at']->format('Y-m-d H:i:s'))->toBe('2024-01-01 10:00:00');
        });

        it('handles invalid date formats gracefully', function () {
            Log::shouldReceive('warning')->once();
            
            Http::fake([
                'https://test.com' => Http::response([
                    'items' => [['date' => 'invalid-date']]
                ], 200),
            ]);
            
            $result = $this->service->fetchFromSource([
                'name' => 'Test',
                'url' => fn() => 'https://test.com',
                'items_path' => 'items',
                'mapping' => ['published_at' => 'date'],
            ], Carbon::now(), Carbon::now());
            
            expect($result[0]['published_at'])->toBeNull();
        });

        it('cleans text content by removing HTML and normalizing whitespace', function () {
            Http::fake([
                'https://test.com' => Http::response([
                    'items' => [[
                        'title' => '<p>Test   Title</p>',
                        'body' => '  <div>Test  &amp;  Body</div>  '
                    ]]
                ], 200),
            ]);
            
            $result = $this->service->fetchFromSource([
                'name' => 'Test',
                'url' => fn() => 'https://test.com',
                'items_path' => 'items',
                'mapping' => [
                    'title' => 'title',
                    'body' => 'body',
                ],
            ], Carbon::now(), Carbon::now());
            
            expect($result[0]['title'])->toBe('Test Title');
            expect($result[0]['body'])->toBe('Test & Body');
        });

        it('handles null and empty values', function () {
            Http::fake([
                'https://test.com' => Http::response([
                    'items' => [[
                        'title' => null,
                        'body' => '',
                        'url' => 'https://test.com'
                    ]]
                ], 200),
            ]);
            
            $result = $this->service->fetchFromSource([
                'name' => 'Test',
                'url' => fn() => 'https://test.com',
                'items_path' => 'items',
                'mapping' => [
                    'title' => 'title',
                    'body' => 'body',
                    'url' => 'url',
                ],
            ], Carbon::now(), Carbon::now());
            
            expect($result[0]['title'])->toBeNull();
            expect($result[0]['body'])->toBeNull();
            expect($result[0]['url'])->toBe('https://test.com');
        });

        it('validates image_url field', function () {
            Http::fake([
                'https://test.com' => Http::response([
                    'items' => [[
                        'image1' => 'https://test.com/image.jpg',
                        'image2' => 123, // Non-string value
                    ]]
                ], 200),
            ]);
            
            $result = $this->service->fetchFromSource([
                'name' => 'Test',
                'url' => fn() => 'https://test.com',
                'items_path' => 'items',
                'mapping' => [
                    'image_url' => 'image1',
                ],
            ], Carbon::now(), Carbon::now());
            
            expect($result[0]['image_url'])->toBe('https://test.com/image.jpg');
            
            // Test non-string image URL
            $result2 = $this->service->fetchFromSource([
                'name' => 'Test',
                'url' => fn() => 'https://test.com',
                'items_path' => 'items',
                'mapping' => [
                    'image_url' => 'image2',
                ],
            ], Carbon::now(), Carbon::now());
            
            expect($result2[0]['image_url'])->toBeNull();
        });
    });

    describe('error handling', function () {
        it('handles empty articles array', function () {
            Http::fake([
                'https://test.com' => Http::response(['articles' => []], 200),
            ]);
            
            $result = $this->service->fetchFromSource([
                'name' => 'Test Source',
                'url' => fn() => 'https://test.com',
                'items_path' => 'articles',
                'mapping' => ['title' => 'headline'],
            ], Carbon::now(), Carbon::now());
            
            expect($result)->toBeEmpty();
        });

        it('handles missing articles key in response', function () {
            Http::fake([
                'https://test.com' => Http::response(['status' => 'ok'], 200),
            ]);
            
            $result = $this->service->fetchFromSource([
                'name' => 'Test Source',
                'url' => fn() => 'https://test.com',
                'items_path' => 'articles',
                'mapping' => ['title' => 'headline'],
            ], Carbon::now(), Carbon::now());
            
            expect($result)->toBeEmpty();
        });

        it('throws exception on HTTP failure', function () {
            Http::fake([
                'https://test.com' => Http::response([], 500),
            ]);
            
            expect(fn() => $this->service->fetchFromSource([
                'name' => 'Test Source',
                'url' => fn() => 'https://test.com',
                'items_path' => 'articles',
                'mapping' => ['title' => 'headline'],
            ], Carbon::now(), Carbon::now()))->toThrow(Exception::class);
        });

        it('retries on connection failure', function () {
            Http::fake([
                'https://test.com' => Http::sequence()
                    ->push([], 500)
                    ->push(['articles' => []], 200),
            ]);
            
            $result = $this->service->fetchFromSource([
                'name' => 'Test Source',
                'url' => fn() => 'https://test.com',
                'items_path' => 'articles',
                'mapping' => ['title' => 'headline'],
            ], Carbon::now(), Carbon::now());
            
            expect($result)->toBeEmpty();
            Http::assertSentCount(2);
        });
    });

    describe('nested field handling', function () {
        it('handles nested field paths with dot notation', function () {
            Http::fake([
                'https://test.com' => Http::response([
                    'items' => [[
                        'nested' => [
                            'field' => 'nested_value'
                        ]
                    ]]
                ], 200),
            ]);
            
            $result = $this->service->fetchFromSource([
                'name' => 'Test',
                'url' => fn() => 'https://test.com',
                'items_path' => 'items',
                'mapping' => [
                    'title' => 'nested.field',
                ],
            ], Carbon::now(), Carbon::now());
            
            expect($result[0]['title'])->toBe('nested_value');
        });

        it('handles empty mapping fields', function () {
            Http::fake([
                'https://test.com' => Http::response([
                    'items' => [['title' => 'Test']]
                ], 200),
            ]);
            
            $result = $this->service->fetchFromSource([
                'name' => 'Test',
                'url' => fn() => 'https://test.com',
                'items_path' => 'items',
                'mapping' => [
                    'title' => 'title',
                    'category' => '', // Empty mapping
                ],
            ], Carbon::now(), Carbon::now());
            
            expect($result[0]['category'])->toBeNull();
        });
    });

    describe('closure mappings', function () {
        it('handles closure mappings for field transformations', function () {
            Http::fake([
                'https://test.com' => Http::response([
                    'items' => [[
                        'article_url' => 'https://example.com/article/123',
                        'main_title' => 'Test Article Title',
                        'metadata' => [
                            'section' => 'Technology',
                            'subsection' => 'AI'
                        ]
                    ]]
                ], 200),
            ]);
            
            $result = $this->service->fetchFromSource([
                'name' => 'Test',
                'url' => fn() => 'https://test.com',
                'items_path' => 'items',
                'mapping' => [
                    'external_id' => function ($item) {
                        return base64_encode($item['article_url']);
                    },
                    'title' => 'main_title',
                    'category' => function ($item) {
                        return $item['metadata']['section'] . ' - ' . $item['metadata']['subsection'];
                    },
                ],
            ], Carbon::now(), Carbon::now());
            
            expect($result[0]['external_id'])->toBe(base64_encode('https://example.com/article/123'));
            expect($result[0]['title'])->toBe('Test Article Title');
            expect($result[0]['category'])->toBe('Technology - AI');
        });

        it('handles mixed closure and string mappings', function () {
            Http::fake([
                'https://test.com' => Http::response([
                    'articles' => [[
                        'id' => '123',
                        'headline' => 'Mixed Mapping Test',
                        'url' => 'https://example.com/news/123',
                        'summary' => 'Test summary'
                    ]]
                ], 200),
            ]);
            
            $result = $this->service->fetchFromSource([
                'name' => 'Test',
                'url' => fn() => 'https://test.com',
                'items_path' => 'articles',
                'mapping' => [
                    'external_id' => function ($item) {
                        return 'prefix_' . $item['id'];
                    },
                    'title' => 'headline', // String mapping
                    'url' => 'url', // String mapping
                    'source' => function ($item) {
                        return 'Dynamic Source';
                    },
                    'body' => 'summary', // String mapping
                ],
            ], Carbon::now(), Carbon::now());
            
            expect($result[0]['external_id'])->toBe('prefix_123');
            expect($result[0]['title'])->toBe('Mixed Mapping Test');
            expect($result[0]['url'])->toBe('https://example.com/news/123');
            expect($result[0]['source'])->toBe('Dynamic Source');
            expect($result[0]['body'])->toBe('Test summary');
        });

        it('handles closures that return null or empty values', function () {
            Http::fake([
                'https://test.com' => Http::response([
                    'items' => [[
                        'id' => '123',
                        'title' => 'Test',
                        'author_info' => null
                    ]]
                ], 200),
            ]);
            
            $result = $this->service->fetchFromSource([
                'name' => 'Test',
                'url' => fn() => 'https://test.com',
                'items_path' => 'items',
                'mapping' => [
                    'external_id' => 'id',
                    'title' => 'title',
                    'author' => function ($item) {
                        return $item['author_info']['name'] ?? null;
                    },
                    'category' => function ($item) {
                        return ''; // Empty return
                    },
                ],
            ], Carbon::now(), Carbon::now());
            
            expect($result[0]['external_id'])->toBe('123');
            expect($result[0]['title'])->toBe('Test');
            expect($result[0]['author'])->toBeNull();
            expect($result[0]['category'])->toBe(''); // Empty string is preserved
        });

        it('handles complex nested data extraction with closures', function () {
            Http::fake([
                'https://test.com' => Http::response([
                    'data' => [[
                        'id' => 'article_456',
                        'content' => [
                            'headline' => 'Complex Article',
                            'body' => 'Article content',
                            'media' => [
                                'images' => [
                                    ['url' => 'https://example.com/thumb.jpg', 'type' => 'thumbnail'],
                                    ['url' => 'https://example.com/main.jpg', 'type' => 'main']
                                ]
                            ]
                        ],
                        'attribution' => [
                            'authors' => [
                                ['name' => 'John Doe', 'role' => 'writer'],
                                ['name' => 'Jane Smith', 'role' => 'editor']
                            ]
                        ]
                    ]]
                ], 200)
            ]);
            
            $result = $this->service->fetchFromSource([
                'name' => 'Test',
                'url' => fn() => 'https://test.com',
                'items_path' => 'data',
                'mapping' => [
                    'external_id' => 'id',
                    'title' => 'content.headline',
                    'body' => 'content.body',
                    'image_url' => function ($item) {
                        $images = $item['content']['media']['images'] ?? [];
                        foreach ($images as $image) {
                            if ($image['type'] === 'main') {
                                return $image['url'];
                            }
                        }
                        return null;
                    },
                    'author' => function ($item) {
                        $authors = $item['attribution']['authors'] ?? [];
                        $writers = array_filter($authors, fn($author) => $author['role'] === 'writer');
                        return !empty($writers) ? $writers[0]['name'] : null;
                    },
                ],
            ], Carbon::now(), Carbon::now());
            
            expect($result[0]['external_id'])->toBe('article_456');
            expect($result[0]['title'])->toBe('Complex Article');
            expect($result[0]['body'])->toBe('Article content');
            expect($result[0]['image_url'])->toBe('https://example.com/main.jpg');
            expect($result[0]['author'])->toBe('John Doe');
        });

        it('replicates real config patterns from NewsAPI', function () {
            Http::fake([
                'https://newsapi.org/v2/everything*' => Http::response([
                    'articles' => [[
                        'title' => 'Real NewsAPI Article',
                        'url' => 'https://example.com/real-article',
                        'description' => 'This is from NewsAPI',
                        'author' => 'NewsAPI Author',
                        'urlToImage' => 'https://example.com/newsapi.jpg',
                        'publishedAt' => '2024-01-01T10:00:00Z'
                    ]]
                ], 200),
            ]);
            
            // Replicate the exact mapping from config/news.php
            $result = $this->service->fetchFromSource([
                'name' => 'NewsAPI.Org',
                'url' => function ($from, $to) {
                    return 'https://newsapi.org/v2/everything?sortBy=publishedAt&apiKey=test&from=' . 
                           $from->format('Y-m-d') . '&to=' . $to->format('Y-m-d') . '&language=en&pageSize=100';
                },
                'items_path' => 'articles',
                'mapping' => [
                    'external_id' => function ($item) {
                        return base64_encode($item['url']);
                    },
                    'title' => 'title',
                    'url' => 'url',
                    'body' => 'description',
                    'source' => 'NewsAPI.Org',
                    'author' => 'author',
                    'image_url' => 'urlToImage',
                    'category' => '',
                    'published_at' => 'publishedAt',
                ],
            ], Carbon::now(), Carbon::now());
            
            expect($result[0]['external_id'])->toBe(base64_encode('https://example.com/real-article'));
            expect($result[0]['title'])->toBe('Real NewsAPI Article');
            expect($result[0]['url'])->toBe('https://example.com/real-article');
            expect($result[0]['source'])->toBe('NewsAPI.Org');
            expect($result[0]['category'])->toBeNull(); // Empty mapping becomes null
            expect($result[0]['published_at'])->toBeInstanceOf(Carbon::class);
        });
    });

    describe('integration test', function () {
        it('performs end-to-end fetch and save with string mappings', function () {
            $from = Carbon::parse('2024-01-01');
            $to = Carbon::parse('2024-01-02');
            
            $mockResponse = [
                'data' => [
                    'articles' => [
                        [
                            'article_id' => 'art_123',
                            'title' => 'Integration Test Article',
                            'link' => 'https://test.com/integration',
                            'summary' => 'This is an integration test article',
                            'writer' => 'Integration Tester',
                            'thumbnail' => 'https://test.com/integration.jpg',
                            'category' => 'Testing',
                            'date_published' => '2024-01-01T15:00:00Z',
                        ],
                    ],
                ],
            ];
            
            Http::fake([
                'https://api.test.com/news*' => Http::response($mockResponse, 200),
            ]);
            
            $sourceConfig = [
                'name' => 'Integration Test Source',
                'url' => function ($from, $to) {
                    return 'https://api.test.com/news?from=' . $from->format('Y-m-d') . '&to=' . $to->format('Y-m-d');
                },
                'items_path' => 'data.articles',
                'mapping' => [
                    'external_id' => 'article_id',
                    'title' => 'title',
                    'url' => 'link',
                    'body' => 'summary',
                    'author' => 'writer',
                    'image_url' => 'thumbnail',
                    'category' => 'category',
                    'published_at' => 'date_published',
                ],
            ];
            
            // Fetch articles
            $articles = $this->service->fetchFromSource($sourceConfig, $from, $to);
            expect($articles)->toHaveCount(1);
            expect($articles[0]['external_id'])->toBe('art_123');
            
            // Add required source field that might be missing
            $articles[0]['source'] = 'Integration Test Source';
            
            // Save articles
            $savedCount = $this->service->saveArticles($articles);
            expect($savedCount)->toBe(1);
            
            // Verify in database
            $savedArticle = Article::first();
            expect($savedArticle->external_id)->toBe('art_123');
            expect($savedArticle->title)->toBe('Integration Test Article');
            expect($savedArticle->published_at)->toBeInstanceOf(Carbon::class);
            expect($savedArticle->published_at->format('Y-m-d H:i:s'))->toBe('2024-01-01 15:00:00');
        });
    });
});