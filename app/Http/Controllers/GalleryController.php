<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\CostEntry;
use App\Models\IncomeEntry;
use App\Models\Project;
use App\Models\ProjectGallery;
use App\Models\ProjectInvestor;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    private function routeName(): string
    {
        return request()->segment(1) === 'projects' ? 'projects' : 'cost-centers';
    }

    private function resolveProject(int $id): Project
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $project = Project::when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->where('id_project', $id)
            ->firstOrFail();

        return $project;
    }

    private function checkAccess(Project $project): void
    {
        $user = auth()->user();
        $akun = Akun::find($user->id_akun ?? $user->id);

        if ($akun && in_array($akun->role, ['ADMIN', 'SUPER ADMIN'])) {
            return;
        }

        $investor = ProjectInvestor::where('id_project', $project->id_project)
            ->where('id_akun', $user->id_akun ?? $user->id)
            ->first();

        if (! $investor) {
            abort(403);
        }
    }

    private function checkAdmin(): void
    {
        $user = auth()->user();
        $akun = Akun::find($user->id_akun ?? $user->id);

        if (! $akun || ! in_array($akun->role, ['ADMIN', 'SUPER ADMIN'])) {
            abort(403);
        }
    }

    public function index(Request $request, int $id)
    {
        $project = $this->resolveProject($id);
        $this->checkAccess($project);

        $labelFilter = $request->get('label');

        $items = ProjectGallery::where('id_project', $project->id_project)
            ->when($labelFilter, fn ($q) => $q->where('label', $labelFilter))
            ->orderBy('created_at', 'desc')
            ->get();

        $labels = ProjectGallery::where('id_project', $project->id_project)
            ->distinct()
            ->orderBy('label')
            ->pluck('label');

        return view('projects.gallery', [
            'project'     => $project,
            'items'       => $items,
            'labels'      => $labels,
            'labelFilter' => $labelFilter,
            'prefix'      => request()->segment(1),
        ]);
    }

    public function store(Request $request, int $id)
    {
        $project = $this->resolveProject($id);
        $this->checkAdmin();

        $user = auth()->user();

        $request->validate([
            'file'    => 'required|file|max:102400',
            'label'   => 'required|string|max:100',
            'caption' => 'nullable|string|max:500',
        ]);

        $file = $request->file('file');

        if (! $file->isValid()) {
            return back()->withErrors(['file' => 'File tidak valid.']);
        }

        $stored = $this->persistFile($file, $project, [
            'label'       => trim($request->label),
            'caption'     => $request->caption ? trim($request->caption) : null,
            'uploaded_by' => $user->id_akun ?? $user->id,
        ]);

        if ($stored instanceof \Illuminate\Validation\ValidationException) {
            return back()->withErrors(['file' => $stored->getMessage()]);
        }

        if ($stored === false) {
            return back()->withErrors(['file' => 'Tipe file tidak didukung. Gunakan jpg/png/webp, mp4/mov, atau pdf.']);
        }

        $routeName = $this->routeName();

        return redirect()->route("{$routeName}.gallery", $project->id_project)
            ->with('success', 'File berhasil diunggah ke galeri.');
    }

    public function storeCostGallery(Request $request, int $id, int $costId)
    {
        return $this->storeEntryGallery($request, $id, 'cost', $costId);
    }

    public function storeIncomeGallery(Request $request, int $id, int $incomeId)
    {
        return $this->storeEntryGallery($request, $id, 'income', $incomeId);
    }

    private function storeEntryGallery(Request $request, int $id, string $type, int $entryId)
    {
        $project = $this->resolveProject($id);
        $this->checkAdmin();

        if ($type === 'cost') {
            $entry = CostEntry::where('id_project', $project->id_project)->findOrFail($entryId);
            $entryKey = 'id_cost';
        } else {
            $entry = IncomeEntry::where('id_project', $project->id_project)->findOrFail($entryId);
            $entryKey = 'id_income';
        }

        $request->validate([
            'files'   => 'required|array|min:1',
            'files.*' => 'required|file|max:102400',
            'label'   => 'nullable|string|max:100',
            'caption' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $label = trim($request->label ?: 'Bukti '.($type === 'cost' ? 'Biaya' : 'Pendapatan'));
        $saved = 0;

        foreach ($request->file('files', []) as $file) {
            if (! $file->isValid()) {
                continue;
            }

            $stored = $this->persistFile($file, $project, [
                'label'       => $label,
                'caption'     => $request->caption ? trim($request->caption) : null,
                'uploaded_by' => $user->id_akun ?? $user->id,
            ]);

            if ($stored === false) {
                continue;
            }

            if ($stored instanceof \Illuminate\Validation\ValidationException) {
                continue;
            }

            $stored->update([$entryKey => $entry->{$entryKey}]);
            $saved++;
        }

        if ($saved === 0) {
            return response()->json(['message' => 'Tidak ada file yang berhasil disimpan.'], 422);
        }

        return response()->json(['message' => "{$saved} file berhasil diunggah."]);
    }

    public function costGallery(int $id, int $costId)
    {
        $project = $this->resolveProject($id);
        $this->checkAccess($project);

        $items = ProjectGallery::where('id_project', $project->id_project)
            ->where('id_cost', $costId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($this->serializeItems($project, $items));
    }

    public function incomeGallery(int $id, int $incomeId)
    {
        $project = $this->resolveProject($id);
        $this->checkAccess($project);

        $items = ProjectGallery::where('id_project', $project->id_project)
            ->where('id_income', $incomeId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($this->serializeItems($project, $items));
    }

    private function serializeItems(Project $project, $items)
    {
        $routeName = $this->routeName();

        return $items->map(fn ($item) => [
            'id'             => $item->id_gallery,
            'file_type'      => $item->file_type,
            'mime_type'      => $item->mime_type,
            'label'          => $item->label,
            'caption'        => $item->caption,
            'original_name'  => $item->original_name,
            'file_size'      => $item->file_size,
            'file_size_human'=> $item->fileSizeHuman(),
            'created_at'     => $item->created_at->format('Y-m-d H:i:s'),
            'serve_url'      => route("{$routeName}.gallery.serve", [$project->id_project, $item->id_gallery]),
        ])->values();
    }

    private function persistFile($file, Project $project, array $data): ProjectGallery|false|\Illuminate\Validation\ValidationException
    {
        $mime = $file->getMimeType();
        $size = $file->getSize();

        $imageTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $videoTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska'];
        $docTypes   = ['application/pdf'];

        if (in_array($mime, $imageTypes)) {
            $fileType = 'image';
            $maxBytes = 5 * 1024 * 1024;
        } elseif (in_array($mime, $videoTypes)) {
            $fileType = 'video';
            $maxBytes = 50 * 1024 * 1024;
        } elseif (in_array($mime, $docTypes)) {
            $fileType = 'document';
            $maxBytes = 10 * 1024 * 1024;
        } else {
            return false;
        }

        if ($size > $maxBytes) {
            $limitMb = $maxBytes / (1024 * 1024);

            return \Illuminate\Validation\ValidationException::withMessages([
                'file' => "Ukuran file melebihi batas {$limitMb}MB untuk tipe ini.",
            ]);
        }

        $ext = $file->getClientOriginalExtension();
        $filename = 'gallery_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;

        $dir = 'gallery/'.$project->id_project;
        $file->storeAs($dir, $filename, 'public');

        return ProjectGallery::create([
            'id_perusahaan' => $project->id_perusahaan,
            'id_project'    => $project->id_project,
            'label'         => $data['label'],
            'file_name'     => $filename,
            'original_name' => $file->getClientOriginalName(),
            'file_type'     => $fileType,
            'mime_type'     => $mime,
            'file_size'     => $size,
            'caption'       => $data['caption'],
            'uploaded_by'   => $data['uploaded_by'],
        ]);
    }

    public function destroy(int $id, int $galleryId)
    {
        $project = $this->resolveProject($id);
        $this->checkAdmin();

        $item = ProjectGallery::where('id_project', $project->id_project)
            ->where('id_gallery', $galleryId)
            ->firstOrFail();

        $item->delete();

        $routeName = $this->routeName();

        return redirect()->route("{$routeName}.gallery", $project->id_project)
            ->with('success', 'File berhasil dihapus dari galeri.');
    }

    public function serve(int $id, int $galleryId)
    {
        $project = $this->resolveProject($id);
        $this->checkAccess($project);

        $item = ProjectGallery::where('id_project', $project->id_project)
            ->where('id_gallery', $galleryId)
            ->firstOrFail();

        $path = $item->storagePath();

        if (! file_exists($path)) {
            abort(404, 'File tidak ditemukan');
        }

        return response()->file($path, [
            'Content-Type'  => $item->mime_type,
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    public function labels(int $id)
    {
        $project = $this->resolveProject($id);
        $this->checkAccess($project);

        $labels = ProjectGallery::where('id_project', $project->id_project)
            ->distinct()
            ->orderBy('label')
            ->pluck('label');

        return response()->json($labels);
    }
}
