<?php

namespace App\Contracts;

interface GoogleSheetsClientInterface
{
    /**
     * Ambil data dari Google Sheets berdasarkan rentang (contoh: "Telemetry!A2:O50").
     *
     * @param string $spreadsheetId ID Dokumen Google Sheets
     * @param string $range Rentang sheet dan sel (A1 notation)
     * @return array<int, array<int, mixed>> Array dua dimensi berisi nilai baris dan kolom
     * @throws \App\Exceptions\GoogleSheetsException
     */
    public function fetchRange(string $spreadsheetId, string $range): array;

    /**
     * Ambil metadata spreadsheet (judul, daftar sheet, dll).
     *
     * @param string $spreadsheetId ID Dokumen Google Sheets
     * @return array
     * @throws \App\Exceptions\GoogleSheetsException
     */
    public function getMetadata(string $spreadsheetId): array;

    /**
     * Apakah client siap dan memiliki konfigurasi autentikasi yang valid.
     */
    public function isConfigured(): bool;

    /**
     * Tipe adapter client yang sedang aktif ('mock' atau 'service_account').
     */
    public function getClientType(): string;
}
