<?php

namespace App\Http\Controllers;

use App\Models\KursHistory;
use App\Services\KursService;
use Illuminate\Http\Request;

class KursController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->get('bulan', now()->format('Y-m'));

        $rows = KursHistory::where('tanggal', 'like', $month.'%')
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Ambil kurs terakhir per mata uang utk info cepat
        $latest = [];
        foreach (KursHistory::CURRENCIES as $cur) {
            $latest[$cur] = KursHistory::where('mata_uang', $cur)
                ->orderBy('tanggal', 'desc')
                ->orderBy('id', 'desc')
                ->first();
        }

        return view('kurs.index', [
            'title' => 'Kurs Mata Uang',
            'rows' => $rows,
            'month' => $month,
            'latest' => $latest,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'mata_uang' => ['required', 'in:'.implode(',', KursHistory::CURRENCIES)],
            'kurs' => 'required|numeric|min:0.0001',
            'keterangan' => 'nullable|string|max:255',
        ]);

        KursHistory::create([
            'tanggal' => $request->tanggal,
            'mata_uang' => $request->mata_uang,
            'sumber' => 'manual',
            'kurs' => $request->kurs,
            'keterangan' => $request->keterangan,
        ]);

        return redirect()->route('kurs.index', ['bulan' => substr($request->tanggal, 0, 7)])
            ->with('success', 'Kurs manual tersimpan.');
    }

    public function fetchBi(Request $request)
    {
        $svc = app(KursService::class);
        $data = $svc->syncBi();

        if ($data === null) {
            return redirect()->route('kurs.index')->with('error', 'Gagal mengambil kurs BI. Coba lagi nanti atau isi manual.');
        }

        return redirect()->route('kurs.index')
            ->with('success', 'Kurs BI tersimpan ('.$data['date'].', sumber '.$data['source'].').');
    }

    public function destroy(Request $request, $id)
    {
        KursHistory::findOrFail($id)->delete();

        return redirect()->route('kurs.index')->with('success', 'Data kurs dihapus.');
    }
}
