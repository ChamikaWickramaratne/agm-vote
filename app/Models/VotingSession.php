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
}
