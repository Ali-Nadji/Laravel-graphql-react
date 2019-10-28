<?php namespace Models\Db\Mission;


use Carbon\Carbon;
use FrenchFrogs\App\Models\Db\Reference;
use FrenchFrogs\Laravel\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Models\Db\Jmaker\Jmaker;
use Models\Db\Deprecated\Realisation;
use Models\Db\Deprecated\SkillDomain;
use Models\Db\Deprecated\TextEntry;
use Models\Db\QuestionRun;


/**
 * Class Run
 * @property int $id
 * @property string $jmaker_uuid
 * @property int $mission_id
 * @property string $status_rid
 * @property json $production
 * @property int $suggestion_score
 * @property json $evaluations
 * @property bool $is_started
 * @property Carbon $started_at
 * @property Carbon $created
 * @property bool $completed
 * @property Carbon $completed_at
 * @property int $session_ct
 * @property Carbon $last_session_at
 * @property Carbon $deleted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package Models\Db\Mission
 */
class Run extends Model
{
    use SoftDeletes;


    protected $table = 'mission_run';


    protected $dates = [
        "started_at",
        "completed_at",
        "last_session_at",
        "deleted_at",
        "created_at",
        "updated_at"
    ];

    protected $casts = [
        "production" => "json",
        "evaluations" => "json",
    ];


    // STATUS DES PRODUCTION
    const PRODUCTION_STATUS_INIT = 'PRODUCTION_STATUS_INIT';
    const PRODUCTION_STATUS_DRAFT = 'PRODUCTION_STATUS_DRAFT';
    const PRODUCTION_STATUS_PUBLISHED = 'PRODUCTION_STATUS_PUBLISHED';
    const PRODUCTION_STATUS_VALID = 'PRODUCTION_STATUS_VALID';

    /**
     *
     *
     * @return BelongsTo
     */
    function jmaker()
    {
        return $this->belongsTo(Jmaker::class, "jmaker_uuid", "uuid");
    }


    /**
     *
     *
     * @return BelongsTo
     */
    function mission()
    {
        return $this->belongsTo(Mission::class, "mission_id", "id");
    }


    /**
     *
     *
     * @return BelongsTo
     */
    function status()
    {
        return $this->belongsTo(Reference::class, "status_rid", "reference_id");
    }

    /**
     *
     *
     * @return bool
     */
    function isStarted()
    {
        return (bool) $this->is_started;
    }


    /**
     * @return bool
     */
    public function isVisible()
    {
        return $this->status_rid ==  \Ref::RUN_STATUS_VISIBLE;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return $this->status_rid == \Ref::RUN_STATUS_HIDDEN;
    }


    /**
     * @return bool
     */
    public function isAccessible()
    {
        return $this->status_rid == \Ref::RUN_STATUS_ACCESSIBLE;
    }


    /**
     * @return bool
     */
    public function isFinished()
    {
        return $this->status_rid == \Ref::RUN_STATUS_FINISHED || $this->status_rid == \Ref::RUN_STATUS_FINISHED_CHECKUP_WITH_JOBMAKER;
    }


    /**
     * @return bool
     */
    public function isInProgress()
    {
        return $this->status_rid == \Ref::RUN_STATUS_INPROGRESS;
    }


    /**
 * Getter pour l'evaluation glmobal de l'atelier
 *
 * @return bool
 */
    function getGlobalEvaluation()
    {
        if (empty($this->evaluations)
            || empty($this->evaluations['questions'])
            || empty($this->evaluations['questions'][0])
            || !isset($this->evaluations['questions'][0]['value'])
            || !is_numeric($this->evaluations['questions'][0]['value'])
            || empty($this->evaluations['questions'][1])
            || !isset($this->evaluations['questions'][1]['value'])
            || !is_numeric($this->evaluations['questions'][1]['value'])) {
            $globalEval = false;
        } else {
            $globalEval = ($this->evaluations['questions'][0]['value']+$this->evaluations['questions'][1]['value'])/2;
        }

        return $globalEval;
    }

    /**
    * Get evaluation for the first Question
    *
    * @return bool
    */
    function getFirstEvaluation()
    {

        if (empty($this->evaluations)
            || empty($this->evaluations['questions'])
            || empty($this->evaluations['questions'][0])
            || !isset($this->evaluations['questions'][0]['value'])) {
            return false;
        }

        return $this->evaluations['questions'][0]['value'];
    }

    /**
     * Get evaluation for the second Question
     *
     * @return bool
     */
    function getSecondEvaluation()
    {

        if (empty($this->evaluations)
            || empty($this->evaluations['questions'])
            || empty($this->evaluations['questions'][1])
            || !isset($this->evaluations['questions'][1]['value'])) {
            return false;
        }

        return $this->evaluations['questions'][1]['value'];
    }

}