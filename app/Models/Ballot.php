class Ballot extends Model {
    protected $fillable = ['voting_session_id','candidate_id','voter_code_hash','jti_hash','cast_at'];
    protected $casts = ['cast_at'=>'datetime'];
    public function session(){ return $this->belongsTo(VotingSession::class,'voting_session_id'); }
    public function candidate(){ return $this->belongsTo(Candidate::class); }
}
