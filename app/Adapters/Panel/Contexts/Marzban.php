<?php

namespace App\Adapters\Panel\Contexts;

use App\Adapters\Panel\Panel;
use Filament\Facades\Filament;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;

class Marzban extends Panel
{

    private ?string $username = null;
    private ?string $password = null;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->authenticate(Filament::auth()->user()->panel_username, Filament::auth()->user()->panel_password);
    }

    protected function AuthedHttp(): PendingRequest{

        return Http::retry(3,500, function ($exception,$request)  { return $exception->getCode() == 401; },false)->withRequestMiddleware(function ($request)  {
            
            if(!$this->username or !$this->password){
                return $request;
            }
            $tokenData = $this->authenticate($this->username, $this->password);
            return $request->withHeader('Authorization', 'Bearer '.$tokenData['access_token']); 
        })->withResponseMiddleware(function ($response) {

            if($response->getStatusCode() == 401){
                error_log('got 401');
                Cache::forget('marzban_token_' . md5($this->config['baseurl'] . $this->username));
                $this->authenticate($this->username, $this->password);
            }
            return $response;
        });
    }
    
    public function authenticate(string $username, string $password): array
    {
        $this->username = $username;
        $this->password = $password;
        
        $cacheKey = 'marzban_token_' . md5($this->config['baseurl'] . $username);
        
        // Try to get token from cache first
        $cachedToken = Cache::get($cacheKey);
        if ($cachedToken) {
            return $cachedToken;
        }
        
        // If not in cache, authenticate
        return $this->performAuthentication();
    }
    
    private function performAuthentication(): array
    {
        $response = Http::asForm()->post($this->config['baseurl'] . '/api/admin/token', [
            'grant_type' => 'password',
            'username' => $this->username,
            'password' => $this->password,
            'scope' => '',
        ]);

        if ($response->failed()) {
            throw new \Exception('Authentication failed: ' . $response->body());
        }

        $tokenData = $response->json();
        
        $cacheKey = 'marzban_token_' . md5($this->config['baseurl'] . $this->username);
        Cache::put($cacheKey, $tokenData, 3600);
        
        return $tokenData;
    }

    /**
     * Create a new user in Marzban with all available inbounds and generated UUIDs
     * 
     * @param string $username Username for the new user
     * @return array Created user response
     * @throws \Exception
     */
    public function createUser(string $username): array
    {
        // Get available inbounds from the system
        $availableInbounds = $this->getAvailableInbounds();
        
        // Generate UUIDs for proxies that need them
        $proxies = $this->generateProxiesWithUUIDs($availableInbounds);
        
        $userData = [
            'username' => $username,
            'data_limit' => 0,
            'data_limit_reset_strategy' => 'no_reset',
            'expire' => 0,
            'inbounds' => $availableInbounds,
            'proxies' => $proxies,
            'note' => '',
            'status' => 'active',
            'on_hold_expire_duration' => 0,
            'on_hold_timeout' => null,
            'next_plan' => null,
        ];

        $response = $this->AuthedHttp()->post($this->config['baseurl'] . '/api/user', $userData);
        
        if ($response->failed()) {
            $error = $response->json();
            throw new \Exception('User creation failed: ' . ($error['detail'] ?? $response->body()));
        }

        return $response->json();
    }

    /**
     * Get available inbounds from the system
     * 
     * @return array Available inbounds grouped by protocol
     */
    private function getAvailableInbounds(): array
    {
        $response = $this->AuthedHttp()->get($this->config['baseurl'] . '/api/inbounds');
        
        if ($response->failed()) {
            throw new \Exception('Failed to get inbounds: ' . $response->body());
        }

        $inbounds = $response->json();
        $groupedInbounds = [];
        foreach ($inbounds as $pr => $i) {
            $inbound = $i[0];
            $protocol = $inbound['protocol'] ?? 'unknown';
            $tag = $inbound['tag'] ?? 'unknown';
            
            if (!isset($groupedInbounds[$protocol])) {
                $groupedInbounds[$protocol] = [];
            }
            
            $groupedInbounds[$protocol][] = $tag;
        }

        return $groupedInbounds;
    }

    /**
     * Generate proxies with UUIDs for protocols that need them
     * 
     * @param array $inbounds Available inbounds
     * @return array Proxies configuration with UUIDs
     */
    private function generateProxiesWithUUIDs(array $inbounds): array
    {
        $proxies = [];

        foreach ($inbounds as $protocol => $tags) {
            switch ($protocol) {
                case 'vmess':
                case 'vless':
                    $proxies[$protocol] = [
                        'id' => $this->generateUUID()
                    ];
                    break;
                case 'trojan':
                    $proxies[$protocol] = [
                        'password' => $this->generateUUID()
                    ];
                    break;
                case 'shadowsocks':
                    $proxies[$protocol] = [
                        'password' => $this->generateRandomPassword()
                    ];
                    break;
                default:
                    $proxies[$protocol] = (object)[];
                    break;
            }
        }

        return $proxies;
    }

    /**
     * Generate a UUID v4
     * 
     * @return string UUID v4
     */
    private function generateUUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Generate a random password for protocols that need it
     * 
     * @return string Random password
     */
    private function generateRandomPassword(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function deactivateUser(string $username): void
    {
        dd('ddd');
        $response = $this->AuthedHttp()->put($this->config['baseurl'] . '/api/user/'.$username, [
            'status' => 'disabled',
        ]);
        dd($response);
        
        if ($response->failed()) {
            throw new \Exception('Failed to deactivate user');
        }
    }

    public function activateUser(string $username): void
    {
        $response = $this->AuthedHttp()->put($this->config['baseurl'] . '/api/user/'.$username, [
            'status' => 'active',
        ]);
        
        if ($response->failed()) {
            throw new \Exception('Failed to activate user');
        }
    }


    /**
     * Get users with optional search, limit, and offset
     *
     * @param string|null $search Search query for username or other fields
     * @param int|null $limit Number of users to return
     * @param int|null $offset Offset for pagination
     * @return array Users list
     * @throws \Exception
     */
    public function getUsers(?string $search = null, ?int $limit = null, ?int $offset = null, ?string $sort = null): array
    {
        $query = [];

        if ($search !== null) {
            $query['search'] = $search;
        }
        if ($limit !== null) {
            $query['limit'] = $limit;
        }
        if ($offset !== null) {
            $query['offset'] = $offset;
        }
        if ($sort !== null) {
            $query['sort'] = $sort;
        }

        $response = $this->AuthedHttp()->get($this->config['baseurl'] . '/api/users', $query);

        if ($response->failed()) {
            throw new \Exception('Failed to get users: ' . $response->body());
        }

        return $response->json();
    }

}