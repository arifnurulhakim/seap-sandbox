<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectIp extends Model
{
    protected $fillable = [
        'project_id',
        'is_ip',
    ];

    // Relasi dengan tabel projects
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
