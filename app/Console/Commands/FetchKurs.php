<?php

namespace App\Console\Commands;

use App\Services\KursService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FetchKurs extends Command
{
    protected $signature = 'kurs:fetch {--date= : Tanggal kurs (d-m-Y), default kemarin}';

    protected $description = 'Ambil kurs tengah USD & SAR (mirror BI via Bea Cukai, fallback Frankfurter) ke kurs_history';

    public function handle(KursService $kurs): int
    {
        $date = $this->option('date')
            ? Carbon::createFromFormat('d-m-Y', $this->option('date'))
            : now()->subDay();

        $data = $kurs->syncBi($date);

        if ($data === null) {
            $this->warn('kurs:fetch gagal dari semua sumber — nilai terakhir dipakai');

            return self::FAILURE;
        }

        $this->info('Kurs tersimpan ('.$data['source'].', '.$data['date'].')');

        return self::SUCCESS;
    }
}
