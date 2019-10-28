<?php namespace Models\Db\Jmaker;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Infrastructure\Database\Eloquent\Model;
use Models\Db\Prescriber\Prescriber;

/**
 * Class JmakerMeeting
 * @property string $uuid
 * @property string $jmaker_uuid
 * @property string $invited_by_prescriber_uuid
 * @property string $type
 * @property string $invitation_uuid
 * @property Carbon $meeting_date
 * @property bool $is_held
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @package Models\Db\Jmaker
 */
class JmakerMeeting extends Model
{
	use SoftDeletes;
	/**
     *
	 *
	 */
    public $keyType = 'string';

    public $incrementing = false;
    /**
     *
     *
     */
	protected $table = 'jmaker_meeting';
	/**
     *
	 *
	 */
	protected $primaryKey = 'uuid';
	/**
	 * 
	 *
	 */
	protected $casts = [
		"uuid" => "string",
		"invited_by_prescriber_uuid" => "string",
		"invitation_uuid" => "string",
		"jmaker_uuid" => "string",
	];
	
	
	/**
	 * 
	 *
	 */
	protected $dates = [
	    "meeting_date",
	    "created_at",
	    "updated_at",
	    "deleted_at"
	];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
	public function jobmaker()
	{
		return $this->hasOne(Jmaker::class, "uuid", "jmaker_uuid");
	}

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function prescriber()
    {
        return $this->hasOne(Prescriber::class, "uuid", "invited_by_prescriber_uuid");
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function invitation()
    {
        return $this->hasOne(JmakerInvitation::class, "uuid", "invitation_uuid");
	}

    /**
     * @return bool
     */
	public function isHeld()
	{
		return (bool) $this->is_held;
	}
}