<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetMaintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        if ($request->isMethod('post')) {
            return $this->store($request);
        }

        $assets = Asset::with(['maintenanceRecords'])
            ->where('deleted_at', null)
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            })
            ->orderByRaw("CASE WHEN status = 'Dijual' THEN 1 ELSE 0 END")
            ->orderBy('nama_asset')
            ->get();

        return view('assets.index', [
            'title' => 'Asset',
            'assets' => $assets,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $request->validate([
            'nama_asset' => 'required|string|max:255',
            'nilai_asset' => 'nullable|numeric|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $nilaiAsset = $request->nilai_asset ? $this->parseIndoMoneyToDecimal($request->nilai_asset) : null;

        $data = [
            'id_perusahaan' => $companyId,
            'nama_asset' => $request->nama_asset,
            'nilai_asset' => $nilaiAsset,
            'keterangan' => $request->keterangan,
        ];

        // Handle image upload
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            if ($file->isValid() && $file->getSize() <= 3 * 1024 * 1024) {
                $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
                if (in_array($file->getMimeType(), $allowedMime)) {
                    $filename = 'asset_' . time() . '_' . bin2hex(random_bytes(4)) . '.webp';
                    $path = $file->storeAs('asset', $filename, 'public');
                    $data['gambar'] = $filename;
                }
            }
        }

        Asset::create($data);

        return redirect()->route('asset.index')->with('success', 'Asset berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $asset = Asset::where('id_asset', $id)
            ->where('deleted_at', null)
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            })
            ->firstOrFail();

        $request->validate([
            'nama_asset' => 'required|string|max:255',
            'nilai_asset' => 'nullable|numeric|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $nilaiAsset = $request->nilai_asset ? $this->parseIndoMoneyToDecimal($request->nilai_asset) : null;

        $data = [
            'nama_asset' => $request->nama_asset,
            'nilai_asset' => $nilaiAsset,
            'keterangan' => $request->keterangan,
        ];

        // Handle image upload
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            if ($file->isValid() && $file->getSize() <= 3 * 1024 * 1024) {
                $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
                if (in_array($file->getMimeType(), $allowedMime)) {
                    // Delete old image
                    if ($asset->gambar) {
                        Storage::disk('public')->delete('asset/' . $asset->gambar);
                    }
                    
                    $filename = 'asset_' . time() . '_' . bin2hex(random_bytes(4)) . '.webp';
                    $path = $file->storeAs('asset', $filename, 'public');
                    $data['gambar'] = $filename;
                }
            }
        }

        $asset->update($data);

        return redirect()->route('asset.index')->with('success', 'Asset berhasil diperbarui.');
    }

    public function delete($id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $asset = Asset::where('id_asset', $id)
            ->where('deleted_at', null)
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            })
            ->firstOrFail();

        // Delete image
        if ($asset->gambar) {
            Storage::disk('public')->delete('asset/' . $asset->gambar);
        }

        $asset->update(['gambar' => null]);
        $asset->delete();

        return redirect()->route('asset.index')->with('success', 'Asset berhasil dihapus.');
    }

    public function sell(Request $request, $id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $asset = Asset::where('id_asset', $id)
            ->where('deleted_at', null)
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            })
            ->firstOrFail();

        if ($asset->isSold()) {
            return back()->with('error', 'Asset sudah dijual.');
        }

        $request->validate([
            'nilai_jual' => 'required|numeric|min:0',
            'alasan_jual' => 'nullable|string',
            'tanggal_jual' => 'nullable|date',
        ]);

        $nilaiJual = $this->parseIndoMoneyToDecimal($request->nilai_jual);

        $asset->update([
            'status' => 'Dijual',
            'nilai_jual' => $nilaiJual,
            'alasan_jual' => $request->alasan_jual,
            'tanggal_jual' => $request->tanggal_jual ?: now(),
        ]);

        return redirect()->route('asset.index')->with('success', 'Asset berhasil dijual.');
    }

    public function addMaintenance(Request $request, $id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $asset = Asset::where('id_asset', $id)
            ->where('deleted_at', null)
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            })
            ->firstOrFail();

        if ($asset->isSold()) {
            return back()->with('error', 'Asset sudah dijual. Tidak bisa menambah maintenance.');
        }

        $request->validate([
            'tanggal' => 'nullable|date',
            'keterangan' => 'nullable|string',
            'biaya' => 'required|numeric|min:0',
        ]);

        $biaya = $this->parseIndoMoneyToDecimal($request->biaya);

        AssetMaintenance::create([
            'id_perusahaan' => $companyId,
            'id_asset' => $id,
            'tanggal' => $request->tanggal ?: now(),
            'keterangan' => $request->keterangan,
            'biaya' => $biaya,
        ]);

        return redirect()->route('asset.index')->with('success', 'Maintenance asset berhasil ditambahkan.');
    }

    public function deleteMaintenance($id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $maintenance = AssetMaintenance::where('id_maintenance', $id)
            ->where('deleted_at', null)
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            })
            ->firstOrFail();

        $asset = Asset::where('id_asset', $maintenance->id_asset)
            ->where('deleted_at', null)
            ->first();

        if ($asset && $asset->isSold()) {
            return back()->with('error', 'Maintenance tidak bisa dihapus karena asset sudah dijual.');
        }

        $maintenance->delete();

        return redirect()->route('asset.index')->with('success', 'Maintenance berhasil dihapus.');
    }

    public function image($id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $asset = Asset::where('id_asset', $id)
            ->where('deleted_at', null)
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            })
            ->firstOrFail();

        if (!$asset->gambar) {
            abort(404, 'Gambar tidak ditemukan');
        }

        $path = storage_path('app/public/asset/' . $asset->gambar);
        
        if (!file_exists($path)) {
            abort(404, 'Gambar tidak ditemukan');
        }

        return response()->file($path, [
            'Content-Type' => mime_content_type($path),
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    private function parseIndoMoneyToDecimal(?string $raw): ?string
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
            $raw = array_shift($parts) . '.' . implode('', $parts);
        }

        if (!preg_match('/^-?\\d+(?:\\.\\d{0,2})?$/', $raw)) {
            $raw = preg_replace('/(\\..{2}).*$/', '$1', $raw) ?? $raw;
        }

        if ($raw === '' || $raw === '-') {
            return null;
        }

        return $raw;
    }
}
