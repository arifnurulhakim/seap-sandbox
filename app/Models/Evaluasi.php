<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluasi extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id',
        'divisi_id',
        'kategori',
        'detail',
        'keterangan',
    ];

    // Relasi ke model Role
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Relasi ke model Divisi
    public function divisi()
    {
        return $this->belongsTo(Divisi::class);
    }
}
