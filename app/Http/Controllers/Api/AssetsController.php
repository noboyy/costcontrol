<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\HandlesDecimal;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetsController extends Controller
{
    use HandlesDecimal;

    public function index(Request $request)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $assets = Asset::with(['maintenanceRecords'])
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->orderByRaw("CASE WHEN status = 'Dijual' THEN 1 ELSE 0 END")
            ->orderBy('nama_asset')
            ->get();

        return response()->json([
            'assets' => $assets->map(fn ($a) => [
                'id_asset' => $a->id_asset,
                'nama_asset' => $a->nama_asset,
                'nilai_asset' => $a->nilai_asset !== null ? (float) $a->nilai_asset : null,
                'keterangan' => $a->keterangan,
                'gambar' => $a->gambar,
                'status' => $a->status,
                'nilai_jual' => $a->nilai_jual !== null ? (float) $a->nilai_jual : null,
                'alasan_jual' => $a->alasan_jual,
                'tanggal_jual' => $a->tanggal_jual?->format('Y-m-d'),
                'is_sold' => $a->isSold(),
                'maintenance_total' => (float) $a->maintenanceRecords->sum('biaya'),
                'maintenance_count' => $a->maintenanceRecords->count(),
                'maintenance' => $a->maintenanceRecords->map(fn ($m) => [
                    'id_maintenance' => $m->id_maintenance,
                    'tanggal' => $m->tanggal?->format('Y-m-d'),
                    'keterangan' => $m->keterangan,
                    'biaya' => (float) $m->biaya,
                ]),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $request->validate([
            'nama_asset' => 'required|string|max:255',
            'nilai_asset' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ]);

        $data = [
            'id_perusahaan' => $companyId,
            'nama_asset' => $request->nama_asset,
            'nilai_asset' => $request->nilai_asset ? $this->parseIndoMoney($request->nilai_asset) : null,
            'keterangan' => $request->keterangan,
        ];

        if ($request->hasFile('gambar')) {
            $data['gambar'] = $this->storeImage($request->file('gambar'));
        }

        $asset = Asset::create($data);

        return response()->json(['message' => 'Asset berhasil ditambahkan.', 'id_asset' => $asset->id_asset], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $asset = $this->findAsset($id, $companyId);

        $request->validate([
            'nama_asset' => 'required|string|max:255',
            'nilai_asset' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ]);

        $data = [
            'nama_asset' => $request->nama_asset,
            'nilai_asset' => $request->nilai_asset ? $this->parseIndoMoney($request->nilai_asset) : null,
            'keterangan' => $request->keterangan,
        ];

        if ($request->hasFile('gambar')) {
            if ($asset->gambar) {
                Storage::disk('public')->delete('asset/'.$asset->gambar);
            }
            $data['gambar'] = $this->storeImage($request->file('gambar'));
        }

        $asset->update($data);

        return response()->json(['message' => 'Asset berhasil diperbarui.']);
    }

    public function delete(Request $request, $id)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $asset = $this->findAsset($id, $companyId);

        if ($asset->gambar) {
            Storage::disk('public')->delete('asset/'.$asset->gambar);
        }
        $asset->update(['gambar' => null]);
        $asset->delete();

        return response()->json(['message' => 'Asset berhasil dihapus.']);
    }

    public function sell(Request $request, $id)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $asset = $this->findAsset($id, $companyId);

        if ($asset->isSold()) {
            return response()->json(['message' => 'Asset sudah dijual.'], 422);
        }

        $request->validate([
            'nilai_jual' => 'required|string',
            'alasan_jual' => 'nullable|string',
            'tanggal_jual' => 'nullable|date',
        ]);

        $asset->update([
            'status' => 'Dijual',
            'nilai_jual' => $this->parseIndoMoney($request->nilai_jual),
            'alasan_jual' => $request->alasan_jual,
            'tanggal_jual' => $request->tanggal_jual ?: now(),
        ]);

        return response()->json(['message' => 'Asset berhasil dijual.']);
    }

    public function addMaintenance(Request $request, $id)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $asset = $this->findAsset($id, $companyId);

        if ($asset->isSold()) {
            return response()->json(['message' => 'Asset sudah dijual. Tidak bisa menambah maintenance.'], 422);
        }

        $request->validate([
            'tanggal' => 'nullable|date',
            'keterangan' => 'nullable|string',
            'biaya' => 'required|string',
        ]);

        AssetMaintenance::create([
            'id_perusahaan' => $companyId,
            'id_asset' => $id,
            'tanggal' => $request->tanggal ?: now(),
            'keterangan' => $request->keterangan,
            'biaya' => $this->parseIndoMoney($request->biaya),
        ]);

        return response()->json(['message' => 'Maintenance asset berhasil ditambahkan.'], 201);
    }

    public function deleteMaintenance(Request $request, $id, $maintenanceId)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $maintenance = AssetMaintenance::where('id_maintenance', $maintenanceId)
            ->where('deleted_at', null)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        $asset = Asset::where('id_asset', $maintenance->id_asset)->first();
        if ($asset && $asset->isSold()) {
            return response()->json(['message' => 'Maintenance tidak bisa dihapus karena asset sudah dijual.'], 422);
        }

        $maintenance->delete();

        return response()->json(['message' => 'Maintenance berhasil dihapus.']);
    }

    public function image(Request $request, $id)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $asset = $this->findAsset($id, $companyId);

        if (! $asset->gambar) {
            abort(404);
        }
        $path = storage_path('app/public/asset/'.$asset->gambar);
        if (! file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => mime_content_type($path),
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    private function findAsset($id, ?int $companyId): Asset
    {
        return Asset::where('id_asset', $id)
            ->where('deleted_at', null)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();
    }

    private function storeImage($file): ?string
    {
        if (! $file || ! $file->isValid() || $file->getSize() > 3 * 1024 * 1024) {
            return null;
        }
        $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
        if (! in_array($file->getMimeType(), $allowedMime)) {
            return null;
        }
        $filename = 'asset_'.time().'_'.bin2hex(random_bytes(4)).'.webp';

        return $file->storeAs('asset', $filename, 'public');
    }
}
