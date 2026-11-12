<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileSkill extends Model
{
    protected $fillable = ['name'];

    public function profile()
    {
        return $this->belongsTo(StudentAlumniProfile::class, 'profile_id');
    }
}