<?php namespace Models\Db\Mission;


use FrenchFrogs\Laravel\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Models\Db\Mission\Step;
use Models\Db\FileFile;
use Models\Db\Question;
use Models\Db\User\Behavior;
use Models\Db\Value;


/**
 * Class Mission
 * @property int $id
 * @property string $icone_url
 * @property string $icone_grey_url
 * @property int $mission_number
 * @property string $type_rid
 * @property string $content
 * @property int $step_id
 * @property string $title
 * @property string $name
 * @property string $slug
 * @property string $controller
 * @property string $description
 * @property string $expected_duration
 * @package Models\Db\Mission
 */
class Mission extends Model
{
    const DETOX = 1;
    const EXPLOITS = 2;
    const SKILLS = 3;
    const CHOICES = 4;
    const CAP = 5;
    const MARKET = 6;
    const ARMS = 7;
    const CAMPAIGN = 8;
    const CHECKUP_SAFRAN = 25;
    const ECO_FINAL = 26;


    public $timestamps = false;


    protected $primaryKey = 'id';


    protected $table = 'mission';

    /**
     *
     *
     * @return HasMany
     */
    public function runs()
    {
        return $this->hasMany(Run::class, "mission_id", "id");
    }


    /**
     * @return HasMany
     */
    public function steps()
    {
        return $this->hasMany(Step::class, "mission_id", "id");
    }


    /**
     *
     *
     * @return HasMany
     */
    public function behaviors()
    {
        return $this->hasMany(Behavior::class, "mission_id", "id");
    }

    /**
     *
     *
     * @return BelongsTo
     */
    public function step()
    {
        return $this->belongsTo(Step::class, "step_id", "id");
    }
}