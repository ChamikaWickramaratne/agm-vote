class Position extends Model {
    protected $fillable = ['name','description','region_id'];
    public function region(){ return $this->belongsTo(Region::class); }
    public function candidates(){ return $this->hasMany(Candidate::class); }
    public function sessions(){ return $this->hasMany(VotingSession::class); }
}
