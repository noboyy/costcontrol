<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KursHistory extends Model
{
    use HasFactory;

    protected $table = 'kurs_history';

    protected $fillable = [
        'tanggal',
        'mata_uang',
        'sumber',
        'kurs',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'kurs' => 'decimal:2',
    ];

    public const CURRENCIES = ['USD', 'SAR'];
}
