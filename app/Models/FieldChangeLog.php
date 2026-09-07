<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldChangeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'changed_by',
        'field_name',
        'old_value',
        'new_value',
        'reason',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}