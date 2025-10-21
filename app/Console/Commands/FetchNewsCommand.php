<?php

namespace App\Console\Commands;

use App\Jobs\FetchSingleSourceNewsJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchNewsCommand extends Command
{
    const DEFAULT_QUEUE = 'news-fetch';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'news:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch jobs to fetch news articles from configured sources';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get news sources configuration
        $newsConfig = config('news.resources');

        $this->info("Dispatching news fetch jobs...");
        $this->info($newsConfig ? 'Found ' . count($newsConfig) . ' news source(s) to fetch from.' : 'No news sources configured.');
        
        $jobsDispatched = 0;
        
        $from = now()->subDay()->startOfDay(); // Yesterday at 12:00 AM
        $to = now()->startOfDay(); // Today at 12:00 AM

        foreach ($newsConfig as $sourceKey => $sourceConfig) {
            try {

                FetchSingleSourceNewsJob::dispatch($sourceKey, $from, $to)->onQueue(self::DEFAULT_QUEUE);
                $jobsDispatched++;

                $this->line("✓ Dispatched job for: {$sourceConfig['name']}");

            } catch (\Exception $e) {
                $this->error("✗ Failed to dispatch job for {$sourceConfig['name']}: {$e->getMessage()}");
                Log::error("Failed to dispatch FetchSingleSourceNewsJob for {$sourceKey}", [
                    'source' => $sourceKey,
                    'error' => $e->getMessage(),
                    'exception' => $e
                ]);
            }
        }
        
        $this->info("Successfully dispatched {$jobsDispatched} job(s)");
        
        if ($jobsDispatched > 0) {
            $this->line('');
            $this->line('Jobs have been dispatched to the queue. Monitor progress with:');
            $this->line('  php artisan queue:work');
            $this->line('  php artisan horizon ');
        }
        
        return self::SUCCESS;
    }
}
