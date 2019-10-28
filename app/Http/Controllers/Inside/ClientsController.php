<?php

namespace App\Http\Controllers\Inside;

use App\Events\Jmakers\JmakerInvitedEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Inside\Clients\ClientDetailController;
use App\Mail\Custom;
use App\Mail\Jobmaker\Invite;
use App\Services\Mail\MailService;
use App\Services\Media\MediaService;
use Carbon\Carbon;
use Exception;
use FrenchFrogs\Container\Javascript;
use FrenchFrogs\Core\FrenchFrogsController;
use FrenchFrogs\Form\Element\Button;
use FrenchFrogs\Table\Table\Table;
use Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Image;
use League\Csv\Reader;
use Models\Acl\Inside;
use Models\Acl\Partner;
use Models\Db\Clients\Client;
use Models\Db\Clients\ClientCampaign;
use Models\Db\Clients\ClientCustomization;
use Models\Db\Jmaker\Jmaker;
use Models\Db\Jmaker\JmakerEvent;
use Models\Db\Jmaker\JmakerHistory;
use Models\Db\Jmaker\JmakerInvitation;
use Models\Db\Jmaker\JmakerMeeting;
use Models\Db\Jmaker\JmakerNextNotificationMeeting;
use Models\Db\Jmaker\JmakerNextNotificationSubscription;
use Models\Db\Jmaker\JmakerNextNotificationWorkshop;
use Models\Db\Languages\LanguageGroup;
use Models\Db\Languages\Languages;
use Models\Db\Mission\Mission;
use Models\Db\Mission\Run;
use Models\Db\Mission\Sequence;
use Models\Db\Operator\Operator;
use Models\Db\Prescriber\Prescriber;
use Models\Db\User\MissionStep;
use Ref;
use Throwable;
use Uuid;
use function form;
use function generateNewUUID;
use function h;
use function js;
use function modal;
use function query;
use function ref;
use function request;


/**
 * Class ClientsController
 * @package App\Http\Controllers\Inside
 */
class ClientsController extends Controller
{

    /**
     * Demo account.
     */
    const JOBMAKER_TEST = [
        Sequence::SEQUENCE_MOBILITY => ['79764C4F6FFF7B8DF04858B8F41DF86D' => 'Next Move Inside'], //7589
        Sequence::SEQUENCE_NEXT_MOVE_INSIDE_RSI => ['846EF855BCC02EED6EC20271461187CE' => 'Next Move Inside RSI'], //10983
        Sequence::SEQUENCE_MOBILITY_ECO => [
            '5C88FB238F63D8306C91CC7956234DDE' => 'Next Move Inside +', //6857
            '3E0065620D0E127EBA4A88A570B419E0' => 'Next Move Inside + : Etape 7' //7025
        ],
        Sequence::SEQUENCE_EXECUTIVE => ['3DCFB45827E0A42EBFD311383AE50000' => 'Next Move'], //7012
        Sequence::SEQUENCE_FOLLOWING => ['55EFEBF46FAC2BCE4E9E62AC7805225A' => 'Next Move Graduate'], //7010
    ];

    const JOBMAKER_TEST_FR_TO_EN = [
        '79764C4F6FFF7B8DF04858B8F41DF86D' => '0A07D45A123E300507299819FED31BEC', //7589 => 9832
        '5C88FB238F63D8306C91CC7956234DDE' => '909C0413F10C0FCB68D452ECDDD7A9B2', //6857 => 9836
        '3E0065620D0E127EBA4A88A570B419E0' => '8EC120A949BFCA9E4EB3CE31CB7D243E', //7025 => 7025
        '3DCFB45827E0A42EBFD311383AE50000' => '3DCFB45827E0A42EBFD311383AE50000', //7012 => 7012
        '55EFEBF46FAC2BCE4E9E62AC7805225A' => '55EFEBF46FAC2BCE4E9E62AC7805225A', //7010 => 7010
    ];

    use FrenchFrogsController;

    /**
     * Create demo jMaker
     * TODO Fix jmaker vs operator for which create the demo account.
     *
     * @param $clientUUID
     * @param $clientCampaignUUID
     * @return string
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function postCreateJMakerDemo($clientUUID, $clientCampaignUUID)
    {
        // validation
        $this->authorize(Inside::PERMISSION_CLIENT);
        $this->validate($request = $this->request(), [
            'clientUUID' => 'required|exists:client,uuid',
            'clientCampaignUUID' => 'required|exists:client_campaign,uuid'
        ]);

        $client = Client::findOrFail($clientUUID);
        $campaign = ClientCampaign::findOrFail($clientCampaignUUID);

        $AllLanguages = ['LANG_FR' => "Français", 'LANG_EN' => "Anglais"];

        // FORM
        $form = form()->enableRemote();
        $form->setLegend('Campagne');

        // ELEMENT
        $form->addSelect('jmaker_uuid_model', 'Modèle', static::JOBMAKER_TEST[$campaign->mission_sequence_sid]);
        $form->addText('firstname', 'Prénom');
        $form->addText('lastname', 'Nom');
        $form->addSelect('language_id', 'Langue', $AllLanguages, true);
        $form->addText('city', 'Ville');
        $form->addSubmit('Enregistrer');

        // TRAITEMENT
        if ($request->has('Enregistrer')) {
            $data = $request->all();
            $form->valid($data);
            if ($form->isValid()) {
                $data = $form->getFilteredValues();
                try {

                    transaction(function () use ($request, $client, $data, $campaign) {

                        if ($data['language_id'] == 'LANG_EN') {
                            $data['jmaker_uuid_model'] = static::JOBMAKER_TEST_FR_TO_EN[$data['jmaker_uuid_model']];
                        }

                        $prescribers = $client->prescribers()->get();
                        $firstAdminPrescriber = null;

                        foreach ($prescribers as $prescriber) {
                            /**@var Prescriber $prescriber */
                            if ($prescriber->can(Partner::PERMISSION_PARTNER_ADMIN)) {
                                $firstAdminPrescriber = $prescriber;
                                break;
                            }
                        }

                        // JOBMAKER
                        $original = Jmaker::find($data['jmaker_uuid_model']);
                        $jmaker = $original->replicate();
                        $jmaker->uuid = generateNewUUID();
                        $jmaker->client_uuid = $client->uuid;
                        $jmaker->campaign_uuid = $campaign->uuid;
                        $jmaker->contract_uuid = $client->contracts()->get()[0]->uuid;
                        $jmaker->prescriber_uuid = $firstAdminPrescriber->uuid;
                        $jmaker->firstname = $data['firstname'];
                        $jmaker->lastname = $data['lastname'];
                        $jmaker->language_id = $data['language_id'];
                        $jmaker->username = $jmaker->firstname . ' ' . $jmaker->lastname;
                        $jmaker->username_canonical = str_slug($jmaker->username);
                        $jmaker->email = str_slug($jmaker->username) . '_' . rand(0, 5000) . '@yopmail.com';
                        $jmaker->password = bcrypt(str_random(12));
                        $jmaker->last_session_at = null;
                        $jmaker->created_at = Carbon::now();
                        $jmaker->registred_at = $jmaker->created_at;
                        $jmaker->last_page_at = null;
                        $jmaker->save();

                        // STEP
                        foreach ($original->steps->pluck('id') as $step) {
                            MissionStep::create(['jmaker_uuid' => $jmaker->uuid, 'mission_step_id' => $step]);
                        }

                        // WAY
                        $wayTmp = $jmaker->way()->make();
                        $wayTmp->uuid = generateNewUUID();
                        $wayTmp->mission_sequence_sid = $campaign->mission_sequence_sid;
                        $wayTmp->save();

                        // INVITATION
                        $invitation = new JmakerInvitation();
                        $invitation->uuid = generateNewUUID();
                        $invitation->token = str_random(60);
                        $invitation->campaign_uuid = $campaign->getKey();
                        $invitation->email = $jmaker->email;
                        $invitation->language_id = $data['language_id'];
                        $invitation->invited_by_prescriber_uuid = null;
                        $invitation->jmaker_uuid = $jmaker->getKey();
                        $invitation->is_started = true;
                        $invitation->started_at = Carbon::now();
                        $invitation->is_completed = true;
                        $invitation->completed_at = Carbon::now();
                        $invitation->data = [
                            'email' => $jmaker->email,
                            'firstname' => $jmaker->firstname,
                            'lastname' => $jmaker->lastname,
                        ];

                        // If client have partner property we add.
                        if ($client->partner) {
                            $invitation->partner_id = $client->partner->id;
                        }

                        // sauvagrde de l'invitation
                        $invitation->save();


                        // RUNS
                        /**@var Collection|Run[] $runs */
                        $runs = Run::where('jmaker_uuid', $data['jmaker_uuid_model'])->get();

                        foreach ($runs as $run) {

                            // creation du nouveau run
                            $new = $run->replicate();
                            $new->jmaker_uuid = $jmaker->uuid;
                            $new->save();
                        }
                    });

                    js()->success()->closeRemoteModal()->reloadDataTable();
                } catch (Exception $e) {
                    dd($e);
                    js()->error($e->getMessage());
                }
            }
        } else {
            $form->populate([
                    'firstname' => 'Demo',
                ]
            );
        }

        return response()->modal($form);
    }


    /**
     * Add and edit new campaign
     * @param $clientUUID
     * @param null $clientCampaignUUID
     * @return mixed
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function postCampaign($clientUUID, $clientCampaignUUID = null)
    {
        // validation
        $this->authorize(Inside::PERMISSION_CLIENT);
        $this->validate($request = $this->request(), [
            'clientUUID' => 'required|exists:client,uuid',
            'clientCampaignUUID' => 'exists:client_campaign,uuid'
        ]);

        // Recupération du client
        $client = Client::findOrFail($clientUUID);

        // Get campaign from database or create one.
        $campaign = ClientCampaign::findOrNew($clientCampaignUUID);

        // Check if campaign has invitation
        $has_invitation = $campaign->invitations()->firstOrNew([])->exists;

        // FORM
        $form = form()->enableRemote();
        $form->setLegend('Campagne');

        // ELEMENT
        $sequences = pairs('mission_sequence', 'sid', 'name');

        $sequence = $form->addSelect('mission_sequence_sid', 'Parcours', $sequences, !$has_invitation);
        $has_invitation && $sequence->enableReadOnly();
        $form->addText('name', 'Nom');

        $adminPrescribers = $client->prescribers()->join('prescriber_permission_prescriber as ppp', function ($join) {
            $join->on('ppp.prescriber_uuid', '=', 'uuid')
                ->where('ppp.prescriber_permission_id', Partner::PERMISSION_PARTNER_ADMIN);
        })->pluck('name', 'uuid');

        $form->addSelect('created_by_prescriber_uuid', 'Créé par', $adminPrescribers, true)->setPlaceholder('Prescripteur Admin');

        $form->addSeparator();
        $prescribers = $client->prescribers()->orderBy('name')->pluck('name', 'uuid')->toArray();

        $form->addCheckbox('prescribers', 'Utilisateurs', $prescribers);


        $form->addSeparator();
        $form->addTitle('Langages');
        // Recuperation de patch de culture applicable
        $languageGroups = LanguageGroup::where('collection', LanguageController::GROUP_COLLECTION_LANGUAGE)->whereNotNull('config')->get();

        // On filte sur les groupe de language campagne
        $languageGroups->filter(function (LanguageGroup $languageGroup) {
            $config = $languageGroup->config;
            return array_get($config, 'type', false) == LanguageGroupController::TYPE_CAMPAIGN;
        })->each(function (LanguageGroup $languageGroup) use (&$form) {
            if ($question = array_get($languageGroup->config, 'question', false)) {
                $form->addBoolean($languageGroup->uuid, $question)->setValue(false);
            }
        });


        $form->addSubmit('Enregistrer');

        // TRAITEMENT
        if ($request->has('Enregistrer')) {
            $data = $request->all();

            $form->valid($data);
            if ($form->isValid()) {

                $data = $form->getFilteredValues();

                try {
                    //If the campain doesn't exist we init the Client and WHO create the campain.
                    if (!$campaign->exists) {
                        $campaign->uuid = generateNewUUID();
                        $campaign->client_uuid = $clientUUID;
                    }

                    $campaign->created_by_prescriber_uuid = $data['created_by_prescriber_uuid'];

                    if (!$has_invitation) {
                        $campaign->mission_sequence_sid = $data['mission_sequence_sid'];
                    }

                    // Recuperation des culture
                    $culture = [];
                    collect($data)->each(function ($v, $uuid) use (&$culture) {
                        if (!empty($v) && Uuid::validate($uuid)) {
                            $culture[] = $uuid;
                        }
                    });

                    $campaign->adaptation = $culture;
                    $campaign->name = $data['name'];
                    $campaign->save();


                    $newPrescibers = [];

                    if(is_array($data['prescribers'])) {
                        $newPrescibers = $data['prescribers'];
                    }

                    $campaign->prescribers()->sync($newPrescibers);

                    js()->success()->closeRemoteModal()->reloadDataTable();
                } catch (Exception $e) {
                    js()->error($e->getMessage());
                }
            }
        } elseif ($campaign->exists) {

            $data = $campaign->toArray();

            foreach ((array)$campaign->adaptation as $group) {
                $data[$group] = true;
            }

            $data['prescribers'] = $campaign->prescribers()->pluck('uuid')->toArray();

            $form->populate($data);
        } else {
            $data = [];

            foreach ((array)$client->adaptation as $group) {
                $data[$group] = true;
            }

            $form->populate($data);
        }

        return response()->modal($form);
    }




    /**
     * Send Custom email to JMaker.
     * @param $jmakerUUID
     * @return mixed
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function postMail($jmakerUUID)
    {
        // REQUEST
        $this->authorize(Inside::PERMISSION_JOBMAKER_USER);
        $this->validate($request = $this->request(), [
            'jmakerUUID' => 'required|exists:jmaker,uuid'
        ]);
        // MODEL
        $jmaker = Jmaker::findOrFail($jmakerUUID);
        $invitation = $jmaker->invitation()->first();

        // FORM
        $form = form()->enableRemote();
        $form->setLegend('Envoyer un email');

        // JOBMAKER

        // ELEMENT
        $form->addLabel('email', 'Email')->setValue($jmaker->email);
        $form->addSeparator();
        $form->addText('subject', 'Sujet');
        $form->addTextarea('custom_message', 'Message')
            ->addAttribute('rows', 8)
            ->addAttribute('maxlength', 800);
        $form->addText('cta_url', 'Lien CTA', false)
            ->addLaravelValidator('url')
            ->setDescription('Si vous ne remplissez pas ce champ, le mail ne contientra pas de CTA');
        $form->addText('cta_label', 'Libellé CTA', false);
        $form->addSubmit('Envoyer');

        // TRAITEMENT
        if ($request->has('_token')) {
            $data = $request->all();
            $form->valid($data);
            if ($form->isValid()) {
                $data = $form->getFilteredValues();
                try {

                    $cta_url = empty($data['cta_url']) ? null : $data['cta_url'];
                    $cta_label = empty($data['cta_label']) ? null : $data['cta_label'];
                    MailService::pushInDB(Custom::class, $jmaker->email, $jmaker->email, $data['subject'], $data['custom_message'], $cta_url, $cta_label, \operator()->uuid);

                    js()->success()->closeRemoteModal()->reloadDataTable();
                } catch (Exception $e) {
                    js()->error($e->getMessage());
                }
            }
        } else {
            $form->populate(['cta_url' => route('jobmaker.dashboard')]);
        }

        return response()->modal($form);
    }


    /**
     * Get form for new invitation
     * Send new invitation
     * Re Send invitation
     * @param $clientUuid
     * @return mixed
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function postInvit($clientUuid)
    {
        $this->authorize(Inside::PERMISSION_JOBMAKER_USER);
        $this->validate($request = $this->request(), [
            'clientUuid' => 'required|exists:client,uuid',
        ]);

        /** @var Client $client */
        $client = Client::findOrFail($clientUuid);
        $form = form()->enableRemote();
        $form->setLegend('Invitation');
        // ELEMENT
        $email = $form->addEmail('email', 'Email');

        $form->addText('firstname', 'Prénom', false);
        $form->addText('lastname', 'Nom', false);

        //If client have many languages we had a language selector
        //else we use the unique language for invitation
        $languages = $client->languages()->orderBy('id', 'desc')->get();

        $languages = $languages->transform(function (Languages $language) {
            //Hard coding due to local feature missing in inside interface
            if ($language->id == 'LANG_FR') {
                return ['name' => "Français", 'id' => $language->id];
            } elseif ($language->id == 'LANG_EN') {
                return ['name' => "Anglais", 'id' => $language->id];
            }
        })->pluck('name', 'id');

        $form->addSelect('language_id', 'Langue', $languages);

        $form->addSeparator();
        $form->addDate('meeting_date', 'Date de debrief', false)
            //->addLaravelValidator('date')
            ->setDescription('Une date de debrief nécessite de choisir un prescripteur');

        $form->addTextarea('message', 'Message', false)
            ->addAttribute('rows', 8)
            ->addAttribute('maxlength', 800);
        $form->addSeparator();
        $campaigns = $client->campaigns()->orderBy('name')->pluck('name', 'uuid');
        $form->addSelect('campaign_uuid', 'Campagne', $campaigns);
        $prescribers = $client->prescribers()->pluck('name', 'uuid');
        $form->addSelect('invited_by_prescriber_uuid', 'Prescripteur', $prescribers, true)->setPlaceholder();
        $form->addSeparator();
        $form->addText('subject_custom', 'Sujet', false);
        $form->addBoolean('from_custom', 'Modifié expediteur')
            ->setDescription('Si activé, l\'expediteur du mail sera invitation@jobmaker.fr')
            ->setValue(true);
        $form->addTextarea('message_custom', 'Message personnalisé', false)
            ->addAttribute('rows', 8)
            ->addAttribute('maxlength', 800)
            ->setDescription('Si ce message est rempli, il remplacera le message original');

        $form->addSubmit('Enregistrer et Envoyer')->setName('send');
        if ($request->has('_token')) {
            $data = $request->all();
            $validator = Validator::make($request->all(), [
                'language_id' => 'required',
                'email' => 'required|unique:jmaker_invitation',
                'campaign_uuid' => 'required',
                'invited_by_prescriber_uuid' => 'required',
            ]);
            if (!$validator->fails()) {
                try {
                    transaction(function () use ($client, $data, $request) {

                        // On determine si on envoie le mail
                        $email = $request->has('send');
                        $meeting_date = $data['meeting_date'];

                        $invitation = new JmakerInvitation();
                        $invitation->uuid = generateNewUUID();
                        $invitation->token = str_random(60);
                        $email = true;

                        $meeting = new JmakerMeeting();
                        $meeting->uuid = generateNewUUID();
                        $meeting->invited_by_prescriber_uuid = $data['invited_by_prescriber_uuid'];
                        $meeting->meeting_date = Carbon::createFromFormat('d/m/Y', $meeting_date);
                        $meeting->type = Ref::MEETING_TYPE_INVITATION;

                        $invitation->email = $data['email'];
                        $invitation->data = json_encode($data);
                        $languages = $client->languages()->get();

                        if ($languages->count() == 1) {
                            $languageId = $languages->first()->id;
                        } else {
                            $validData = $client->languages()->get()->pluck('id')->all();

                            //Check if $data['language_id'] in valid language;
                            //If not we use default language FR
                            if (in_array($data['language_id'], $validData)) {
                                $languageId = $data['language_id'];
                            } else {
                                $languageId = Ref::LANG_FR;
                            }
                        }

                        $invitation->language_id = $languageId;
                        //Creation of the Jmaker
                        $jmaker = new Jmaker();
                        $jmaker->uuid = generateNewUUID();
                        $jmaker->client_uuid = $client->uuid;
                        $jmaker->contract_uuid = $client->contracts()->first()->uuid;
                        $jmaker->campaign_uuid = $data['campaign_uuid'];
                        $jmaker->email = $data['email'];
                        $jmaker->language_id = $languageId;
                        $jmaker->prescriber_uuid = $data['invited_by_prescriber_uuid'];
                        $jmaker->locked = false;
                        $jmaker->expired = false;
                        $jmaker->state = Ref::JMAKER_STATE_INVITED;
                        $jmaker->credentials_expired = false;
                        $jmaker->created_at = Carbon::now();
                        $jmaker->registred_at = null;
                        $jmaker->save();

                        $invitation->jmaker_uuid = $jmaker->uuid;
                        // Creatrion de l'invitation
                        $invitation->campaign_uuid = $data['campaign_uuid'];
                        $invitation->invited_by_prescriber_uuid = $data['invited_by_prescriber_uuid'];
                        $invitation->jmaker_uuid = $jmaker->uuid;

                        // si il y a une campagne, on l'attribu
                        if ($client->partner) {
                            $invitation->partner_id = $client->partner->id;
                        }
                        $invitation->save();

                        if (!empty($meeting)) {
                            $meeting->invitation_uuid = $invitation->uuid;
                            $meeting->save();
                        }
                        // envoie du mail
                        $email && MailService::pushInDB(Invite::class, $invitation->email, $invitation->token, $request->get('from_custom'), $request->get('subject_custom'), $request->get('message_custom'));
                        $email && event(new JmakerInvitedEvent($invitation));
                    });

                    js()->success()->closeRemoteModal()->reloadDataTable();
                } catch (Exception $e) {
                    js()->error($e->getMessage());
                }
            } else {
                $errors = $validator->errors();
                switch ($errors){
                    case $errors->has('email'):
                        js()->error($validator->errors()->get('email')[0]);
                        break;
                    case $errors->has('language_id'):
                        js()->error($validator->errors()->get('language_id')[0]);
                        break;
                    case $errors->has('campaign_uuid'):
                        js()->error($validator->errors()->get('campaign_uuid')[0]);
                        break;
                    case $errors->has('invited_by_prescriber_uuid'):
                        js()->error($validator->errors()->get('invited_by_prescriber_uuid')[0]);
                        break;
                }
            }
        }
        return response()->modal($form);

    }
    /**
     * Get form for jmakerInvitation update
     * Edit JmakerInvitation information
     * @param $clientUUID
     * @param $jmakerInvitationUUID
     * @return mixed
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function postJmakerInvit($clientUUID,$jmakerInvitationUUID = null)
    {
        $this->authorize(Inside::PERMISSION_JOBMAKER_USER);
        $this->validate($request = $this->request(), [
           'jmakerInvitationUUID' => 'exists:jmaker_invitation,uuid'
       ]);

        $jmakerInvitation = JmakerInvitation::find($jmakerInvitationUUID);
        $jmaker = $jmakerInvitation->jmaker()->first();
        $meeting = JmakerMeeting::where('invitation_uuid',$jmakerInvitationUUID)->first();
        $client = Client::find($clientUUID);
        $languages = $client->languages()->orderBy('id', 'desc')->get();
        $languages = $languages->transform(function (Languages $language) {
            //Hard coding due to local feature missing in inside interface
            if ($language->id == 'LANG_FR') {
                return ['name' => "Français", 'id' => $language->id];
            } elseif ($language->id == 'LANG_EN') {
                return ['name' => "Anglais", 'id' => $language->id];
            }
        })->pluck('name', 'id');

        $campaigns = $client->campaigns()->get()->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE);
        $campaigns = $campaigns->transform(function (ClientCampaign $campaign) {
            return ['name' => $campaign->name, 'uuid' => $campaign->uuid];
        })->pluck('name', 'uuid');

        $prescribers = $client->prescribers()->get();
        $prescribers = $prescribers->transform(function (Prescriber $prescriber) {
            return ['name' => $prescriber->name, 'uuid' => $prescriber->uuid];
        })->pluck('name', 'uuid');

        $form = form()->enableRemote();
        $form->setLegend('Modification de l\'invitation de '. $jmaker->firstname . ' ' .$jmaker->lastname);
        $form->addEmail('email', 'Email', true);
        $form->addText('firstname', 'Prénom', true);
        $form->addText('lastname', 'Nom', true);
        $form->addSelect('language_id', 'Langue', $languages, true);
        $form->addSelect('campaign_uuid', 'Campagne', $campaigns, true);
        $form->addSelect('prescriber_uuid', 'Prescripteur', $prescribers, true);
        $form->addSeparator();
        $form->addDate('meeting_date', 'Date de debrief', false)
            ->setDescription('Une date de debrief nécessite de choisir un prescripteur');

        $form->addTextarea('message', 'Message', false)
            ->addAttribute('rows', 8)
            ->addAttribute('maxlength', 800);
        $form->addSeparator();

        $form->addSubmit('Relancer');

        if ($request->has('_token')) {
            $data = $request->all();
            $form->valid($data);

            if ($form->isValid()) {
                unset($data['__prescriber_uuid']);
                unset($data['__campaign_uuid']);
                unset($data['__jmakerUUID']);
                unset($data['__clientUUID']);
                unset($data['___token']);
                unset($data['_token']);

                $campaign = ClientCampaign::find($data['campaign_uuid']);
                $campaignPrescribers = $campaign->prescribers()->get();
                $prescribersRule = array();
                foreach ($campaignPrescribers as $key => $p) {
                    $prescribersRule[$key] = $p->uuid;
                }
                $messages = [
                    'in' => 'Le prescripteur sélectionné n\'est pas rattaché à la campagne choisie',
                ];
                $validator = Validator::make($request->all(), [
                    'email' => 'string|email',
                    'prescriber_uuid' => [
                        Rule::in($prescribersRule),
                    ],
                ], $messages);
                if (!$validator->fails()) {
                    try {
                        transaction(function () use ($jmaker, $jmakerInvitation, $meeting, $client, $data, $request) {

                            //Modification jmaker
                            $jmaker->firstname = $data['firstname'];
                            $jmaker->lastname = $data['lastname'];
                            $jmaker->email = $data['email'];
                            $jmaker->language_id = $data['language_id'];
                            $jmaker->prescriber_uuid = $data['prescriber_uuid'];
                            $jmaker->campaign_uuid = $data['campaign_uuid'];
                            $jmaker->save();

                            //Modification jmakerInvitation
                            $jmakerInvitation->data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
                            $jmakerInvitation->email = $data['email'];
                            $jmakerInvitation->language_id = $data['language_id'];
                            $jmakerInvitation->invited_by_prescriber_uuid = $data['prescriber_uuid'];
                            $jmakerInvitation->campaign_uuid = $data['campaign_uuid'];
                            $jmakerInvitation->save();

                            //Modification meeting
                            if ($data['meeting_date']) {
                                if ($meeting) {
                                    $meeting->meeting_date = Carbon::createFromFormat('d/m/Y', $data['meeting_date']);
                                } else {
                                    $meeting = new JmakerMeeting();
                                    $meeting->uuid = generateNewUUID();
                                    $meeting->invitation_uuid = $jmakerInvitation->uuid;
                                    $meeting->invited_by_prescriber_uuid = $data['prescriber_uuid'];
                                    $meeting->meeting_date = Carbon::createFromFormat('d/m/Y', $data['meeting_date']);
                                    $meeting->type = Ref::MEETING_TYPE_INVITATION;
                                }
                                $meeting->save();
                            }

                            MailService::pushInDB(Invite::class, $jmakerInvitation->email, $jmakerInvitation->token);
                            event(new JmakerInvitedEvent($jmakerInvitation));

                            js()->success()->closeRemoteModal()->reloadDataTable();
                        });
                    } catch (Exception $e) {
                        js()->error($e->getMessage());
                    }
                } else {
                    js()->error("Erreur: " . $validator->errors()->getMessageBag()->get('prescriber_uuid')[0]);
                }
            }
        } else {

            $form->populate([
                    'firstname' => $jmaker->firstname,
                    'lastname' => $jmaker->lastname,
                    'email' => $jmakerInvitation->email,
                    'language_id' => $jmakerInvitation->language_id,
                    'campaign_uuid' => $jmakerInvitation->campaign_uuid,
                    'prescriber_uuid' => $jmakerInvitation->invited_by_prescriber_uuid,
                    'meeting_date' => $meeting ? $meeting->meeting_date : "",
                    'message' => $jmakerInvitation->data ? $jmakerInvitation->data['message'] : "",
                ]
            );
        }

        return response()->modal($form);
    }
    /**
     * Get form for jmaker update
     * Edit Jmaker information
     * @param $clientUUID
     * @param $jmakerUUID
     * @return mixed
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function postJMaker($clientUUID,$jmakerUUID = null)
    {
        $this->authorize(Inside::PERMISSION_JOBMAKER_USER);
        $this->validate($request = $this->request(), [
            'jmakerUUID' => 'exists:jmaker,uuid',
        ]);
        /** @var Jmaker $jmaker */
        $jmaker = Jmaker::find($jmakerUUID);
        $client = $jmaker->client()->first();
        // FORM
        $form = form()->enableRemote();
        $form->setLegend('Modification de ' . $jmaker->firstname . ' ' .$jmaker->lastname);
        $email = $form->addEmail('email', 'Email', false);
        $form->addText('firstname', 'Prénom', false);
        $form->addText('lastname', 'Nom', false);

        $languages = $client->languages()->orderBy('id', 'desc')->get();

        $languages = $languages->transform(function (Languages $language) {
            //Hard coding due to local feature missing in inside interface
            if ($language->id == 'LANG_FR') {
                return ['name' => "Français", 'id' => $language->id];
            } elseif ($language->id == 'LANG_EN') {
                return ['name' => "Anglais", 'id' => $language->id];
            }
        })->pluck('name', 'id');

        $form->addSelect('language_id', 'Langue', $languages,false);

        /** @var  $jmakerCampaign */
        $jmakerCampaign = $jmaker->campaign()->first();
        if($jmakerCampaign instanceof ClientCampaign){
            if(!(null == $jmakerCampaign->prescribers)){
                /** @var  $prescribers */
                $prescribers = Prescriber::whereIn('uuid',$jmakerCampaign->prescribers)->get();
                $prescribers = $prescribers->transform(function (Prescriber $prescriber) {
                    return ['name' => $prescriber->name, 'uuid' => $prescriber->uuid];
                })->pluck('name', 'uuid');

                $form->addSelect('prescriber_uuid', 'Prescripteur', $prescribers,false)->setPlaceholder($jmaker->prescriber()->first()->name)->setValue($jmaker->prescriber_uuid);
            }
        }

        $form->addSeparator();

        $form->addSubmit('Modifier');

        // TRAITEMENT
        if ($request->has('_token')) {
            $data = $request->all();
            $validator = Validator::make($request->all(), [
                'email' => 'string|email'
            ]);
            if (!$validator->fails()) {
                if($data['prescriber_uuid'] != $jmaker->prescriber_uuid){
                    /** @var  $historyJmaker */
                    $historyJmaker = new JmakerHistory();
                    $historyJmaker->jmaker_uuid = $jmaker->uuid;
                    $historyJmaker->client_uuid = $jmaker->client_uuid;
                    $historyJmaker->prescriber_uuid = $jmaker->prescriber_uuid;
                    $historyJmaker->campaign_uuid = $jmaker->campaign_uuid;
                    $historyJmaker->contract_uuid = $jmaker->contract_uuid;

                    $historyJmaker->save();
                }

                /** @var  $jmaker */
               $jmaker->firstname = $data['firstname'];
               $jmaker->lastname = $data['lastname'];
               $jmaker->email = $data['email'];
               $jmaker->language_id = $data['language_id'];
               $jmaker->prescriber_uuid = $data['prescriber_uuid'];

               $jmaker->save();
                js()->success()->closeRemoteModal()->reloadDataTable();
            } else {
                $errors = $validator->errors();

                js()->error($validator->errors()->get('email')[0]);

                }
            }

        js()->appendJs('form input#email', 'change', "function() {
    
            _firstname = $('form input#firstname');
            _lastname = $('form input#lastname');
            
            if (_firstname.val() + _lastname.val() == '') {
                var pattern = /([^\\.]+)\\.([^.]+)@.+/g;
                var res = pattern.exec($(this).val());
                if (res != null) {
                    _firstname.val(ucfirst(res[1]));
                    _lastname.val(ucfirst(res[2]));
                }
            }
        }"
        );
        $form->populate([
                'firstname' => $jmaker->firstname,
                'lastname' => $jmaker->lastname,
                'email' => $jmaker->email,
                'language_id' => $jmaker->language_id,
                'prescriber_uuid' => $jmaker->prescriber_uuid
            ]
        );
        return response()->modal($form);
    }

    /**
     * Change client statis from inactive to prospect
     *
     * @param $clientUUID
     * @return string
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function postProspect($clientUUID)
    {

        // VALIDATION
        $this->authorize(Inside::PERMISSION_CLIENT);
        $this->validate($this->request(), [
            'clientUUID' => [
                'required',
                Rule::exists('client', 'uuid')->where(function (Builder $query) {
                    $query->where('client_status_rid', Ref::CLIENT_STATUS_SUSPECT);
                })
            ]
        ]);
        $client = Client::findOrNew($clientUUID);

        // FORM
        $form = form()->enableRemote();
        $form->setLegend('Passage à prospect');

        if (!$client->contacts()->where('contact_type_rid', Ref::CONTACT_TYPE_SALES)->first()) {
            return '<div class="callout callout-danger">
                        <h4>Pas de contact commercial</h4>
                        <p>non, non , non, vous ne pouvez pas passer en prospect s\'il n\'y a pas de contact commercial</p>
                    </div>';
        } else {

            // ELEMENT
            $form->addText('name', 'Nom');
            $form->addSelect('client_type_rid', 'Type', ref('clients.types')->pairs());
            $form->addSubmit('Envoyer');

            // TRAITEMENT
            if (request()->has('Envoyer')) {
                $data = request()->all();
                $form->valid($data);
                if ($form->isValid()) {
                    $data = $form->getFilteredValues();
                    try {
                        // sauvegarde
                        $client->client_status_rid = Ref::CLIENT_STATUS_PROSPECT;
                        $client->client_type_rid = $data['client_type_rid'];
                        $client->name = $data['name'];
                        $client->active_at = Carbon::now();
                        $client->saveOrFail();

                        js()->success()->closeRemoteModal()->reload();
                    } catch (Exception $e) {
                        js()->error($e->getMessage());
                    }
                }
            } else {
                $form->populate($client->toArray());
            }
        }

        return response()->modal($form);
    }


    /**
     * Change client status from inactive to prospect
     *
     * @param $clientUUID
     * @return string
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function postActive($clientUUID)
    {
        $this->authorize(Inside::PERMISSION_CLIENT);
        $this->validate($request = $this->request(), [
            'clientUUID' => [
                'required',
                Rule::exists('client', 'uuid')->where(function ($query) {
                    $query->where('client_status_rid', Ref::CLIENT_STATUS_PROSPECT);
                })
            ]
        ]);
        $client = Client::findOrNew($clientUUID);


        if (!$client->invoices()->first()) {
            return '<div class="callout callout-danger">
                        <h4>Pas de proposition commerciale</h4>
                        <p>non, non , non, vous ne pouvez pas passer le statut En négociation si vous n\'avez pas envoyé de proposition commerciale</p>
                    </div>';
        } else {

            // MODAL
            $modal = modal(null, 'Etes vous sûr de vouloir cette fiche en Prospect : <b>' . $client->name . '</b>');
            $button = (new Button('yes', 'Actif !'))
                ->setOptionAsDanger()
                ->enableCallback('post')
                ->addAttribute('href', action_url(static::class, __FUNCTION__, func_get_args(), ['actif' => true]));
            $modal->appendAction($button);

            // TRAITEMENT
            if ($request->has('actif')) {

                try {
                    $client->client_status_rid = Ref::CLIENT_STATUS_ACTIVE;
                    $client->prospect_at = Carbon::now();
                    $client->saveOrFail();

                    js()->success()->closeRemoteModal()->reload();
                } catch (Exception $e) {
                    js()->error($e->getMessage());
                }
                return js();
            }
        }

        return response()->modal($modal);
    }

    /**
     * Use to Link an alone prescriber to a existing client.
     * @param $id
     * @return mixed
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function postLink($id)
    {
        $this->authorize(Inside::PERMISSION_USER);
        $this->validate($request = $this->request(), [
            'id' => 'required|exists:client,uuid'
        ]);

        // Recuperation du client
        /** @var Client $client */
        $client = Client::findOrNew($id);

        // FORM
        $form = form()->enableRemote();
        $form->setLegend('Utilisateur');


        $prescribers = Prescriber::where('interface_rid', Ref::INTERFACE_PARTNER)->pluck('name', 'uuid');
        $form->addSelect('prescriber_uuid', 'Utilisateur', $prescribers);
        $form->addSubmit('Enregistrer');

        // enregistrement
        if ($request->has('Enregistrer')) {
            $form->valid($request->all());
            if ($form->isValid()) {
                $data = $form->getFilteredAliasValues();
                try {
                    // recuperation de l'utilistateur
                    $prescriber = Prescriber::findOrFail($data['prescriber_uuid']);

                    // on verifie que l'utilisateur n'est lié a aucun client
                    if ($prescriber->client_uuid) {
                        throw new Exception('Cette utilistateur est déjà lié à un client');
                    }

                    $prescriber->client_uuid = $client->uuid;

                    $contract = $client->contracts()->first();

                    if ($contract) {
                        $prescriber->contract_uuid = $contract->uuid;
                    }

                    $prescriber->save();

                    js()->success()->closeRemoteModal()->reloadDataTable();
                } catch (Exception $e) {
                    js()->error($e->getMessage());
                }
            }
        }

        return response()->modal($form);
    }


    /**
     * Download synthesis
     * @param $id
     * @return ResponseFactory|Response
     * @throws AuthorizationException
     * @throws FileNotFoundException
     * @throws ValidationException
     */
    public function getRapport($id)
    {
        //RULER
        $this->authorize(Inside::PERMISSION_JOBMAKER_WAY);
        $this->validate($this->request(), ['id' => 'required|exists:way,jmaker_uuid']);

        // Find jmaker
        $jobmaker = Jmaker::findOrFail($id);

        //Test if PDF file exist
        $exists = Storage::disk('synthesisPDF')->exists("synthesis-" . $id . ".pdf");
        if (!$exists) {
            abort(404, 'No PDF file found for this Jobmaker.');
        }

        $PDFfile = Storage::disk('synthesisPDF')->get("synthesis-" . $id . ".pdf");

        $lastname = $jobmaker->lastname;
        $firstname = $jobmaker->firstname;
        $pdfFilename = "Jobmaker";
        if ($lastname !== '') {
            $pdfFilename = $pdfFilename . "_" . $lastname;
        }
        if ($firstname !== '') {
            $pdfFilename = $pdfFilename . "_" . $firstname;
        }

        $pdfFilename = $pdfFilename . ".pdf";

        return response($PDFfile)
            ->header('Content-Type', "application/pdf")
            ->header('Content-Disposition', 'inline; filename="' . $pdfFilename . '"');
    }

    /**
     * Show status, progress of a JMaker
     * @param $jmakerUUID
     * @return mixed
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function postView($jmakerUUID)
    {
        // validation
        $this->authorize(Inside::PERMISSION_JOBMAKER_USER);


        $this->validate($request = $this->request(), [
            'jmakerUUID' => 'required|exists:jmaker,uuid'
        ]);


        $jmaker = Jmaker::findOrFail($jmakerUUID);
        $invitation = $jmaker->invitation()->first();


        $missions = Mission::pluck('title', 'id');
        $status = ref('run.status')->pairs();

        $runs = $jmaker->runs()->get();

        $completion_data = $runs->map(function (Run $run) use (&$completion_data, $missions, $status) {

            return [
                'full_title' => $missions[$run->mission_id],
                'status' => $status[$run->status_rid],
                'started_at' => $run->started_at,
                'completed_at' => $run->completed_at,
                'evaluation-1' => str_repeat('<i class="fa fa-star"  aria-hidden="true"></i>', (int)$run->getFirstEvaluation()),
                'evaluation-2' => str_repeat('<i class="fa fa-star"  aria-hidden="true"></i>', (int)$run->getSecondEvaluation())
            ];
        })->toArray();

        // MODEL
        $completion = \table($completion_data);
        $completion->disableFooter();
        $completion->useDefaultPanel('Completion');
        $completion->addText('full_title', 'Etape');
        $completion->addText('status', 'Statut');
        $completion->addDate('started_at', 'Commencé');
        $completion->addDate('completed_at', 'Terminé le');
        $completion->addText('evaluation-1', 'Evaluation Q1')->center();
        $completion->addText('evaluation-2', 'Evaluation Q2')->center();

        $global = form();
        $global->useDefaultPanel('Indicateurs');
        $global->addLabelDate('created_at', 'Invité le')->setValue($invitation->created_at);
        $global->addLabelDate('begin_at', 'Commencé le')->setValue($invitation->completed_at);

        $languageText = "Français";
        if ($jmaker->language->id == 'LANG_EN') {
            $languageText = "Anglais";
        }

        $global->addLabel('language', 'Langue')->setValue($languageText);
        $global->addLabel('Notification', 'Notifications mail')->setValue($jmaker->want_notification ? 'Activé' : 'Désactivé');
        if($jmaker->want_notification != true)
        {
            /** We fetch the event regarding the unsubscription */
            $event = JmakerEvent::where('jmaker_uuid',$jmaker->uuid)
                ->where('type', Ref::EVENT_MAIL_NOTIFICATIONS_UNSUBSCRIPTION)
                ->orderBy('date','desc')
                ->first();
            if($event instanceof JmakerEvent)
            {
                $global->addLabelDate('notificiation_unsubscribe_date', 'Désinscrit des notifications le')->setValue($event->date);
            }
        }
        // Recommandation checkup mobilité
        if ($run = $runs->where('mission_id', Mission::CHECKUP_SAFRAN)->first()) {
            $name = '';
            array_get($run->production, 'final') && ($name = array_get($run->production['final'], 'name'));
            $global->addLabel('recommandation', 'Recommandation Check Up Mobility')->setValue($name);

            $nm = $runs->whereIn('status_rid', [Ref::RUN_STATUS_INPROGRESS, Ref::RUN_STATUS_FINISHED])
                ->whereNotIn('mission_id', [Mission::DETOX, Mission::CHECKUP_SAFRAN])
                ->isNotEmpty();

            $global->addLabel('next_move', 'Next Move')->setValue($nm ? 'OUI' : 'NON');
        }

        $html = html('div.col-md-12', [], $global);
        $html .= '<hr>';
        $html .= html('div.col-md-12', [], $completion);
        $html = html('div.row', [], $html);

        return response()->modal($jmaker->username, $html);
    }

    /**
     * Delete invitation
     * @param $jmakerInvitationUUID
     * @return Javascript
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function deleteJMaker($jmakerInvitationUUID)
    {
        // validation
        $this->authorize(Inside::PERMISSION_JOBMAKER_USER);
        $this->validate($request = $this->request(), [
            'jmakerInvitationUUID' => [
                'required',
                Rule::exists('jmaker_invitation', 'uuid')->where(function ($query) {
                    /** @var Builder $query */
                    $query->where('is_completed', false);
                    $query->whereNull('deleted_at');
                })
            ]
        ]);
        $invitation = JmakerInvitation::findOrFail($jmakerInvitationUUID);
        $jmaker = $invitation->jmaker()->first();

        // MODAL
        $modal = modal(null, 'Etes vous sûr de vouloir supprimer cette invitation : <b>' . $invitation->email . '</b>');
        $button = (new Button('yes', 'Supprimer !'))
            ->setOptionAsDanger()
            ->enableCallback('delete')
            ->addAttribute('href', action_url(static::class, __FUNCTION__, func_get_args(), ['delete' => true]));
        $modal->appendAction($button);

        // TRAITEMENT
        if ($request->has('delete')) {

            try {
                $invitation->email = '_deleted_';
                $invitation->save();
                $invitation->delete();

                app('App\Http\Controllers\Inside\JmakerController')->deleteJobmaker($jmaker->uuid);

                return js()->success()->closeRemoteModal()->reloadDataTable();
            } catch (Exception $e) {
                return js()->error($e->getMessage());
            }
        }

        return response()->modal($modal);
    }


    /**
     * List all jobmaker for a client
     * @param $clientUUID
     * @return Factory|View
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function getJMakersPage($clientUUID)
    {
        $this->authorize(Inside::PERMISSION_CLIENT);
        $this->validate($this->request(), ['clientUUID' => 'required|exists:client,uuid']);
        // clients
        $client = Client::find($clientUUID);
        // Liens vers la fiche client
        $client_url = action_url(ClientDetailController::class, 'getInformation', $clientUUID);

        // Les choses à faire pour le passer inactive
        if ($client->isSuspect()) {
            $next = [
                'color' => 'bg-aqua'
            ];
        }

        // si fiche prospect
        if ($client->isProspect()) {
            $next = [
                'color' => 'bg-yellow'
            ];
        }

        // si fiche Active
        if ($client->isActive()) {
            $next = [
                'color' => 'bg-red'
            ];
        }

        // si fiche prospect
        if ($client->isClient()) {
            $next = [
                'color' => 'bg-green'
            ];
        }
        $metrics = query('client_metric_jmaker as mt')
            ->addSelect('mt.client_uuid')
            ->addSelectSum('(jmaker_state_invited)+(jmaker_state_onboarding)+(jmaker_state_active) as invitation_ct')
            ->addSelectSum('jmaker_state_active as active_ct')
            ->addSelectSum('(jmaker_ws_finished_1 + jmaker_ws_finished_2 + jmaker_ws_finished_3 + jmaker_ws_finished_4 + jmaker_ws_finished_5 + jmaker_ws_finished_6 + jmaker_ws_finished_7 + jmaker_ws_finished_8 + jmaker_ws_finished_9) as jmakerEngaged')
            ->addSelect('workshop_finished as completed_ct')
            ->addSelect('distinct_shared_ct as shared_ct')
            ->where('mt.client_uuid', $client->getKey());

        $kpi = $metrics->first();

        // TABLE

        $campaigns = "";
        $customizations = "";
        $jobmakers = "";
        $invitations = "";

        $campaigns = $this->tableCampaigns($clientUUID);

        $customizations = "";
        $customizations = $this->tableCustomizations($clientUUID);
        $jobmakers = $this->tableJMakers($clientUUID);
        $invitations = $this->tableJMakerInvitations($clientUUID);

        // titre de la page
        h()->title($client->name . ' : Jobmakers');

        return view('inside.clients.jobmakers', compact('kpi', 'client_url', 'next', 'client', 'campaigns', 'jobmakers', 'customizations', 'invitations'));
    }

    /**
     * Add and edit Culture for client
     * @param $clientUUID
     * @return mixed
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function postCulture($clientUUID)
    {
        $this->authorize(Inside::PERMISSION_JOBMAKER_USER);
        $this->validate($request = $this->request(), [
            'clientUUID' => 'required|exists:client,uuid'
        ]);
        // MODEL
        $client = Client::find($clientUUID);

        // Recuperation de patch de culture applicable
        $languageGroups = LanguageGroup::where('collection', LanguageController::GROUP_COLLECTION_LANGUAGE)->whereNotNull('config')->get();

        $form = form()->enableRemote();
        $form->setLegend('Culture du client');

        // Culture CLient, It seems that isn't used today.
        $culture = $languageGroups->filter(function (LanguageGroup $languageGroup) {
            $config = $languageGroup->config;
            return array_get($config, 'type', false) == LanguageGroupController::TYPE_CLIENT;
        })->mapWithKeys(function (LanguageGroup $languageGroup, $key) {
            return [$languageGroup->uuid => $languageGroup->name];
        });
        $form->addSelect('language_group_uuid', 'Culture client', $culture->toArray(), false)->setPlaceholder();


        // GEstion des question en fonction des cultures générales
        $fields = [];
        $languageGroups->filter(function (LanguageGroup $group) {
            $config = $group->config;
            return array_get($config, 'type', false) == LanguageGroupController::TYPE_CULTURE;

        })->each(function (LanguageGroup $languageGroup) use (&$fields) {

            $config = $languageGroup->config;

            // Gestion de la category
            empty($fields[$config['category']]) && $fields[$config['category']] = [];

            $fields[$config['category']][] = [
                $languageGroup->uuid,
                $config['question'],
                $config['answer'],
            ];
        });

        if (!empty($fields)) {

            $form->addSeparator();
            $form->addTitle('Patchs Culture');

            // Ajout des field au formulaire
            foreach ($fields as $title => $elements) {

                $form->addTitle($title);

                foreach ($elements as $e) {

                    $form->addContent('_test'.$e[0], '<h4>'.$e[1].'</h4>');
                    $form->addRadio($e[0],"Réponse",[false => "&nbsp;&nbsp;" .$e[2][0],true => "&nbsp;&nbsp;" .$e[2][1] . ' (Cut Video)'],true)->setValue(false);
                }
            }
        }


        // Default coimpaign
        // GEstion des question en fonction des cultures générales
        $form->addSeparator();
        $form->addTitle('Patchs Campagne');
        // On filte sur les groupe de language campagne
        $languageGroups->filter(function (LanguageGroup $group) {
            $config = $group->config;
            return array_get($config, 'type', false) == LanguageGroupController::TYPE_CAMPAIGN;
        })->each(function (LanguageGroup $languageGroup) use (&$form) {
            if ($question = array_get($languageGroup->config, 'question', false)) {
                $form->addBoolean($languageGroup->uuid, $question)->setValue(true);
            }
        });

        $form->addSubmit('Enregistrer');

        // enregistrement
        if ($request->has('Enregistrer')) {
            $form->valid($request->all());
            if ($form->isValid()) {
                $data = $form->getFilteredAliasValues();

                try {

                    // Recuperation des culture
                    $culture = [];
                    collect($data)->each(function ($v, $uuid) use (&$culture) {
                        if (!empty($v) && Uuid::validate($uuid)) {
                            $culture[] = $uuid;
                        }
                    });

                    // SAVE
                    $client->adaptation = $culture;
                    $client->language_group_uuid = $data['language_group_uuid'];
                    $client->save();

                    js()->success()->closeRemoteModal()->reloadDataTable();
                } catch (Exception $e) {
                    js()->error($e->getMessage());
                }
            }
        } else {

            $data = [];
            foreach ((array)$client->adaptation as $group) {
                $data[$group] = true;
            }

            $data['language_group_uuid'] = $client->language_group_uuid;

            $form->populate($data);
        }

        return response()->modal($form);
    }

    /**
     * Build JMaker table
     *
     * @param $client_uuid
     * @return Table
     * @throws AuthorizationException
     */
    public function tableJMakers($client_uuid)
    {
        // AUTORISATION
        $this->authorize(Inside::PERMISSION_JOBMAKER_USER);

        // MODEL
        $client = Client::find($client_uuid);
        $meetingQuery = query('jmaker_meeting')
            ->addSelect('jmaker_uuid')
            ->selectRaw('MAX(meeting_date) as meeting_date')
            ->whereNull('deleted_at')
            ->groupBy('jmaker_uuid');

        // SUBQUERY  : MISSION
        $missions = query('mission_run')
            ->addSelect('jmaker_uuid')
            ->selectRaw('COUNT(jmaker_uuid) > 0 as is_active')
            ->selectRaw('COUNT(jmaker_uuid) as completed_ct')
            ->whereIn('status_rid', [Ref::RUN_STATUS_FINISHED, Ref::RUN_STATUS_FINISHED_CHECKUP_WITH_JOBMAKER])
            ->groupBy('jmaker_uuid');

        $synthesisShare = query('synthesis_share')
            ->addSelect('jmaker_uuid')
            ->selectRaw('MAX(shared_at) as shared_at')
            ->selectRaw(' 1 as is_shared')
            ->groupBy('jmaker_uuid');

        // QUERY
        $query = query('jmaker as j', [
            'j.uuid as uuid',
            'p.name as prescriber_name',
            'cc.name as campaign_name',
            'j.email',
            raw("(CASE WHEN (j.state = 'JMAKER_STATE_ACTIVE') THEN 'Active' ELSE 'Invited' END) as state"),
            raw("(CASE WHEN (j.state = 'JMAKER_STATE_ACTIVE') THEN 'TRUE' ELSE 'FALSE' END) as is_completed"),
            'j.language_id',
            'j.created_at',
            raw("(CASE WHEN (j.state = 'JMAKER_STATE_ONBOARDING') THEN 'TRUE' ELSE 'FALSE' END) as is_started"),
            'mq.meeting_date',
            'j.created_at as creation',
            'ss.is_shared',
            'ss.shared_at',
            raw('IFNULL(m.completed_ct, 0) as completed_ct'),
            'j.last_page_at',
        ])
            ->selectRaw('IFNULL(is_active, false) as is_active')
            ->leftJoin('prescriber as p', 'j.prescriber_uuid', "=", 'p.uuid')
            ->leftJoin('client_campaign as cc', 'j.campaign_uuid', 'cc.uuid')
            ->leftJoinQuery($meetingQuery, 'mq', 'j.uuid', 'mq.jmaker_uuid')
            ->leftJoinQuery($missions, 'm', 'j.uuid', 'm.jmaker_uuid')
            ->leftJoinQuery($synthesisShare, 'ss', 'j.uuid', 'ss.jmaker_uuid')
            ->whereNull('j.deleted_at')
            ->where('j.state', "=", 'JMAKER_STATE_ACTIVE')
            ->where('j.client_uuid', $client_uuid);

        // TABLE
        $table = \table($query);
        $table->setConstructor(static::class, __FUNCTION__, $client_uuid)->enableRemote()->enableDatatable();
        $panel = $table->useDefaultPanel('Jobmakers')->getPanel();
        $panel->addButton('invite_user', 'Inviter', action_url(static::class, 'postInvit', $client_uuid))
            ->icon('fa fa-plus', false)
            ->setOptionAsPrimary()
            ->enableRemote();
        $panel->addButton('invite_mass_user', 'Importer', action_url(static::class, 'postImport', $client_uuid))
            ->icon('fa fa-download', false)
            ->setOptionAsWarning()
            ->enableRemote();

        $table->setIdField('uuid');

        $table->addDatatableButtonExport();

        // EXPORT
        $table->setExport(function (Table $table) {
            $table->clearColumns();
            $table->setCSVSeparator(';');
            $table->addText('email', 'Email');
            $table->addText('campaign_name', 'Campagne');
            $table->addText('prescriber_name', 'Prescripteur');
            $table->addText('state', 'State');
            $table->addDate('creation', 'Invité le');
            $table->addBoolean('is_started', 'Commencé?');
            //$table->addDate('started_at', 'Commencé le');
            $table->addBoolean('is_completed', 'Activé?');
            $table->addBoolean('is_active', 'Engagé?');
            $table->addNumber('completed_ct', 'Nb ateliers', 0);
            $table->addDate('meeting_date', 'Date de debrief')->setOrder('mq.meeting_date');
            $table->addBoolean('is_shared', 'Rapport?');
            $table->addDate('last_page_at', 'Actif le.');
        });

        // COLMUMN
        $table->addText('email', 'Email')->setStrainerText('j.email');
        $campaigns = $client->campaigns()->orderBy('name')->pluck('name', 'uuid');
        $table->addText('campaign_name', 'Campagne')->setStrainerSelect($campaigns, 'cc.uuid');
        $prescribersList = $client->prescribers()->pluck('name', 'uuid');
        $table->addText('prescriber_name', 'Prescripteur')->setStrainerSelect($prescribersList, 'j.prescriber_uuid');
        $table->addText('state', 'State')->setStrainerText('j.state')->setWidth('50')->center();;
        $table->addDate('created_at', 'Invité le')->setOrder('j.created_at');
        $table->addBoolean('is_active', 'Engagé?')->setStrainerBoolean(raw('IFNULL(is_active, false)'));
        $table->addNumber('completed_ct', 'Nb ateliers', 0)->setOrder('completed_ct')->center();
        $table->addDate('meeting_date', 'Date de debrief')->setOrder('mq.meeting_date');
        $table->addBoolean('is_shared', 'Synth ?')->setStrainerBoolean('is_shared');
        $table->addDate('last_page_at', 'Dernière conx')->setOrder('j.last_page_at');
        $table->addText('language_id', 'Language')->setStrainerSelect(['LANG_FR'=>'FR','LANG_EN'=>'EN'], 'j.language_id');
        // ACTION
        $action = $table->addContainer('action', 'Action')->setWidth('160')->right();

        // connexion sur interface utrilisateur
        if (Gate::check(Inside::PERMISSION_JOBMAKER_WAY)) {

            $action->addButton('goto_invitation', 'Onboarding', route('invitation', '%s'), 'token')
                ->addAttribute('target', '_blank')
                ->icon('fa fa-plane')
                ->setOptionAsDefault()
                ->setVisibleCallback(function ($e, $row) {
                    return !$row['is_completed'];
                });


            $action->addButton('linkme', 'Interface', action_url(JmakerController::class, 'getLink', '%s'), 'uuid')
                ->addAttribute('target', '_blank')
                ->icon('fa fa-external-link')
                ->setOptionAsDefault()
                ->setVisibleCallback(function ($e, $row) {
                    return $row['is_completed'];
                });


            $action->addButton('rapport', 'Rapport', action_url(static::class, 'getRapport', '%s'), 'uuid')
                ->addAttribute('target', '_blank')
                ->icon('fa fa-file')
                ->setVisibleCallback(function ($e, $row) {
                    return $row['is_shared'];
                });
        }

        $action->addButtonRemote('mail', 'Envoyer un email', action_url(static::class, 'postMail', '%s'), 'uuid')
            ->icon('fa fa-envelope')
            ->setVisibleCallback(function ($e, $row) {
                return $row['is_completed'];
            });

        $action->addButtonRemote('view', 'Voir', action_url(static::class, 'postView', '%s'), 'uuid')
            ->icon('fa fa-eye')
            ->setOptionAsSuccess()
            ->setVisibleCallback(function ($e, $row) {
                return $row['is_completed'];
            });

        /*$action->addButtonRemote('refresh', 'Relance', action_url(static::class, 'postJMaker', 'uuid'))
            ->icon('fa fa-refresh')
            ->setOptionAsPrimary()
            ->setVisibleCallback(function ($e, $row) {
                return !$row['is_completed'];
            });*/

        $action->addButtonEdit(action_url(static::class, 'postJMaker', [$client_uuid, '%s']), 'uuid')
            ->setVisibleCallback(function ($e, $row) {
                return $row['is_completed'];
            });

        $action->addButtonDelete(action_url(static::class, 'deleteJMaker', '%s'), 'uuid')
            ->setVisibleCallback(function ($e, $row) {
                return !$row['is_completed'];
            });


        $action->addButtonRemote('migrate', 'Migrer sur une autres campagne', action_url(static::class, 'postMigrateJobmaker', '%s'), 'uuid')
            ->setOptionAsDanger()
            ->icon('fa fa-send');

        return $table;
    }

    /**
     * Build invitation table for one Client
     *
     * @param $client_uuid
     * @return Table
     * @throws AuthorizationException
     * @throws Exception
     */
    public function tableJMakerInvitations($client_uuid)
    {

        // AUTORISATION
        $this->authorize(Inside::PERMISSION_JOBMAKER_USER);
        // MODEL
        $client = Client::find($client_uuid);

        $meetingQuery = query('jmaker_meeting')
            ->addSelect('invitation_uuid')
            ->selectRaw('MAX(meeting_date) as meeting_date')
            ->whereNull('deleted_at')
            ->groupBy('invitation_uuid');

        // QUERY
        $query = query('jmaker_invitation as i', [
            'jmaker_uuid',
            'data',
            'p.name as prescriber_name',
            'cc.name as campaign_name',
            'i.email',
            'token',
            'i.created_at',
            'i.is_started',
            'i.is_completed',
            'i.started_at',
            'i.uuid as uuid',
            'mq.meeting_date'
        ])
            ->leftJoin('prescriber as p', 'i.invited_by_prescriber_uuid', 'p.uuid')
            ->leftJoin('client_campaign as cc', 'i.campaign_uuid', 'cc.uuid')
            ->leftJoinQuery($meetingQuery, 'mq', 'i.uuid', 'mq.invitation_uuid')
            ->whereNull('i.deleted_at')
            ->where('i.is_completed', "=", false)
            ->where('cc.client_uuid', $client_uuid);

        // TABLE
        $table = \table($query);
        $table->setConstructor(static::class, __FUNCTION__, $client_uuid)->enableRemote()->enableDatatable();

        $panel = $table->useDefaultPanel('Invitations en cours')->getPanel();

        $table->setIdField('uuid');
        $table->addDatatableButtonExport();

        // EXPORT
        $table->setExport(function (Table $table) {
            $table->clearColumns();
            $table->addText('email', 'Email');
            $table->addText('campaign_name', 'Campagne');
            $table->addText('prescriber_name', 'Prescripteur');
            $table->addDate('created_at', 'Invité le');
            $table->addBoolean('is_started', 'Commencé?');
            $table->addDate('started_at', 'Commencé le');
            $table->addDate('meeting_date', 'Date de debrief');
            $table->addBoolean('is_completed', 'Activé?');
        });

        // COLMUMN
        $table->addText('email', 'Email')->setStrainerText('i.email');
        $campaigns = $client->campaigns()->orderBy('name')->pluck('name', 'uuid');
        $table->addText('campaign_name', 'Campagne')->setStrainerSelect($campaigns, 'cc.uuid');
        $prescribersList = $client->prescribers()->pluck('name', 'uuid');
        $table->addText('prescriber_name', 'Prescripteur')->setStrainerSelect($prescribersList, 'i.invited_by_prescriber_uuid');
        $table->addDate('created_at', 'Invité le')->setOrder('i.created_at');
        $table->addDate('meeting_date', 'Date de debrief')->setOrder('mq.meeting_date');
        $table->addBoolean('is_started', 'Commencé?')->setStrainerBoolean('i.is_started');

        // ACTION
        $action = $table->addContainer('action', 'Action')->setWidth('160')->right();

        // connexion sur interface utrilisateur
        if (Gate::check(Inside::PERMISSION_JOBMAKER_WAY)) {

            $action->addButton('goto_invitation', 'Onboarding', route('invitation', '%s'), 'token')
                ->addAttribute('target', '_blank')
                ->icon('fa fa-plane')
                ->setOptionAsDefault()
                ->setVisibleCallback(function ($e, $row) {
                    return !$row['is_completed'];
                });
        }

        $action->addButtonRemote('refresh', 'Relance', action_url(static::class, 'postJmakerInvit', [$client_uuid, '%s']), 'uuid')
            ->icon('fa fa-refresh')
            ->setOptionAsPrimary()
            ->setVisibleCallback(function ($e, $row) {
                return !$row['is_completed'];
            });

        $action->addButtonDelete(action_url(static::class, 'deleteJMaker', '%s'), 'uuid')
            ->setVisibleCallback(function ($e, $row) {
                return !$row['is_completed'];
            });

        $action->addButtonRemote('migrate', 'Migrer sur une autres campagne', action_url(static::class, 'postMigrateJobmaker', '%s'), 'uuid')
            ->setOptionAsDanger()
            ->icon('fa fa-send');

        return $table;
    }

    /**
     * Delete campaign
     * @param $clientUUID
     * @param $clientCampaignUUID
     * @return Javascript
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function deleteCampaign($clientUUID, $clientCampaignUUID)
    {

        // validation
        $this->authorize(Inside::PERMISSION_CLIENT);
        $this->validate($request = $this->request(), [
            'clientUUID' => 'required|exists:client,uuid',
            'clientCampaignUUID' => 'exists:client_campaign,uuid'
        ]);

        // recuperation du model principale
        $campaign = ClientCampaign::findOrNew($clientCampaignUUID);

        // MODAL
        $modal = modal(null, 'Etes vous sûr de vouloir supprimer : <b>' . $campaign->name . '</b>');
        $button = (new Button('yes', 'Supprimer !'))
            ->setOptionAsDanger()
            ->enableCallback('delete')
            ->addAttribute('href', action_url(static::class, __FUNCTION__, func_get_args(), ['delete' => true]));
        $modal->appendAction($button);

        // TRAITEMENT
        if (request()->has('delete')) {
            try {
                $campaign->delete();
                js()->success()->closeRemoteModal()->reloadDataTable();
            } catch (Exception $e) {
                js()->error($e->getMessage());
            }
            return js();
        }

        return response()->modal($modal);
    }

    /**
     * Create many invitation from a CSV file
     *
     * @param $uuid
     * @return mixed
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function postImport($uuid)
    {
        $this->authorize(Inside::PERMISSION_JOBMAKER_USER);
        $this->validate($request = $this->request(), [
            'uuid' => 'required|exists:client,uuid'
        ]);

        // MODEL
        $client = Client::find($uuid);

        // FORM
        $form = form()->enableRemote();
        $form->setLegend('Invitations depuis un fichier');

        // ELEMENT
        $form->addFile('file_csv', 'Fichier', false);
        $form->addTextarea('message', 'Message', false)
            ->addAttribute('rows', 8)
            ->addAttribute('maxlength', 800);
        $form->addSeparator();

        $campaigns = $client->campaigns()->pluck('name', 'uuid');
        $form->addSelect('campaign_uuid', 'Campagne', $campaigns);
        $prescribersList = $client->prescribers()->pluck('name', 'uuid');
        $form->addSelect('prescriber_uuid', 'Prescripteur', $prescribersList, true)->setPlaceholder();

        $form->addSubmit('Envoyer');

        // TRAITEMENT
        if ($request->has('Envoyer')) {
            $data = $request->all();
            $form->valid($data);
            if ($form->isValid()) {
                $data = $form->getFilteredValues();
                try {


                    // Gestion du fichier
                    $file = $request->file('file_csv');

                    if ($file instanceof UploadedFile && $file->isValid()) {

                        // netoyage des data
                        unset($data['file_csv']);


                        // read the file
                        $reader = Reader::createFromPath($file->getRealPath());

                        // traitement
                        transaction(function () use ($reader, $client, $data) {
                            // envoie des invitations individuelles
                            collect($reader->fetchAll())
                                ->each(function ($row) use ($client, $data) {
                                    // initialisation
                                    $email = $firstname = $lastname = null;

                                    foreach ($row as $value) {

                                        // si valeur vide, on passe
                                        if (empty($value)) {
                                            continue;
                                        }
                                        // si email on remplie
                                        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                            $email = $value;
                                        } elseif (is_null($lastname)) {
                                            $lastname = $value;
                                        } elseif (is_null($firstname)) {
                                            $firstname = $value;
                                        }
                                    }

                                    // si pas de mail, on ne continue pas
                                    if (empty($email)) {
                                        return;
                                    }

                                    // On determine si on envoie le mail
                                    /** @var JmakerInvitation $invitation */
                                    $invitation = JmakerInvitation::where('email', $email)->firstOrNew([]);

                                    // cas de la création
                                    if (!$invitation->exists) {

                                        // Creatrion de l'invitation
                                        $invitation->uuid = generateNewUUID();
                                        $invitation->token = str_random(60);
                                        $invitation->campaign_uuid = $data['campaign_uuid'];
                                        $invitation->email = $email;
                                        $invitation->invited_by_prescriber_uuid = $data['prescriber_uuid'];
                                        $invitation->data = $data + [
                                                'email' => $email,
                                                'firstname' => $firstname,
                                                'lastname' => $lastname,
                                            ];

                                        // si il y a une campagne, on l'attribu
                                        if ($client->partner) {
                                            $invitation->partner_id = $client->partner->id;
                                        }

                                        // sauvagrde de l'invitation
                                        $invitation->save();

                                        // envoie du mail
                                        MailService::pushInDB(Invite::class, $invitation->email, $invitation->token);
                                        event(new JmakerInvitedEvent($invitation));
                                    }
                                });
                        });
                    } else {
                        throw new Exception('Impossible de recuperer le fichier');
                    }

                    js()->success()->closeRemoteModal()->reloadDataTable();
                } catch (Exception $e) {
                    js()->error($e->getMessage());
                }
            }
        }

        return response()->modal($form);
    }

    /**
     * Post, edit, show customization
     * @param $clientUUID
     * @param null $language_id
     * @return mixed
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function postCustomise($clientUUID, $language_id = null)
    {
        // validation
        $this->authorize(Inside::PERMISSION_CLIENT);
        $this->validate($request = $this->request(), ['clientUUID' => 'required']);
        $client = Client::findOrFail($clientUUID);

        $customizations = $client->customizations();
        $customization = $customizations->where("language_id", $language_id)->first();

        // FORM
        $form = form()->enableRemote();
        $form->setLegend('Client : ' . $client->name);
        $form->addText('params[url]', 'Liens partenaire', false)->setDescription("Lien vers le site client, utilisé lorsqu'un utilisateur clique sur le logo");;
        $form->addFile('params[img]', 'Logo', false)->setDescription('Format attendu JPEG ou PNG, taille 286x98px');
        $form->addSeparator();
        $form->addTitle('Page d\'arrivée : ');
        $form->addContent('_landing', '<div class="text-center"><img height="350" src="/_partner/img/_preview_landing.png"></div>');
        $form->addFile('params[background]', 'Votre fond', false)->setDescription('Format attendu JPEG ou PNG, taille 1366x445px');
        $form->addText('params[devise]', 'Votre slogan', false);
        $form->addTextarea('params[description]', 'Votre citation', false)
            ->addAttribute('rows', 3)
            ->addAttribute('maxlength', 200);
        $form->addSeparator();
        $form->addTitle('Emails invitation Jobmaker : ');
        $form->addContent('_email', '<div class="text-center"><img height="250" src="/_partner/img/_preview_email.png"></div>');
        $form->addText('params[mail_title]', 'Titre', false);
        $form->addTextarea('params[mail_content]', 'Paragraphe', false)
            ->addAttribute('rows', 8)
            ->addAttribute('maxlength', 450);
        $form->addContent('_test', '<div class="text-center"><img height="250" src="/_partner/img/_preview_test.jpeg"></div>');
        $form->addTextarea('params[mail_content_2]', 'Paragraphe 2', false)
            ->addAttribute('rows', 8)
            ->addAttribute('maxlength', 650);
        $form->addSeparator();
        $form->addTitle('Emails invitation Prescripteur: ');
        $form->addContent('_email', '<div class="text-center"><img height="250" src="/_partner/img/_preview_email.png"></div>');
        $form->addText('params[mail_user_invitation_subject]', 'Sujet du mail', false);
        $form->addText('params[mail_user_invitation_title]', 'Titre', false);
        $form->addTextarea('params[mail_user_invitation_content]', 'Paragraphe', false)
            ->addAttribute('rows', 8)
            ->addAttribute('maxlength', 450);
        $form->addSeparator();
        $form->addTitle('Rendu Customisé');
        $form->addText('params[view_mail]', 'Vue mail', false);
        $form->addText('params[view_landing]', 'Vue landing', false);
        $form->addSubmit('Enregistrer');

        // TRAITEMENT
        if ($request->has('Enregistrer')) {

            $form->valid(request()->all());
            if ($form->isValid()) {
                $data = $form->getFilteredValues();

                try {

                    $landing = $request->get('params');

                    $customizations = $client->customizations();
                    $customization = $customizations->where("language_id", $language_id)->first();

                    $previousParam = [];

                    if (empty($customization)) {
                        $customization = new ClientCustomization();
                        $customization->uuid = generateNewUUID();
                        $customization->language_id = $language_id;
                        $customization->client_uuid = $client->uuid;
                    } else {
                        $previousParam = $customization->params;
                    }

                    // Gestion du fichier
                    ($file = $request->file('params')) && !empty($file['img']) && ($file = $file['img']);

                    if ($file instanceof UploadedFile && $file->isValid()) {

                        // resize de l'image
                        Image::make($file)->resize(null, 120, function ($constraint) {
                            $constraint->aspectRatio();
                        })->save();
                        $media = MediaService::create([$file->getClientOriginalName(), Ref::MEDIA_TYPE_PARTNER, $file->getMimeType(), file_get_contents($file->getRealPath())]);
                        $landing['img'] = $media->uuid;
                    } else {
                        $landing['img'] = empty($previousParam['img']) ? '' : $previousParam['img'];
                    }

                    // background
                    ($file = $request->file('params')) && !empty($file['background']) && ($file = $file['background']);
                    if ($file instanceof UploadedFile && $file->isValid()) {

                        // resize de l'image
                        Image::make($file)->resize(null, 600, function ($constraint) {
                            $constraint->aspectRatio();
                        })->save();
                        $media = MediaService::create([$file->getClientOriginalName(), Ref::MEDIA_TYPE_PARTNER, $file->getMimeType(), file_get_contents($file->getRealPath())]);
                        $landing['background'] = $media->uuid;
                    } else {
                        $landing['background'] = empty($previousParam['background']) ? '' : $previousParam['background'];
                    }

                    $customization->params = $landing;
                    $customization->save();

                    js()->success()->closeRemoteModal()->reloadDataTable();
                } catch (Exception $e) {
                    js()->error($e->getMessage());
                }
            }
        } else {

            if ($customization) {

                $default = $customization->toArray();
                $default['params[url]'] = empty($default['params']['url']) ? '' : $default['params']['url'];
                $default['params[description]'] = empty($default['params']['description']) ? '' : $default['params']['description'];
                $default['params[mail_content]'] = empty($default['params']['mail_content']) ? '' : $default['params']['mail_content'];
                $default['params[mail_content_2]'] = empty($default['params']['mail_content_2']) ? '' : $default['params']['mail_content_2'];
                $default['params[mail_title]'] = empty($default['params']['mail_title']) ? '' : $default['params']['mail_title'];
                $default['params[mail_user_invitation_subject]'] = empty($default['params']['mail_user_invitation_subject']) ? '' : $default['params']['mail_user_invitation_subject'];
                $default['params[mail_user_invitation_content]'] = empty($default['params']['mail_user_invitation_content']) ? '' : $default['params']['mail_user_invitation_content'];
                $default['params[mail_user_invitation_title]'] = empty($default['params']['mail_user_invitation_title']) ? '' : $default['params']['mail_user_invitation_title'];
                $default['params[view_landing]'] = empty($default['params']['view_landing']) ? '' : $default['params']['view_landing'];
                $default['params[view_mail]'] = empty($default['params']['view_mail']) ? '' : $default['params']['view_mail'];
                $default['params[devise]'] = empty($default['params']['devise']) ? '' : $default['params']['devise'];
                $form->populate($default);
            }
        }

        return response()->modal($form);
    }




    /**
     * Add and edit Client
     * @param null $clientUUID
     * @return mixed
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function postIndex($clientUUID = null)
    {
        $this->authorize(Inside::PERMISSION_CLIENT);
        $this->validate($this->request(), ['id' => 'exists:client,uuid']);
        // MODEL
        $client = Client::findOrNew($clientUUID);
        // FORM
        $form = form()->enableRemote();
        $form->setLegend('Gestion client');

        $mandatory = !$client->isSuspect() && $client->exists;

        // ELEMENT
        $form->addText('name', 'Nom', true); //true

        $status = ref('client_status')->pairs();
        $form->addSelect('client_status_rid', 'Status Client', $status, false)->setPlaceholder()->addAttribute('disabled', 'disabled');
        $form->addSelect('client_type_rid', 'Type', ref('clients.types')->pairs(), $mandatory)->setPlaceholder();

        $operators = Operator::orderBy('name')->pluck('name', raw("upper(uuid)"));
        $form->addSelect('operator_uuid', 'Commercial', $operators, true); //true
        $form->addBoolean('chatEnabled', 'Chat disponible');
        $form->addBoolean('whiteListEnabled', 'White listage');
        $form->addBoolean('enableMultipleInvitation','Multiple invitations');
        $form->addBoolean('enableEditMail','Modification email')->setDescription('Permet à un jmaker de changer son adresse email');
        $form->addBoolean('enableB2BasB2C','B2B as B2C')->setDescription('Prescripteur avec droits restreints, pas de notion de partage de synthèse, de date d\'invitation, de suivi de l\'avancement...');
        $form->addBoolean('meetingDateMandatory', 'Date meeting obligatoire');
        $form->addBoolean('remember_me', 'Connexion auto');
        $form->addSelect('lang_id', 'Langue(s)', ["LANG_FR" => "Français", "LANG_EN" => "Anglais"], true)->enableMultiple();
        $form->addSeparator();
        $form->addMarkdown('comment', 'Commentaire', false)
            ->addAttribute('rows', 3);
        $form->addSeparator();
        $form->addSubmit('Sauvegarder');

        // Valeur par default
        if (!$client->exists) {
            $client->uuid = generateNewUUID();
            $client->client_status_rid = Ref::CLIENT_STATUS_SUSPECT;
            $client->operator_uuid = \operator()->uuid;
        }

        // TRAITEMENT
        if (request()->has('Sauvegarder')) {
            $data = request()->all();

            $form->valid($data);
            if ($form->isValid()) {
                $data = $form->getFilteredValues();
                try {
                    // sauvegarde
                    transaction(function () use ($client, $data) {

                        $client->name = $data['name'];
                        $client->client_type_rid = $data['client_type_rid'];
                        $client->operator_uuid = $data['operator_uuid'];
                        $client->chatEnabled = $data['chatEnabled'];
                        $client->whiteListEnabled = $data['whiteListEnabled'];
                        $client->enableB2BasB2C = $data['enableB2BasB2C'];
                        $client->enableEditMail = $data['enableEditMail'];
                        $client->enableMultipleInvitation = $data['enableMultipleInvitation'];
                        $client->meetingDateMandatory = $data['meetingDateMandatory'];
                        $client->remember_me = $data['remember_me'];
                        $client->comment = $data['comment'];
                        $client->saveOrFail();

                        $client->languages()->sync($data['lang_id']);

                        $client->save();

                        $client->invoices()->where('invoice_status_rid', Ref::INVOICE_STATUS_PURCHASE_ORDER)->update(['purchase_order_document' => null]);
                    });

                    js()->success()->closeRemoteModal()->reloadDataTable();
                } catch (Exception $e) {
                    js()->error($e->getMessage());
                }
            }
        } else {
            $data = $client->toArray();
            $language = $client->languages();

            if ($language) {
                $data['lang_id'] = $language->pluck('id')->all();
            }
            $form->populate($data);
        }

        return response()->modal($form);
    }

    /**
     * Page d'accueil du module
     * @return Factory|View
     * @throws AuthorizationException
     */
    public function getIndex()
    {
        $this->authorize(Inside::PERMISSION_CLIENT);
        return $this->basic('Client', static::tableClients());
    }

    /**
     * Liste des client, qui est la liste principale de ce module
     *
     * @return Table
     * @throws AuthorizationException
     */
    public function tableClients()
    {
        $this->authorize(Inside::PERMISSION_CLIENT);

        // QUERY
        $query = query('client as c', [
            'c.uuid as id',
            'c.name',
            'o.name as operator_name',
            'c.client_status as status_name',
            'rt.name as type_name',
            'client_status_rid',
            'city',
            'c.updated_at',
            'a.*',
            raw('a.jmaker_state_invited +  a.jmaker_state_active + a.jmaker_state_onboarding as jmakerTotal'),
            raw('a.jmaker_ws_finished_1 +  a.jmaker_ws_finished_2 + a.jmaker_ws_finished_3 + a.jmaker_ws_finished_4 +  a.jmaker_ws_finished_5 + a.jmaker_ws_finished_6 + a.jmaker_ws_finished_7 +  a.jmaker_ws_finished_8 + a.jmaker_ws_finished_9 as jmakerEngaged')
        ])
            ->leftJoin('reference as r', 'r.reference_id', 'client_status_rid')
            ->leftJoin('reference as rt', 'rt.reference_id', 'client_type_rid')
            ->leftJoin('operator as o', 'o.uuid', 'c.operator_uuid')
            ->leftJoin('client_metric_jmaker as a', 'c.uuid', 'a.client_uuid');
        $clientStatus = array(Ref::CLIENT_STATUS_ACTIVE => "Activé", Ref::CLIENT_STATUS_FREEZED => "Gelé");
        //\Ref::CLIENT_STATUS_DELETED=>"Supprimé"
        // TABLE
        $table = \table($query);
        $table->setConstructor(static::class, __FUNCTION__)->enableRemote()->enableDatatable();
        $panel = $table->useDefaultPanel('Client')->getPanel();
        $panel->addButton('add_client', 'Ajouter', action_url(static::class, 'postIndex'))->enableRemote();
        $table->setIdField('id');

        // COLMUMN
        $table->addText('name', 'Nom')->setOrder('c.name')->setStrainerText('c.name');
        $table->addText('type_name', 'Type')->setStrainerSelect(ref('clients.types')->pairs(), 'c.client_type_rid');
        $table->addText('status_name', 'Statut Tech')->setStrainerSelect($clientStatus, 'c.client_status')->addFilter('format',function($value){
            switch ($value) {
                case Ref::CLIENT_STATUS_ACTIVE:
                    $value = "ACTIVE";
                    break;
                case Ref::CLIENT_STATUS_FREEZED:
                    $value = "GELÉ";
                    break;
                case Ref::CLIENT_STATUS_DELETED:
                    $value = "SUPPRIMÉ";
                    break;
            }

            return $value;
        });

        $operatorsList = pairs('operator', 'uuid', 'name', 'name');
        $table->addText('operator_name', 'Commercial')->setStrainerSelect($operatorsList, 'c.operator_uuid');

        $table->addNumber('jmakerTotal', 'Invit.', 0);
        $table->addNumber('jmaker_state_active', 'Onboardé', 0);
        $table->addNumber('jmakerEngaged', 'Engagé', 0);
        $table->addNumber('workshop_finished', 'Ateliers', 0);
        $table->addNumber('distinct_shared_ct', 'Synthèses', 0);

        // ACTION
        $action = $table->addContainer('action', 'Action')->setWidth('80')->right();
        $action->addButton('jobmaker', 'Voir les Jobmakers', action_url(static::class, 'getJMakersPage', '%s'), 'id')
            ->addAttribute('target', '_blank')
            ->icon('fa fa-user')
            ->setVisibleCallback(function ($e, $row) {
                return $row['client_status_rid'] != Ref::CLIENT_STATUS_SUSPECT;
            });
        $action->addButton('information', 'Voir la fiche', action_url(ClientDetailController::class, 'getInformation', '%s'), 'id')
            ->addAttribute('target', '_blank')
            ->icon('fa fa-search');
        $action->addButtonEdit(action_url(static::class, 'postIndex', '%s'), 'id');

        $table->addDatatableButtonReset();

        ///. Ordre par default
        if (!$this->request()->isXmlHttpRequest()) {
            $table->getColumn('name')->order('asc');
            $table->getColumn('status_name')->getStrainer()->call($table, Ref::CLIENT_STATUS_ACTIVE);

        }

        return $table;
    }

    /**
     * Data activity for a Client
     *
     * @return Factory|View
     * @throws AuthorizationException
     */
    public function getActivity()
    {
        $this->authorize(Inside::PERMISSION_CLIENT);
        return $this->basic('Client : Activité', $this->tableActivity());
    }

    /**
     * Migrate jmaker from one campaign to another
     * @param $jmakerUUID
     * @return mixed
     * @throws AuthorizationException
     * @throws ValidationException
     */
    protected function postMigrateJobmaker($jmakerUUID)
    {
        // validation
        $this->authorize(Inside::PERMISSION_JOBMAKER_USER);
        $this->validate($request = $this->request(), ['jmakerUUID' => 'required|exists:jmaker,uuid']);

        // Get Jmaker Invitation
        $jmaker = Jmaker::findOrFail($jmakerUUID);

        /** @var JmakerInvitation $invitation */
        $invitation = $jmaker->invitation()->first();
        $campaigns = query('client_campaign as cc', [
            raw('CONCAT(c.name, " / ", cc.name) as campaign_name'),
            raw('cc.uuid as campaign_uuid')

        ])->join('client as c', 'c.uuid', 'cc.client_uuid')
            ->where('mission_sequence_sid', $invitation->campaign->mission_sequence_sid)
            ->where('c.uuid', '=', $jmaker->client_uuid)
            ->orderBy('c.name')
            ->orderBy('cc.name')
            ->pluck('campaign_name', 'campaign_uuid');

        // FORM
        foreach($campaigns as $key => $campaign){
            if($key == $invitation->campaign_uuid){
                $selectedCampaign = $campaign;
            }
        }
        $form = form()->enableRemote();
        $form->setLegend('Changer de campagne');
        $form->addSelect('campaign_uuid', 'Campagne', $campaigns)->setPlaceholder($selectedCampaign);
        $form->addSubmit('Migrer!');

        // TRAITEMENT
        if ($request->has('Migrer!')) {

            $data = $request->all();
            $form->valid($data);
            if ($form->isValid()) {
                try {
                    $campaign = ClientCampaign::findOrFail($request->get('campaign_uuid'));

                    /*history*/
                    $history = new JmakerHistory();
                    $history->uuid =  generateNewUUID();
                    $history->jmaker_uuid = $jmaker->uuid;
                    $history->client_uuid = $jmaker->client_uuid;
                    $history->prescriber_uuid = $jmaker->prescriber_uuid;
                    $history->campaign_uuid = $jmaker->campaign_uuid;
                    $history->contract_uuid = $jmaker->contract_uuid;
                    $history->save();

                    $invitation->campaign_uuid = $campaign->getKey();
                    $invitation->save();
                    js()->success()->closeRemoteModal()->reloadDataTable();
                } catch (Exception $e) {
                    js()->error($e->getMessage());
                }
            }
        }
        return response()->modal($form);
    }



    /**
     * Build customization table for one client
     *
     * @param $uuid
     * @return Table
     * @throws AuthorizationException
     */
    public function tableCustomizations($uuid)
    {
        $this->authorize(Inside::PERMISSION_CLIENT);

        // clients
        $client = Client::find($uuid);

        $languages = $client->languages()->orderBy('id', 'desc')->get()->transform(function ($item, $key) {

            if ($item['id'] == 'LANG_FR') {
                $item['name'] = "Français";
            } else if ($item['id'] == 'LANG_EN') {
                $item['name'] = "Anglais";
            }

            return $item;
        });

        // TABLE
        $table = \table($languages->toArray());
        $table->setConstructor(static::class, __FUNCTION__, $uuid)->enableRemote()->enableDatatable();
        $panel = $table->useDefaultPanel('Customisations')->getPanel();

        $table->setIdField('id');

        // COLMUMN
        $table->addText('name', 'Nom')->setOrder('name');

        // ACTION
        $action = $table->addContainer('action', 'Action')->setWidth('220')->right();

        $action->addButton('goto_preview_email_jobmaker', 'Voir email Jobmaker', route('preview.mail', [$uuid, '%s']), 'id')
            ->addAttribute('target', '_blank')
            ->icon('fa fa-envelope');

        $action->addButton('goto_preview_landing', 'Voir landing', route('preview.landing', [$uuid, '%s']), 'id')
            ->addAttribute('target', '_blank')
            ->icon('fa fa-home');

        $action->addButton('goto_preview_email_prescripteur', 'Voir email Prescripteur', route('preview.mail_partner', [$uuid, '%s']), 'id')
            ->addAttribute('target', '_blank')
            ->icon('fa fa-envelope')->setOptionAsSuccess();

        $action->addButtonEdit(action_url(static::class, 'postCustomise', [$uuid, '%s']), 'id');

        return $table;
    }


    /**
     * Build campaign table
     * @param $clientUUID
     * @return Table
     * @throws AuthorizationException
     * @throws Exception
     */
    public function tableCampaigns($clientUUID)
    {
        $this->authorize(Inside::PERMISSION_JOBMAKER_USER);
        // QUERY
        $query = query('client_campaign as cc')
            ->addSelect('cc.uuid as uuid')
            ->addSelect([
                'cc.name as campaign_name',
                'ms.sid as sequence_sid',
                'ms.name as sequence_name',
                'cc.created_at',
                'ccm.jmaker_state_active as jobmaker_ct',
                'ccm.workshop_finished as completed_ct',
                'ccm.distinct_shared_ct as is_shared',
            ])
            ->selectRaw('(jmaker_state_invited)+(jmaker_state_onboarding)+(jmaker_state_active) as invitation_ct')
            ->selectRaw('(ccm.jmaker_ws_finished_1 + ccm.jmaker_ws_finished_2 + ccm.jmaker_ws_finished_3 + ccm.jmaker_ws_finished_4 + ccm.jmaker_ws_finished_5 + ccm.jmaker_ws_finished_6 + ccm.jmaker_ws_finished_7 + ccm.jmaker_ws_finished_8 + ccm.jmaker_ws_finished_9) as active_ct')
            ->leftJoin('mission_sequence as ms', 'ms.sid', '=','cc.mission_sequence_sid')
            ->leftJoin('client_campaign_metric_jmaker as ccm',  'cc.uuid','=','ccm.campaign_uuid')
            ->orderBy('cc.name')
            ->whereNull('cc.deleted_at')
            ->where('client_uuid', $clientUUID);
        // TABLE
        $table = \table($query)->setItemsPerPage(10);
        $table->setConstructor(static::class, __FUNCTION__, $clientUUID)->enableRemote()->enableDatatable();
        $panel = $table->useDefaultPanel('Campagnes')->getPanel();

        $panel->addButton('culture', 'Culture', action_url(static::class, 'postCulture', $clientUUID))
            ->icon('fa fa-book')
            ->addAttribute('data-size', 'modal-lg')
            ->setOptionAsPrimary()
            ->enableRemote();

        $panel->addButton('add_campaign', 'Ajouter', action_url(static::class, 'postCampaign', $clientUUID))->enableRemote();

        // COLMUMN
        $table->addText('campaign_name', 'Nom');
        $table->addText('sequence_name', 'Parcours');
        $table->addNumber('invitation_ct', 'Invit.', 0);
        $table->addNumber('jobmaker_ct', 'Activés', 0);
        $table->addNumber('active_ct', 'Engagés', 0);
        $table->addNumber('is_shared', 'Syn partagées', 0);
        $table->addNumber('completed_ct', 'Ateliers', 0);

        // ACTION
        $action = $table->addContainer('action', 'Action')->setWidth('80')->right();

        $action->addButtonRemote('test_campaign', 'Utilisateurs test', action_url(static::class, 'postCreateJMakerDemo', [$clientUUID, '%s']), 'uuid')
            ->setOptionAsWarning()
            ->setVisibleCallback(function ($e, $row) {
                $test = static::JOBMAKER_TEST;
                return !empty($test[$row['sequence_sid']]);
            })
            ->icon('fa  fa-user-secret')
            ->enableRemote();
        $action->addButtonEdit(action_url(static::class, 'postCampaign', [$clientUUID, '%s']), 'uuid');
        $action->addButtonDelete(action_url(static::class, 'deleteCampaign', [$clientUUID, '%s']), 'uuid')
            ->setVisibleCallback(function ($e, $row) {
                return empty($row['invitation_ct']);
            });
        return $table;
    }

    /**
     * Activity table
     * @return Table
     * @throws AuthorizationException
     */
    public function tableActivity()
    {
        $this->authorize(Inside::PERMISSION_CLIENT);

        $query = query('kpi_activity as a', [
            raw('cc.client_uuid as client_uuid'),
            raw('cc.name as campaign_name'),
            raw('c.name as client_name'),
            raw('a.*'),
        ])
            ->join('client_campaign as cc', 'a.campaign_uuid', 'cc.uuid')
            ->join('client as c', 'cc.client_uuid', 'c.uuid');


        // TABLE
        $table = \table($query);
        $table->setConstructor(static::class, __FUNCTION__)->enableRemote()->enableDatatable();
        $panel = $table->useDefaultPanel('Activité')->getPanel();

        // COLMUMN
        $table->addText('client_name', 'Client')->setOrder('client.name')
            ->setStrainerSelect(pairs('client', 'uuid', 'name', 'uuid'));
        $table->addText('campaign_name', 'Campagne')->setStrainerSelect(pairs('client_campaign', 'uuid', 'name'), 'cc.uuid');
        $table->addNumber('invited_ct', 'Invit.', 0)->setOrder('invitation');
        $table->addNumber('started_ct', 'Commencés', 0)->setOrder('started');
        $table->addNumber('activated_ct', 'Activés', 0)->setOrder('activated');
        $table->addNumber('engaged_ct', 'Engagés', 0)->setOrder('engaged');
        $table->addNumber('workshops_ct', 'Ateliers', 0)->setOrder('workshops');
        $table->addNumber('rapport_ct', 'Rapports', 0)->setOrder('rapport');

        $action = $table->addContainer('action', 'Action')->setWidth('80')->right();
        $action->addButton('jobmaker', 'Voir les Jobmakers', action_url(static::class, 'getJMakersPage', '%s'), 'client_uuid')
            ->addAttribute('target', '_blank')
            ->icon('fa fa-user');
        $action->addButton('information', 'Voir la fiche', action_url(ClientDetailController::class, 'getInformation', '%s'), 'client_uuid')
            ->addAttribute('target', '_blank')
            ->icon('fa fa-search');
        $table->addDatatableButtonReset();

        return $table;
    }

    /**
     * Edit status for a client
     * @param $clientUUID
     * @return mixed
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function postStatus($clientUUID)
    {
        $this->authorize(Inside::PERMISSION_CLIENT);
        $this->validate($request = $this->request(), [
            'clientUUID' => 'required|exists:client,uuid'
        ]);
        // MODEL
        $client = Client::find($clientUUID);

        $form = form()->enableRemote();
        $form->addLaravelValidator([
            'ref_id' => 'required|string'
        ]);
        $form->setLegend('Statut technique');
        $form->addAttribute("id","form-status");
        $clientStatus = array(Ref::CLIENT_STATUS_ACTIVE => "--Activé--", Ref::CLIENT_STATUS_FREEZED => "--Gelé--");

        $form->addSelect('client_status', "Statut technique", $clientStatus);
        $form->addSubmit('Enregistrer');
        // enregistrement
        if ($request->has('Enregistrer')) {
            $form->valid($request->all());
            if ($form->isValid()) {
                $data = $form->getFilteredAliasValues();
                try {
                    // SAVE
                    transaction(function() use ($data, $client) {
                        $client->client_status = $data['client_status'];
                        $client->save();

                        //FREEZE NOTIFICATIONS

                        $frozen = $data['client_status'] == Ref::CLIENT_STATUS_FREEZED ? true : false;

                        $jmakerInvitationTabUUID = array();

                        $jmakerTab = $client->jmakers()->where("state",Ref::JMAKER_STATE_INVITED)->get();
                        foreach($jmakerTab as $jmaker){
                            $invitation = $jmaker->invitation()->first();
                            if($invitation instanceof JmakerInvitation){
                                array_push($jmakerInvitationTabUUID,$invitation->uuid);
                            }
                        }

                        $jmakerNextNotificationSubscriptions = JmakerNextNotificationSubscription::whereIn('jmaker_invitation_uuid',$jmakerInvitationTabUUID)->get();
                        foreach ($jmakerNextNotificationSubscriptions as $jmakerNextNotificationSubscription) {
                            /** @var JmakerNextNotificationSubscription $jmakerNextNotificationSubscription  */
                            $jmakerNextNotificationSubscription->frozen = $frozen;
                            $jmakerNextNotificationSubscription->save(); //invitation only
                        }

                        $jmakerTabUUID =$client->jmakers()->withTrashed()->pluck('uuid');

                        $jmakerNextNotificationMeetings = JmakerNextNotificationMeeting::with(['jmaker'])->whereIn('jmaker_uuid',$jmakerTabUUID)->get();
                        foreach ($jmakerNextNotificationMeetings as $jmakerNextNotificationMeeting) {
                            /** @var JmakerNextNotificationMeeting $jmakerNextNotificationMeeting  */
                            if(!$frozen && !$jmakerNextNotificationMeeting->jmaker->want_notification) {
                                $frozen = true;
                            }
                            $jmakerNextNotificationMeeting->frozen = $frozen;
                            $jmakerNextNotificationMeeting->save();
                        }

                        $jmakerNextNotificationWorkshops = JmakerNextNotificationWorkshop::with(['jmaker'])->whereIn('jmaker_uuid',$jmakerTabUUID)->get();
                        foreach ($jmakerNextNotificationWorkshops as $jmakerNextNotificationWorkshop) {
                            /** @var JmakerNextNotificationWorkshop $jmakerNextNotificationWorkshop  */
                            if(!$frozen && !$jmakerNextNotificationWorkshop->jmaker->want_notification) {
                                $frozen = true;
                            }
                            $jmakerNextNotificationWorkshop->frozen = $frozen;
                            $jmakerNextNotificationWorkshop->save();
                        }

                    });

                    js()->success()->closeRemoteModal()->reloadDataTable();
                } catch (Exception $e) {
                    js()->error($e->getMessage());
                }
            }
        }else{
            $data = $client->toArray();
            $language = $client->languages();

            if ($language) {
                $data['lang_id'] = $language->pluck('id')->all();
            }
            $form->populate($data);
        }
        return response()->modal($form);
    }

}
