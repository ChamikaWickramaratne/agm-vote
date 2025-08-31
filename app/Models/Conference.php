<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conference extends Model {
    protected $fillable = ['start_date','end_date'];
    protected $casts = ['start_date'=>'datetime','end_date'=>'datetime'];
    public function sessions(){ return $this->hasMany(VotingSession::class); }

    protected static function booted(): void
    {
        static::creating(function ($conf) {
            if (empty($conf->public_token)) {
                $conf->public_token = bin2hex(random_bytes(16)); // 32 hex chars
            }
        });
    }

    public function scopeActive($q)
    {
        return $q->whereNull('end_date')
                ->where(function($qq){
                    $qq->whereNull('start_date')->orWhere('start_date','<=',now());
                });
    }
}
