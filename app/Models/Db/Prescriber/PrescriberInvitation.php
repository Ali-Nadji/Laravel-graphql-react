<?php namespace Models\Db\Prescriber;


use FrenchFrogs\Laravel\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PrescriberInvitation
 * @property string $uuid
 * @property string $token
 * @property string $prescriber_uuid
 * @property string $invited_by_prescriber_uuid
 * @property string $mail_uuid
 * @property json $data
 * @property bool $is_started
 * @property Carbon $started_at
 * @property bool $is_completed
 * @property Carbon $completed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @package Models\Db\Prescriber
 */
class PrescriberInvitation extends Model
{
    use SoftDeletes;
    /**
     *
     *
     */
    public $keyType = 'string';
    /**
     *
     *
     */
    protected $table = 'prescriber_invitation';
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
        "prescriber_uuid" => "string",
        "mail_uuid" => "string",
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
     * Return Prescriber user;
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function prescriber()
    {
        return $this->hasOne(Prescriber::class, "uuid", "prescriber_uuid");

    }

}