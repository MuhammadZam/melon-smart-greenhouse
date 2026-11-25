<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LogisticsForecastTest extends TestCase
{
    public function test_signed_in_user_can_view_bundled_forecast(): void
    {
        $this->get(route('logistics.forecast'))->assertRedirect(route('login'));

        $this->actingAs(User::where('role', 'user')->firstOrFail())
            ->get(route('logistics.forecast', ['contoh' => 1]))
            ->assertOk()
            ->assertViewHas('forecast', fn (array $forecast) =>
                count($forecast['rows']) === 864
                && $forecast['methods'] === 'LSTM'
                && $forecast['metrics']['CO2_ppm']['hasMae']
            );
    }

    public function test_uploaded_forecasts_are_sorted_and_invalid_dates_are_rejected(): void
    {
        $this->actingAs(User::where('role', 'admin')->firstOrFail());
        $header = "waktu_prediksi,metode,moisture_pct,temp_C,EC_uScm,CO2_ppm\n";

        $this->post(route('logistics.forecast.upload'), [
            'forecast' => UploadedFile::fake()->createWithContent(
                'forecast.csv',
                $header."14-10-26 20:10,LSTM,77,28,194,517\n14-10-26 20:05,LSTM,78,29,193,516\n"
            ),
        ])->assertOk()->assertViewHas('forecast', fn (array $forecast) =>
            $forecast['rows'][0]['moisture_pct'] === 78.0
            && !$forecast['metrics']['CO2_ppm']['hasMae']
        );

        $this->post(route('logistics.forecast.upload'), [
            'forecast' => UploadedFile::fake()->createWithContent('forecast.csv', $header."31-02-26 20:05,LSTM,78,29,193,516\n"),
        ])->assertOk()
            ->assertSee('Waktu prediksi tidak valid pada baris 2.')
            ->assertViewMissing('forecast');
    }
}
