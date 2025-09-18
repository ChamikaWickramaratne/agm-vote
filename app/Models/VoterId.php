<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoterId extends Model
{
    protected $fillable = [
        'voting_session_id','member_id','voter_code_hash',
        'issued_by','issued_at','used','used_at','conference_id',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'used'      => 'boolean',
        'used_at'   => 'datetime',
    ];

    public function session()  { return $this->belongsTo(\App\Models\VotingSession::class,'voting_session_id'); }
    public function member()   { return $this->belongsTo(\App\Models\Member::class); }
    public function issuer()   { return $this->belongsTo(\App\Models\User::class,'issued_by'); }

    public function conference()
    {
        return $this->belongsTo(\App\Models\Conference::class);
    }
}
