<?php namespace Models\Db\Jmaker;

use Carbon\Carbon;
use Infrastructure\Database\Eloquent\Model;

/**
 * Class JmakerLastWorkshopEvent
 * @property int $jmaker_uuid
 * @property int $event_mission_id
 * @property string $event_status
 * @property string $type
 * @property int $iteration
 * @property int $mission_id
 * @property string $mission_status
 * @property Carbon $send_at
 * @property boolean processed
 * @property boolean frozen
 * @package Models\Db\Jmaker
 */
class JmakerNextNotificationWorkshop extends Model
{
    protected $table = 'jmaker_next_notification_workshop';

    protected $primaryKey = 'jmaker_uuid';

    public $timestamps = false;

    protected $casts = [
        "jmaker_uuid" => 'string',
        "processed" => 'boolean',
        "frozen" => 'boolean'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function jmaker()
    {
        return $this->hasOne(Jmaker::class, "uuid", "jmaker_uuid");
    }
}