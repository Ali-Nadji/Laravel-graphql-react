<?php namespace Models\Db\Jmaker;

use Carbon\Carbon;
use Infrastructure\Database\Eloquent\Model;

/**
 * Class JmakerLastWorkshopEvent
 * @property int $jmaker_uuid
 * @property int $mission_id
 * @property string $event_status
 * @property bool $needComputeNotification
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package Models\Db\Jmaker
 */
class JmakerLastWorkshopEvent extends Model
{
    protected $table = 'jmaker_last_workshop_event';

    protected $primaryKey = 'jmaker_uuid';

    protected $casts = [
        "jmaker_uuid" => 'string',
        "mail_uuid" => 'string'
    ];
}