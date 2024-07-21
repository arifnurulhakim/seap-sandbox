<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectCategory extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'project_category';
    protected $fillable = [
        'id_project',
        'name',
    ];
    public function project()
    {
        return $this->belongsTo(Project::class)->without('projectCategory');
    }
}
