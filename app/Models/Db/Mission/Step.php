<?php namespace Models\Db\Mission;

use FrenchFrogs\Laravel\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Models\Db\VideoNavigation;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Models\Db\Mission\Mission;
use Models\Db\User\MissionStep;
use \Carbon\Carbon;


/**
 * Class Step
 * @property int $id
 * @property int $mission_id
 * @property int $next_id
 * @property string $slug
 * @property string $action
 * @property string $content
 * @property Carbon $deleted_at
 * @package Models\Db\Deprecated\Mission
 */
class Step extends Model
{
	use SoftDeletes;
	
	
	public $timestamps = false;
	
	
	protected $table = 'mission_step';
	
	
	protected $dates = [
	    "deleted_at"
	];
	
	
	public function video()
	{
		return $this->hasOne(VideoNavigation::class, 'mission_step_id');
	}
	
	
	/**
	 * 
	 *
	 * @return BelongsTo
	 */
	function next()
	{
		return $this->belongsTo(Step::class, "next_id", "id");
	}
	
	
	/**
	 * 
	 *
	 * @return HasMany
	 */
	function missions()
	{
		return $this->hasMany(Mission::class, "step_id", "id");
	}
	
	
	/**
	 * 
	 *
	 * @return BelongsTo
	 */
	function mission()
	{
		return $this->belongsTo(Mission::class, "mission_id", "id");
	}
	
	
	/**
	 * 
	 *
	 * @return HasMany
	 */
	function steps()
	{
		return $this->hasMany(MissionStep::class, "mission_step_id", "id");
	}
	
	
	/**
	 * 
	 *
	 * @return HasMany
	 */
	function videos()
	{
		return $this->hasMany(VideoNavigation::class, "mission_step_id", "id");
	}
}