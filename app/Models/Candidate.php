<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    protected $fillable = ['position_id','member_id','name','bio','photo_url'];

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function ballots()
    {
        return $this->hasMany(Ballot::class);
    }

    public function sessions()
    {
        return $this->belongsToMany(\App\Models\VotingSession::class, 'session_candidates')
            ->withTimestamps();
    }
}
