<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAttendanceSession extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'user_attendance_session';
    protected $fillable = [
        'id_user',
        'time_start',
        'time_end',
        'is_end',
        'is_overtime',
        'corraction',

    ];

}
