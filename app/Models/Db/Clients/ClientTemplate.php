<?php

namespace Models\Db\Clients;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ClientTemplate
 * @property string $uuid
 * @property string $client_uuid
 * @property string $type
 * @property json $data
 */
class ClientTemplate extends Model
{
    /**
     * No timestamps
     * @var bool
     */
    public $timestamps = false;

    protected $primaryKey = 'uuid';
    protected $table = 'client_template';

    /**
     * Return Client
     * @return  \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function client(){
        return $this->hasOne(Client::class,'uuid','client_uuid');
    }
}
