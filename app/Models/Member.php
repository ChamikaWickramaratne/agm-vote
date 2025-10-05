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
    public function conferences()
    {
        return $this->belongsToMany(\App\Models\Conference::class, 'conference_members')
            ->withTimestamps()
            ->withPivot('checked_in_at');
    }

}
