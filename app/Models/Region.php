class Region extends Model {
    protected $fillable = ['postal_code','name','description'];
    public function positions(){ return $this->hasMany(Position::class); }
}
