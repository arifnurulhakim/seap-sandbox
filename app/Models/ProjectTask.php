<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectTask extends Model
{

    use HasFactory;
    public $timestamps = false;
    protected $table = 'project_task';
    protected $fillable = [
        'id_asset',
        'id_subasset',
        'id_artist',
        'name',
        'description',
        'time_start',
        'time_end',
        'time_estimate',
        'date_task',
        'status',
        'log_user',
        'log_time',
    ];

    public function sessions()
    {
        return $this->hasMany(ProjectTaskSession::class, 'id_task');
    }
}
