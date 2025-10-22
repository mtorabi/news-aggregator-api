<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create articles with specific data for better testing
        $articles = [
            [
                'external_id' => 'tech-001',
                'title' => 'Revolutionary AI Technology Breakthrough',
                'url' => 'https://technews.com/ai-breakthrough',
                'body' => 'Scientists have developed a new artificial intelligence system that can understand and generate human-like text with unprecedented accuracy.',
                'source' => 'TechNews',
                'author' => 'Dr. Sarah Johnson',
                'category' => 'Technology',
                'published_at' => Carbon::now()->subDays(1),
            ],
            [
                'external_id' => 'business-001',
                'title' => 'Global Market Trends Show Economic Recovery',
                'url' => 'https://businesstimes.com/market-recovery',
                'body' => 'Economic indicators suggest a strong recovery following recent market volatility, with technology stocks leading the charge.',
                'source' => 'Business Times',
                'author' => 'Michael Chen',
                'category' => 'Business',
                'published_at' => Carbon::now()->subDays(2),
            ],
            [
                'external_id' => 'sports-001',
                'title' => 'Championship Final Draws Record Viewership',
                'url' => 'https://sportsdaily.com/championship-final',
                'body' => 'The championship final broke all previous viewership records with over 50 million viewers worldwide.',
                'source' => 'Sports Daily',
                'author' => 'Emma Rodriguez',
                'category' => 'Sports',
                'published_at' => Carbon::now()->subDays(3),
            ],
            [
                'external_id' => 'health-001',
                'title' => 'New Study Reveals Benefits of Regular Exercise',
                'url' => 'https://healthnews.com/exercise-benefits',
                'body' => 'A comprehensive study involving 10,000 participants shows significant health improvements from just 30 minutes of daily exercise.',
                'source' => 'Health News',
                'author' => 'Dr. James Wilson',
                'category' => 'Health',
                'published_at' => Carbon::now()->subDays(4),
            ],
            [
                'external_id' => 'politics-001',
                'title' => 'New Environmental Policy Announced',
                'url' => 'https://politicalnews.com/environmental-policy',
                'body' => 'Government announces comprehensive environmental policy aimed at reducing carbon emissions by 50% over the next decade.',
                'source' => 'Political News',
                'author' => 'Lisa Thompson',
                'category' => 'Politics',
                'published_at' => Carbon::now()->subDays(5),
            ],
            [
                'external_id' => 'science-001',
                'title' => 'Scientists Discover New Species in Deep Ocean',
                'url' => 'https://sciencenews.com/new-species',
                'body' => 'Marine biologists have discovered several new species of deep-sea creatures during a recent expedition to the Pacific Ocean.',
                'source' => 'Science News',
                'author' => 'Dr. Sarah Johnson',
                'category' => 'Science',
                'published_at' => Carbon::now()->subDays(6),
            ],
            [
                'external_id' => 'tech-002',
                'title' => 'Cybersecurity Threats on the Rise',
                'url' => 'https://technews.com/cybersecurity-threats',
                'body' => 'Security experts warn of increasing sophisticated cyber attacks targeting both individuals and corporations.',
                'source' => 'TechNews',
                'author' => 'Alex Kumar',
                'category' => 'Technology',
                'published_at' => Carbon::now()->subDays(7),
            ],
            [
                'external_id' => 'entertainment-001',
                'title' => 'Film Festival Showcases Independent Cinema',
                'url' => 'https://entertainment.com/film-festival',
                'body' => 'This year\'s film festival featured an impressive lineup of independent films from emerging directors around the world.',
                'source' => 'Entertainment Weekly',
                'author' => 'Rachel Green',
                'category' => 'Entertainment',
                'published_at' => Carbon::now()->subDays(8),
            ],
        ];

        foreach ($articles as $articleData) {
            Article::updateOrCreate(
                ['external_id' => $articleData['external_id']],
                $articleData
            );
        }

        // Generate additional random articles
        Article::factory(42)->create();

        $this->command->info('Articles seeded successfully!');
    }
}