<?php namespace Models\Db\Prescriber;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PrescriberPermission
 * @property string $id
 * @property string $prescriber_permission_group_id
 * @property string $user_interface_id
 * @property string $interface_rid
 * @property string $name
 * @property Carbon $deleted_at
 * @package Models\Db\Prescriber
 */
class PrescriberPermission extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $table = 'prescriber_permission';
    public $timestamps = false;
    public $incrementing = false;
}