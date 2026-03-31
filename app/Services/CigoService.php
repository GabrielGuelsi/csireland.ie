<?php

namespace App\Services;

/**
 * Stub for future CIGO system integration.
 *
 * When ready, implement getStudent() to fetch student data
 * from the CIGO API using the student's CIGO ID or name/phone.
 */
class CigoService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.cigo.url', '');
        $this->apiKey  = config('services.cigo.key', '');
    }

    /**
     * Fetch a student record from CIGO by their ID.
     * Returns null until the integration is configured.
     */
    public function getStudent(string $cigoId): ?array
    {
        if (empty($this->baseUrl) || empty($this->apiKey)) {
            return null;
        }

        // TODO: implement HTTP GET to CIGO API
        // $response = Http::withToken($this->apiKey)->get("{$this->baseUrl}/students/{$cigoId}");
        // return $response->ok() ? $response->json() : null;

        return null;
    }
}
