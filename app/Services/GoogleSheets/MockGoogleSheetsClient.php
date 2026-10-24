<?php

namespace App\Services\GoogleSheets;

use App\Contracts\GoogleSheetsClientInterface;
use App\Exceptions\GoogleSheetsException;

class MockGoogleSheetsClient implements GoogleSheetsClientInterface
{
    protected array $rows = [];
    protected bool $simulateRateLimit = false;
    protected bool $simulateTimeout = false;
    protected ?string $simulateApiError = null;
    protected bool $simulateNotFound = false;
    protected bool $isConfigured = true;

    public function __construct(array $initialRows = [])
    {
        $this->rows = $initialRows;
    }

    public function setMockRows(array $rows): self
    {
        $this->rows = $rows;
        return $this;
    }

    public function addMockRow(array $row): self
    {
        $this->rows[] = $row;
        return $this;
    }

    public function simulateRateLimit(bool $fail = true): self
    {
        $this->simulateRateLimit = $fail;
        return $this;
    }

    public function simulateTimeout(bool $fail = true): self
    {
        $this->simulateTimeout = $fail;
        return $this;
    }

    public function simulateApiError(?string $message): self
    {
        $this->simulateApiError = $message;
        return $this;
    }

    public function simulateNotFound(bool $fail = true): self
    {
        $this->simulateNotFound = $fail;
        return $this;
    }

    public function setConfigured(bool $configured): self
    {
        $this->isConfigured = $configured;
        return $this;
    }

    public function reset(): self
    {
        $this->rows = [];
        $this->simulateRateLimit = false;
        $this->simulateTimeout = false;
        $this->simulateApiError = null;
        $this->simulateNotFound = false;
        $this->isConfigured = true;
        return $this;
    }

    public function fetchRange(string $spreadsheetId, string $range): array
    {
        if ($this->simulateRateLimit) {
            throw GoogleSheetsException::rateLimited('Rate limit exceeded (HTTP 429 Mock).');
        }

        if ($this->simulateTimeout) {
            throw GoogleSheetsException::timeout('Connection timed out after 10000ms (Mock).');
        }

        if ($this->simulateNotFound) {
            throw GoogleSheetsException::notFound("Spreadsheet {$spreadsheetId} or range {$range} not found.");
        }

        if ($this->simulateApiError !== null) {
            throw GoogleSheetsException::general($this->simulateApiError, 500);
        }

        // Parse range if simple row slicing is needed e.g. "Telemetry!A2:O50"
        if (preg_match('/[A-Z]+(\d+):[A-Z]+(\d*)/i', $range, $matches)) {
            $startRow = (int) $matches[1];
            $endRow = !empty($matches[2]) ? (int) $matches[2] : null;

            // In A1 notation, row numbers are 1-indexed.
            // If rows array represents data starting from row 2 (header in row 1):
            // We can return corresponding slice if setMockRows was keyed or 0-indexed list
            if (!empty($this->rows)) {
                // If caller requested range starting at row 2 and we have rows:
                return $this->rows;
            }
        }

        return $this->rows;
    }

    public function getMetadata(string $spreadsheetId): array
    {
        if ($this->simulateNotFound) {
            throw GoogleSheetsException::notFound("Spreadsheet {$spreadsheetId} not found.");
        }

        return [
            'spreadsheetId' => $spreadsheetId,
            'properties' => [
                'title' => 'Greenhouse Melon Telemetry (Mock)',
                'timeZone' => 'Asia/Jakarta',
            ],
            'sheets' => [
                [
                    'properties' => [
                        'sheetId' => 0,
                        'title' => 'Telemetry',
                        'gridProperties' => [
                            'rowCount' => count($this->rows) + 1,
                            'columnCount' => 15,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    public function getClientType(): string
    {
        return 'mock';
    }
}
