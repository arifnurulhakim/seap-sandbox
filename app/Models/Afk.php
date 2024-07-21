<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Afk extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_afk',
        'end_afk',
        'absensi_id',
        'total_afk'
    ];

    protected $casts = [
        'start_afk' => 'datetime',
        'end_afk' => 'datetime',
        'total_afk' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // public function calculateTotalAfk()
    // {
    //     if ($this->start_afk && $this->end_afk) {
    //         $this->total_afk = $this->start_afk->diffInSeconds($this->end_afk);
    //     }
    // }

    // public function save(array $options = [])
    // {
    //     $this->calculateTotalAfk();
    //     parent::save($options);
    // }
}
