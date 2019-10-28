<?php namespace Models\Db\Jmaker;

use Illuminate\Database\Eloquent\SoftDeletes;
use Infrastructure\Database\Eloquent\Model;

/**
 * Class JmakerNotifications
 * @property string $uuid
 * @property string $jmaker_uuid
 * @property string $mail_uuid
 * @property int $mission_run_id
 * @property string $mission_run_status_rid
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @package Models\Db\Jmaker
 */
class JmakerNotifications extends Model
{
    use SoftDeletes;

    public $keyType = 'string';

    protected $table = 'jmaker_mission_notification';

    protected $primaryKey = 'uuid';

    protected $casts = [
        "uuid" => 'string',
        "mail_uuid" => 'string'
    ];
}