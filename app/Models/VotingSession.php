class VotingSession extends Model {
    protected $fillable = ['position_id','voting_rules','status','start_time','end_time','close_condition'];
    protected $casts = ['voting_rules'=>'array','start_time'=>'datetime','end_time'=>'datetime'];
    public function position(){ return $this->belongsTo(Position::class); }
    public function ballots(){ return $this->hasMany(Ballot::class); }
    public function voterIds(){ return $this->hasMany(VoterId::class); }
}