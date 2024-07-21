<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeIndustry extends Model
{
    protected $fillable = [
        'name',
        'created_by',
        'modified_by',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }
}
