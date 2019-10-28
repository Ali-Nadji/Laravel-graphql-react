<?php namespace Models\Db\Prescriber;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PrescriberPermissionGroup
 * @property string $id
 * @property string $name
 * @property Carbon $deleted_at
 * @package Models\Db\Prescriber
 */
class PrescriberPermissionGroup extends Model
{
    use SoftDeletes;
    protected $primaryKey = 'id';
    protected $table = 'prescriber_permission_group';
    public $timestamps = false;
    public $incrementing = false;
}