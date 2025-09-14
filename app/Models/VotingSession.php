<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VotingSession extends Model
{
    protected $fillable = [
        'conference_id',
        'position_id',
        'voting_rules',
        'status',
        'start_time',
        'end_time',
        'close_condition',
        'close_after_minutes',
        'majority_percent',
    ];

    protected $casts = [
        'voting_rules' => 'array',
        'start_time'   => 'datetime',
        'end_time'     => 'datetime',
    ];

    public function conference()
    {
        return $this->belongsTo(Conference::class);
    }

    public function position()
    {
        return $this->belongsTo(\App\Models\Position::class);
    }

    public function voterIds() { return $this->hasMany(\App\Models\VoterId::class, 'voting_session_id'); }

    public function candidates()
    {
        return $this->hasMany(Candidate::class, 'position_id', 'position_id');
    }


}
