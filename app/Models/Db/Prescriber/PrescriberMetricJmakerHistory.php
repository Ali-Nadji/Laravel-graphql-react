<?php namespace Models\Db\Prescriber;

use Carbon\Carbon;
use Infrastructure\Database\Eloquent\Model;

/**
 * Class ClientMetricJmakerHistory
 * @property string $uuid
 * @property string $prescriber_uuid
 * @property int $jmaker_state_invited
 * @property int $jmaker_state_onboarding
 * @property int $jmaker_state_active
 * @property int $jmaker_state_deleted
 * @property int $jmaker_state_archived
 * @property int $workshop_started
 * @property int $workshop_finished
 * @property int $jmaker_ws_finished_1
 * @property int $jmaker_ws_finished_2
 * @property int $jmaker_ws_finished_3
 * @property int $jmaker_ws_finished_4
 * @property int $jmaker_ws_finished_5
 * @property int $jmaker_ws_finished_6
 * @property int $jmaker_ws_finished_7
 * @property int $jmaker_ws_finished_8
 * @property int $jmaker_ws_finished_9
 * @property int $step_finished
 * @property int $shared_ct
 * @property int $distinct_shared_ct
 * @property int $avg_delay_between_invit_active
 * @property int $trim_avg_delay_between_invit_active'
 * @property Carbon $created_at
 * @package Models\Db
 */
class PrescriberMetricJmakerHistory extends Model
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

	protected $table = 'prescriber_metric_jmaker_history';
	
	protected $primaryKey = 'uuid';

	protected $casts = [
	    "uuid" => "string",
        "prescriber_uuid" => "string",
	];

    protected $dates = [
        "created_at",
    ];
}