<?php

namespace App\GraphQL\Mutations;

use App\Events\Jmakers\MeetingDateUpdatedEvent;
use App\Mail\Jobmaker\DebriefDateNotification;
use App\Services\Mail\MailService;
use Carbon\Carbon;
use Models\Db\Jmaker\Jmaker;
use GraphQL;
use GraphQL\Type\Definition\Type;
use Models\Db\Jmaker\JmakerMeeting;
use Rebing\GraphQL\Support\Mutation;


class SendMeetingMutation extends Mutation
{

    protected $attributes = [
        'name' => 'sendMeeting'
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('JmakerMeeting'));
    }

    public function args(): array
    {
        return [
            'uuid' => ['name' => 'uuid', 'type' => Type::string()],
            'date' => ['name' => 'date', 'type' => Type::string()],
            'object' => ['name' => 'object', 'type' => Type::string()],
            'message' => ['name' => 'message', 'type' => Type::string()],
        ];
    }

    public function resolve($root, $args)
    {
        try{
            $jmaker = Jmaker::find($args['uuid']);
            transaction(function () use ($jmaker, $args) {
                $meeting_date = new \DateTime($args['date']);
                if (isset($args['message'])) {
                    $message = $args['message'];
                }
                $meeting = new JmakerMeeting();
                $meeting->uuid = generateNewUUID();
                $meeting->invited_by_prescriber_uuid = prescriber()->uuid;
                $meeting->meeting_date = Carbon::createFromFormat("d/m/Y", $meeting_date->format('d/m/Y'));
                $meeting->jmaker_uuid = $jmaker->uuid;
                $meeting->type = \Ref::MEETING_TYPE_SCHEDULED;
                $meeting->save();

                event(new MeetingDateUpdatedEvent($jmaker, $meeting));
                //Send email
                MailService::pushInDB(DebriefDateNotification::class, $jmaker->email, $jmaker->uuid, $meeting->meeting_date, $message);

                return $meeting;
            });
        }catch(\Exception $e){
            return $e;
        }

    }
}
