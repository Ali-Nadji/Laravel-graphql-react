<?php

namespace App\GraphQL\Mutations;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Models\Db\Prescriber\PrescriberTemplate;
use Rebing\GraphQL\Support\Mutation;

class SaveRelanceObj extends Mutation
{

    protected $attributes = [
        'name' => 'saveRelanceObj'
    ];

    public function type(): Type
    {
        return GraphQL::type('PrescriberTemplate');
    }

    public function args(): array
    {
        return [
            'objectFr' => ['name' => 'objectFr', 'type' => Type::string()],
            'objectEn' => ['name' => 'objectEn', 'type' => Type::string()],
            'messageFr' => ['name' => 'messageFr', 'type' => Type::string()],
            'messageEn' => ['name' => 'messageEn', 'type' => Type::string()],
        ];
    }

    public function resolve($root, $args)
    {
        $templatePrescriber = PrescriberTemplate::where('prescriber_uuid',prescriber()->uuid)->first();
        if($templatePrescriber instanceof PrescriberTemplate){
            $templatePrescriber->data = json_decode($templatePrescriber->data);
            $templatePrescriber->data = json_encode(['objectFr' => isset($args['objectFr']) ? $args['objectFr'] : "",
                                                    'messageFr' => isset($args['messageFr']) ? $args['messageFr'] : "",
                                                    'objectEn' => isset($args['objectEn']) ? $args['objectEn'] : "",
                                                    'messageEn' => isset($args['messageEn']) ? $args['messageEn'] : ""
                                                    ]);
        }else{
            $templatePrescriber = new PrescriberTemplate();
            $templatePrescriber->uuid =  generateNewUUID();
            $templatePrescriber->prescriber_uuid = prescriber()->uuid;
            $templatePrescriber->type = \Ref::PRESCRIBER_RELANCE;
            $templatePrescriber->data = json_encode([
                'objectFr' => isset($args['objectFr']) ? $args['objectFr'] : "",
                'messageFr' => isset($args['messageFr']) ? $args['messageFr'] : "",
                'objectEn' => isset($args['objectEn']) ? $args['objectEn'] : "",
                'messageEn' => isset($args['messageEn']) ? $args['messageEn'] : "",
            ]);
        }

        $templatePrescriber->save();

        return $templatePrescriber;
    }
}
