<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Project;
use App\Models\ProjectGallery;
use App\Models\ProjectInvestor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            return back()->withErrors(['file' => 'Tipe file tidak didukung. Gunakan jpg/png/webp, mp4/mov, atau pdf.']);
        }

        if ($size > $maxBytes) {
            $limitMb = $maxBytes / (1024 * 1024);
            return back()->withErrors(['file' => "Ukuran file melebihi batas {$limitMb}MB untuk tipe ini."]);
        }

        $ext = $file->getClientOriginalExtension();
        $filename = 'gallery_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;

        $dir = 'gallery/'.$project->id_project;
        $file->storeAs($dir, $filename, 'public');

        ProjectGallery::create([
            'id_perusahaan' => $project->id_perusahaan,
            'id_project'    => $project->id_project,
            'label'         => trim($request->label),
            'file_name'     => $filename,
            'original_name' => $file->getClientOriginalName(),
            'file_type'     => $fileType,
            'mime_type'     => $mime,
            'file_size'     => $size,
            'caption'       => $request->caption ? trim($request->caption) : null,
            'uploaded_by'   => $user->id_akun ?? $user->id,
        ]);

        $routeName = $this->routeName();

        return redirect()->route("{$routeName}.gallery", $project->id_project)
            ->with('success', 'File berhasil diunggah ke galeri.');
    }

    public function destroy(int $id, int $galleryId)
    {
        $project = $this->resolveProject($id);
        $this->checkAdmin();

        $item = ProjectGallery::where('id_project', $project->id_project)
            ->where('id_gallery', $galleryId)
            ->firstOrFail();

        $storagePath = 'gallery/'.$project->id_project.'/'.$item->file_name;
        Storage::disk('public')->delete($storagePath);

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
