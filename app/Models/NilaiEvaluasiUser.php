<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class NilaiEvaluasiUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reviewer_id',
        'evaluasi_id',
        'nilai',
        'periode',
        'label'
    ];

    protected $casts = [
        'user_id',
        'reviewer_id',
        'evaluasi_id',
        'nilai',
        'periode',
    ];

    public function users()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class);
    }

    public function evaluasi()
    {
        return $this->belongsTo(Evaluasi::class);
    }
}
