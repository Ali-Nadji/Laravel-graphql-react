<?php namespace Models\Db\Jmaker;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Infrastructure\Database\Eloquent\Model;

/**
 * Class JmakerInvitationReminders
 * @property string $uuid
 * @property string $invitation_uuid
 * @property int $reminder_position
 * @property string $mail_uuid
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @package Models\Db\Jmaker
 */
class JmakerInvitationReminders extends Model
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
	protected $table = 'jmaker_invitation_reminders';
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
        "mail_uuid" => "string",
	    "invitation_uuid" => "string",
	];
	
	
	/**
	 * 
	 *
	 */
	protected $dates = [
	    "created_at",
	    "updated_at",
	    "deleted_at"
	];
	
	
	/**
	 * 
	 *
	 */
	public function invitation()
	{
		return $this->hasOne(JmakerInvitation::class, "uuid", "invitation_uuid");
	}
}