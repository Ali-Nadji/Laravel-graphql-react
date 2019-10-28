<?php namespace Models\Db\Mission;

use FrenchFrogs\Laravel\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Sequence
 * @property string $sid
 * @property string $name
 * @property json $sequence_content
 * @property Carbon $deleted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package Models\Db\Mission
 */
class Sequence extends Model
{
    use SoftDeletes;

    const SEQUENCE_HIRING = 'hiring';
    const SEQUENCE_FOLLOWING = 'following';
    const SEQUENCE_DEFAULT = 'default';
    const SEQUENCE_MOBILITY = 'mobility';
    const SEQUENCE_MOBILITY_ECO = 'mobility_eco';
    const SEQUENCE_EXECUTIVE = 'executive';
    const SEQUENCE_ECOSYSTEM = 'ecosystem';
    const SEQUENCE_NEXTMOVE = 'nextmove';
    const SEQUENCE_CHECKUP_SAFRAN = 'checkup_safran';
    const SEQUENCE_NEXT_MOVE_INSIDE_RSI = 'next_move_rsi';
    const SEQUENCE_NEXT_MOVE_INSIDE_TOTAL = 'next_move_inside_total';

    const MISSION_SEQUENCE_MAX_COUNT = 8;
    public $incrementing = false;
    protected $primaryKey = 'sid';
    protected $table = 'mission_sequence';
    protected $casts = [
        'sequence_content' => 'json',
    ];
}