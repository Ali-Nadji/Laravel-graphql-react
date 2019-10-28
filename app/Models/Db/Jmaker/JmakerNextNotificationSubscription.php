<?php namespace Models\Db\Jmaker;

use Carbon\Carbon;
use Infrastructure\Database\Eloquent\Model;

/**
 * Class JmakerNextNotificationSubscription
 * @property string $jmaker_invitation_uuid
 * @property string $type
 * @property Carbon $send_at
 * @property bool $processed
 * @property bool $frozen
 * @package Models\Db\Jmaker
 */
class JmakerNextNotificationSubscription extends Model
{
    protected $table = 'jmaker_next_notification_subscription';

    protected $primaryKey = 'jmaker_invitation_uuid';

    public $timestamps = false;

    protected $casts = [
        "jmaker_invitation_uuid" => 'string',
        "processed" => 'boolean',
        "frozen" => 'boolean'
    ];

    protected $dates = [
        "send_at",
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function jmakerInvitation()
    {
        return $this->hasOne(JmakerInvitation::class, "uuid", "jmaker_invitation_uuid");
    }
}