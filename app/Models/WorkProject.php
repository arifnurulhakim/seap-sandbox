<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkProject extends Model
{
    use HasFactory;
    protected $table = 'work_project';
    protected $fillable = [
        'id_lead',
        'id_country',
        'name',
        'client',
        'job_desc',
        'contact_name',
        'contact_email',
        'description',
        'requirement',
        'status',
        'log_user',
    ];

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class, 'id_project');
    }
}
