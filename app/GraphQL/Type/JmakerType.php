<?php
namespace App\GraphQL\Type;

use GraphQL\Type\Definition\Type;
use Models\Db\Jmaker\Jmaker;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class JmakerType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Jmaker',
        'description' => 'A type',
        'model' => Jmaker::class,
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
              'created_at' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The dat eof creation of the jmaker'

            ], 'state' => [
                'type' => Type::string(),
                'description' => 'The current state of the jmaker'

            ],
                'language_id' => [
                'type' => Type::string(),
                'description' => 'The language of the jmaker interface'
            ],

            'last_page_at' => [
                'type' => Type::string(),
                'description' => 'The date of the last page visited by the jmaker'
            ],
            'meeting_date' => [
                'type' => Type::string(),
                'description' => 'The date of the meeting with the HR'
            ],'synthesis_shared_at' => [
                'type' => Type::string(),
                'description' => 'The date when a the jmaker shared his synthesis with the HR'
            ],
            'recall_at' => [
                'type' => Type::string(),
                'description' => 'The date of the last time the jmaker has been recalled'
            ],
            'recall_ct' => [
                'type' => Type::int(),
                'description' => 'How many times the jmaker has been recalled'
            ],
            'email' => [
                'type' => Type::string(),
                'description' => 'The email of the jmaker'
            ],
            'missions_ct' => [
                'type' => Type::int(),
                'description' => ''
            ],
            'registred_at' => [
                'type' => Type::string(),
                'description' => 'The activation date'
            ],
            'runs'=> [
                'type' => Type::listOf(GraphQL::type('Runs')),
                'description' => 'Jmaker s run',
                'selectable' => true
            ],
            'prescriber' => [
                'type' => GraphQL::type('Prescriber'),
                'description' => 'The prescriber linked to the jmaker',
                'selectable' => true
            ]

        ];
    }    protected function resolveEmailField($root, $args)
    {
        return strtolower($root->email);
    }
}