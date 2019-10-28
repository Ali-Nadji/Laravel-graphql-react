<?php
namespace App\GraphQL\Type;

use GraphQL\Type\Definition\Type;
use Models\Db\Languages\LanguageContent;
use Models\Db\Prescriber\PrescriberTemplate;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Support\Facades\GraphQL;

class PrescriberTemplateType extends GraphQLType
{
    protected $attributes = [
        'name' => 'PrescriberTemplate',
        'description' => 'A type',
        'model' => PrescriberTemplate::class,
    ];
    public function fields()
    {
        return [

            'uuid' => [
                'type' => Type::string(),
                'description' => 'The template uuid'
            ],
            'prescriber_uuid' => [
                'type' => Type::string(),
                'description' => 'The prescriber\'uuid'
            ],
            'type' => [
                'type' => Type::string(),
                'description' => 'the template type'
            ],
            'data' => [
                'type' => Type::string(),
                'description' => 'the template data'
            ]
        ];
    }
    protected function resolveEmailField($root, $args)
    {
        return strtolower($root->email);
    }
}