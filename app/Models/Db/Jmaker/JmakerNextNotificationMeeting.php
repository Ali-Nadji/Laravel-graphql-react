<?php namespace Models\Db\Jmaker;

use Carbon\Carbon;
use Infrastructure\Database\Eloquent\Model;

/**
 * Class JmakerNextMeetingNotification
 * @property int $jmaker_uuid
 * @property Carbon send_at
 * @property Carbon meeting_date
 * @property boolean processed
 * @property boolean frozen
 * @package Models\Db\Jmaker
 */
class JmakerNextNotificationMeeting extends Model
{
    protected $table = 'jmaker_next_notification_meeting';

    protected $primaryKey = 'jmaker_uuid';

    public $timestamps = false;

    protected $casts = [
        "jmaker_uuid" => 'string',
        "processed" => 'boolean',
        "frozen" => 'boolean',
    ];

    protected $dates = [
        "meeting_date",
        "send_at",
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function jmaker()
    {
        return $this->hasOne(Jmaker::class, "uuid", "jmaker_uuid");
    }
}