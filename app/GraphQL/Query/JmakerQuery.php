<?php
namespace App\GraphQL\Query;
use Models\Db\Jmaker\Jmaker;
use GraphQL\Type\Definition\Type;
use Models\Db\Jmaker\JmakerMeeting;
use Models\Db\Jmaker\SynthesisShare;
use Models\Db\Languages\LanguageContent;
use Models\Db\Mission\Mission;
use Models\Db\Mission\Run;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Illuminate\Support\Facades\Auth;

class JmakerQuery extends Query
{
    protected $attributes = [
        'name'  => 'jmaker',
    ];

    public function authorize(array $args = [])
    {
        return Auth::guard(\Ref::INTERFACE_PARTNER)->check();
    }

    public function type()
    {
        return GraphQL::type('Jmaker'); //retrieve a jmaker
    }

    public function args()
    {
        return [
            'uuid'   => [
                'name' => 'uuid',
                'type' => Type::string(),
            ],
        ];
    }

    public function rules(array $args = [])
    {
        return [
            'uuid' => [
                'string',
            ],
        ];
    }

    public function resolve($root, $args)
    {
        if(isset($args['uuid'])){
            $jmaker = Jmaker::find($args['uuid']);
            return $jmaker;
        }

    }
}