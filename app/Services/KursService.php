<?php

namespace App\Services;

use App\Models\KursHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class KursService
{
    /**
     * Ambil kurs tengah USD & SAR dari mirror Bea Cukai BI; fallback Frankfurter (ECB).
     *
     * @return array{date:string, source:string, rates:array<string,float>}|null
     */
    public function fetchBi(?Carbon $date = null): ?array
    {
        $date = $date ?? now()->subDay();

        return $this->fromBeaCukai($date) ?? $this->fromFrankfurter();
    }

    /**
     * Simpan hasil fetch ke tabel history (sumber = bi).
     */
    public function syncBi(?Carbon $date = null): ?array
    {
        $data = $this->fetchBi($date);

        if ($data === null) {
            return null;
        }

        foreach ($data['rates'] as $currency => $rate) {
            if ($rate > 0) {
                KursHistory::create([
                    'tanggal' => $data['date'],
                    'mata_uang' => $currency,
                    'sumber' => 'bi',
                    'kurs' => $rate,
                ]);
            }
        }

        return $data;
    }

    /**
     * Kurs terdekat (<= tanggal) untuk mata uang & sumber tertentu.
     */
    public function nearest(?string $date, string $mataUang, string $sumber = 'bi'): ?float
    {
        $rate = KursHistory::where('mata_uang', $mataUang)
            ->where('sumber', $sumber)
            ->when($date, fn ($q) => $q->where('tanggal', '<=', $date))
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->value('kurs');

        return $rate !== null ? (float) $rate : null;
    }

    public function label(string $sumber): string
    {
        return $sumber === 'bi' ? 'Kurs BI' : 'Manual';
    }

    private function fromBeaCukai(Carbon $date): ?array
    {
        try {
            $url = 'https://www.beacukai.go.id/kurs/'.$date->format('d-m-Y');
            $res = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                'Accept' => 'text/html',
            ])->timeout(30)->get($url);

            if (! $res->successful()) {
                return null;
            }

            $html = $res->body();
            if (! preg_match_all('/<tr>(.*?)<\/tr>/s', $html, $rows)) {
                return null;
            }

            $rates = [];
            foreach ($rows[1] as $row) {
                if (! preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $row, $cells)) {
                    continue;
                }
                $texts = array_values(array_filter(array_map(
                    fn ($c) => trim(strip_tags($c)),
                    $cells[1]
                )));
                if (count($texts) >= 2 && in_array($texts[0], KursHistory::CURRENCIES, true)) {
                    $value = (float) str_replace(',', '', $texts[1]);
                    if ($value > 0) {
                        $rates[$texts[0]] = $value;
                    }
                }
            }

            if (count(array_intersect(KursHistory::CURRENCIES, array_keys($rates))) === 0) {
                return null;
            }

            preg_match('/badge-info[^>]*>(\d{1,2}-\d{1,2}-\d{4})/', $html, $m);
            $rateDate = isset($m[1])
                ? Carbon::createFromFormat('d-m-Y', $m[1])
                : $date;

            return [
                'source' => 'beacukai',
                'date' => $rateDate->format('Y-m-d'),
                'rates' => array_intersect_key($rates, array_flip(KursHistory::CURRENCIES)),
            ];
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    private function fromFrankfurter(): ?array
    {
        try {
            $res = Http::timeout(30)->get('https://api.frankfurter.dev/v1/latest', [
                'base' => 'USD',
                'symbols' => 'IDR,SAR',
            ]);

            if (! $res->successful()) {
                return null;
            }

            $json = $res->json();
            $rates = $json['rates'] ?? [];

            if (! isset($rates['IDR'])) {
                return null;
            }

            $out = ['USD' => (float) $rates['IDR']];
            if (isset($rates['SAR']) && $rates['SAR'] > 0) {
                $out['SAR'] = round($rates['IDR'] / $rates['SAR'], 4);
            }

            return [
                'source' => 'frankfurter',
                'date' => $json['date'] ?? now()->format('Y-m-d'),
                'rates' => $out,
            ];
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
