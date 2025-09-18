<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model {
    protected $fillable = [
        'title',
        'first_name',
        'last_name',
        'branch_name',
        'member_type',
        'email',
        'bio',
        'photo',
        // keep legacy:
        'name',
    ];
    public function voterIds(){ return $this->hasMany(VoterId::class); }
}
