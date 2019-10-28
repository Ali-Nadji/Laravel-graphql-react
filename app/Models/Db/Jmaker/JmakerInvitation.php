<?php namespace Models\Db\Jmaker;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Infrastructure\Database\Eloquent\Model;
use Models\Db\Clients\Client;
use Models\Db\Clients\ClientCampaign;
use Models\Db\Deprecated\Partner;
use Models\Db\Languages\Languages;
use Models\Db\Prescriber\Prescriber;


/**
 * Class JmakerInvitation
 * @property string $uuid
 * @property string $jmaker_uuid
 * @property string $token
 * @property string $email
 * @property mixed $data
 * @property string $goal_delay
 * @property string $invited_by_prescriber_uuid
 * @property string $campaign_uuid
 * @property int $partner_id
 * @property string $language_id
 * @property bool $is_started
 * @property Carbon $started_at
 * @property bool $is_completed
 * @property Carbon $completed_at
 * @property Carbon $reminder_at
 * @property int $reminder_count
 * @property bool $allow_reminder
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @package Models\Db
 */
class JmakerInvitation extends Model
{
	use SoftDeletes;


    public $incrementing = false;

    /**
     *
     *
     */
	protected $table = 'jmaker_invitation';
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
	    "data" => "json",
	];
	
	
	/**
	 * 
	 *
	 */
	protected $dates = [
	    "started_at",
	    "completed_at",
	    "created_at",
	    "updated_at",
	    "deleted_at"
	];


	/**
	 * 
	 *
	 */
	public function campaign()
	{
		return $this->hasOne(ClientCampaign::class, "uuid", "campaign_uuid");
	}


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
	public function jmaker()
	{
		return $this->hasOne(Jmaker::class, "uuid", "jmaker_uuid");
	}

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
	public function jmakerMeetings()
	{
		return $this->hasMany(JmakerMeeting::class, "invitation_uuid", "uuid");
	}
	
	/**
	 * 
	 *
	 */
	public function partner()
	{
		return $this->hasOne(Partner::class, "id", "partner_id");
	}

    /**
     *
     *
     */
    public function prescriber()
    {
        return $this->hasOne(Prescriber::class, "uuid", "invited_by_prescriber_uuid");
    }
	
	/**
     *
     *
     */
    public function language()
    {
        return $this->hasOne(Languages::class, "id", "language_id");
    }
	
	/**
	 * 
	 *
	 */
	public function isStarted()
	{
		return (bool) $this->is_started;
	}
	
	/**
	 * 
	 *
	 */
	public function isCompleted()
	{
		return (bool) $this->is_completed;
	}
}