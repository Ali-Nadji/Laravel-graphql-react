<?php namespace Models\Db\Jmaker;

use Carbon\Carbon;
use Infrastructure\Database\Eloquent\Model;

/**
 * Class JmakerNotificationMeeting
 * @property string $uuid
 * @property int $jmaker_uuid
 * @property string $mail_uuid
 * @property string $type
 * @property Carbon $meeting_date
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package Models\Db\Jmaker
 */
class JmakerNotificationMeeting extends Model
{
    protected $table = 'jmaker_notification_meeting';

    protected $primaryKey = 'uuid';

    protected $casts = [
        "uuid" => 'string',
        "jmaker_uuid" => 'string',
        "mail_uuid" => 'string'
    ];
}