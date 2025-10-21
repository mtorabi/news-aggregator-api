<?php

namespace App\Jobs;

use App\Services\NewsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchSingleSourceNewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $sourceKey;
    protected ?Carbon $fromDate;
    protected ?Carbon $toDate;

    /**
     * Create a new job instance.
     */
    public function __construct(string $sourceKey, ?Carbon $fromDate = null, ?Carbon $toDate = null)
    {
        $this->sourceKey = $sourceKey;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting FetchSingleSourceNewsJob for source: {$this->sourceKey}", [
            'source' => $this->sourceKey,
            'from' => $this->fromDate?->toDateString(),
            'to' => $this->toDate?->toDateString()
        ]);

        $newsConfig = config('news.resources');

        try {
            $newsService = new NewsService();
            
            // Fetch articles from the specific source
            $articles = $newsService->fetchFromSource($newsConfig[$this->sourceKey], $this->fromDate, $this->toDate);

            Log::info("Fetched {count} articles from {source}", [
                'count' => count($articles),
                'source' => $this->sourceKey
            ]);
            
            // Save articles to database
            $savedCount = $newsService->saveArticles($articles);
            
            Log::info("FetchSingleSourceNewsJob completed successfully for {source}", [
                'source' => $this->sourceKey,
                'total_fetched' => count($articles),
                'saved_count' => $savedCount,
                'duplicates_skipped' => count($articles) - $savedCount
            ]);
            
        } catch (\Exception $e) {
            Log::error("FetchSingleSourceNewsJob failed for source {source}: {error}", [
                'source' => $this->sourceKey,
                'error' => $e->getMessage(),
                'exception' => $e
            ]);
            
            // Re-throw the exception to mark the job as failed
            throw $e;
        }
    }

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['news-fetch', "source:{$this->sourceKey}"];
    }
}