class Candidate extends Model {
    protected $fillable = ['position_id','name','bio','photo_url'];
    public function position(){ return $this->belongsTo(Position::class); }
    public function ballots(){ return $this->hasMany(Ballot::class); }
}
