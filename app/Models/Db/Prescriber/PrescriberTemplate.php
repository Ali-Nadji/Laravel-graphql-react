<?php

namespace Models\Db\Prescriber;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Prescriber
 * @property string $uuid
 * @property string $prescriber_uuid
 * @property string $type
 * @property json $data
 */
class PrescriberTemplate extends Model
{

    /**
     * No timestamps
     * @var bool
     */
    public $timestamps = false;
    protected $primaryKey = 'uuid';
    protected $table = 'prescriber_template';

    /**
     * Return Prescriber
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function prescriber()
    {
        return $this->hasOne(Prescriber::class, "uuid", "prescriber_uuid");

    }
}
