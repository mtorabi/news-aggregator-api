<?php
namespace App\Services\Interfaces;

/**
 * Interface for News Service
 * Core functionalities for fetching and saving news articles from various sources
 */
interface INewsService
{
    /**
     * Fetch articles from a specific news source
     * @param array $sourceConfig Configuration for the news source
     * @param \Carbon\Carbon $from Start date for fetching articles
     * @param \Carbon\Carbon $to End date for fetching articles
     * @return array List of mapped articles
     */
    public function fetchFromSource(array $sourceConfig, \Carbon\Carbon $from, \Carbon\Carbon $to): array;

    /**
     * Save articles to the database
     * @param array $articles List of articles to save
     * @return int Number of articles saved
     */
    public function saveArticles(array $articles): int;
}