<?php

namespace App\Services\GoogleSheets;

use App\Contracts\GoogleSheetsClientInterface;
use App\Exceptions\GoogleSheetsException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleSheetsApiClient implements GoogleSheetsClientInterface
{
    protected ?string $spreadsheetId;
    protected ?string $credentialsPath;
    protected int $timeout;
    protected ?string $cachedAccessToken = null;
    protected ?int $tokenExpiresAt = null;

    public function __construct(
        ?string $spreadsheetId = null,
        ?string $credentialsPath = null,
        int $timeout = 10
    ) {
        $this->spreadsheetId = $spreadsheetId ?? config('services.google_sheets.spreadsheet_id');
        $this->credentialsPath = $credentialsPath ?? config('services.google_sheets.credentials_path');
        $this->timeout = $timeout > 0 ? $timeout : (int) config('services.google_sheets.timeout_seconds', 10);
    }

    public function isConfigured(): bool
    {
        if (empty($this->spreadsheetId)) {
            return false;
        }

        if (empty($this->credentialsPath) || !File::exists($this->credentialsPath)) {
            return false;
        }

        try {
            $json = json_decode(File::get($this->credentialsPath), true);
            return is_array($json) && !empty($json['client_email']) && !empty($json['private_key']);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getClientType(): string
    {
        return 'service_account';
    }

    public function fetchRange(string $spreadsheetId, string $range): array
    {
        if (!$this->isConfigured()) {
            throw GoogleSheetsException::unauthenticated(
                'Live Google Sheets integration is PENDING: Service Account credentials or Spreadsheet ID is missing/invalid.'
            );
        }

        $token = $this->getAccessToken();
        $encodedRange = urlencode($range);
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$encodedRange}";

        try {
            $response = Http::withToken($token)
                ->timeout($this->timeout)
                ->retry(3, 500, function ($exception, $request) {
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                }, throw: false)
                ->get($url);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw GoogleSheetsException::timeout("Connection timed out reaching Google Sheets API: " . $e->getMessage(), $e);
        } catch (\Throwable $e) {
            throw GoogleSheetsException::general("Network error while connecting to Google Sheets API: " . $e->getMessage(), null, $e);
        }

        if ($response->status() === 429) {
            throw GoogleSheetsException::rateLimited('Google Sheets API rate limit reached (HTTP 429).');
        }

        if ($response->status() === 404) {
            throw GoogleSheetsException::notFound("Google Spreadsheet [{$spreadsheetId}] or range [{$range}] was not found (HTTP 404).");
        }

        if ($response->status() === 401 || $response->status() === 403) {
            throw GoogleSheetsException::unauthenticated("Google Sheets API authorization error: " . $response->body());
        }

        if ($response->failed()) {
            throw GoogleSheetsException::general("Google Sheets API returned error: " . $response->body(), $response->status());
        }

        $data = $response->json();
        return $data['values'] ?? [];
    }

    public function getMetadata(string $spreadsheetId): array
    {
        if (!$this->isConfigured()) {
            throw GoogleSheetsException::unauthenticated(
                'Live Google Sheets integration is PENDING: Service Account credentials or Spreadsheet ID is missing/invalid.'
            );
        }

        $token = $this->getAccessToken();
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}?fields=spreadsheetId,properties.title,sheets.properties";

        try {
            $response = Http::withToken($token)
                ->timeout($this->timeout)
                ->retry(2, 500, throw: false)
                ->get($url);
        } catch (\Throwable $e) {
            throw GoogleSheetsException::general("Failed to retrieve spreadsheet metadata: " . $e->getMessage(), null, $e);
        }

        if ($response->failed()) {
            throw GoogleSheetsException::general("Failed to retrieve spreadsheet metadata: " . $response->body(), $response->status());
        }

        return $response->json() ?? [];
    }

    /**
     * Dapatkan OAuth2 Access Token menggunakan Service Account JWT.
     */
    protected function getAccessToken(): string
    {
        if ($this->cachedAccessToken && $this->tokenExpiresAt && time() < ($this->tokenExpiresAt - 60)) {
            return $this->cachedAccessToken;
        }

        $creds = json_decode(File::get($this->credentialsPath), true);
        $clientEmail = $creds['client_email'];
        $privateKey = $creds['private_key'];

        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claim = [
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/spreadsheets.readonly',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ];

        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlClaim = $this->base64UrlEncode(json_encode($claim));
        $signatureInput = $base64UrlHeader . '.' . $base64UrlClaim;

        $signature = '';
        $binarySignature = '';
        if (!openssl_sign($signatureInput, $binarySignature, $privateKey, 'SHA256')) {
            throw GoogleSheetsException::unauthenticated('Failed to sign JWT for Google Service Account authentication.');
        }

        $jwt = $signatureInput . '.' . $this->base64UrlEncode($binarySignature);

        $response = Http::asForm()->timeout($this->timeout)->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if ($response->failed()) {
            throw GoogleSheetsException::unauthenticated('OAuth2 token exchange failed: ' . $response->body());
        }

        $tokenData = $response->json();
        $this->cachedAccessToken = $tokenData['access_token'];
        $this->tokenExpiresAt = $now + ($tokenData['expires_in'] ?? 3600);

        return $this->cachedAccessToken;
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
