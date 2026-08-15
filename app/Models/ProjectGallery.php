<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProjectGallery extends Model
{
    protected $table = 'project_gallery';

    protected $primaryKey = 'id_gallery';

    protected $fillable = [
        'id_perusahaan',
        'id_project',
        'id_cost',
        'id_income',
        'label',
        'file_name',
        'original_name',
        'file_type',
        'mime_type',
        'file_size',
        'caption',
        'uploaded_by',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $model) {
            if ($model->file_name) {
                Storage::disk('public')->delete('gallery/'.$model->id_project.'/'.$model->file_name);
            }
        });
    }

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'id_project', 'id_project');
    }

    public function cost()
    {
        return $this->belongsTo(CostEntry::class, 'id_cost', 'id_cost');
    }

    public function income()
    {
        return $this->belongsTo(IncomeEntry::class, 'id_income', 'id_income');
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
