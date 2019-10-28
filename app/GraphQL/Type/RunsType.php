<?php
namespace App\GraphQL\Type;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Models\Db\Mission\Run;

class RunsType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Runs',
        'description' => 'A type',
        'model' => Run::class,
    ];
    public function fields()
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'The id of the mission'
            ],
            'jmaker_uuid' => [
                'type' => Type::string(),
                'description' => 'The jmaker s id'
            ],
            'mission_id' => [
                'type' => Type::int(),
                'description' => 'The mission s id'
            ],
            'status_rid' => [
                'type' => Type::string(),
                'description' => 'The status of the mission'
            ],
            'started_at' => [
                'type' => Type::string(),
                'description' => 'The start date of the mission'
            ],
            'completed_at' => [
                'type' => Type::string(),
                'description' => 'The completion date'
            ],
            'mission' => [
                'type' => GraphQL::type('Mission'),
                'description' => 'Jmaker s mission',
                'selectable' => true
            ]
        ];
    }
    protected function resolveEmailField($root, $args)
    {
        return strtolower($root->email);
    }
}