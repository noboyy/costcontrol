<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectGallery extends Model
{
    protected $table = 'project_gallery';

    protected $primaryKey = 'id_gallery';

    protected $fillable = [
        'id_perusahaan',
        'id_project',
        'label',
        'file_name',
        'original_name',
        'file_type',
        'mime_type',
        'file_size',
        'caption',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'id_project', 'id_project');
    }

    public function uploader()
    {
        return $this->belongsTo(Akun::class, 'uploaded_by', 'id_akun');
    }

    public function storagePath(): string
    {
        return storage_path('app/public/gallery/'.$this->id_project.'/'.$this->file_name);
    }

    public function fileSizeHuman(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }
        return $bytes.' B';
    }
}
