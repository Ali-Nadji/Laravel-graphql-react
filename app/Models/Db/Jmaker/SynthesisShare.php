<?php namespace Models\Db\Jmaker;


use Carbon\Carbon;
use Models\Db\Jmaker\Jmaker;
use FrenchFrogs\Laravel\Database\Eloquent\Model;
use Models\Db\Prescriber\Prescriber;

/**
 * Class SynthesisShare
 * @property string $uuid
 * @property string $jmaker_uuid
 * @property string $prescriber_uuid
 * @property string $way_uuid
 * @property Carbon $shared_at
 * @package Models\Db\User
 */
class SynthesisShare extends Model
{
    public $keyType = "string";

    protected $table = 'synthesis_share';

    protected $primaryKey = 'uuid';

    /**
     * Disable auto incrementing
     * @var bool
     */
    public $incrementing = false;

    /**
     * No timestamp.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */

    /**
     * Dates
     * @var array
     */
    protected $dates = [
        "shared_at"
    ];

    /**
     * List all users
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function jmaker()
    {
        return $this->belongsTo(Jmaker::class, 'jmaker_uuid');
    }

    /**
     * Return Prescriber user;
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function prescriber()
    {
        return $this->hasOne(Prescriber::class, "uuid", "prescriber_uuid");

    }
}