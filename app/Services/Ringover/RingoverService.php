<?php

namespace App\Services\Ringover;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RingoverService
{
    protected $apiKey;
    protected $baseUrl;
    protected $timeout;

    public function __construct()
    {
        // Pulling from the config/services.php we just updated
        $this->apiKey = config('services.ringover.key');
        $this->baseUrl = config('services.ringover.url');
        $this->timeout = config('services.ringover.timeout');
    }

    /**
     * Fetch call history
     */
    public function getCallHistory(array $params = [])
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'Accept'        => 'application/json',
            ])
            ->timeout($this->timeout)
            ->withoutVerifying()
            ->get("{$this->baseUrl}/calls", $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("Ringover API Error: " . $response->status() . " - " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("Ringover Exception: " . $e->getMessage());
            return null;
        }
    }
}