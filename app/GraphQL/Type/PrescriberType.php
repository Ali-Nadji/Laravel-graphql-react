<?php

namespace App\GraphQL\Type;

use GraphQL\Type\Definition\Type;
use Models\Db\Prescriber\Prescriber;
use Rebing\GraphQL\Support\Type as GraphQLType;

class PrescriberType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Prescriber',
        'description' => 'A type',
        'model' => Prescriber::class,
    ];

    public function fields()
    {
        return [
            'uuid' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The uuid of the jmaker'
            ],
            'firstname' => [
                'type' => Type::string(),
                'description' => 'The firstname of the jmaker'
            ],
            'lastname' => [
                'type' => Type::string(),
                'description' => 'The firstname of the jmaker'
            ],
            'language' => [
                'type' => Type::string(),
                'description' => 'The language of the jmaker interface'
            ],
            'email' => [
                'type' => Type::string(),
                'description' => 'The email of the jmaker'
            ],


        ];
    }

    protected function resolveEmailField($root, $args)
    {
        return strtolower($root->email);
    }
}