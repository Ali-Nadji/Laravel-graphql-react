<?php namespace Models\Db\Jmaker;

use Infrastructure\Database\Eloquent\Model;

/**
 * Class ZMetricJmaker
 * @property string $uuid
 * @property string $jmaker_uuid
 * @property Carbon $created_at
 * @property string $jmaker_state
 * @property int $workshop_started
 * @property int $workshop_finished
 * @property int $step_finished
 * @property int $shared_ct
 * @package Models\Db
 */
class ZMetricJmaker extends Model
{
    /**
     * Disable auto incrementing
     * @var bool
     */
    public $incrementing = false;

    /**
     * No timestamps
     * @var bool
     */
    public $timestamps = false;

	protected $table = 'z_metric_jmaker_history';
	
	protected $primaryKey = 'uuid';

	protected $casts = [
	    "uuid" => "string",
        "jmaker_uuid" => "string",
	];
}