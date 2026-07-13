<?php

namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Member extends Authenticatable
{
    protected $table='members';
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at'
    ];
}