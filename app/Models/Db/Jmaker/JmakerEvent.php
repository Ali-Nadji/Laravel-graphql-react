<?php namespace Models\Db\Jmaker;

use Carbon\Carbon;
use Infrastructure\Database\Eloquent\Model;

/**
 * Class JmakerLastWorkshopEvent
 * @property string $uuid
 * @property string $jmaker_uuid
 * @property string $type
 * @property string $scope
 * @property string $ref_1_uuid
 * @property string $ref_1_type
 * @property string $ref_2_uuid
 * @property string $ref_2_type
 * @property Carbon $date
 * @package Models\Db\Jmaker
 */
class JmakerEvent extends Model
{
    protected $table = 'jmaker_event';

    public $timestamps = false;

    protected $primaryKey = 'uuid';

    protected $casts = [
        "jmaker_uuid" => 'string',
        "ref_uuid" => 'string'
    ];
}