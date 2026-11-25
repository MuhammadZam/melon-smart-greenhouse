<?php

namespace App\Services;

use App\Models\Monitoring;
use App\Services\FuzzyLogic\FuzzyPipelineService;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class MonitoringIngestionService
{
    public const OBSERVATION_ID_PATTERN = '/\A[A-Za-z0-9][A-Za-z0-9._:-]{0,127}\z/';
    public const OBSERVATION_TIME_PATTERN = '/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-](?:0\d|1[0-4]):[0-5]\d)\z/';

    /** @return array{monitoring: Monitoring, created: bool} */
    public function ingest(array $attributes, ?string $eventKey = null, ?array $externalFuzzy = null, ?string $observationId = null): array
    {
        $observedAt = Carbon::parse($attributes['monitored_at'], config('app.timezone'))->setTimezone(config('app.timezone'));
        $identityTime = $observationId === null ? $observedAt : $observedAt->copy()->utc();
        $canonicalTime = $identityTime->format('Y-m-d H:i:s.u');
        $legacyEligible = $observationId === null && $eventKey === null && $observedAt->micro === 0;
        $attributes['monitored_at'] = $observedAt->format('Y-m-d H:i:s');
        $identity = hash('sha256', json_encode($observationId !== null ? [
            'v2', $attributes['device_id'], $observationId,
        ] : [
            'v1', $attributes['source'], $attributes['device_id'], (int) $attributes['plant_id'],
            $eventKey === null ? 'timestamp' : 'event', $eventKey ?? $canonicalTime,
        ], JSON_THROW_ON_ERROR));
        unset($attributes['source_identifier']);
        // Channel-specific metadata belongs to the first accepted copy. Compare
        // the observation itself, including plant and the exact normalized instant.
        $payload = $observationId === null ? $attributes : array_intersect_key(
            $attributes,
            array_flip(['plant_id', 'temperature', 'soil_moisture', 'ph'])
        );
        $payloadHash = $this->fingerprint($payload + ['observed_at' => $canonicalTime]);

        try {
            return DB::transaction(function () use ($attributes, $identity, $payloadHash, $legacyEligible, $canonicalTime, $externalFuzzy) {
                // Unique insertion comes before monitoring/side effects, serializing duplicates.
                DB::table('monitoring_ingestion_keys')->insert([
                    'identity_hash' => $identity, 'payload_hash' => $payloadHash,
                    'observed_at' => $canonicalTime,
                    'monitoring_id' => null, 'created_at' => now(),
                ]);
                if ($legacyEligible) {
                    $legacy = Monitoring::where('source', $attributes['source'])
                        ->where('device_id', $attributes['device_id'])
                        ->where('plant_id', $attributes['plant_id'])
                        ->where('monitored_at', $attributes['monitored_at'])->get();
                    if ($legacy->isNotEmpty()) {
                        if ($legacy->count() !== 1 || $this->fingerprint($legacy->first()->only(array_keys($attributes)) + ['observed_at' => $canonicalTime]) !== $payloadHash) {
                            throw new ConflictHttpException('Identitas observasi historis ambigu atau payload berbeda; tinjau tanpa menghapus histori.');
                        }
                        $monitoring = $legacy->first();
                        DB::table('monitoring_ingestion_keys')->where('identity_hash', $identity)->update(['monitoring_id' => $monitoring->id]);

                        return ['monitoring' => $monitoring, 'created' => false];
                    }
                }
                $monitoring = Monitoring::create($attributes + ['source_identifier' => $identity]);
                app(MonitoringAlarmService::class)->evaluate($monitoring);
                app(FuzzyPipelineService::class)->process(
                    $monitoring,
                    $attributes['source'] === 'google_sheets' ? ($externalFuzzy !== null ? 'google_sheets_payload' : 'google_sheets_sync') : ($externalFuzzy !== null ? 'api_payload' : 'api_ingest'),
                    $externalFuzzy
                );
                DB::table('monitoring_ingestion_keys')->where('identity_hash', $identity)->update(['monitoring_id' => $monitoring->id]);

                return ['monitoring' => $monitoring, 'created' => true];
            }, 5);
        } catch (UniqueConstraintViolationException $e) {
            // Outside the failed transaction: use the committed winner, never repeat effects.
            $receipt = DB::table('monitoring_ingestion_keys')->where('identity_hash', $identity)->first();
            if (!$receipt) throw $e;
            if (!hash_equals($receipt->payload_hash, $payloadHash) || !$receipt->monitoring_id) {
                throw new ConflictHttpException('Idempotency key telah digunakan untuk payload berbeda atau observasi yang telah dihapus.');
            }

            return ['monitoring' => Monitoring::findOrFail($receipt->monitoring_id), 'created' => false];
        }
    }

    private function fingerprint(array $attributes): string
    {
        if (isset($attributes['monitored_at'])) {
            $attributes['monitored_at'] = Carbon::parse($attributes['monitored_at'], config('app.timezone'))
                ->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
        }
        foreach (['plant_id','temperature','humidity','soil_moisture','ph','ec','light_intensity','nutrient_temperature','tank_level','plant_height','plant_density','fuzzy_score'] as $key) {
            if (isset($attributes[$key])) $attributes[$key] = (float) $attributes[$key];
        }
        ksort($attributes);

        return hash('sha256', json_encode($attributes, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
    }
}
