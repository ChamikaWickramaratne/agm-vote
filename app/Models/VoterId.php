class VoterId extends Model {
    protected $table = 'voter_ids';
    protected $fillable = ['voting_session_id','voter_code','issued_by','issued_at','used','used_at'];
    protected $casts = ['issued_at'=>'datetime','used'=>'boolean','used_at'=>'datetime'];
    public function session(){ return $this->belongsTo(VotingSession::class,'voting_session_id'); }
    public function issuer(){ return $this->belongsTo(User::class,'issued_by'); }
}
