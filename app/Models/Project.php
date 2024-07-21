<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'client',
        'contact',
        'email',
        'country',
        'description',
        'requirement',
        'ae_id',
        'timeline_link',
        'trello_link',
        'status',
    ];
}
