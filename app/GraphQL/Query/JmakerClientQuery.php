<?php
namespace App\GraphQL\Query;
use Illuminate\Support\Facades\Auth;
use Models\Db\Clients\ClientTemplate;
use Models\Db\Jmaker\Jmaker;
use GraphQL\Type\Definition\Type;
use Models\Db\Jmaker\JmakerMeeting;
use Models\Db\Jmaker\SynthesisShare;
use Models\Db\Languages\LanguageContent;
use Models\Db\Mission\Run;
use Models\Db\Prescriber\PrescriberTemplate;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;


class JmakerClientQuery extends Query
{
    protected $attributes = [
        'name'  => 'jmakerClient',
    ];

    public function authorize(array $args = [])
    {
        return Auth::guard(\Ref::INTERFACE_PARTNER)->check();
    }

    public function type()
    {
        return Type::listOf(GraphQL::type('JmakerClient'));
    }

    public function resolve($root, $args)
    {
        if (prescriber()) {
            $jmakers = Jmaker::where('prescriber_uuid','=',prescriber()->uuid)->get();
            /**Generiq relance content**/
            $where = ['code'=>'XqC9sR','lang_rid'=>\Ref::LANG_FR];
            $defaultObjFr = LanguageContent::where($where)->first();
            $where = ['code'=>'XqC9sR','lang_rid'=>\Ref::LANG_EN];
            $defaultObjEn = LanguageContent::where($where)->first();
            $where = ['code'=>'Cyyhch','lang_rid'=>\Ref::LANG_FR];
            $defaultMsgFr = LanguageContent::where($where)->first();
            $where = ['code'=>'Cyyhch','lang_rid'=>\Ref::LANG_EN];
            $defaultMsgEn = LanguageContent::where($where)->first();
            /**Fetch template client**/
            $clientRelanceTemplate = ClientTemplate::where('client_uuid','=',prescriber()->client_uuid)->first();
            if($clientRelanceTemplate instanceof ClientTemplate){
                $clientRelanceTemplate->data = json_decode($clientRelanceTemplate->data);
                $defaultJmakerRelanceObjFr = $clientRelanceTemplate->data->objectFr ? $clientRelanceTemplate->data->objectFr : $defaultObjFr->instruction['params'][0];
                $defaultJmakerRelanceObjEn = $clientRelanceTemplate->data->objectEn ? $clientRelanceTemplate->data->objectEn : $defaultObjEn->instruction['params'][0];
                $defaultJmakerRelanceMsgFr = $clientRelanceTemplate->data->messageFr ? $clientRelanceTemplate->data->messageFr : $defaultMsgFr->instruction['params'][0];
                $defaultJmakerRelanceMsgEn = $clientRelanceTemplate->data->messageEn ? $clientRelanceTemplate->data->messageEn : $defaultMsgEn->instruction['params'][0];
            }else{
                $defaultJmakerRelanceObjFr = $defaultObjFr->instruction['params'][0];
                $defaultJmakerRelanceObjEn = $defaultObjEn->instruction['params'][0];
                $defaultJmakerRelanceMsgFr = $defaultMsgFr->instruction['params'][0];
                $defaultJmakerRelanceMsgEn = $defaultMsgEn->instruction['params'][0];
            }


            foreach ($jmakers as $jmaker){
                $jmaker->name = $jmaker->firstname." ".$jmaker->lastname."-".$jmaker->email;
                $where = ['jmaker_uuid' => $jmaker->uuid, 'status_rid' => 'RUN_STATUS_FINISHED'];
                $missions = Run::where($where)->get();
                $jmaker->missions_ct = count($missions);

                $where = ['jmaker_uuid' => $jmaker->uuid, 'invited_by_prescriber_uuid' => $jmaker->prescriber_uuid];
                $meeting = JmakerMeeting::where($where)->orderBy('updated_at','desc')->first();
                if($meeting)
                {
                    $jmaker->meeting_date = $meeting->meeting_date->format('Y-m-d');
                }

                $where = ['jmaker_uuid' => $jmaker->uuid, 'prescriber_uuid' => $jmaker->prescriber_uuid];
                $synthesis = SynthesisShare::where($where)->first();
                if($synthesis)
                {
                    $jmaker->synthesis_shared_at = $synthesis->shared_at->format('Y-m-d').":".$jmaker->uuid;
                }

                $prescriberRelanceTemplate = PrescriberTemplate::where('prescriber_uuid','=',prescriber()->uuid)->first();
                if($prescriberRelanceTemplate instanceof PrescriberTemplate){
                    $prescriberRelanceTemplate->data = json_decode($prescriberRelanceTemplate->data);
                    $jmaker->default_relance_object_fr = $prescriberRelanceTemplate->data->objectFr ? $prescriberRelanceTemplate->data->objectFr : $defaultJmakerRelanceObjFr;
                    $jmaker->default_relance_object_en = $prescriberRelanceTemplate->data->objectEn ? $prescriberRelanceTemplate->data->objectEn : $defaultJmakerRelanceObjEn;
                    $jmaker->default_relance_message_fr = $prescriberRelanceTemplate->data->messageFr ? $prescriberRelanceTemplate->data->messageFr : $defaultJmakerRelanceMsgFr;
                    $jmaker->default_relance_message_en = $prescriberRelanceTemplate->data->messageEn ? $prescriberRelanceTemplate->data->messageEn : $defaultJmakerRelanceMsgEn;
                }else{
                    $jmaker->default_relance_object_fr = $defaultJmakerRelanceObjFr;
                    $jmaker->default_relance_object_en = $defaultJmakerRelanceObjEn;
                    $jmaker->default_relance_message_fr = $defaultJmakerRelanceMsgFr;
                    $jmaker->default_relance_message_en = $defaultJmakerRelanceMsgEn;
                }



                //get reminders count
                $jmakerEvents = $jmaker->events()->orderBy("date","desc")->get();
                if($jmakerEvents->isNotEmpty()) {
                    $jmaker->recall_at = $reminderAtHtml = $jmakerEvents->first()->date;
                    $jmaker->recall_ct = $jmakerEvents->count();
                }

            }
            return $jmakers;
        }

        return Jmaker::all();
    }
}