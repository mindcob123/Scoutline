<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    // The table associated with the model (Laravel detects this automatically, but explicitly defining it is safe)
    protected $table = 'businesses';

    // The attributes that are mass assignable
    protected $fillable = [
        'member_id',
        'name',
        'address',
        'category',
        'phone',
        'website',
        'fetched_at', // <-- Allows Laravel to save your FastAPI timestamp!
    ];
}