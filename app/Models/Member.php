<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    // Allow mass assignment
    protected $fillable = [
        'title',
        'first_name',
        'last_name',
        'branch',
        'member_type',
        'email',
        'bio',
    ];
}
