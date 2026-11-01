<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Jobs;

use Asignua\FilamentSeoFiles\Support\IndexNow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * One IndexNow request (at most 10 000 URLs). 200/202 = accepted; 429 and 5xx are retried
 * with a back-off; any other 4xx (a wrong key, a host mismatch) is a configuration error
 * and fails the job at once.
 */
class PingIndexNow implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    /**
     * @param list<string>          $urls
     * @param array<string, string> $signatures recorded as pinged once the request succeeds
     */
    public function __construct(public array $urls, public array $signatures = [])
    {
        $connection = config('filament-seo-files.indexnow.connection');
        $queue = config('filament-seo-files.indexnow.queue');

        if (is_string($connection) && $connection !== '') {
            $this->onConnection($connection);
        }

        if (is_string($queue) && $queue !== '') {
            $this->onQueue($queue);
        }
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(): void
    {
        if (!IndexNow::enabled() || !IndexNow::validKey()) {
            return;
        }

        $response = Http::asJson()
            ->timeout(15)
            ->post((string) config('filament-seo-files.indexnow.endpoint', 'https://api.indexnow.org/indexnow'), [
                'host' => IndexNow::host(),
                'key' => IndexNow::key(),
                'keyLocation' => IndexNow::keyLocation(),
                'urlList' => $this->urls,
            ]);

        $status = $response->status();

        if ($status === 200 || $status === 202) {
            IndexNow::remember($this->signatures);

            return;
        }

        if ($status === 429 || $status >= 500) {
            throw new RuntimeException("IndexNow answered {$status}; will retry.");
        }

        Log::warning('IndexNow rejected the ping', ['status' => $status, 'urls' => count($this->urls)]);
        $this->fail(new RuntimeException("IndexNow rejected the ping with {$status} (check the key and that {$this->keyHint()} is reachable)."));
    }

    private function keyHint(): string
    {
        return IndexNow::keyLocation();
    }
}
