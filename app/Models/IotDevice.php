<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class IotDevice extends Model
{
    use HasFactory;

    public const TYPE_ESP32_HARDWARE = 'esp32_hardware';
    public const TYPE_PYTHON_SIMULATOR = 'python_simulator';
    public const TYPE_GOOGLE_SHEETS_GATEWAY = 'google_sheets_gateway';

    protected $fillable = [
        'device_id',
        'name',
        'type',
        'token_hash',
        'token_prefix',
        'is_active',
        'last_seen_at',
        'last_ip',
        'revoked_at',
        'revoked_reason',
    ];

    protected function casts(): array
    {
        return [
            'is_active'    => 'boolean',
            'last_seen_at' => 'datetime',
            'revoked_at'   => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function monitorings(): HasMany
    {
        return $this->hasMany(Monitoring::class, 'device_id', 'device_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('revoked_at');
    }

    public function scopeRevoked($query)
    {
        return $query->whereNotNull('revoked_at');
    }

    // ─── Token Management Helpers ─────────────────────────────────────────────

    /**
     * Daftarkan perangkat baru dan hasilkan token autentikasi acak berkeamanan tinggi.
     * Token polos hanya dikembalikan sekali saat pembuatan dan tidak pernah disimpan dalam plaintext.
     *
     * @param  string  $deviceId   Contoh: 'ESP32-MELON-01'
     * @param  string  $name       Contoh: 'Node Mikroklimat Greenhouse A'
     * @param  string  $type       Tipe perangkat
     * @param  string|null $customToken Token eksplisit opsional (misal: simulator dev token)
     * @return array{device: self, plain_token: string}
     */
    public static function createWithToken(string $deviceId, string $name, string $type = self::TYPE_ESP32_HARDWARE, ?string $customToken = null): array
    {
        $plainToken = $customToken ?: ('melon_dev_' . Str::random(40));
        $tokenHash = hash('sha256', $plainToken);
        $tokenPrefix = substr($plainToken, 0, 10);

        $device = self::create([
            'device_id'    => $deviceId,
            'name'         => $name,
            'type'         => $type,
            'token_hash'   => $tokenHash,
            'token_prefix' => $tokenPrefix,
            'is_active'    => true,
        ]);

        return [$device, $plainToken];
    }

    /**
     * Verifikasi token mentah dari header HTTP request (mencocokkan hash tanpa filter active).
     */
    public static function verifyToken(string $plainToken): ?self
    {
        $hash = hash('sha256', trim($plainToken));

        return self::where('token_hash', $hash)->first();
    }

    /**
     * Cari perangkat aktif berdasarkan token mentah.
     */
    public static function findByToken(string $plainToken): ?self
    {
        $hash = hash('sha256', trim($plainToken));

        return self::where('token_hash', $hash)
            ->active()
            ->first();
    }

    /**
     * Cabut (revoke) token perangkat sehingga tidak dapat digunakan kembali.
     */
    public function revoke(?string $reason = null): void
    {
        $this->update([
            'is_active'      => false,
            'revoked_at'     => now(),
            'revoked_reason' => $reason ?: 'Dicabut oleh Administrator sistem.',
        ]);
    }

    /**
     * Rotasi token: ganti hash token lama dengan token baru.
     */
    public function rotateToken(?string $newToken = null): string
    {
        $plainToken = $newToken ?: ('melon_dev_' . Str::random(40));
        $tokenHash = hash('sha256', $plainToken);
        $tokenPrefix = substr($plainToken, 0, 10);

        $this->update([
            'token_hash'     => $tokenHash,
            'token_prefix'   => $tokenPrefix,
            'is_active'      => true,
            'revoked_at'     => null,
            'revoked_reason' => null,
        ]);

        return $plainToken;
    }

    /**
     * Rekam detak aktivitas (heartbeat) dan alamat IP pengirim.
     */
    public function recordHeartbeat(?string $ip = null): void
    {
        $this->update([
            'last_seen_at' => now(),
            'last_ip'      => $ip,
        ]);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null || !$this->is_active;
    }
}
