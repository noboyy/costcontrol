<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $this->migrateFor('cost_entry', 'id_cost', 'bukti/cost');
        $this->migrateFor('income_entry', 'id_income', 'bukti/income');
    }

    public function down(): void
    {
        // Data migration one-way; tidak perlu rollback.
    }

    private function migrateFor(string $table, string $entryKey, string $srcDir): void
    {
        $rows = DB::table($table)
            ->whereNotNull('file_bukti')
            ->where('file_bukti', '!=', '')
            ->get();

        $imageTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $videoTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska'];

        foreach ($rows as $row) {
            $srcFile = $row->file_bukti;

            if (DB::table('project_gallery')->where($entryKey, $row->id)->exists()) {
                continue;
            }

            $src = storage_path('app/public/'.$srcDir.'/'.$srcFile);

            if (! is_file($src)) {
                continue;
            }

            $mime = mime_content_type($src);
            if (in_array($mime, $imageTypes)) {
                $fileType = 'image';
            } elseif (in_array($mime, $videoTypes)) {
                $fileType = 'video';
            } else {
                $fileType = 'document';
            }

            $ext = pathinfo($srcFile, PATHINFO_EXTENSION) ?: 'bin';
            $newName = 'gallery_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
            $destDir = 'gallery/'.$row->id_project;

            Storage::disk('public')->makeDirectory($destDir);
            Storage::disk('public')->copy($srcDir.'/'.$srcFile, $destDir.'/'.$newName);

            DB::table('project_gallery')->insert([
                'id_perusahaan' => $row->id_perusahaan,
                'id_project'    => $row->id_project,
                $entryKey       => $row->id,
                'label'         => 'Bukti',
                'file_name'     => $newName,
                'original_name' => $srcFile,
                'file_type'     => $fileType,
                'mime_type'     => $mime,
                'file_size'     => filesize($src),
                'caption'       => null,
                'uploaded_by'   => null,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }
};
