<?php
namespace App\GraphQL\Type;

use GraphQL\Type\Definition\Type;
use Models\Db\Jmaker\JmakerMeeting;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Models\Db\Mission\Mission;

class JmakerMeetingType extends GraphQLType
{
    protected $attributes = [
        'name' => 'JmakerMeeting',
        'description' => 'A type',
        'model' => JmakerMeeting::class,
    ];
    public function fields()
    {
        return [
            'uuid' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The uuid of the mission'
            ],
            'jmaker_uuid' => [
                'type' => Type::string(),
                'description' => 'The jmaker\'s meeting uuid'
            ],
            'date' => [
              'type' => Type::string(),
              'description' => 'The date of the meeting'
            ]
        ];
    }
    protected function resolveEmailField($root, $args)
    {
        return strtolower($root->email);
    }
}