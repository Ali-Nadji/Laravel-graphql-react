<?php namespace Models\Db\Languages;

use FrenchFrogs\Laravel\Database\Eloquent\Model;

/**
 * Class Languages
 * @property string $id
 * @property string $translate_code
 * @property string $locale
 * @package Models\Db\Languages
 */
class Languages extends Model
{
    /**
     * No timestamp.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $table = 'languages';

    protected $primaryKey = 'id';

    protected $casts = [
        "id" => "string",
        "translate_code" => "string",
        "locale" => "string",
    ];
}