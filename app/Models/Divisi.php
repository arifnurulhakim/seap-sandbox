<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class Divisi extends Model
{
    use HasFactory;
    public function users()
    {
        return $this->hasMany(User::class);
    }
    public static function getDivisiName($divisi_id)
    {
        $divisi = Divisi::find($divisi_id);
        return $divisi ? $divisi->name : null;
    }
}
