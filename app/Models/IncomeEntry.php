<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncomeEntry extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'income_entry';

    protected $primaryKey = 'id_income';

    protected $fillable = [
        'id_perusahaan',
        'id_project',
        'id_income_type',
        'tanggal',
        'keterangan',
        'qty',
        'unit',
        'harga_satuan',
        'total',
        'catatan',
        'file_bukti',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'qty' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if ($model->qty !== null && $model->harga_satuan !== null && $model->isDirty(['qty', 'harga_satuan'])) {
                $model->total = (float) $model->qty * (float) $model->harga_satuan;
            }
        });
    }

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'id_perusahaan', 'id_perusahaan');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'id_project', 'id_project');
    }

    public function incomeType()
    {
        return $this->belongsTo(IncomeType::class, 'id_income_type', 'id_income_type');
    }

    public function gallery()
    {
        return $this->hasMany(ProjectGallery::class, 'id_income', 'id_income');
    }
}
