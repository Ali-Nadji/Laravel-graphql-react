<?php
namespace App\GraphQL\Type;

use GraphQL\Type\Definition\Type;
use Models\Db\Languages\LanguageContent;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Support\Facades\GraphQL;

class LanguageContentType extends GraphQLType
{
    protected $attributes = [
        'name' => 'LanguageContent',
        'description' => 'A type',
        'model' => LanguageContent::class,
    ];
    public function fields()
    {
        return [
            'code' => [
                'type' =>  Type::string(),
                'description' => 'The id of the mission'
            ],
            'lang_rid' => [
                'type' => Type::string(),
                'description' => 'The jmaker s id'
            ],
            'type_rid' => [
                'type' => Type::string(),
                'description' => 'The mission s id'
            ],
            'instructions' => [
                'type' => Type::string(),
                'description' => 'The status of the mission'
            ]
        ];
    }
    protected function resolveEmailField($root, $args)
    {
        return strtolower($root->email);
    }
}