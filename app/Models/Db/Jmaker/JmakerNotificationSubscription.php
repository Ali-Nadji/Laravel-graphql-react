<?php namespace Models\Db\Jmaker;

use Carbon\Carbon;
use Infrastructure\Database\Eloquent\Model;

/**
 * Class JmakerNotificationSubscription
 * @property string $uuid
 * @property int $jmaker_invitation_uuid
 * @property string $mail_uuid
 * @property string $type
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package Models\Db\Jmaker
 */
class JmakerNotificationSubscription extends Model
{
    protected $table = 'jmaker_notification_subscription';

    protected $primaryKey = 'uuid';

    protected $casts = [
        "uuid" => 'string',
        "jmaker_invitation_uuid" => 'string',
        "mail_uuid" => 'string'
    ];
}