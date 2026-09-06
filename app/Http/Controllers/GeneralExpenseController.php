<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDecimal;
use App\Models\CostEntry;
use App\Models\CostType;
use App\Services\CashService;
use Illuminate\Http\Request;

class GeneralExpenseController extends Controller
{
    use HandlesDecimal;

    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $month = $request->get('month', now()->format('Y-m'));

        $query = CostEntry::with('costType')
            ->whereNull('id_project')
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->where('tanggal', 'like', $month.'%');

        $entries = $query->orderBy('tanggal', 'desc')->orderBy('id_cost', 'desc')->get();

        $cash = app(CashService::class);
        $position = $cash->positionCompany($companyId);
        $summary = $cash->summaryCompany($companyId);

        $costTypes = CostType::where('id_perusahaan', $companyId ?: null)
            ->orderBy('kategori')->orderBy('nama')->get();

        return view('general-expenses.index', [
            'title' => 'Pengeluaran Umum',
            'entries' => $entries,
            'month' => $month,
            'monthTotal' => $entries->sum('total'),
            'position' => $position,
            'summary' => $summary,
            'costTypes' => $costTypes,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $request->validate([
            'id_cost_type' => 'required|exists:cost_type,id_cost_type',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string|max:255',
            'total' => 'required|string',
            'catatan' => 'nullable|string',
            'file_bukti' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);

        $total = $this->normalizeMoney($request->total);
        if ($total <= 0) {
            return back()->withInput()->with('error', 'Nominal harus lebih dari 0.');
        }

        $data = [
            'id_perusahaan' => $companyId,
            'id_project' => null,
            'id_cost_type' => $request->id_cost_type,
            'tanggal' => $request->tanggal,
            'keterangan' => $request->keterangan,
            'qty' => 1,
            'unit' => null,
            'harga_satuan' => $total,
            'total' => $total,
            'catatan' => $request->catatan,
        ];

        if ($request->hasFile('file_bukti')) {
            $file = $request->file('file_bukti');
            $ext = $file->getClientOriginalExtension() ?: 'bin';
            $filename = 'general_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
            $file->storeAs('bukti/cost', $filename, 'public');
            $data['file_bukti'] = $filename;
        }

        CostEntry::create($data);

        return redirect()->route('general-expenses.index')
            ->with('success', 'Pengeluaran umum berhasil dicatat.');
    }

    public function destroy(Request $request, $id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $entry = CostEntry::where('id_cost', $id)
            ->whereNull('id_project')
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        $entry->delete();

        return back()->with('success', 'Pengeluaran umum dihapus.');
    }

    public function bukti(Request $request, $id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $entry = CostEntry::where('id_cost', $id)
            ->whereNull('id_project')
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        if (! $entry->file_bukti) {
            abort(404, 'Bukti tidak ditemukan');
        }

        $path = storage_path('app/public/bukti/cost/'.$entry->file_bukti);

        if (! file_exists($path)) {
            abort(404, 'Bukti tidak ditemukan');
        }

        return response()->file($path, [
            'Content-Type' => mime_content_type($path),
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
