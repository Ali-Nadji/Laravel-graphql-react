<?php
namespace App\GraphQL\Type;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Models\Db\Mission\Mission;

class MissionType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Mission',
        'description' => 'A type',
        'model' => Mission::class,
    ];
    public function fields()
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'The id of the mission'
            ],
            'title' => [
                'type' => Type::string(),
                'description' => 'The title of the mission'
            ],
            'started_at' => [
                'type' => Type::string(),
                'description' => 'The start date of the mission'
            ],
            'status' => [
              'type' => Type::string(),
              'description' => 'The status of the mission progress'
            ],
              'ended_at' => [
                'type' => Type::string(),
                'description' => 'The end date of the mission'
            ]
        ];
    }
    protected function resolveEmailField($root, $args)
    {
        return strtolower($root->email);
    }
}