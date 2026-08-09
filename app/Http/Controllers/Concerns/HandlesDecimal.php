<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Project;
use App\Services\DailyControlService;
use Carbon\Carbon;

trait HandlesDecimal
{
    protected function normalizeDecimal($value, $default = null)
    {
        if ($value === null || $value === '') {
            return $default;
        }
        $clean = str_replace(['.', ' '], ['', ''], (string) $value);
        $clean = str_replace(',', '.', $clean);

        return (float) $clean;
    }

    protected function guardClosedDay(Project $project, $date): ?string
    {
        if (! $project->isUmkm()) {
            return null;
        }
        if ($project->lock_closed_days === false) {
            return null;
        }
        if (app(DailyControlService::class)->isDayClosed($project, $date)) {
            $label = Carbon::parse($date)->format('d M Y');

            return "Tanggal {$label} sudah ditutup. Buka ulang tutup kas dulu untuk mengubah entri.";
        }

        return null;
    }

    protected function parseIndoMoney(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        $raw = preg_replace('/[^0-9\\.,-]/', '', $raw) ?? '';
        $raw = trim($raw);
        if ($raw === '' || $raw === '-' || $raw === ',' || $raw === '.') {
            return null;
        }

        $raw = str_replace('.', '', $raw);
        $raw = str_replace(',', '.', $raw);

        if (substr_count($raw, '.') > 1) {
            $parts = explode('.', $raw);
            $raw = array_shift($parts).'.'.implode('', $parts);
        }

        if (! preg_match('/^-?\\d+(?:\\.\\d{0,2})?$/', $raw)) {
            $raw = preg_replace('/(\\..{2}).*$/', '$1', $raw) ?? $raw;
        }

        if ($raw === '' || $raw === '-') {
            return null;
        }

        return $raw;
    }
}
