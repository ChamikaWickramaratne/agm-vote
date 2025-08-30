<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model {
    protected $fillable = ['name','email'];
    public function voterIds(){ return $this->hasMany(VoterId::class); }
}
