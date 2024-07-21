<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectSubasset extends Model
{use HasFactory;
    public $timestamps = false;
    protected $table = 'project_subasset';

    protected $fillable = [
        'id_asset',
        'id_category',
        'name',
        'detail',
        'time_estimate',
        'status',
        'log_user',
        'log_time',
    ];
}
