<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = ['name']; // add other fields you have in your positions table

    public function votingSessions()
    {
        return $this->hasMany(VotingSession::class);
    }
}
