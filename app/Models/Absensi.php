<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class Absensi extends Model
{

    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_day',
        'total_afk',
        'end_day',
        'total_jam_kerja',
        'status',
        'keterangan'
    ];

    protected $casts = [
        'start_day' => 'datetime',
        'total_afk' => 'datetime',
        'end_day' => 'datetime',
        'total_jam_kerja' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
