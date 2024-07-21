<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'name_prospect',
        'channel',
        'contact',
        'pic',
        'type_project_id',
        'type_prospect_id',
        'type_industry_id',
        'last_contact',
        'details',
        'status',
        'decline_reason',
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

    public function typeProject()
    {
        return $this->belongsTo(TypeProject::class);
    }

    public function typeProspect()
    {
        return $this->belongsTo(TypeProspect::class);
    }

    public function typeIndustry()
    {
        return $this->belongsTo(TypeIndustry::class);
    }
}
