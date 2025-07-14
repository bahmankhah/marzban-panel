<?php

namespace App\Adapters\Panel\Contexts;

use App\Adapters\Panel\Panel;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class Marzban extends Panel
{
    private ?string $accessToken = null;
    private ?string $tokenType = 'bearer';
    private string $baseUrl;

    public function authenticate(string $username, string $password): array
    {
        $response = Http::asForm()->post($this->baseUrl . '/api/admin/token', [
            'grant_type' => 'password',
            'username' => $username,
            'password' => $password,
            'scope' => '',
        ]);

        if ($response->failed()) {
            throw new \Exception('Authentication failed: ' . $response->body());
        }

        $tokenData = $response->json();
        
        $this->accessToken = $tokenData['access_token'];
        $this->tokenType = $tokenData['token_type'] ?? 'bearer';
        
        return $tokenData;
    }

    /**
     * Create a new user in Marzban
     * 
     * @param array $userData User data following UserCreate schema
     * @return array Created user response
     * @throws \Exception
     */
    public function createUser(array $userData): array
    {
        if (!$this->accessToken) {
            throw new \Exception('Not authenticated. Please call authenticate() first.');
        }

        // Validate required fields
        if (!isset($userData['username']) || empty($userData['username'])) {
            throw new \Exception('Username is required');
        }

        // Set defaults for optional fields
        $userData = array_merge([
            'proxies' => [],
            'expire' => null,
            'data_limit' => null,
            'data_limit_reset_strategy' => 'no_reset',
            'inbounds' => [],
            'note' => null,
            'status' => 'active',
            'sub_updated_at' => null,
            'sub_last_user_agent' => null,
            'online_at' => null,
            'on_hold_expire_duration' => null,
            'on_hold_timeout' => null,
            'auto_delete_in_days' => null,
            'next_plan' => null,
        ], $userData);

        $response = Http::withHeaders([
            'Authorization' => ucfirst($this->tokenType) . ' ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/api/user', $userData);

        if ($response->failed()) {
            $error = $response->json();
            throw new \Exception('User creation failed: ' . ($error['detail'] ?? $response->body()));
        }

        return $response->json();
    }

    /**
     * Get current access token
     * 
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    /**
     * Check if authenticated
     * 
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return $this->accessToken !== null;
    }

    /**
     * Make authenticated request to Marzban API
     * 
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return Response
     * @throws \Exception
     */
    protected function makeAuthenticatedRequest(string $method, string $endpoint, array $data = []): Response
    {
        if (!$this->accessToken) {
            throw new \Exception('Not authenticated. Please call authenticate() first.');
        }

        $client = Http::withHeaders([
            'Authorization' => ucfirst($this->tokenType) . ' ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ]);

        return $client->$method($this->baseUrl . $endpoint, $data);
    }
}