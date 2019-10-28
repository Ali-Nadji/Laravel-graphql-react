<?php namespace Models\Db\Jmaker;

use Illuminate\Database\Eloquent\SoftDeletes;
use Infrastructure\Database\Eloquent\Model;

/**
 * Class JmakerHistory
 * @property string $uuid
 * @property string $jmaker_uuid
 * @property string $client_uuid
 * @property string $prescriber_uuid
 * @property string $campaign_uuid
 * @property string $contract_uuid
 * @property Carbon $deleted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package Models\Db\Jmaker
 */
class JmakerHistory extends Model
{
    use SoftDeletes;

    public $keyType = 'string';

    /**
     * Disable auto incrementing
     * @var bool
     */
    public $incrementing = false;

    protected $table = 'jmaker_history';

    protected $primaryKey = 'uuid';

    protected $casts = [
        "uuid" => 'string',
    ];

    protected $dates = [
        "created_at",
        "updated_at",
        "deleted_at"
    ];
}