<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class PeriodeEvaluasi extends Model
{
    use HasFactory;
    protected $table = 'periode_evaluasis';
    protected $fillable = ['periode', 'label', 'user_id', 'isLock'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
