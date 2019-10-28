<?php namespace App\Http\Controllers\Partner;

use App\Events\Jmakers\JmakerInvitedEvent;
use App\Events\Jmakers\MeetingDateUpdatedEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExportCalendarRequest;
use App\Mail\Jobmaker\DebriefDateNotification;
use App\Mail\Jobmaker\Invite;
use App\Services\Client\ClientCampaignMetricJmakerService;
use App\Services\Client\ClientMetricJmakerService;
use App\Services\Client\ClientService;
use App\Services\Mail\MailService;
use App\Services\Prescriber\PrescriberMetricJmakerService;
use Carbon\Carbon;
use DateTime;
use Exception;
use FrenchFrogs\Container\Javascript;
use FrenchFrogs\Core\FrenchFrogsController;
use FrenchFrogs\Form\Element\Button;
use Gate;
use Ical\Ical;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Models\Acl\Partner;
use Models\Db\Clients\Client;
use Models\Db\Clients\ClientCampaign;
use Models\Db\Jmaker\Jmaker;
use Models\Db\Jmaker\JmakerInvitation;
use Models\Db\Jmaker\JmakerMeeting;
use Models\Db\Languages\LanguageContent;
use Models\Db\Languages\Languages;
use Models\Db\Mission\Mission;
use Models\Db\Prescriber\Prescriber;
use Ref;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Validator;
use function generateNewUUID;
use function js;
use function modal;
use function query;


/**
 * Interface partenaire front office
 *
 * Class DefaultController
 * @package App\Http\Controllers\Partner
 */
class PartnerJmakerController extends Controller
{
    use FrenchFrogsController;

    /**
     * List all Jobmakers
     * @return Factory|View
     * @throws Throwable
     */
    public function jobmakers()
    {
        //Get current client for jmaker
        $client = \prescriber()->client()->first();
        throw_if(empty($client), 'impossible de touver le client associé');

        $clientImgUrl = ClientService::getDefaultImgUrl($client);
        //True if admin false otherwise
        $partnerAdmin = \prescriber()->can(Partner::PERMISSION_PARTNER_ADMIN);

        if ($partnerAdmin) {
            $kpi = PrescriberMetricJmakerService::getClientKpi($client);
        } else {
            $kpi = PrescriberMetricJmakerService::getPrescriberAdminKpi($client);
        }

        // Get All Jobmaker for Current Prescribers.
        $jobmakers = $this->listJobmakersAndInvitation();


        return view('partnerV2.jobmakers',
            compact('client', 'clientImgUrl', 'jobmakers', 'partnerAdmin', 'kpi'));
    }

    /**
     * Create new invitation or update the previsou one, send email for all case.
     * @param null $jmakerInvitationUUID
     * @return mixed
     * @throws ValidationException
     * @throws Throwable
     */
    public function jmakerInvitation($jmakerInvitationUUID = null)
    {
        // recuperation du client
        $client = prescriber()->client()->first();
        $prescriber = prescriber();

        $this->validate($request = $this->request(), [
            'jmakerInvitationUUID' => 'exists:jmaker_invitation,uuid'
        ]);

        $jmakerLimit = $client->contracts()->first()->jmaker_limit;
        $clientJmakers = Jmaker::where('client_uuid', $client->uuid)->count();
        if ($clientJmakers >= $jmakerLimit && $jmakerLimit > -1) {
            $formHtml = "<div class='content'>
                    <div class='row justify-content-center'>
                        <div class='col-8 text-center'>
                            <p style='font-size:21px;font-family: Roboto, Montserrat, -apple-system, system-ui, BlinkMacSystemFont, \"Segoe UI\", \"Helvetica Neue\", Arial, sans-serif;
                            font-weight: 300;color: #39cfb4;'>" . __('1fYbsV') . "</p>
                        </div>
                    </div>";
            return response()->modal(null, $formHtml);
        }

        // MODEL
        $invitation = JmakerInvitation::findOrNew($jmakerInvitationUUID);
        $languages = $client->languages()->orderBy('id', 'desc')->get();
        if ($languages->count() > 1) {
            $languages = $languages->transform(function (Languages $language) {
                return ['name' => __($language->translate_code), 'id' => $language->id];
            });
        }

        // CAMPAIGN
        $campaigns = $client->campaigns()->get()->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE);
        if (!Gate::check(Partner::PERMISSION_PARTNER_ADMIN)) {
            $campaigns = prescriber()->campaigns()->get()->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE);
        }
        $campaigns = $campaigns->transform(function (ClientCampaign $campaign) {
            return ['name' => $campaign->name, 'uuid' => $campaign->uuid];
        });


        if ($languages->count() > 1) {
            $languageRule = collect($languages)->transform(function ($language) {
                return $language['id'];
            })->toArray();


            $validator = Validator::make($request->all(), [
                'email' => ['required', Rule::unique('jmaker_invitation', 'email')->ignore($invitation->uuid, 'uuid')
                    ->where(function ($query) {
                        return $query->where('deleted_at', null);
                    })],
                'firstname' => 'required',
                'lastname' => 'required',
                'language' => ['required', Rule::in($languageRule)]
            ], [
                'email.required' => 'l\'adresse email est obligatoire',
                'email.unique' => 'Un compte existe déja avec cette adresse email.',
                'required' => 'Ce champs est obligatoire'
            ]);

        } else {

            $validator = Validator::make($request->all(), [
                'email' => ['required', Rule::unique('jmaker_invitation', 'email')->ignore($invitation->uuid, 'uuid')
                    ->where(function ($query) {
                        return $query->where('deleted_at', null);
                    })],
                'firstname' => 'required',
                'lastname' => 'required',
            ], [
                'email.required' => 'l\'adresse email est obligatoire',
                'email.unique' => 'Un compte existe déja avec cette adresse email.',
                'required' => 'Ce champs est obligatoire'
            ]);
        }

        if ($client->meetingDateMandatory && !$client->enableB2BasB2C) {
            $validator->addRules(['meeting_date' => 'required']);
        }
        // TRAITEMENT
        if ($request->has('_token')) {

            $data = $request->all();

            unset($data['_token']);
            unset($data['___token']);
            unset($data['__campaign']);
            unset($data['__id']);
            unset($data['__invited_by_prescriber_uuid']);
            unset($data['__uuid']);
            unset($data['__jmakerInvitationUUID']);

            if (!$validator->fails() && !(!empty($data['meeting_date']) && empty($data['invited_by_prescriber_uuid']) && Gate::check(Partner::PERMISSION_PARTNER_ADMIN))) {


                try {
                    transaction(function () use ($client, $invitation, $data, $request, $prescriber) {

                            // On determine si on envoie le mail
                            $email = $request->has('send');

                            $meeting_date = null;
                            $meeting = null;

                            $meeting_date = null;
                            if (array_key_exists('meeting_date', $data)) {
                                $meeting_date = $data['meeting_date'];
                            }
                            $meeting = null;

                            if (Gate::check(Partner::PERMISSION_PARTNER_ADMIN)) {
                                $invitation->invited_by_prescriber_uuid = $data['invited_by_prescriber_uuid'];

                            } else {
                                $invitation->invited_by_prescriber_uuid = prescriber()->uuid;
                            }

                            // cas de la création
                            if (!$invitation->exists) {
                                $invitation->uuid = generateNewUUID();
                                $invitation->token = str_random(60);
                                $email = true;

                                if (!empty($meeting_date)) {
                                    $meeting = new JmakerMeeting();
                                    $meeting->uuid = generateNewUUID();
                                    $meeting->invited_by_prescriber_uuid = $invitation->invited_by_prescriber_uuid;
                                    $meeting->meeting_date = Carbon::createFromFormat(($prescriber->language == 'LANG_EN' ? 'm/d/Y' : 'd/m/Y'), $meeting_date);
                                    $meeting->type = Ref::MEETING_TYPE_INVITATION;
                                }


                            } else {

                                //Invitation already exist
                                $meeting = $invitation->jmakerMeetings()->whereNull("deleted_at")->orderBy('created_at', 'desc')->first();

                                $invitation->reminder_at = Carbon::now();
                                $invitation->reminder_count = $invitation->reminder_count + 1;


                                if (empty($meeting_date)) {
                                    //We delete all previous event.
                                    if ($meeting) {
                                        $meeting->delete();
                                        $meeting = null;
                                    }
                                } else {
                                    //we update the current meetings
                                    if ($meeting) {
                                        $meeting->meeting_date = Carbon::createFromFormat(($prescriber->language == 'LANG_EN' ? 'm/d/Y' : 'd/m/Y'), $meeting_date);
                                    } else {
                                        $meeting = new JmakerMeeting();
                                        $meeting->uuid = generateNewUUID();
                                        $meeting->invited_by_prescriber_uuid = $invitation->invited_by_prescriber_uuid;
                                        $meeting->meeting_date = Carbon::createFromFormat(($prescriber->language == 'LANG_EN' ? 'm/d/Y' : 'd/m/Y'), $meeting_date);
                                        $meeting->type = Ref::MEETING_TYPE_INVITATION;
                                    }
                                }
                            }

                            // cas de l'edition
                            if (!$invitation->isCompleted()) {

                                $invitation->email = trim($data['email']);

                                $invitation->data = $data;

                                $languages = $client->languages()->get();

                                if ($languages->count() == 1) {
                                    $languageId = $languages->first()->id;
                                } else {
                                    $validData = $client->languages()->get()->pluck('id')->all();

                                    //Check if $data['language_id'] in valid language;
                                    //If not we use default language FR
                                    if (in_array($data['language'], $validData)) {
                                        $languageId = $data['language'];
                                    } else {
                                        $languageId = Ref::LANG_FR;
                                    }
                                }

                                $invitation->language_id = $languageId;
                            }

                            $jmaker = null;
                            if (!$invitation->exists) {
                                //Create JMAKER

                                if ($languages->count() == 1) {
                                    $languageId = $languages->first()->id;
                                } else {
                                    $validData = $client->languages()->get()->pluck('id')->all();

                                    //Check if $data['language_id'] in valid language;
                                    //If not we use default language FR
                                    if (in_array($data['language'], $validData)) {
                                        $languageId = $data['language'];
                                    } else {
                                        $languageId = Ref::LANG_FR;
                                    }
                                }

                                $jmaker = new Jmaker();
                                $jmaker->uuid = generateNewUUID();
                                $jmaker->client_uuid = $client->uuid;
                                $jmaker->contract_uuid = $client->contracts()->first()->uuid;
                                $jmaker->campaign_uuid = $data['campaign'];
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

                            } else {
                                $jmaker = $invitation->jmaker()->first();
                            }

                            $jmaker->email = $data['email'];
                            $jmaker->username = $data['firstname'] . ' ' . $data['lastname'];
                            $jmaker->firstname = $data['firstname'];
                            $jmaker->lastname = $data['lastname'];
                            $invitation->campaign_uuid = $data['campaign'];

                            $previousCampaign = $jmaker->campaign_uuid;

                            $jmaker->campaign_uuid = $data['campaign'];
                            $jmaker->save();

                            if (Gate::check(Partner::PERMISSION_PARTNER_ADMIN)) {
                                $invitation->invited_by_prescriber_uuid = $data['invited_by_prescriber_uuid'];
                            } else {
                                $invitation->invited_by_prescriber_uuid = prescriber()->uuid;
                            }

                            // Si l'utilisateur à un Partner
                            if ($client->partner) {
                                $invitation->partner_id = $client->partner->id;
                            }

                            $invitation->save();

                            if ($previousCampaign != $invitation->campaign_uuid) {
                                ClientCampaignMetricJmakerService::updateJmakerMetrics($previousCampaign);
                            }

                            ClientCampaignMetricJmakerService::updateJmakerMetrics($invitation->campaign_uuid);

                            if (Gate::check(Partner::PERMISSION_PARTNER_ADMIN)) {
                                PrescriberMetricJmakerService::updateJmakerMetrics($data['invited_by_prescriber_uuid']);
                            } else {
                                PrescriberMetricJmakerService::updateJmakerMetrics(\prescriber()->uuid);
                            }

                            ClientMetricJmakerService::updateJmakerMetrics($client->uuid);

                            if (!empty($meeting)) {
                                $meeting->invitation_uuid = $invitation->uuid;
                                $meeting->jmaker_uuid = $invitation->jmaker_uuid;
                                $meeting->save();
                            }

                            // envoie du mail
                            $email && MailService::pushInDB(Invite::class, $invitation->email, $invitation->token);
                            $email && event(new JmakerInvitedEvent($invitation));
                        });

                        $clientImgUrl = ClientService::getDefaultImgUrl($client);
                        $partnerAdmin = \prescriber()->can(Partner::PERMISSION_PARTNER_ADMIN);

                        $name = null;
                        $email = null;
                        $noInvit = null;

                        if (array_key_exists('meeting_date', $data)) {

                            $meeting_date = $data['meeting_date'];

                            if(!empty($meeting_date)) {
                                $date = explode('/',$data['meeting_date']);
                                if ($prescriber->language == 'LANG_EN') {
                                    $dateForCalendarStart = $date[0] . " " . $date[1] . "," . $date[2] . " 00:00:00";
                                } else {
                                    $dateForCalendarStart = $date[1] . " " . $date[0] . "," . $date[2] . " 00:00:00";
                                }
                                //Creation d'un objet dateTime dans le but de cree une date de fin d'évènement égale a D+1
                                $datetime = new DateTime();
                                $newDate = $datetime->createFromFormat(($prescriber->language == 'LANG_EN' ? 'm/d/Y' : 'd/m/Y'), $data['meeting_date']);
                                //$nextDay = $newDate->modify('+1 day');
                                $dateForCalendarEnd = $newDate->format('m')." ".$newDate->format('d').",".$newDate->format('Y')." 00:00:00";
                                $name = prescriber()->name;
                                $email = $data['email'];
                                $noInvit = false;

                                return view('partnerV2.calendar',compact('clientImgUrl','client','partnerAdmin','dateForCalendarStart','dateForCalendarEnd','name','email','noInvit'));

                            } else {
                                js()->success()->closeRemoteModal()->reload();
                            }

                        } else {
                            js()->success()->closeRemoteModal()->reload();
                        }

                    } catch (Exception $e) {

                        js()->error($e->getMessage());
                    }


            } else {
                //Manuel testing to bypass FrenchFrog validation
                if (!empty($data['meeting_date']) & empty($data['invited_by_prescriber_uuid'])) {
                    js()->error("invited_by_prescriber_uuid:required: Le prescripteur est obligatoire lorsqu'une date de debrief est saisie");
                }
            }

        } elseif ($invitation->exists) {

            $populate['invited_by_prescriber_uuid'] = $invitation->invited_by_prescriber_uuid;

        } else {
            $populate['invited_by_prescriber_uuid'] = prescriber()->uuid;
        }

        $currentCampaginUiid = null;
        $formMeetingDateHtml = null;
        if ($request->has('_token')) {
            //Init aved les onnées précédement envoyées.

            $formMeetingDateHtml = empty($data['meeting_date']) ? '' : $data['meeting_date'];

        } else {
            if ($invitation->exists) {
                $data = $invitation->data;

                $meeting = $invitation->jmakerMeetings()->whereNull("deleted_at")->orderBy('created_at', 'desc')->first();
                if ($meeting) {
                    $data['meeting_date'] = $meeting->meeting_date;
                }

                $currentCampaginUiid = $invitation->campaign()->first()->uuid;

                $formMeetingDateHtml = empty($data['meeting_date']) ? '' : Carbon::parse($data['meeting_date'])->format(($prescriber->language == 'LANG_EN' ? 'm/d/Y' : 'd/m/Y'));

            } else {
                //DoNothing
            }
        }

        $formFirstnameHtml = empty($data['firstname']) ? '' : $data['firstname'];
        $formLastnameHtml = empty($data['lastname']) ? '' : $data['lastname'];
        $formMessageHtml = empty($data['message']) ? '' : $data['message'];
        $formEmailHtml = empty($data['email']) ? '' : trim($data['email']);


        $formTitleHtml = __('g5j2lx');
        if (null != $jmakerInvitationUUID) { // Create new invitation
            $formTitleHtml = "Relancer l'invitation";
        }

        $formIdHtml = 'form-' . rand();
        $formActionHtml = str_replace(request()->getSchemeAndHttpHost(), '', request()->fullUrl());

        $errors = $validator->errors();

        $errorEmailClass = '';
        $errorEmailMessage = '';

        if ($request->has('_token')) {
            if ($errors->has('email')) {
                $errorEmailClass = 'has-danger';
                $errorEmailMessage = $errors->first('email');
            }
        }
        $firstname = __('JdDJ6v');
        $lastname = __('qpGvzN');
        $debrief = __('gijdGs');
        $message = __('XxTikK');
        $campagne = __('BNnbBi');
        $lang = __('pfLvWa');
        $pres = __('FPszCu');
        $send = __('IbpDp2');
        $emailTxt = __('2UgDMW');
        $csrf_token = csrf_token();

        $formHtml = "<div class='content'>
                    <div class='row justify-content-center'>
                        <div class='col-6 text-center'>
                            <p style='font-size:21px;'>{$formTitleHtml}</p>
                        </div>
                    </div>
                    <form id='{$formIdHtml}'  action='{$formActionHtml}' url='{$formActionHtml}' method='POST' role='form' class='form-remote form-horizontal'>
                        <div style=''>
                            <input type='hidden' name='_token' value='{$csrf_token}'>
                            <div class='container-fluid'>
                                <div class='row justify-content-center'>
                                    <div class='col-5' style='margin-top:14px;'>
                                        <p style='font-size:12px;font-weight: 300;padding-left:10px;margin-bottom: 0.2rem;'>{$firstname}</p>
                                        <input class='form-control ' name='firstname' type='text' value='{$formFirstnameHtml}' id='firstname-input' required='required'>
                                        <div class='form-control-feedback'></div>
                                    </div>
                                    <div class='col-5' style='margin-top:14px;'>
                                        <p style='font-size:12px;font-weight: 300;padding-left:10px;margin-bottom: 0.2rem;'>{$lastname}</p>
                                        <input class='form-control ' name='lastname' type='text' value='{$formLastnameHtml}' id='lastname-input' required='required'>
                                        <div class='form-control-feedback'></div>
                                    </div>
                                </div>
                                <div class='row justify-content-center'>
                                    <div class='col-5' style='margin-top:14px;'>
                                        <p style='font-size:12px;font-weight: 300;padding-left:10px;margin-bottom: 0.2rem;'>{$emailTxt}*</p>
                                        <input class='form-control {$errorEmailClass}' type='email' value='{$formEmailHtml}' id='email-input' name='email' required='required'>
                                        <div class='form-control-feedback'>{$errorEmailMessage}</div>
                                    </div>
                                    <div class='col-5' style='margin-top:14px;'>";

        if (!$client->enableB2BasB2C) {

            if ($client->meetingDateMandatory) {
                $formHtml .= "<p style='font-size:12px;font-weight: 300;padding-left:10px;margin-bottom: 0.2rem;'>{$debrief}</p>
                                                        <span id='datepickeron' class='input-group'>
                                                        <input class='form-control' placeholder='" . ($prescriber->language == 'LANG_EN' ? 'mm/dd/yyyy' : 'jj/mm/aaaa') . "' name='meeting_date' value='{$formMeetingDateHtml}' id='debrief-input' style='width: 77%' required='required'>";
            } else {
                $formHtml .= "
                                                            <p style='font-size:12px;font-weight: 300;padding-left:10px;margin-bottom: 0.2rem;'>Debrief</p>
                                                            <span id='datepickeron' class='input-group'>
                                                                <input class='form-control' placeholder='" . ($prescriber->language == 'LANG_EN' ? 'mm/dd/yyyy' : 'jj/mm/aaaa') . "' name='meeting_date' value='{$formMeetingDateHtml}' id='debrief-input' style='width: 77%;'>";
            }

            $formHtml .= "<span class='input-group-text'><i class='mdi mdi-calendar'></i></span>
                                        </div>";
        }

        $formHtml .= "
                                        </div>
                                </div>
                                <div class='row justify-content-center'>
                                    <div class='col-10' style='margin-top:14px;'>
                                        <p style='font-size:12px;font-weight: 300;padding-left:10px;margin-bottom: 0.2rem;'>{$message}</p>
                                        <textarea id='message-input' name='message' class='form-control  ' maxlength='800' rows='6' placeholder=''>{$formMessageHtml}</textarea>
                                    </div>
                                </div>
                                <div class='row justify-content-center'>
                                    <div class='col-5' style='margin-top:14px;'>
                                        <p style='font-size:12px;font-weight: 300;padding-left:10px;margin-bottom: 0.2rem;'>{$campagne}</p>
                                        <select class='form-control' name='campaign' id='campaign-input'>";

        $formHtml .= "<script>
                        $(document).ready(function () {
                         $('#datepickeron').datepicker({
                           weekStart: 1, 
                           language: '" . ($prescriber->language == 'LANG_EN' ? 'en' : 'fr') . "',
                           autoclose: true,
                           todayHighlight: true
                         });
                        });
                      </script>";
        foreach ($campaigns as $campaign) {

            $campaginName = $campaign['name'];
            $campaginUuid = $campaign['uuid'];

            $campaginSelected = '';
            if ($invitation->exists && 0 == strcmp($currentCampaginUiid, $campaginUuid)) {
                $campaginSelected = "selected='selected'";
            }

            $formHtml .= "<option value='{$campaginUuid}' {$campaginSelected}>{$campaginName}</option>";
        }

        $formHtml .= "</select>
                                    </div><div class='col-5' style='margin-top:14px;'>";

        if ($languages->count() > 1) {

            $formHtml .= "<p style='font-size:12px;font-weight: 300;padding-left:10px;margin-bottom: 0.2rem;'>{$lang}</p>
                                        <select class='form-control' name='language' id='language-input'>";

            foreach ($languages as $language) {
                $languageNameHtml = $language['name'];
                $LanguageUuid = $language['id'];

                $LanguageSelected = '';

                if ($invitation->exists && 0 == strcmp($currentCampaginUiid, $campaginUuid)) {
                    $LanguageSelected = "selected='selected'";
                }

                $formHtml .= "<option value='{$LanguageUuid}' {$LanguageSelected}>{$languageNameHtml}</option>";
            }


            $formHtml .= "</select>";
        }

        $formHtml .= "</div></div>";

        if (!$invitation->exists && Gate::check(Partner::PERMISSION_PARTNER_ADMIN)) {
            $prescribers = $client->prescribers()->orderBy('lastname')->get();

            $formHtml .= "<div class='row justify-content-center'>
                                    <div class='col-5' style='margin-top:14px;'>
                                        <p style='font-size:12px;font-weight: 300;padding-left:10px;margin-bottom: 0.2rem;'>{$pres}</p>
                                        <select class='form-control' name='invited_by_prescriber_uuid' id='invited_by_prescriber_uuid-input' >";

            foreach ($prescribers as $prescriberValue) {
                /** @var Prescriber $prescriberValue */
                $prescriber_uuid = $prescriberValue->uuid;
                $prescriber_lastname = $prescriberValue->lastname;
                $prescriber_firstname = $prescriberValue->firstname;
                $formHtml .= "<option value='{$prescriber_uuid}'>{$prescriber_lastname} {$prescriber_firstname}</option>";
            }

            $formHtml .= "              </select>
                                    </div><div class='col-5' style='margin-top:14px;'></div>
                                </div>";

        } else {
            if ($invitation->exists) {
                $prescriber_uuidHtml = $invitation->invited_by_prescriber_uuid;
            } else {
                $prescriber_uuidHtml = prescriber()->uuid;
            }

            $formHtml .= "<input type='hidden' name='invited_by_prescriber_uuid' value='{$prescriber_uuidHtml}'>";
        }


        $formHtml .= "<div class='row justify-content-center'>
                                    <div class='col-10' style='margin-top:14px;'>
                                        <div class='text-right'>
                                            <button type='submit' name='send' class='btn btn-primary button-title waves-effect waves-light' style='margin-top: 20px;margin-right: 0px;font-weight: 600;font-size: 12.5px;'>
                                                <i class='ti-user'></i>&nbsp;&nbsp;|&nbsp;&nbsp;{$send}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                
                        </div>
                    </form></div>";

        return response()->modal(null, $formHtml);
    }

    /**
     * Update and send email for new meeting date
     *
     * @param $jmakerInvitationUUID invitation uuid
     * @return mixed
     * @throws Throwable
     */
    public function postMeetingDate(Request $request, $jmakerInvitationUUID = null)
    {
        $client = \prescriber()->client()->first();
        $prescriber = \prescriber();
        $this->validate($request = $this->request(), [
            'jmakerInvitationUUID' => 'exists:jmaker_invitation,uuid'
        ]);

        // MODEL
        $invitation = JmakerInvitation::findOrFail($jmakerInvitationUUID);
        $jmaker = $invitation->jmaker()->first();

        $validator = Validator::make($request->all(), [
            'meetingDate' => 'required',
        ], [
            'required' => 'Ce champs est obligatoire'
        ]);
        $meeting = $jmaker->meetings()->whereNull("deleted_at")->orderBy('created_at', 'desc')->first();
        // TRAITEMENT
        if ($request->has('_token')) {

            $data = $request->all();
            if (!$validator->fails()) {
                //$data = $form->getFilteredValues();

                try {
                    transaction(function () use ($client, $invitation, $data, $request, $prescriber) {

                        //Find all meeting for jmaker and delete them.
                        $jmaker = $invitation->jmaker()->first();

                        $prevMeeting = $jmaker->meetings()->first();
                        if($prevMeeting)
                        {
                            $prevMeeting->delete();
                        }

                        $meeting_date = $data['meetingDate'];
                        if(isset($data['message']))
                        {
                            $message = $data['message'];
                        }
                        $meeting = new JmakerMeeting();
                        $meeting->uuid = generateNewUUID();
                        $meeting->invited_by_prescriber_uuid = $invitation->invited_by_prescriber_uuid;
                        $meeting->meeting_date = Carbon::createFromFormat(($prescriber->language == 'LANG_EN' ? 'm/d/Y' : 'd/m/Y'), $meeting_date);
                        $meeting->jmaker_uuid = $jmaker->uuid;
                        $meeting->type = Ref::MEETING_TYPE_SCHEDULED;
                        $meeting->save();

                        event(new MeetingDateUpdatedEvent($jmaker,$meeting));
                        //Send email
                        MailService::pushInDB(DebriefDateNotification::class,$jmaker->email, $jmaker->uuid, $meeting->meeting_date,$message,$request->get('postpone'));
                    });

                    //\js()->success()->closeRemoteModal()->reload();
                    $clientImgUrl = ClientService::getDefaultImgUrl($client);
                    $partnerAdmin = \prescriber()->can(Partner::PERMISSION_PARTNER_ADMIN);
                    $date =explode('/',$data['meetingDate']);
                    if ($prescriber->language == 'LANG_EN') {
                        $dateForCalendarStart = $date[0] . " " . $date[1] . "," . $date[2] . " 00:00:00";
                    } else {
                        $dateForCalendarStart = $date[1] . " " . $date[0] . "," . $date[2] . " 00:00:00";
                    }
                    //Creation d'un objet dateTime dans le but de cree une date de fin d'évènement égale a D+1
                    $datetime = new DateTime();
                    $newDate = $datetime->createFromFormat(($prescriber->language == 'LANG_EN' ? 'm/d/Y' : 'd/m/Y'), $data['meetingDate']);
                    $nextDay = $newDate->modify('+1 day');
                    $dateForCalendarEnd = $newDate->format('m')." ".$newDate->format('d').",".$newDate->format('Y')." 00:00:00";

                    if ($meeting) {
                        $noInvit = true;
                    }else{
                        $noInvit = false;
                    }
                    $name = prescriber()->name;
                    $email = $jmaker->email;
                    js()->reloadDataTable();

                    return view('partnerV2.calendar',compact('clientImgUrl','client','partnerAdmin','data','dateForCalendarStart','dateForCalendarEnd','name','email','noInvit'));
                } catch (Exception $e) {
                    js()->error($e->getMessage());
                }
            } else {
                //RETURN ERROR
                //Manuel testing to bypass FrenchFrog validation
                if (!empty($data['meetingDate']) & empty($data['jmaker_uuid'])) {
                    js()->error("uuid:required: Le prescripteur est obligatoir lorsqu'une date de debrief est saisie");
                }
            }
        }

        //jmaker already has meeting date
        $meeting = $jmaker->meetings()->whereNull("deleted_at")->orderBy('created_at', 'desc')->first();


        $languageRid = $jmaker->language_id;

        if(empty($languageRid)) {
            $languageRid = 'LANG_FR';
        }

        $meeting_dateHtml = '';
        $formTitleHtml = __('RsnWkg');

        $meetingIntroductionCode = 'RsnWkg';
        if ($meeting) {
            $populate['meetingDate'] = $meeting->meeting_date;
            $meeting_dateHtml = Carbon::parse($meeting->meeting_date)->format(($prescriber->language == 'LANG_EN' ? 'm/d/Y' : 'd/m/Y'));
            $formTitleHtml = __('VWqDGG');
            $meetingIntroductionCode = 'ng5zSO';
        }

        /** @var LanguageContent $meetingIntroductionLanguageContent */
        $meetingIntroductionLanguageContent = LanguageContent::where('code','=',$meetingIntroductionCode)
            ->where('lang_rid','=',$languageRid)
            ->where('is_published','=',true)
            ->first();
        $meetingIntroductionText = $meetingIntroductionLanguageContent->instruction['params'][0];

        $meetingIntroductionText = sprintf($meetingIntroductionText,'');

        $formIdHtml = 'form-' . rand();
        $formActionHtml = str_replace(request()->getSchemeAndHttpHost(), '', request()->fullUrl());

        $htmlForm = "<form id='{$formIdHtml}'  action='{$formActionHtml}' url='{$formActionHtml}' method='POST' role='form' class='form-remote'>
                    <div class='modal-body'>
                        <div class='row justify-content-center'><div class='col-md-12 text-center' style='font-size:22px;'>{$formTitleHtml}</div></div>                    
                        <input type='hidden' name='_token' value='{csrf_token()}'>
                         <div class='row justify-content-center'>
                         <div class='col-10' style='margin-top:14px;'>
                                <p style='font-size:11px;font-weight: 300;padding-left:10px;margin-bottom: 0.2rem;'>
                                    <label for='meetingDate'>".__('61bVZP')."</label>                                  
                                </p>
                                   <input value='{$meetingIntroductionText}' class='date-picker form-control' disabled='disabled'>
                                   <p style='font-size:11px;font-weight: 300;padding-left:2px;margin-bottom: 0.2rem;font-style: italic;color: darkgray;padding-top: 1%;'>";
                                    if($jmaker->language_id == "LANG_FR"){
                                        $htmlForm .= __('FFz63d').$jmaker->firstname." ".$jmaker->lastname." ".__('in8ap8')." ".__('4fQa3D');
                                    }else{
                                        $htmlForm .=__('FFz63d').$jmaker->firstname." ".$jmaker->lastname." ".__('in8ap8')." ".__('czDhVO');
                                    }
                                    $htmlForm.="
                            </p>
                            </div>
                            <div class='col-10' style='margin-top:14px;'>
                                <p style='font-size:11px;font-weight: 300;padding-left:10px;margin-bottom: 0.2rem;'>
                                    <label for='meetingDate'>".__('gijdGs')."</label>
                                </p>
                                <span id='datepickeron' class='input-group'>
                                    <input name='meetingDate'
                                           required
                                           format='" . ($prescriber->language == 'LANG_EN' ? "mm/dd/yyyy" : "dd/mm/yyyy") . "'
                                           autocomplete='off'
                                           value='{$meeting_dateHtml}'
                                           class='form-control'>
                                          </span>
                            </div>
                               <br>
                             <div class='col-10' style='margin-top:14px;'>
                                <p style='font-size:11px;font-weight: 300;padding-left:10px;margin-bottom: 0.2rem;'>
                                    <label for='message'>Message</label>
                                </p>
                                <textarea name='message'
                                       class='form-control'
                                       rows='8' 
                                       cols='33' />
                            </div>
                            
                         </div>
                         <div class='row justify-content-center m-t-30'>
                            <div class='col-8' style='font-size:11px;font-weight: 300;margin-left: 16%;'>
                                <p>".__('9B0bS5')."</p>
                            </div>
                         </div>
                    </div>
                    <div class='modal-footer'>
                        <button name='send' type='submit' class='btn-primary btn' value='Envoyer'><i class='ti-email'></i>&nbsp;&nbsp;|&nbsp;&nbsp;".__('IbpDp2')."
                    </button>
                    </div>
                </form>";

        $htmlForm .="<script>
                        $(document).ready(function () {
                         $('#datepickeron').datepicker({
                           weekStart: 1, 
                           language: " . ($prescriber->language == 'LANG_EN' ? "'en'" : "'fr'") . ",
                           autoclose;: true,
                           todayHighlight;: true
                         })
                        });
                      </script>";

        return response()->modal(null, $htmlForm);
    }


    /**
     * Show jmaker information
     *
     * @param $jmakerUUID
     * @return mixed
     * @throws Exception
     */
    public function getView($jmakerUUID)
    {
        $this->validate($request = $this->request(), [
            'jmakerUUID' => [
                'required',
                Rule::exists('jmaker', 'uuid')
            ]
        ]);

        /** @var Jmaker $jmaker */
        $jmaker = Jmaker::find($jmakerUUID);
        /** @var Client $client */
        $client = $jmaker->client()->first();

        $lastSharedSynthesis = null;
        $nextMeeting = null;
        $langueHtml = '-';
        $lastActivityAtHtml = '-';
        $meetingDateHtml = '-';
        $reminderAtHtml = '-';
        $reminderCount = '0';

        $dateFormat = 'd/m/Y';

        if('LANG_EN' == \prescriber()->language) {
            $dateFormat = 'm/d/Y';
        }


        $nameHtml = $jmaker->firstname . " " . $jmaker->lastname;
        $emailHtml = $jmaker->email;

        $codes = array(
            'utilisateur'=>__('3RnyFb'),
            'detail'=>__('3RnyFb'),
            'invite'=>__('yNb7LL'),
            'langue_interface'=>__('uGxCUS'),
            'date_debrief'=>__('gijdGs'),
            'activation'=>__('93xKse'),
            'derniere_connexion'=>__('AqlkQW'),
            'synthese_partage'=>__('eoxh8V'),
            'etape'=>__('IFDAKH'),
            'statut'=>__('xoGQeq'),
            'commence'=>__('4vRkeD'),
            'termine'=>__('p9egeK'),
            'see'=>__('zaRi7U'),
        );


        $lastSharedSynthesis = $jmaker->synthesisShares()->orderBy("shared_at", 'desc')->first();
        $nextMeeting = $jmaker->nextMeeting();
        if('LANG_EN' == \prescriber()->language) {
            $langueHtml = $jmaker->language()->first()->id == "LANG_EN" ? "English" : "French";
        } else  {
            $langueHtml = $jmaker->language()->first()->id == "LANG_EN" ? "Anglais" : "Français";
        }


        $lastActivityAtHtml = $jmaker->last_page_at ? Carbon::parse($jmaker->last_page_at)->format($dateFormat) : '-';
        $meetingDateHtml = $nextMeeting ? Carbon::parse($nextMeeting->meeting_date)->format($dateFormat) : '-';
        $firstnameHtml = $jmaker->firstname;
        $lastnameHtml = $jmaker->lastname;
        $emailHtml = $jmaker->email;

        /** @var Collection $jmakerEvents */
        $jmakerEvents = $jmaker->events()->orderBy("date","desc")->get();

        if($jmakerEvents->isNotEmpty()) {
            $reminderAtHtml = $jmakerEvents->first()->date ? Carbon::parse($jmakerEvents->first()->date)->format($dateFormat) : '-';
            $reminderCount = $jmakerEvents->count();
        }

        $reminderAtHtmlTitle = __('fhOukq');
        $reminderCountTitle = __('ODWA33');


        $synthesisSharedAtHtml = $lastSharedSynthesis ? Carbon::parse($lastSharedSynthesis->shared_at)->format($dateFormat) : "-";
        $synthesisSharedAtLink = $lastSharedSynthesis ? "<a target='_blank' href='" . route('partner.jobmakers.rapport', ['id' => $jmaker->uuid]) . "'>{$codes["see"]}</a>" : "";
        $invitedAtHtml = $jmaker->created_at ? Carbon::parse($jmaker->created_at)->format($dateFormat) : '-';
        $completed_at = $jmaker->invitation()->first()->completed_at;
        $completedAtHtml =  $completed_at ? Carbon::parse($completed_at)->format($dateFormat) : '-';


        $globalNew = "<div class='row justify-content-center'><div class='col-md-12 text-center' style='font-size:22px;'>{$nameHtml}</div></div>";
        $globalNew .= "<div class='row justify-content-center'><div class='col-md-12 text-center' style='font-size:13px;'>{$emailHtml}</div></div>";
        $globalNew .= '<div class="row justify-content-center m-t-20" style="font-size=12.5px;padding-left:35px;padding-right:35px;">';
        $globalNew .= '<div class="col-md-6"><div class="mt-0" >'.__('3RnyFb').'</div>';
        $globalNew .= "<table class='table table-striped' style='font-size: 12px;'>
                                                <thead>
                                                <tr>
                                                    <th style='padding:0px;'></th>
                                                    <th style='padding:0px;'></th>
                                                    <th style='padding:0px;'></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td >{$codes["invite"]}</td>
                                                    <td >{$invitedAtHtml}</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td >{$reminderAtHtmlTitle}</td>
                                                    <td >{$reminderAtHtml}</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>{$codes["langue_interface"]}</td>
                                                    <td>{$langueHtml}</td>
                                                    <td></td>
                                                </tr>";
        if (!$client->enableB2BasB2C) {
            $globalNew .= "<tr>
                                                    <td>{$codes["date_debrief"]}</td>
                                                    <td>{$meetingDateHtml}</td>
                                                    <td></td>
                                                </tr>";
        }

        $globalNew .= "</tbody>
                                                <tfoot>
                                                <tr>
                                                    <td style='padding:0px;'></td>
                                                    <td style='padding:0px;'></td>
                                                    <td style='padding:0px;'></td>
                                                </tr>
                                                </tfoot>
                                            </table>";

        $globalNew .= '</div>';
        $globalNew .= '<div class="col-md-6" >';
        $globalNew .= '<div class="mt-0">'.__('V4dvuO').'</div>';
        $globalNew .= "<table class='table table-striped' style='font-size: 12px;'>
                                                <thead>
                                                <tr>
                                                    <th style='padding:0px;'></th>
                                                    <th style='padding:0px;'></th>
                                                    <th style='padding:0px;'></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>{$codes["activation"]}</td>
                                                    <td>{$completedAtHtml}</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td >{$reminderCountTitle}</td>
                                                    <td >{$reminderCount}</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>{$codes["derniere_connexion"]}</td>
                                                    <td>{$lastActivityAtHtml}</td>
                                                    <td></td>
                                                </tr>";
                                    if (!$client->enableB2BasB2C) {
                                    $globalNew .= "<tr>
                                                    <td>{$codes["synthese_partage"]}</td>
                                                    <td>{$synthesisSharedAtHtml}</td>
                                                    <td>{$synthesisSharedAtLink}</td>
                                                </tr>";
                                    }
                                $globalNew .= "</tbody>
                                                <tfoot>
                                                <tr>
                                                    <td style='padding:0px;'></td>
                                                    <td style='padding:0px;'></td>
                                                    <td style='padding:0px;'></td>
                                                </tr>
                                                </tfoot>
                                            </table>";
        $globalNew .= '</div></div>';

        if (!empty($jmaker) && $jmaker->state == Ref::JMAKER_STATE_ACTIVE && !$client->enableB2BasB2C) {
            $runs = $jmaker->runs;

            // Recommandation checkup mobilité
            if ($run = $runs->where('mission_id', Mission::CHECKUP_SAFRAN)->first()) {

                array_get($run->production, 'final') && ($name = array_get($run->production['final'], 'name'));
                $nm = $runs->whereIn('status_rid', [Ref::RUN_STATUS_INPROGRESS, Ref::RUN_STATUS_FINISHED])
                    ->whereNotIn('mission_id', [Mission::DETOX, Mission::CHECKUP_SAFRAN])
                    ->isNotEmpty();

                $nmHtml = $nm ? 'OUI' : 'NON';

                $globalNew .= '<div class="row m-t-20 justify-content-center" style="font-size=12.5px;padding-left:35px;padding-right:35px;"><div class="col-md-12">';
                $globalNew .= '<div class="mt-0">Checkup Mobility</div>';
                $globalNew .= "<table class='table table-striped' style='font-size: 12px;'>
                                                    <tbody>
                                                    <tr>
                                                        <td>Recommandation</td>
                                                        <td>{$name}</td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Next Move Inside</td>
                                                        <td>{$nmHtml}</td>
                                                        <td></td>
                                                    </tr>
                                                    </tbody>
                                                </table>";
                $globalNew .= '</div></div>';

            }

            $missions = Mission::pluck('title', 'id');
            $missionNamesArray = array(
                1 => __("C8OaW4"),
                2 => __("gD9H9S"),
                3 => __("GkbWPc"),
                4 => __("lAfcvb"),
                5 => __("MTwHvJ"),
                6 => __("CNWmgL"),
                7 => __("BG0IzD"),
                8 => __("IrrvwG"),
                9 =>__("C8OaW4"),
                10 =>__("gD9H9S"),
                11 => __("GkbWPc"),
                12 => __("lAfcvb"),
                13 => __("MTwHvJ"),
                14 => __("CNWmgL"),
                15 => __("BG0IzD"),
                16 => __("C8OaW4"),
                17 => __("gD9H9S"),
                18 => __("GkbWPc"),
                19 => __("lAfcvb"),
                20 => __("MTwHvJ"),
                21 => __("CNWmgL"),
                22 => __("BG0IzD"),
                23 => __("IrrvwG"),
                24 => __("IrrvwG"),
                25 => "Checkup mobility",
                26 => "My Ecosytem"
            );
            $status = ref('run.status')->pairs();

            if('LANG_EN' == \prescriber()->language) {
                $status = [
                    'RUN_STATUS_ACCESSIBLE' => 'Accessible',
                    'RUN_STATUS_ARCHIVED' => 'Archived',
                    'RUN_STATUS_FINISHED' => 'Finished',
                    'RUN_STATUS_FINISHED_CHECKUP_WITH_JOBMAKER' => 'Finished',
                    'RUN_STATUS_HIDDEN' =>'Hidden',
                    'RUN_STATUS_INPROGRESS' =>'In progress',
                    'RUN_STATUS_VISIBLE' => 'Visible'
                ];
            }

            $globalNew .= '<div class="row m-t-20 justify-content-center" style="font-size=12.5px;padding-left:35px;padding-right:35px;"><div class="col-md-12">';
            $globalNew .= '<div class="mt-0">'.__('Ov08SF').'</div>';
            $globalNew .= "<table class='table table-striped' style='font-size: 12px;'>
                                                <thead>
                                                <tr>
                                                    <th style='width:200px;'>{$codes['etape']}</th>
                                                    <th style='width:200px;'>{$codes['statut']}</th>
                                                    <th class='text-center'>{$codes['commence']}</th>
                                                    <th class='text-center'>{$codes['termine']}</th>
                                                </tr>
                                                </thead>
                                                <tbody>";
            foreach ($runs as $run) {
                $missionNameHtml = $missionNamesArray[$run->mission_id];
                $missionStatusHtml = $status[$run->status_rid];
                $startedAtHtml = $run->started_at ? Carbon::parse($run->started_at)->format($dateFormat) : '-';
                $completedAtHtml = $run->completed_at ? Carbon::parse($run->completed_at)->format($dateFormat) : '-';
                $globalNew .= "<tr>
                                                    <td>{$missionNameHtml}</td>
                                                    <td>{$missionStatusHtml}</td>
                                                    <td class='text-center'>{$startedAtHtml}</td>
                                                    <td class='text-center'>{$completedAtHtml}</td>
                                                </tr>";
            }

            $globalNew .= "</tbody><tfoot><tr><td></td><td></td><td></td><td></td></tr></tfoot></table></div></div>";
        }

        return response()->modal(null, $globalNew);
    }

    /**
     * Download synthesis
     * @param $jmakerUUID
     * @return ResponseFactory|\Illuminate\Http\Response
     * @throws FileNotFoundException
     * @throws ValidationException
     */
    public function getRapport($jmakerUUID)
    {
        //RULER
        $this->validate($this->request(), ['jmakerUUID' => 'required|exists:way,jmaker_uuid']);

        // recuperation du client
        $client = \prescriber()->client()->first();

        $partnerAdmin = \prescriber()->can(Partner::PERMISSION_PARTNER_ADMIN);

        if(!$partnerAdmin) {
            //['invited_by_prescriber_uuid', \prescriber()->uuid]
            $invitation = JmakerInvitation::where('invited_by_prescriber_uuid', '=', \prescriber()->uuid);
        }
        $jmakerQuery = query('jmaker')
            ->addSelect('uuid')
            ->addSelect('lastname')
            ->addSelect('firstname');

        $results = query('synthesis_share as ss')
            ->selectRaw('DATE_FORMAT(ss.shared_at, \'%Y-%m-%d\') AS shared_at')
            ->addSelect('j.lastname')
            ->addSelect('j.firstname')
            ->leftJoinQuery($jmakerQuery, 'j', 'j.uuid', 'ss.jmaker_uuid')
            ->where('j.uuid', $jmakerUUID)
            ->orderBy('shared_at', 'desc')
            ->first();

        if (empty($results)) {
            abort(404, 'No synthesis found for this Jobmaker.');
        }

        //test if PDF file exist
        $exists = Storage::disk('synthesisPDF')->exists("synthesis-" . $jmakerUUID . ".pdf");
        if (!$exists) {
            abort(404, 'No PDF file found for this Jobmaker.');
        }

        $PDFfile = Storage::disk('synthesisPDF')->get("synthesis-" . $jmakerUUID . ".pdf");

        $lastname = $results->lastname;
        $firstname = $results->firstname;
        $pdfFilename = "Jobmaker";
        if ($lastname !== '') {
            $pdfFilename = $pdfFilename . "_" . $lastname;
        }
        if ($firstname !== '') {
            $pdfFilename = $pdfFilename . "_" . $firstname;
        }

        $pdfFilename = $pdfFilename . "_" . $results->shared_at . ".pdf";

        return response($PDFfile)
            ->header('Content-Type', "application/pdf")
            ->header('Content-Disposition', 'inline; filename="' . $pdfFilename . '"');
    }

    /**
     * Delete invitation.
     * @param $jmakerInvitationUUID
     * @return Javascript
     * @throws ValidationException
     */
    public function deleteJobmaker($jmakerInvitationUUID)
    {

        // validation
        $this->validate($request = $this->request(), [
            'jmakerInvitationUUID' => [
                'required',
                Rule::exists('jmaker_invitation', 'uuid')->where(function (Builder $query) {
                    $query->where('is_completed', false);
                    $query->whereNull('deleted_at');
                })
            ]
        ]);

        // Recuperation de l'invitation
        $invitation = JmakerInvitation::findOrFail($jmakerInvitationUUID);

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
                $invitation->delete();

                $jmaker = $invitation->jmaker();
                if($jmaker) {
                    $jmaker->delete();

                    ClientCampaignMetricJmakerService::updateJmakerMetrics($invitation->campaign_uuid);
                    PrescriberMetricJmakerService::updateJmakerMetricsForAllPrescribers($jmaker->prescriber_uuid);
                    ClientMetricJmakerService::updateJmakerMetrics($jmaker->client_uuid);
                }

                js()->success()->closeRemoteModal()->reload();
            } catch (Exception $e) {
                js()->error($e->getMessage());
            }
            return js();
        }

        return response()->modal($modal);
    }


    /**
     * Return Jobmaker and Invitation
     * @return Collection
     * @throws Exception
     */
    public function listJobmakersAndInvitation()
    {

        // recuperation du client
        $client = \prescriber()->client()->first();

        $requestDateFormat = "'%d/%m/%Y'";

        if('LANG_EN' == \prescriber()->language) {
            $requestDateFormat = "'%m/%d/%Y'";
        }

        $meetingQuery = query('jmaker_meeting')
            ->addSelect('jmaker_uuid')
            ->selectRaw('MAX(meeting_date) as meeting_date')
            ->whereNull('deleted_at')
            ->groupBy('jmaker_uuid');

        $meetingQueryInvitation = query('jmaker_meeting')
            ->addSelect('invitation_uuid')
            ->selectRaw('MAX(meeting_date) as meeting_date')
            ->whereNull('deleted_at')
            ->groupBy('invitation_uuid');

        $synthesisShare = query('synthesis_share')
            ->addSelect('jmaker_uuid')
            ->selectRaw('MAX(shared_at) as shared_at')
            ->selectRaw(' 1 as is_shared')
            ->groupBy('jmaker_uuid');

        // SUBQUERY  : MISSION
        $missions = query('mission_run')
            ->addSelect('jmaker_uuid')
            ->selectRaw('COUNT(jmaker_uuid) > 0 as is_active_old')
            ->selectRaw('COUNT(jmaker_uuid) as completed_ct')
            ->whereIn('status_rid', [Ref::RUN_STATUS_FINISHED, Ref::RUN_STATUS_FINISHED_CHECKUP_WITH_JOBMAKER])
            ->groupBy('jmaker_uuid');

        // QUERY
        $query = query('jmaker as j', [
            'j.uuid',
            'p.name as prescriber_name',
            'cc.name as campaign_name',
            'j.email',
            'ji.uuid as invitationUUID',
            raw('if(j.state = \'JMAKER_STATE_ACTIVE\',true,false) as is_active'),
            raw('DATE_FORMAT(j.created_at, ' . $requestDateFormat . ') AS created_at'),
            raw('DATE_FORMAT(j.created_at, \'%Y%m%d\') AS created_at_iso'),
            raw("(CASE WHEN (j.state = 'JMAKER_STATE_ONBOARDING') THEN true ELSE false END) as is_started"),
            raw("(CASE WHEN (j.state = 'JMAKER_STATE_ACTIVE') THEN true ELSE false END) as is_completed"),
            raw('DATE_FORMAT(mqi.meeting_date,  '.$requestDateFormat.') AS meeting_date_invitation'),
            raw('DATE_FORMAT(mqi.meeting_date, \'%Y%m%d\') AS meeting_date_iso_invitation'),
            raw('DATE_FORMAT(mq.meeting_date,  '.$requestDateFormat.') AS meeting_date'),
            raw('DATE_FORMAT(mq.meeting_date, \'%Y%m%d\') AS meeting_date_iso'),
            raw('IF(ss.shared_at is null,false,true) as is_shared'),
            raw('DATE_FORMAT(ss.shared_at,  '.$requestDateFormat.') AS shared_at'),
            raw('m.completed_ct as completed_ct'),
            raw('DATE_FORMAT(j.last_page_at,  '.$requestDateFormat.') AS last_page_at'),
            raw('DATE_FORMAT(j.last_page_at, \'%Y%m%d\') AS last_page_at_iso')

        ])
            ->selectRaw('CONCAT(j.lastname," ", j.firstname) as jmaker_name')
            ->leftJoin('prescriber as p', 'p.uuid', 'j.prescriber_uuid')
            ->leftJoin('client_campaign as cc', 'j.campaign_uuid', 'cc.uuid')
            ->leftJoin('way as w', 'j.uuid', 'w.jmaker_uuid')
            ->leftJoin('jmaker_invitation as ji', 'j.uuid', 'ji.jmaker_uuid')
            ->leftJoinQuery($meetingQueryInvitation, 'mqi', 'ji.uuid', '=', 'mqi.invitation_uuid')
            ->leftJoinQuery($meetingQuery, 'mq', 'j.uuid', 'mq.jmaker_uuid')
            ->leftJoinQuery($synthesisShare, 'ss', 'j.uuid', 'ss.jmaker_uuid')
            ->leftJoinQuery($missions, 'm', 'j.uuid', 'm.jmaker_uuid')
            ->whereNull('j.deleted_at')
            ->where('j.client_uuid', $client->uuid);

        $campaigns = [];
        if (!Gate::check(Partner::PERMISSION_PARTNER_ADMIN)) {
            $query->where('j.prescriber_uuid', \prescriber()->uuid);
        }
        return $query->get();
    }

    public function exportIcs(ExportCalendarRequest $request) {
        $ical = (new Ical())
            ->setAddress($request->input('loc'))
            ->setDateStart(new DateTime($request->input('start')))
            ->setDateEnd(new DateTime($request->input('end')))
             ->setTimezoneICal('+01:00')
            ->setDescription($request->input('desc'))
            ->setSummary($request->input('title'))
            ->setOrganizer(prescriber()->email)
            ->setFilename(uniqid());
        return response($ical->getICAL(), Response::HTTP_OK, [
            'Content-type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename=' . $ical->getFilename() . '.ics'
        ]);
    }
}