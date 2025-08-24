<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conference extends Model {
    protected $fillable = ['start_date','end_date'];
    protected $casts = ['start_date'=>'datetime','end_date'=>'datetime'];
    public function sessions(){ return $this->hasMany(VotingSession::class); }
}
