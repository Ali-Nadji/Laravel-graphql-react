<?php

namespace App\Http\Controllers\Partner;

use App\Events\Jmakers\JmakerInvitedEvent;
use App\Http\Controllers\Controller;
use App\Mail\Jobmaker\Invite;
use App\Services\CGU\CGUService;
use App\Services\Client\ClientCampaignMetricJmakerService;
use App\Services\Client\ClientMetricJmakerService;
use App\Services\Mail\MailService;
use App\Services\Prescriber\PrescriberMetricJmakerService;
use Carbon\Carbon;
use Exception;
use Gate;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use infrastructure\Http\Middleware\LanguageMiddleware;
use Models\Acl\Partner;
use Models\Db\CGU\CGU;
use Models\Db\CGU\PrescriberCgu;
use Models\Db\Clients\ClientCampaign;
use Models\Db\Jmaker\Jmaker;
use Models\Db\Jmaker\JmakerInvitation;
use Models\Db\Jmaker\JmakerMeeting;
use Models\Db\Languages\Languages;
use Models\Db\Prescriber\Prescriber;
use Models\Db\Prescriber\PrescriberInvitation;
use Ref;
use Validator;

class PartnerOnboardingController extends Controller
{

    const STEP_COACH = 'stepCoach';

    const STEP_FIRST_INVITATION = 'stepInvitation';

    const STEP_PARTNER = 'stepPartner';

    public function invitation(Request $request)
    {

        // recuperation du client
        $client = \prescriber()->client()->first();
        $prescriber = \prescriber();

        // MODEL
        //$invitation = Invitation::findOrNew($request->get('__id'));
        $jmakerInvitation = null;

        //Languages
        $languages = $client->languages()->orderBy('id','desc')->get();
        if($languages->count() > 1) {
            $languages = $languages->transform(function (Languages $language) {
                return ['name' => __($language->translate_code), 'id' => $language->id];
            });
        }

        // CAMPAIGN
        $campaigns = $client->campaigns->sortBy('name',SORT_NATURAL|SORT_FLAG_CASE);
        if (!Gate::check(Partner::PERMISSION_PARTNER_ADMIN)) {
            $campaigns = \prescriber()->campaigns()->get()->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE);
        }
        $campaigns = $campaigns->transform(function (ClientCampaign $campaign) {
            return ['name' => $campaign->name, 'uuid' => $campaign->uuid];
        });

        $validator = null;

        //INIT FORM FIELD
        $firstname = "";
        $lastname = "";
        $email = "";
        $message = "";
        $campaign = "";
        $language = "";
        $meeting_date = "";

        // TRAITEMENT
        if ($request->has('send')) {

            $data = $request->all();

            if($languages->count() > 1) {
                $languageRule = collect($languages)->transform(function ($language) {
                    return $language['id'];
                })->toArray();

                $validator = Validator::make($request->all(), [
                    'email' => 'required|unique:jmaker,email|unique:jmaker_invitation,email',
                    'firstname' => 'required',
                    'lastname' => 'required',
                    'language' => ['required', Rule::in($languageRule)]
                ], [
                    'email.required' => 'l\'adresse email est obligatoire',
                    'email.unique' => 'Un compte existe déja avec cette adresse email.',
                    'required' => 'Ce champs est obligatoire'
                ]);
            } else {

                $languageRule = collect($languages)->transform(function ($language) {
                    return $language['id'];
                })->toArray();

                $validator = Validator::make($request->all(), [
                    'email' => 'required|unique:jmaker,email|unique:jmaker_invitation,email',
                    'firstname' => 'required',
                    'lastname' => 'required',
                ], [
                    'email.required' => 'l\'adresse email est obligatoire',
                    'email.unique' => 'Un compte existe déja avec cette adresse email.',
                    'required' => 'Ce champs est obligatoire'
                ]);
            }

            if ($client->meetingDateMandatory) {
                $validator->addRules(['meeting_date' => 'required']);
            }

            //Hack due to FrenchFrogs validation that doesn't work with required_with or without
            if (!$validator->fails()) {

                try {

                    transaction(function () use ($client, $jmakerInvitation, $data, $request, $prescriber) {

                        // On determine si on envoie le mail
                        $meeting_date = null;
                        $meeting = null;

                        $jmakerInvitation = new JmakerInvitation();

                        $meeting_date = $data['meeting_date'];

                        $jmakerInvitation->uuid = generateNewUUID();
                        $jmakerInvitation->token = str_random(60);


                        $jmakerInvitation->email = $data['email'];
                        $jmakerInvitation->data = $data;

                        $languages = $client->languages()->get();

                        if($languages->count() == 1) {
                            $languageId = $languages->first()->id;
                        } else {
                            $validData = $client->languages()->get()->pluck('id')->all();

                            //Check if $data['language_id'] in valid language;
                            //If not we use default language FR
                            if(in_array($data['language'],$validData)) {
                                $languageId =  $data['language'];
                            } else {
                                $languageId = Ref::LANG_FR;
                            }
                        }

                        $jmakerInvitation->language_id = $languageId;

                        // Creatrion de l'invitation
                        $jmakerInvitation->campaign_uuid = $data['campaign'];
                        $jmakerInvitation->invited_by_prescriber_uuid = \prescriber()->uuid;

                        // If Client have a partner
                        if ($client->partner) {
                            $jmakerInvitation->partner_id = $client->partner->id;
                        }

                        $jmaker = new Jmaker();
                        $jmaker->uuid = generateNewUUID();
                        $jmaker->username = $data['firstname'] . ' ' . $data['lastname'];
                        $jmaker->firstname = $data['firstname'];
                        $jmaker->lastname = $data['lastname'];
                        $jmaker->client_uuid = $client->uuid;
                        $jmaker->contract_uuid = $client->contracts()->first()->uuid;
                        $jmaker->campaign_uuid = $data['campaign'];
                        $jmaker->email = $data['email'];
                        $jmaker->language_id = $languageId;
                        $jmaker->prescriber_uuid = \prescriber()->uuid;
                        $jmaker->locked = false;
                        $jmaker->expired = false;
                        $jmaker->state = Ref::JMAKER_STATE_INVITED;
                        $jmaker->credentials_expired = false;
                        $jmaker->created_at = Carbon::now();
                        $jmaker->registred_at = null;
                        $jmaker->save();

                        $jmakerInvitation->jmaker_uuid = $jmaker->uuid;

                        $jmakerInvitation->save();

                        if (!empty($meeting_date)) {
                            $meeting = new JmakerMeeting();
                            $meeting->uuid = generateNewUUID();
                            $meeting->invited_by_prescriber_uuid =  $jmakerInvitation->invited_by_prescriber_uuid;
                            $meeting->meeting_date = Carbon::createFromFormat(($prescriber->language == 'LANG_EN' ? "m/d/Y" : "d/m/Y"), $meeting_date);
                            $meeting->type = Ref::MEETING_TYPE_INVITATION;
                            $meeting->invitation_uuid = $jmakerInvitation->uuid;
                            $meeting->save();
                        }

                        ClientCampaignMetricJmakerService::updateJmakerMetrics($jmakerInvitation->campaign_uuid);
                        PrescriberMetricJmakerService::updateJmakerMetrics(\prescriber()->uuid);
                        ClientMetricJmakerService::updateJmakerMetrics($client->uuid);

                        // envoie du mail
                        MailService::pushInDB(Invite::class, $jmakerInvitation->email, $jmakerInvitation->token);
                        event(new JmakerInvitedEvent($jmakerInvitation));
                    });

                    return redirect(route('partner.home'));
                } catch (Exception $e) {

                }
            }

            $firstname = empty($data['firstname']) ? '' : $data['firstname'];
            $lastname = empty($data['lastname']) ? '' : $data['lastname'];
            $email = empty($data['email']) ? '' : $data['email'];
            $campaign = empty($data['campaign']) ? '' : $data['campaign'];
            $language = empty($data['language']) ? '' : $data['language'];
            $message = empty($data['message']) ? '' : $data['message'];
            $meeting_date = empty($data['meeting_date']) ? '' : $data['meeting_date'];
        }

        $meeting_date_required =  false;
        if ($client->meetingDateMandatory) {
            $meeting_date_required = true;
        }

        return view("partnerV2.onboarding.invitation",
            compact('languages','campaigns','email',
                'lastname','firstname', 'campaign','language','message', 'meeting_date','meeting_date_required'))->withErrors($validator);
    }

    /**
     * Entrypoint
     *
     * @param Request $request
     * @return Factory|RedirectResponse|Response|View|mixed
     */
    public function register(Request $request, $token)
    {
        // Fin partner invitation
        $prescriberInvitation = PrescriberInvitation::where('token', $token)->first();
        // If none invitation found we display an error.
        if (empty($prescriberInvitation)) {
            abort(401, 'Cette invitation n\'existe pas');
        }

        //Check if user already choose a password
        //If yes he have to login
        if(!empty($prescriberInvitation->prescriber->password)) {
            //We force logout

            /** @var Prescriber $prescriber */
            $prescriber = $prescriberInvitation->prescriber;

            if (Ref::LANG_EN == $prescriber->language) {
                $this->updateSessionLanguage('EN');
            } else {
                $this->updateSessionLanguage('FR');
            }

            auth(Ref::INTERFACE_PARTNER)->logout();
            return redirect(route('partner.login'));
        }

        /** @var Prescriber $prescriber */
        $prescriber = $prescriberInvitation->prescriber;
        if ($prescriber->client()->first()->client_status == Ref::CLIENT_STATUS_FREEZED)
        {
         abort(500);
        }
        $firstname = $prescriber->firstname;
        $lastname = $prescriber->lastname;

        $validator = null;

        // If post method user push information
        if ($request->isMethod('post')) {

            $validator = Validator::make($request->all(), [
                'firstname' => 'required',
                'lastname' => 'required',
                'password' => 'required|min:8|confirmed',
                'password_confirmation' => 'required|min:8',
                'legal' => ['required',Rule::in(["true"])],
            ], [
                'required' => 'Ce champs est obligatoire'
            ]);



            if (!$validator->fails() && checkPassword($request->get('password')) == true) {

                try {

                    $prescriber->lastname  = $request->get('lastname');
                    $prescriber->firstname = $request->get('firstname');
                    $prescriber->name = $prescriber->firstname . ' ' . $prescriber->lastname;
                    $prescriber->password = bcrypt($request->get('password'));
                    $prescriber->save();
                    $lastCGUID =  CGUService::getCurrentCGUForPrescriber($prescriber)->uuid;
                    $cgu = CGU::findOrFail($lastCGUID);

                    $prescriberCGU = new PrescriberCgu();
                    $prescriberCGU->prescriber_uuid = $prescriber->uuid;
                    $prescriberCGU->cgu_uuid = $cgu->uuid;
                    $prescriberCGU->accepted_at = Carbon::now();
                    $prescriberCGU->save();

                    auth(Ref::INTERFACE_PARTNER)->login($prescriber);
                    return redirect(route('partner.onboarding'));

                } catch (Exception $e) {
                    //Do nothing.
                }
            }
        }

        if($request->get('lang')) {
            if ($request->get('lang') == "EN") {
                $languageId = Ref::LANG_EN;
                $prescriber->language = $languageId;
            } elseif ($request->get('lang') == "FR") {
                $languageId = Ref::LANG_FR;
                $prescriber->language = $languageId;
            }
            $prescriber->save();
        }

        $languageId = $prescriber->language;
        $previousLocal = session('local', Ref::LANG_FR);

        session()->put('local', $languageId);

        //If previsous local in session change we have to recompute Locale Singleton
        if ($previousLocal != session('local', Ref::LANG_FR)) {
            LanguageMiddleware::computeLanguage($languageId, Ref::INTERFACE_PARTNER);
        }


        return view("partnerV2.onboarding.register", compact('lastname','firstname','token'))->withErrors($validator);
    }

    /**
 * onboarding entrypoint
 *
 * @param Request $request
     * @return Factory|RedirectResponse|Response|View|mixed
 */
    public function onboarding(Request $request)
    {
        if(prescriber()->onboarded) {
            return redirect(route("partner.home"));
        }

        $prescriber = \prescriber();
        $prescriberInvitaiton = $prescriber->prescriberInvitation()->first();

        $data = $prescriberInvitaiton->data;

        //Init each step if first visit
        if(empty($data['step'])) {
            $data['step'][static::STEP_COACH] = true;
            $data['step'][static::STEP_FIRST_INVITATION] = true;
            $data['step'][static::STEP_PARTNER] = true;
            $prescriberInvitaiton->data = $data;
            $prescriberInvitaiton->save();
        }

        if (Ref::LANG_EN == $prescriber->language) {
            $data['step'][static::STEP_COACH] = false;
            $data['step'][static::STEP_FIRST_INVITATION] = false;
            $data['step'][static::STEP_PARTNER] = false;
            $prescriberInvitaiton->data = $data;
            $prescriberInvitaiton->save();
            return redirect(route('partner.home'));
        }

        $choiceCoach = $data['step'][static::STEP_COACH];
        $choiceFirstInvit = $data['step'][static::STEP_FIRST_INVITATION];
        $choicePartner = $data['step'][static::STEP_PARTNER];
        return view("partnerV2.onboarding.mainChoice", compact('choiceCoach','choiceFirstInvit','choicePartner'));
    }

    /**
     * coach entrypoint
     *
     * @param Request $request
     * @return Factory|RedirectResponse|Response|View|mixed
     */
    public function coach(Request $request)
    {
        return view("partnerV2.onboarding.coach");
    }

    /**
     * video
     * @param Request $request
     * @return Factory|View
     */
    public function video(Request $request)
    {
        $prescriberInvitaiton = \prescriber()->prescriberInvitation()->first();

        $data = $prescriberInvitaiton->data;
        $data['step'][static::STEP_COACH] = false;
        $prescriberInvitaiton->data = $data;
        $prescriberInvitaiton->save();

        return view("partnerV2.onboarding.video");
    }

    /**
     * video
     * @param Request $request
     * @return Factory|View
     */
    public function partner(Request $request)
    {
        $prescriberInvitaiton = \prescriber()->prescriberInvitation()->first();

        $data = $prescriberInvitaiton->data;
        $data['step'][static::STEP_PARTNER] = false;
        $prescriberInvitaiton->data = $data;
        $prescriberInvitaiton->save();

        return view("partnerV2.onboarding.videoPartner");
    }

    /**
     * video
     * @param Request $request
     * @return Factory|View
     */
    public function demoJobmaker(Request $request)
    {

        $prescriberInvitaiton = \prescriber()->prescriberInvitation()->first();

        $data = $prescriberInvitaiton->data;
        $data['step'][static::STEP_COACH] = false;
        $prescriberInvitaiton->data = $data;
        $prescriberInvitaiton->save();

        $jmaker = Jmaker::find('5C88FB238F63D8306C91CC7956234DDE');
        auth(Ref::INTERFACE_JOBMAKER)->login($jmaker);
        return redirect(route('jobmaker.dashboard'));
    }

    /**
     * Update Language
     * @param $language
     */
    protected function updateSessionLanguage($language) {

        $languageId = null;

        if(strcasecmp($language,'EN') == 0) {
            $languageId = Ref::LANG_EN;
        }
        if(strcasecmp($language,'FR') == 0) {
            $languageId = Ref::LANG_FR;
        }

        if ($languageId != null) {

            $previousLocal = session('local', Ref::LANG_FR);

            session()->put('local', $languageId);

            //If previsous local in session change we have to recompute Locale Singleton
            if ($previousLocal != session('local', Ref::LANG_FR)) {
                LanguageMiddleware::computeLanguage($languageId, Ref::INTERFACE_PARTNER);
            }
        }
    }
}
