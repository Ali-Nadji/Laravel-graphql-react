<?php namespace Models\Db\Languages;

use Carbon\Carbon;
use FrenchFrogs\App\Models\Db\Reference;
use FrenchFrogs\Laravel\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Models\Db\Languages\LanguageGroup;


/**
 * Class Contents
 * @property string $uuid
 * @property string $code
 * @property string $lang_rid
 * @property string $language_group_uuid
 * @property string $type_rid
 * @property json $instruction
 * @property string $url
 * @property bool $is_published
 * @property Carbon $published_at
 * @property string $published_by_operator_uuid
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @package Models\Db\Languages
 */
class LanguageContent extends Model
{
    use SoftDeletes;

    /**
     * Disable auto incrementing
     * @var bool
     */
    public $incrementing = false;

    protected $table = 'language_content';


    protected $primaryKey = 'uuid';


    public $keyType = 'string';


    protected $casts = [
        "uuid" => "string",
        "language_group_uuid" => "string",
        "instruction" => "json",
        "published_by_operator_uuid" => "string"
    ];


    protected $dates = [
        "published_at",
        "created_at",
        "updated_at",
        "deleted_at"
    ];


    /**
     * Return language group
     * @return BelongsTo
     */
    function languageGroup()
    {
        return $this->belongsTo(LanguageGroup::class, "language_group_uuid", "uuid");
    }


    /**
     *
     *
     * @return BelongsTo
     */
    /*
    public function publishedByOperatorUuid()
    {
        return $this->belongsTo(Operator::class, "published_by_operator_uuid", "uuid");
    }*/


    /**
     *
     *
     * @return bool
     */
    function isPublished()
    {
        return (bool)$this->is_published;
    }

    /**
     *
     *
     * @return BelongsTo
     */
    function type()
    {
        return $this->belongsTo(Reference::class, "type_rid", "reference_id");
    }

    /**
     *
     *
     * @return BelongsTo
     */
    function lang()
    {
        return $this->belongsTo(Reference::class, "lang_rid", "reference_id");
    }
}