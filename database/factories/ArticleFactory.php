<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'external_id' => $this->faker->unique()->uuid(),
            'title' => $this->faker->sentence(6),
            'url' => $this->faker->url(),
            'body' => $this->faker->paragraphs(3, true),
            'source' => $this->faker->randomElement([
                'NewsAPI.Org',
                'The Guardian',
                'BBC News',
                'New York Times',
                'TechCrunch',
                'Reuters'
            ]),
            'author' => $this->faker->name(),
            'image_url' => $this->faker->imageUrl(640, 480, 'news'),
            'category' => $this->faker->randomElement([
                'Technology',
                'Business',
                'Sports',
                'Entertainment',
                'Health',
                'Science',
                'Politics'
            ]),
            'published_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the article is from a specific source.
     */
    public function fromSource(string $source): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => $source,
        ]);
    }

    /**
     * Indicate that the article belongs to a specific category.
     */
    public function inCategory(string $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }

    /**
     * Indicate that the article is by a specific author.
     */
    public function byAuthor(string $author): static
    {
        return $this->state(fn (array $attributes) => [
            'author' => $author,
        ]);
    }

    /**
     * Indicate that the article was published recently.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Indicate that the article was published on a specific date.
     */
    public function publishedOn(Carbon $date): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => $date,
        ]);
    }
}