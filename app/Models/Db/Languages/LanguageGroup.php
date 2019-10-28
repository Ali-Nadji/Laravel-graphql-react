<?php namespace Models\Db\Languages;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Infrastructure\Database\Eloquent\Model;


/**
 * Class Groups
 * @property string $uuid
 * @property string $name
 * @property string $collection
 * @property array $config
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @package Models\Db
 */
class LanguageGroup extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $table = 'language_group';


    protected $primaryKey = 'uuid';


    public $keyType = 'string';


    protected $casts = [
        "uuid" => "string",
        "config" => "json"
    ];


    protected $dates = [
        "created_at",
        "updated_at",
        "deleted_at"
    ];


    /**
     *
     *
     * @return HasMany
     */
    function languageContents()
    {
        return $this->hasMany(LanguageContent::class, "language_group_uuid", "uuid");
    }
}