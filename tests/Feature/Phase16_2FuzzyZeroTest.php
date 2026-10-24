<?php

namespace Tests\Feature;

use App\Models\FuzzyInferenceResult;
use App\Models\Monitoring;
use App\Models\Plant;
use App\Services\FuzzyLogic\ExternalFuzzyScore;
use App\Services\FuzzyLogic\FuzzyPipelineService;
use App\Services\GoogleSheets\GoogleSheetsSyncService;
use App\Services\GoogleSheets\GoogleSheetsTelemetryService;
use App\Services\GoogleSheets\MockGoogleSheetsClient;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Phase16_2FuzzyZeroTest extends TestCase
{
    public static function validScores(): array
    {
        return [[0,'BURUK'],[0.0,'BURUK'],['0','BURUK'],[0.01,'BURUK'],[39.99,'BURUK'],[40,'PERLU_PERHATIAN'],[64.99,'PERLU_PERHATIAN'],[65,'BAIK'],[100,'BAIK']];
    }

    /** @dataProvider validScores */
    public function test_api_preserves_external_score_and_uses_existing_category(mixed $score, string $category): void
    {
        $this->authenticateDevice();
        $response = $this->postJson('/api/monitorings', $this->payload() + ['fuzzy_score' => $score])
            ->assertCreated()->assertJsonPath('data.fuzzy_label', $category);
        $id = $response->json('data.id');
        $this->assertEquals((float) $score, $response->json('data.fuzzy_score'));
        $this->assertDatabaseHas('fuzzy_inference_results', ['monitoring_id' => $id, 'inference_source' => 'api_payload', 'fuzzy_score' => (float) $score, 'fuzzy_category' => $category]);
        $this->assertEquals((float) $score, Monitoring::find($id)->fuzzy_score);
    }

    public static function invalidScores(): array
    {
        return [[''],[' '],[-0.01],[100.01],['NaN'],['Infinity'],[true],[[]]];
    }

    /** @dataProvider invalidScores */
    public function test_invalid_external_score_cannot_store_observation(mixed $score): void
    {
        $this->authenticateDevice(); $before = Monitoring::count();
        $this->postJson('/api/monitorings', $this->payload() + ['fuzzy_score' => $score])
            ->assertUnprocessable()->assertJsonValidationErrors('fuzzy_score');
        $this->assertSame($before, Monitoring::count());
    }

    public function test_nonfinite_native_values_are_rejected(): void
    {
        foreach ([NAN,INF,-INF] as $value) {
            try { ExternalFuzzyScore::normalize($value); $this->fail('Nonfinite score accepted'); }
            catch (ValidationException $e) { $this->assertArrayHasKey('fuzzy_score', $e->errors()); }
        }
    }

    public function test_null_or_missing_external_score_is_unavailable_not_external_zero(): void
    {
        $monitoring = Monitoring::factory()->create(['plant_id' => Plant::first()->id]);
        foreach ([[],['score' => null]] as $payload) {
            $this->assertNull(app(FuzzyPipelineService::class)->process($monitoring, 'external_esp32', $payload));
        }
        $this->assertSame(0, FuzzyInferenceResult::where('monitoring_id', $monitoring->id)->count());
        $this->assertNull(ExternalFuzzyScore::normalize(null));
        $this->authenticateDevice();
        foreach ([[],['fuzzy_score' => null]] as $i => $extra) {
            $this->postJson('/api/monitorings', array_merge($this->payload(), ['event_id' => 'missing-'.$i], $extra))
                ->assertCreated();
        }
        $this->assertSame(0, FuzzyInferenceResult::where('inference_source', 'api_payload')->count());
    }

    public function test_external_zero_history_is_not_overwritten_on_reprocessing(): void
    {
        $m = Monitoring::factory()->create(['plant_id' => Plant::first()->id]);
        $pipeline = app(FuzzyPipelineService::class);
        $result = $pipeline->process($m, 'external_esp32', ['score' => 0, 'category' => 'BAIK']);
        $snapshot = $result->fresh()->getRawOriginal();
        $pipeline->process($m, 'external_esp32', ['score' => 95]);
        $this->assertSame($snapshot, $result->fresh()->getRawOriginal());
        $this->assertSame('BURUK', $result->fresh()->fuzzy_category);
    }

    public function test_sheet_zero_import_replay_and_different_device_are_safe(): void
    {
        $plant = Plant::first();
        $row = ['device_id' => 'SHEET-1','timestamp' => '2026-10-22 10:00:00','plant_code' => $plant->name,'temperature' => 27,'humidity' => 70,'soil_moisture' => 70,'ph' => 6.5,'fuzzy_score' => '0','fuzzy_label' => 'BAIK'];
        $client = new MockGoogleSheetsClient([$row,$row,array_merge($row,['device_id' => 'SHEET-2'])]);
        $sync = app(GoogleSheetsSyncService::class); $sync->getTelemetryService()->setClient($client);
        $config = $sync->getOrCreateDefaultConfig();
        $log = $sync->sync($config);
        $this->assertSame(2, $log->records_imported); $this->assertSame(1, $log->records_skipped);
        $m = Monitoring::where('device_id', 'SHEET-1')->firstOrFail();
        $this->assertEquals(0, $m->fuzzy_score);
        $this->assertSame('BURUK', $m->fuzzy_label);
        $this->assertDatabaseHas('fuzzy_inference_results', ['monitoring_id' => $m->id, 'fuzzy_score' => 0, 'fuzzy_category' => 'BURUK']);
        $config->update(['last_synced_row' => 1]);
        $log = $sync->sync($config); $this->assertSame(0, $log->records_imported); $this->assertSame(3, $log->records_skipped);
        foreach (['NaN','Infinity',-1,101] as $score) $this->assertFalse(app(GoogleSheetsTelemetryService::class)->parseAndValidateRow(array_merge($row,['fuzzy_score' => $score]))['valid']);
    }

    public function test_zero_is_visible_in_history_dashboard_and_csv(): void
    {
        $this->authenticateDevice();
        $id = $this->postJson('/api/monitorings', $this->payload() + ['fuzzy_score' => 0])->assertCreated()->json('data.id');
        $user = \App\Models\User::where('role', 'user')->first();
        $this->actingAs($user)->get('/fuzzy/history')->assertOk()->assertSee('BURUK');
        $this->get('/dashboard')->assertOk()->assertSee('BURUK');
        $csv = $this->get('/reports/monitoring/export/csv?date_from=2026-10-22&date_to=2026-10-22')->assertOk()->streamedContent();
        $this->assertStringContainsString('0.00,BURUK', $csv);
        $this->assertEquals(0, Monitoring::find($id)->latestFuzzyInference->fuzzy_score);
    }

    private function payload(): array
    {
        return ['plant_id' => Plant::first()->id, 'monitored_at' => '2026-10-22 10:00:00', 'temperature' => 27, 'humidity' => 70, 'soil_moisture' => 70, 'ph' => 6.5, 'plant_height' => 40, 'plant_density' => 60, 'condition' => 'optimal'];
    }
}
