<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = ['name'];

    public function votingSessions()
    {
        return $this->hasMany(VotingSession::class);
    }
}
