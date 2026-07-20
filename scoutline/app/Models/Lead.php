<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $table = 'leads';

    protected $fillable = [
        'member_id',
        'business_id',
        'domain',
        'leads',
    ];

    // Automatically converts the JSON column to/from a PHP array
    protected $casts = [
        'leads' => 'array',
    ];
}