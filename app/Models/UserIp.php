<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserIp extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
    ];

    // Relasi dengan tabel projects
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // Relasi dengan tabel users
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
