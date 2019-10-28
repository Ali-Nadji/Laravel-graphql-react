<?php

namespace App\GraphQL\Mutations;

use App\Events\Jmakers\JmakerInvitedEvent;
use App\Events\Jmakers\MailPrescriberToJmakerEvent;
use App\Mail\Jobmaker\Invite;
use App\Mail\Partner\PartnerToUser;
use App\Services\Mail\MailService;
use Carbon\Carbon;
use Closure;
use Models\Db\Jmaker\Jmaker;
use GraphQL;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Mutation;
use Symfony\Component\HttpFoundation\Request;

class SendRelanceMutation extends Mutation
{

    protected $attributes = [
        'name' => 'sendRelance'
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Jmaker'));
    }

    public function args(): array
    {
        return [
            'uuid' => ['name' => 'uuid', 'type' => Type::listOf(Type::string())],
            'objectFr' => ['name' => 'objectFr', 'type' => Type::string()],
            'messageFr' => ['name' => 'messageFr', 'type' => Type::string()],
            'objectEn' => ['name' => 'objectEn', 'type' => Type::string()],
            'messageEn' => ['name' => 'messageEn', 'type' => Type::string()],
        ];
    }

    public function resolve($root, $args)
    {
        $tabJmaker = array();
        foreach($args['uuid'] as $jmakerUuid){
            $jmaker = Jmaker::find($jmakerUuid);
            if($jmaker->state == \Ref::JMAKER_STATE_INVITED){
                $invitation = $jmaker->invitation()->first();
                $mail = MailService::pushInDB(Invite::class, $invitation->email,$invitation->token,prescriber()->name,
                                                            $jmaker->language_id == 'LANG_FR' ?$args['objectFr']:$args['objectEn'],  $jmaker->language_id == 'LANG_FR' ? $args['messageFr']:$args['messageEn']);
                event(new JmakerInvitedEvent($invitation));
            }else{
                $mail = MailService::pushInDB(PartnerToUser::class, $jmaker->email, $jmaker->uuid,
                                                $jmaker->language_id == 'LANG_FR' ? $args['objectFr']:$args['objectEn'],  $jmaker->language_id == 'LANG_FR' ? $args['messageFr']:$args['messageEn']);
            }
            event(new MailPrescriberToJmakerEvent($jmaker->uuid,Carbon::now(),$mail->uuid,\prescriber()->uuid));

            array_push($tabJmaker,$jmaker);
        }

        return $tabJmaker;
    }
}
