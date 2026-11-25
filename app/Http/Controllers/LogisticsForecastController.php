<?php

namespace App\Http\Controllers;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class LogisticsForecastController extends Controller
{
    private const METRICS = [
        'moisture_pct' => ['label' => 'Kelembapan tanah', 'unit' => '%'],
        'temp_C' => ['label' => 'Suhu udara', 'unit' => '°C'],
        'EC_uScm' => ['label' => 'Konduktivitas listrik (EC)', 'unit' => 'µS/cm'],
        'CO2_ppm' => ['label' => 'Karbon dioksida (CO₂)', 'unit' => 'ppm'],
    ];

    public function index(Request $request): View
    {
        if (!$request->boolean('contoh')) {
            return view('logistics.forecast');
        }

        $path = resource_path('data/logistics-forecast-example.csv');

        return $this->dashboard($path, 'Contoh prakiraan LSTM');
    }

    public function upload(Request $request): View
    {
        $request->validate([
            'forecast' => ['required', 'file', 'extensions:csv', 'max:2048'],
        ], [
            'forecast.required' => 'Pilih file CSV prakiraan.',
            'forecast.extensions' => 'Gunakan file dengan ekstensi .csv.',
            'forecast.max' => 'Ukuran file maksimal 2 MB.',
        ]);

        $file = $request->file('forecast');

        return $this->dashboard($file->getPathname(), $file->getClientOriginalName());
    }

    private function dashboard(string $path, string $filename): View
    {
        try {
            $forecast = $this->readCsv($path);
        } catch (InvalidArgumentException $exception) {
            return view('logistics.forecast', ['uploadError' => $exception->getMessage()]);
        }

        return view('logistics.forecast', ['forecast' => $forecast, 'filename' => $filename]);
    }

    private function readCsv(string $path): array
    {
        $file = fopen($path, 'rb');
        if ($file === false) {
            throw new InvalidArgumentException('File CSV tidak dapat dibaca.');
        }

        $rows = [];
        $metrics = self::METRICS;
        $methods = [];

        try {
            $header = fgetcsv($file, null, ',', '"', '');
            if ($header === false) {
                throw new InvalidArgumentException('File CSV kosong.');
            }
            $header = array_map(fn ($column) => trim((string) $column), $header);
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            $required = ['waktu_prediksi', 'metode', ...array_keys($metrics)];
            $missing = array_diff($required, $header);
            if ($missing || count($header) !== count(array_unique($header))) {
                throw new InvalidArgumentException('Gunakan header unik sesuai file contoh. Kolom wajib: '.implode(', ', $required).'.');
            }

            foreach ($metrics as $key => &$metric) {
                $metric['hasMae'] = in_array('MAE_uji_'.$key, $header, true);
            }
            unset($metric);

            $line = 1;
            while (($values = fgetcsv($file, null, ',', '"', '')) !== false) {
                $line++;
                if ($values === [null]) {
                    continue;
                }
                if (count($values) !== count($header)) {
                    throw new InvalidArgumentException("Jumlah kolom tidak sesuai pada baris {$line}.");
                }

                $record = array_combine($header, $values);
                $time = $this->timestamp(trim($record['waktu_prediksi']), $line);
                $method = trim($record['metode']);
                if ($method === '' || strlen($method) > 80 || !mb_check_encoding($method, 'UTF-8')) {
                    throw new InvalidArgumentException("Metode tidak valid pada baris {$line}.");
                }
                if (isset($rows[$time])) {
                    throw new InvalidArgumentException("Waktu prediksi duplikat pada baris {$line}.");
                }
                $methods[$method] = true;

                $row = ['time' => $time * 1000];
                foreach ($metrics as $key => $metric) {
                    $row[$key] = $this->number($record[$key], $key, $line);
                    if ($metric['hasMae']) {
                        $column = 'MAE_uji_'.$key;
                        $row[$column] = $this->number($record[$column], $column, $line, true);
                    }
                }
                $rows[$time] = $row;
                if (count($rows) > 10000) {
                    throw new InvalidArgumentException('CSV maksimal berisi 10.000 titik prediksi.');
                }
            }
        } finally {
            fclose($file);
        }

        if ($rows === []) {
            throw new InvalidArgumentException('CSV belum berisi baris prakiraan.');
        }

        ksort($rows, SORT_NUMERIC);

        return [
            'rows' => array_values($rows),
            'metrics' => $metrics,
            'methods' => implode(', ', array_keys($methods)),
            'start' => gmdate('d/m/Y H:i', array_key_first($rows)),
            'end' => gmdate('d/m/Y H:i', array_key_last($rows)),
        ];
    }

    private function timestamp(string $value, int $line): int
    {
        foreach (['d-m-y H:i', 'd-m-y G:i', 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value, new DateTimeZone('UTC'));
            if ($date !== false && $date->format($format) === $value) {
                return $date->getTimestamp();
            }
        }

        throw new InvalidArgumentException("Waktu prediksi tidak valid pada baris {$line}.");
    }

    private function number(string $value, string $column, int $line, bool $nonNegative = false): float
    {
        $value = trim($value);
        if (!is_numeric($value) || !is_finite((float) $value) || ($nonNegative && (float) $value < 0)) {
            throw new InvalidArgumentException("Nilai {$column} tidak valid pada baris {$line}.");
        }

        return (float) $value;
    }
}
