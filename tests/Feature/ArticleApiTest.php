<?php

namespace Tests\Feature;

use App\Models\Article;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test articles
        Article::factory()->create([
            'title' => 'Technology News Article',
            'body' => 'This is about artificial intelligence',
            'source' => 'TechNews',
            'category' => 'Technology',
            'author' => 'John Doe',
            'published_at' => Carbon::now()->subDays(1),
        ]);

        Article::factory()->create([
            'title' => 'Sports Update',
            'body' => 'Latest sports news',
            'source' => 'SportsDaily',
            'category' => 'Sports',
            'author' => 'Jane Smith',
            'published_at' => Carbon::now()->subDays(2),
        ]);

        Article::factory()->create([
            'title' => 'Business Report',
            'body' => 'Economic analysis and trends',
            'source' => 'BusinessTimes',
            'category' => 'Business',
            'author' => 'John Doe',
            'published_at' => Carbon::now()->subDays(3),
        ]);
    }

    public function test_can_get_articles_list()
    {
        $response = $this->getJson('/api/v1/articles');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'url',
                            'body',
                            'source',
                            'author',
                            'image_url',
                            'category',
                            'published_at',
                            'created_at',
                            'updated_at',
                        ]
                    ],
                    'links',
                    'meta'
                ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_can_search_articles()
    {
        $response = $this->getJson('/api/v1/articles?search=technology');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertStringContainsString('Technology', $response->json('data.0.title'));
    }

    public function test_can_filter_by_source()
    {
        $response = $this->getJson('/api/v1/articles?source[]=TechNews');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('TechNews', $response->json('data.0.source'));
    }

    public function test_can_filter_by_category()
    {
        $response = $this->getJson('/api/v1/articles?category[]=Sports');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Sports', $response->json('data.0.category'));
    }

    public function test_can_filter_by_author()
    {
        $response = $this->getJson('/api/v1/articles?author[]=John%20Doe');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_filter_by_date_range()
    {
        $dateFrom = Carbon::now()->subDays(2)->format('Y-m-d');
        $dateTo = Carbon::now()->format('Y-m-d');

        $response = $this->getJson("/api/v1/articles?date_from={$dateFrom}&date_to={$dateTo}");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_sort_articles()
    {
        $response = $this->getJson('/api/v1/articles?sort_by=title&sort_order=asc');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('Business Report', $data[0]['title']);
    }

    public function test_can_paginate_articles()
    {
        $response = $this->getJson('/api/v1/articles?per_page=2&page=1');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        $this->assertEquals(1, $response->json('meta.current_page'));
        $this->assertEquals(2, $response->json('meta.last_page'));
    }

    public function test_can_get_single_article()
    {
        $article = Article::first();
        
        $response = $this->getJson("/api/v1/articles/{$article->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'title',
                        'url',
                        'body',
                        'source',
                        'author',
                        'image_url',
                        'category',
                        'published_at',
                        'created_at',
                        'updated_at',
                    ]
                ]);

        $this->assertEquals($article->id, $response->json('data.id'));
    }

    public function test_returns_404_for_nonexistent_article()
    {
        $response = $this->getJson('/api/v1/articles/999');

        $response->assertStatus(404)
                ->assertJson(['error' => 'Article not found']);
    }

    public function test_can_get_sources_filter_options()
    {
        $response = $this->getJson('/api/v1/filters/sources');

        $response->assertStatus(200)
                ->assertJsonStructure(['data']);

        $sources = $response->json('data');
        $this->assertContains('TechNews', $sources);
        $this->assertContains('SportsDaily', $sources);
        $this->assertContains('BusinessTimes', $sources);
    }

    public function test_can_get_categories_filter_options()
    {
        $response = $this->getJson('/api/v1/filters/categories');

        $response->assertStatus(200)
                ->assertJsonStructure(['data']);

        $categories = $response->json('data');
        $this->assertContains('Technology', $categories);
        $this->assertContains('Sports', $categories);
        $this->assertContains('Business', $categories);
    }

    public function test_can_get_authors_filter_options()
    {
        $response = $this->getJson('/api/v1/filters/authors');

        $response->assertStatus(200)
                ->assertJsonStructure(['data']);

        $authors = $response->json('data');
        $this->assertContains('John Doe', $authors);
        $this->assertContains('Jane Smith', $authors);
    }

    public function test_validates_date_format()
    {
        $response = $this->getJson('/api/v1/articles?date_from=invalid-date');

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['date_from']);
    }

    public function test_validates_per_page_limit()
    {
        $response = $this->getJson('/api/v1/articles?per_page=150');

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['per_page']);
    }

    public function test_validates_sort_by_field()
    {
        $response = $this->getJson('/api/v1/articles?sort_by=invalid_field');

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['sort_by']);
    }
}