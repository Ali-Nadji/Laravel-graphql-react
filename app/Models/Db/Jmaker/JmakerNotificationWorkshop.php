<?php namespace Models\Db\Jmaker;

use Carbon\Carbon;
use Infrastructure\Database\Eloquent\Model;

/**
 * Class JmakerLastWorkshopEvent
 * @property string $uuid
 * @property int $jmaker_uuid
 * @property string $mail_uuid
 * @property int $event_mission_id
 * @property string $event_status
 * @property string $type
 * @property int $iteration
 * @property int $mission_id
 * @property string $mission_status
 * @property int $generic_msg_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package Models\Db\Jmaker
 */
class JmakerNotificationWorkshop extends Model
{
    protected $table = 'jmaker_notification_workshop';

    protected $primaryKey = 'uuid';

    protected $casts = [
        "uuid" => 'string',
        "jmaker_uuid" => 'string',
        "mail_uuid" => 'string'
    ];
}