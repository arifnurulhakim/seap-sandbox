<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{

    use HasFactory;
    
    protected $fillable = [

        'user_id',
        'reviewer_id',
        'evaluasi_id',
        'feedback',
        'periode',
        'label'
    ];
    
    protected $casts = [

        'user_id',
        'reviewer_id',
        'evaluasi_id',
        'feedback',
        'periode',
        'label'
    ];

    public function reviewer()
    {
        return $this->belongsTo(User::class);
    }

    public function periode()
    {
        return $this->belongsTo(NilaiEvaluasiUser::class);
    }

}
