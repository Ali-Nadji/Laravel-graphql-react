<?php
namespace App\GraphQL\Type;

use GraphQL\Type\Definition\Type;
use Models\Db\Jmaker\Jmaker;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class JmakerClientType extends GraphQLType
{
    protected $attributes = [
        'name' => 'JmakerClient',
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
            'name' => [
                'type' => Type::string(),
                'description' => 'The name of the jmaker'
            ],
              'created_at' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The dat eof creation of the jmaker'

            ], 'state' => [
                'type' => Type::string(),
                'description' => 'The current state of the jmaker'

            ], 'last_page_at' => [
                'type' => Type::string(),
                'description' => 'The date of the last page visited by the jmaker'
            ],
             'missions_ct' => [
                'type' => Type::int(),
                'description' => 'The number of finished missions'
            ],
            'meeting_date' => [
                'type' => Type::string(),
                'description' => 'The date of the meeting with the HR'
            ],'synthesis_shared_at' => [
                'type' => Type::string(),
                'description' => 'The date when a the jmaker shared his synthesis with the HR'
            ],
            'default_relance_object_fr' => [
                'type' => Type::string(),
                'description' => 'The default object for a relance'
            ],
            'default_relance_message_fr' => [
                'type' => Type::string(),
                'description' => 'The default message for a relance'
            ],
            'default_relance_object_en' => [
                'type' => Type::string(),
                'description' => 'The default object for a relance'
            ],
            'default_relance_message_en' => [
                'type' => Type::string(),
                'description' => 'The default message for a relance'
            ],
            'prescriber' => [
                'type' => GraphQL::type('Prescriber'),
                'description' => 'The prescriber linked to the jmaker',
                'selectable' => true
            ],
             'language_id' => [
            'type' => Type::string(),
            'description' => 'The language of the jmaker'
            ],
            'recall_at' => [
                'type' => Type::string(),
                'description' => 'The date of the last time the jmaker has been recalled'
            ],
            'recall_ct' => [
                'type' => Type::int(),
                'description' => 'How many times the jmaker has been recalled'
            ],

        ];
    }    protected function resolveEmailField($root, $args)
    {
        return strtolower($root->email);
    }
}