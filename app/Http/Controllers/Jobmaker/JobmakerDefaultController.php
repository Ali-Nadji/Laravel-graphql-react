<?php

namespace App\Http\Controllers\Jobmaker;

use App\Events\Jmakers\MailNotificationsSubscriptionEvent;
use App\Events\Jmakers\MailNotificationsUnsubscriptionEvent;
use App\Events\Jmakers\RapportShared;
use App\Http\Controllers\Controller;
use App\Pdf\Rapport;
use App\Services\CGU\CGUService;
use App\Services\Mail\MailService;
use App\Services\Way\WayService;
use Carbon\Carbon;
use DateInterval;
use DateTime;
use Exception;
use FrenchFrogs\Container\Javascript;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Jenssegers\Date\Date;
use Models\Db\CGU\CGU;
use Models\Db\CGU\JmakerCgu;
use Models\Db\Clients\Client;
use Models\Db\Deprecated\Payment;
use Models\Db\Deprecated\VoucherCode;
use Models\Db\Jmaker\Jmaker;
use Models\Db\Jmaker\JmakerInvitation;
use Models\Db\Jmaker\JmakerNextNotificationMeeting;
use Models\Db\Jmaker\SynthesisShare;
use Models\Db\Languages\Languages;
use Models\Db\Mission\Mission;
use Models\Db\Mission\Run;
use Models\Db\Payment\Stripe;
use Ref;
use Session;
use Validator;
use function form;
use function js;
use function redirect;

class JobmakerDefaultController extends Controller
{

    /**
     *
     * Page d'accueil
     * @param Request $request
     * @return Factory|View
     * @throws Exception
     */
    public function index(Request $request)
    {
        if (auth(Ref::INTERFACE_PARTNER)->check() && (\jmaker()->uuid == "5C88FB238F63D8306C91CC7956234DDE") || \jmaker()->uuid == "67FA38AFFA2B0D6D08349D13DC8002F8") { //6857
            $request->session()->put('demoFromPartner', true);
        } else {
            $request->session()->put('demoFromPartner', false);
        }
        $wayService = app(WayService::class)->completion();

        $wayService->calculate();

        $newUser = $request->session()->has('newUser');

        // Definition du conseil

        $runs = $wayService->getRuns()->where('mission_id', '!=', Mission::ECO_FINAL);

        $current = $wayService->suggestRun();

        // Check des conditions d'accès
        $require = $wayService->getRequire();

        // Recuperation des information du parcours
        $missions = Mission::whereIn('id', $runs->pluck('mission_id')->toArray())
            //->where('type_rid', \Ref::MISSION_TYPE_WORKSHOP)
            ->orderBy('mission_number')
            ->get();
        //************************** */
        //        CGU
        //************************** */
        $jmaker = \jmaker();
        $needToAcceptCGU = !(CGUService::acceptedLastJmakerCGU($jmaker));

        $lastCGUID = null;
        if ($needToAcceptCGU) {
            $lastCGUID = CGUService::getCurrentCGUForJmaker($jmaker)->uuid;
        }

        //************************** */
        //        Synthesis
        //************************** */

        //Synthesis compute if share button must be display or not
        $lastShareDate = Carbon::minValue();
        $LastShare = SynthesisShare::where([
                'jmaker_uuid' => \jmaker()->uuid,
                'way_uuid' => $wayService->getWay()->uuid]
        )->orderBy('shared_at', 'desc')
            ->first();

        if (!empty($LastShare)) {
            $lastShareDate = $LastShare->shared_at;
        }

        //************************** */
        //        Meeting
        //************************** */
        Date::setLocale(Languages::find(session('local'))->locale);

        $nextMeeting = $jmaker->nextMeeting();
        $currentDate = new DateTime();
        $nextMeetingDate = null;
        $showNextMeeting = false;
        if ($nextMeeting) {
            $showNextMeeting = $currentDate < date_add(new DateTime($nextMeeting->meeting_date), new DateInterval('P1D'));
            $nextMeetingDate = (new Date($nextMeeting->meeting_date))->format('j F Y');
        }
        $prescriber = $jmaker->prescriber()->first();
        $prescriberName = null;
        if ($prescriber) {
            $prescriberName = $prescriber->name;
        }

        $lastUpdateMissions = Run::where([
            'status_rid' => Ref::RUN_STATUS_FINISHED,
            'jmaker_uuid' => \jmaker()->uuid
        ])->orderBy('updated_at', 'desc')
            ->first();

        $showSharedSynthesisButton = false;
        if (!empty($lastUpdateMissions) && ($lastUpdateMissions->updated_at)->gt($lastShareDate)) {
            $showSharedSynthesisButton = true;
        }

        /** @var Client $client */
        $client = $jmaker->client()->first();

        if($client && $client->enableB2BasB2C) {
            $showSharedSynthesisButton = false;
            $nextMeetingDate = null;
        }

        // Count How many runs are finished
        // if count > 1 synthesis is available otherwise no
        $synthesisAvailable = false;
        $runFinished = 0;
        foreach ($runs as $run) {
            if ($run->status_rid == REF::RUN_STATUS_FINISHED && $run->mission->id != "25") {
                $runFinished = $runFinished + 1;
            }
        }
        if ($runFinished > 1) {
            $synthesisAvailable = true;
        }
        $samePrescriber = false;
        //Fetching of associated synthesis_shared model
        if(!$jmaker->isB2c() && $prescriber) {
            $synthese = SynthesisShare::where('jmaker_uuid',"=",$jmaker->uuid)->orderBy('shared_at', 'desc')->first();
            $prescriberUUID = $prescriber->uuid;
            if($synthese ){
                if($synthese->prescriber_uuid == $prescriberUUID){
                    $samePrescriber = true;
                }
            }
        }

        return view('jobmaker.index', compact('wayService', 'missions', 'current', 'runs',
            'require', 'synthesisAvailable', 'showSharedSynthesisButton',
            'lastShareDate', 'nextMeetingDate', 'prescriberName', 'newUser', 'needToAcceptCGU', 'lastCGUID','samePrescriber','showNextMeeting'));
    }


    /**
     * @param Request $request
     * @return Factory|View
     */
    public function infos(Request $request)
    {
        /**@var Jmaker $jmaker */
        $jmaker = \jmaker();
        $grades = ref('jobmaker.grade')->pairs();
        $birthdate = $jmaker->birthdate;
        empty($birthdate) && $birthdate = Carbon::today();

        // initialisation des variables de la date
        $birthday_month = [];

        Date::setLocale(Languages::find(session('local'))->locale);

        //On récupère les infos de l'invitation si elle existe
        /** @var JmakerInvitation $invitation */
        $invitation = $jmaker->invitation()->first();

        $cgus = JmakerCgu::where('jmaker_uuid',"=",$jmaker->uuid)->get();

        $answers = null;
        $questions = null;

        if (isset($invitation->data['questions'])) {

            $data = collect($invitation->data['questions'])->only('goal', 'need', 'profil');

            //Gell all questions
            $questions = $data->Map(function ($value, $key) {
                return ['name' => $key, 'questions' =>
                    collect($value['answers'])->map(function ($value) {
                        return $value['name'];
                    })];
            })->toArray();

            //Et les réponses
            $answers = $data->mapWithKeys(function ($value, $key) {
                return [$key => collect($value['answers'])->where('is_checked', true)->first()['name']];
            });

        }
        // TRAITEMENT
        if ($request->getMethod() == 'POST') {
            try {

                transaction(function() use ($request,$jmaker, $invitation, $answers, $questions) {

                    $this->validate($request, [
                        'firstname' => 'required|between:2,30',
                        'lastname' => 'required|between:2,30',

                    ]);

                    //INFO PERSO
                    if($request->get('email'))
                    {
                        $jmaker->email = $request->get('email');
                    }
                    $jmaker->firstname = $request->get('firstname');
                    $jmaker->lastname = $request->get('lastname');
                    /** Persist of the date of notification unsubscription only if want_notification is false */
                    if($jmaker->want_notification != $request->get('want_notification'))
                    {
                        $jmaker->want_notification = $request->get('want_notification');

                        if($request->get('want_notification'))
                        {
                            /** if the jmaker wants to recieve notifications we fire the subscription event */
                            event(new MailNotificationsSubscriptionEvent($jmaker->uuid,Carbon::today()));
                        }
                        else
                        {
                            /** othserwise we fire the Unsubscription event */
                            event(new MailNotificationsUnsubscriptionEvent($jmaker->uuid,Carbon::today()));
                        }

                        $jmakerNextNotificationMeetings = JmakerNextNotificationMeeting::where('jmaker_uuid','=',$jmaker->uuid)->get();
                        foreach ($jmakerNextNotificationMeetings as $jmakerNextNotificationMeeting) {
                            /** @var JmakerNextNotificationMeeting $jmakerNextNotificationMeeting  */
                            $jmakerNextNotificationMeeting->frozen = $jmaker->want_notification ? true : false;
                            $jmakerNextNotificationMeeting->save();
                        }
                    }

                    $jmaker->save();


                    //For B2B user
                    if(!$jmaker->isB2c()) {

                        $client = $jmaker->client()->first();

                        $jmakerNextNotificationWorkshop = $jmaker->jmakerNextNotificationWorkshop()->first();

                        if($jmakerNextNotificationWorkshop) {
                            $jmakerNextNotificationWorkshop->frozen = !$jmaker->want_notification | ($client && $client->client_status == Ref::CLIENT_STATUS_FREEZED);
                            $jmakerNextNotificationWorkshop->save();
                        }

                        $jmakerNextNotificationMeeting = $jmaker->jmakerNextNotificationMeeting()->first();

                        if($jmakerNextNotificationMeeting) {
                            $jmakerNextNotificationMeeting->frozen = !$jmaker->want_notification | ($client && $client->client_status == Ref::CLIENT_STATUS_FREEZED);
                            $jmakerNextNotificationMeeting->save();
                        }
                    }




                    //INFO INVITATION
                    if (($invitation) && isset($invitation->data['questions'])) {

                        // On récupere les questions du formulaire d'invitation
                        $data = $invitation->data;
                        $questions = $data['questions'];


                        //Reponse du formulaire de la page d'info perso
                        $results = ['goal', 'profil', 'need'];

                        foreach ($results as $result) {

                            //Validation
                            $list = collect($questions[$result]['answers'])->map(function ($answer) {
                                return base64_encode($answer['name']);
                            })->toArray();

                            $validator = Validator::make([$result => $request->get($result)], [
                                $result => ['required', Rule::in($list)]
                            ], [
                                'required' => 'Ce champs est obligatoire'
                            ]);

                            //Enregistrement
                            if (!$validator->fails()) {

                                $response = base64_decode($request->get($result));
                                foreach ($questions[$result]['answers'] as &$answer) {
                                    $answer['is_checked'] = ($answer['name'] == $response);
                                }
                            }
                        }

                        //Save
                        $data['questions'] = $questions;
                        $invitation->data = $data;
                        $invitation->save();

                    }
                });


            } catch (ValidationException $e) {

                return back();

            } catch (Exception $e) {
                dd($e->getMessage());
                return back()->with('sucess',false);

            }

            return back()->with('success', true);

        }


        if (Session::has('success')) {
            js()->success();
        }


        $translate = [
            'email' => 'ouQBFq',
            'firstname' => 'kN9NkV',
            'lastname' => '6MgAnz',
            'phone' => 'rgK6Xl',
            'language' => [
                'answers' => [
                    'LANG_FR' => '4fQa3D',
                    'LANG_EN' => 'czDhVO'
                ]
            ],
            'grade' => [
                'name' => 'gsSJU7',
                'answers' => [
                    'CAP/BEP' => 'AN3Spf',
                    'BAC' => 'h3OaUu',
                    'DUT/BTS' => 'pHGbxV',
                    'Licence/Bachelor' => 'Vqsa65',
                    'Master ou +' => 'KkE3OH',
                    'Pas de diplôme' => 'Kmu52b',
                ]
            ],
            'need' => [
                'name' => 'K2AQaY',
                'answers' => [
                    'Acquérir des techniques pour : m’orienter, me présenter et mener ma recherche de poste' => 'jn8uDn',
                    'Être guidé(e) pour clarifier mon projet professionnel, structurer mes envies, mes idées' => 'eJjboh',
                    'Être soutenu(e) pendant cette phase de réflexion et de recherche' => 'iOe0T8',
                    'Découvrir une solution innovante d’accompagnement ' => 'NSd41k',
                ]
            ],
            'goal' => [
                'name' => 'JHZu34',
                'answers' => [
                    'Je souhaite que les choses bougent le plus rapidement possible' => '38dj6Y',
                    'J’envisage une transition dans les 6 mois' => 'PNJxLC',
                    'Je me laisse une année pour mener à bien ma transition' => 'ubkp0Q',
                    'Je me projette dans une transition d’ici 18 / 24 mois' => 'bSQSIz',
                    'Je n’ai pas d’échéance prédéfinie' => 'UVP8uK'
                ]
            ],
            'profil' => [
                'name' => 'jAULiP',
                'answers' => [
                    'Un(e) planificateur(trice): agenda à jour, retroplanning, respect des échéances fixées' => 'Txtv02',
                    'Un(e) flexible: vision globale sur les grandes échéances, révision des priorités au quotidien' => 'mYtgBH',
                    'Un(e) improvisateur(trice): gestion des imprévus, réalisation des tâches en dernière minute' => 'dOUsEV',
                ]
            ],
            'contact' => [
                'name' => '30p2E2',
                'answers' => [
                    'Par email' => 'WjSPEd',
                    'Par SMS' => 'PPJPJL',
                    'Les deux' => 'uqdQkm',
                    'De vive voix' => 'CSefkT'
                ]
            ]
        ];

        // $wayService
        $wayService = app(WayService::class);

        return view('jobmaker.infos', compact('jmaker', 'grades', 'birthdate', 'birthday_month', 'questions', 'answers', 'wayService', 'translate','cgus'));
    }

    /**
     * @param Request $request
     * @return
     */
    public function password(Request $request)
    {

        // MODEL
        $user = \jmaker();

        // FORM
        $form = form()->enableRemote();
        $form->setLegend(__('UQk2ib'));

        // ELEMENT
        $form->addPassword('password', __('5RKQJs'))
            ->addLaravelValidator('required|min:8|regex:/^(?=.*[a-z])(?=.*\d).+$/')
            ->setDescription(__('5OIF53'));
        $form->addPassword('password_confirmation', __('xUW9gh'))
            ->addValidator('confirmed', function ($value, $data) {
                return $value == $data;
            }, request('password'), __('VVsQBB'));
        $form->addSubmit(__('YWPni9'))->setName('modify');

        // TRAITEMENT
        if ($request->has('modify')) {
            $data = request()->all();
            $form->valid($data);
            if ($form->isValid()) {
                $data = $form->getFilteredValues();
                try {
                    $test = checkPassword($data['password']);
                    if ($test == true) {
                        $user->password = bcrypt($data['password']);
                        $user->save();
                        js()->success("Le mot de passe à été modifié avec succès")->closeRemoteModal();
                    } else {
                        js()->error("Le mot de passe doit contenir au minimum 8 caracteres dont 1 chiffre.");
                    }
                } catch (Exception $e) {
                    js()->error($e->getMessage());
                }
            }
        }
        return response()->modal($form);
    }


    /**
     * @param Request $request
     * @return Factory|JsonResponse|RedirectResponse|View
     */
    public function payment(Request $request)
    {

        // recupération de l'utilisateur
        $user = \jmaker();

        // Si l'utilisateur a deja payé, on le renvoie vers la home
        if ($user->hasPaymentSucceed() || $user->invitation()->whereNotNull('client_uuid')->first()) {
            return redirect()->route('jobmaker.dashboard');
        }

        // On définit le prix
        $price = $original_price = Payment::DEFAULT_PRICE;

        // Initialisation du message de retour à l'utilisateur
        $message = '';

        // Vérification que le code promo est rentré
        if ($request->has('voucher_code')) {

            // Recupération de la saisie du code promo
            $value = $request->get('voucher_code');

            // Recupération du Code Promo dans la base de donnée
            $voucher = VoucherCode::where('value', $value)->first();

            // Si le code n'existe pas on informe l'utilisateur
            if (empty($voucher)) {
                $message = sprintf('Le Code Promo "%s" n\'existe pas !', $value);
            } elseif (Carbon::now()->gt($voucher->expire_at)) {
                // Si le code a expiré on informe l'utilisateur
                $message = sprintf('Le Code Promo "%s" est expiré !', $value);
            } else {
                // On met voucher dans la Session
                Session::put('voucher', $voucher->id);
            }

            return redirect()->route('jobmaker.payment')->with('message', $message);
        }


        // gestion d'un message mis en flash
        if ($request->session()->has('message')) {
            $message = $request->session()->get('message');
        }

        // Definition du paiement
        if (Session::has('voucher')) {
            // Recuperation du code promo mis en session
            $voucher = VoucherCode::where('id', Session::get('voucher'))->first();

            // Appliquer la réduction
            $price -= $voucher->discount_price;
            $message = sprintf('Vous bénéficiez d\'une réduction de "%s€" grâce au code "%s" !', number_french($voucher->discount_price / 100, 2), $voucher->value);


            // si code promo de la totalité du paiement, on créer un payement OK
            if (empty($price)) {
                // creation du payment dans notre bdd
                $payment = new Payment();

                $payment->jmaker_uuid = \jmaker()->uuid;
                $payment->status = Payment::STATUS_SUCCEEDED;
                $payment->voucher_code_id = $voucher->id;
                $payment->amount = 0;
                $payment->paid = 1;
                $payment->currency = 'EUR';
                $payment->save();

                // suppression du voucher en session
                Session::forget('voucher');

                return redirect()->route('jobmaker.dashboard');
            }
        }


        // Vérification que toutes les informations de la carte soient rentrées
        if ($request->isXmlHttpRequest() && $request->has('stripeToken')) {
            // initialissation du retour
            $success = false;
            $message = '';

            try {

                /**@var Stripe $stripe */
                $stripe = \jmaker()->stripes()->first();

                if (is_null($stripe)) {
                    $stripe = \jmaker()->stripes()->create();
                    $stripe->uuid = generateNewUUID();
                    $stripe->createAsStripeCustomer($request->get('stripeToken'), [
                        'email' => \jmaker()->email,
                    ]);
                } else {
                    $stripe->updateCard($request->get('stripeToken'));
                }

                // on prend le fric
                $charge = $stripe->charge($price);

                // creation du payment dans notre bdd
                $payment = new Payment();
                $payment->jmaker_uuid = \jmaker()->uuid;
                $payment->status = $charge->paid ? Payment::STATUS_SUCCEEDED : Payment::STATUS_FAILED;
                $payment->voucher_code_id = !empty($voucher) ? $voucher->id : null;
                $payment->ext_id = $charge->id;
                $payment->amount = $charge->amount;
                $payment->paid = $charge->paid;
                $payment->captured = $charge->captured;
                $payment->error_code = $charge->failure_code;
                $payment->currency = 'EUR';
                $payment->save();

                // on verifie que le paiement a bien eu lieu
                if (is_null($charge->failure_code)) {
                    $success = true;

                    // on envoie le mail
                    MailService::pushInDB(\App\Mail\Payment::class, $user->email,  $payment->id);

                    // suppression du voucher en session
                    Session::forget('voucher');
                } else {
                    $message = 'charge error : ' . $charge->failure_message;
                }
            } catch (Exception $e) {
                $message = $e->getMessage();
            }

            return response()->json(compact('success', 'message'));
        }

        return view('jobmaker.payment', ['price' => $price, 'original_price' => $original_price, 'message' => $message]);
    }

    /**
     * @return Response
     */
    public function rapport()
    {

        $user = \jmaker();

        $PDFfile = Rapport::build(\jmaker()->uuid)->show();

        $lastname = $user->lastname;
        $pdfFilename = "jobmaker.pdf";
        if ($lastname !== '') {
            $pdfFilename = "jobmaker-" . $lastname . ".pdf";
        }

        return response($PDFfile)
            ->header('Content-Type', "application/pdf")
            ->header('Content-Disposition', 'inline; filename="' . $pdfFilename . '"');

    }


    /**
     * Jobmaker wants shared his synthesis with HR service
     * Generate PDF for user instead update Way.
     *
     * @return Javascript
     */
    public function rapportAccess()
    {
        try {

            $jmaker = \jmaker();
            // Shared at
            $synthesisShared = new SynthesisShare();
            $synthesisShared->uuid = generateNewUUID();
            $synthesisShared->jmaker_uuid = $jmaker->uuid;
            $synthesisShared->prescriber_uuid = $jmaker->prescriber_uuid;
            $synthesisShared->way_uuid = $jmaker->way()->first()->uuid;
            $synthesisShared->shared_at = Carbon::now();
            if($jmaker->prescriber_uuid){
                $synthesisShared->save();
                $PDFfile = Rapport::build(\jmaker()->uuid)->show();

                Storage::disk('synthesisPDF')->put("synthesis-" . $jmaker->uuid . ".pdf", $PDFfile);

                event(new RapportShared($jmaker->uuid));
            }
            return redirect('/espace-perso');

        } catch (Exception $e) {

            js()->error($e->getMessage());
        }

        return js();
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function changeLanguage(Request $request)
    {
        if ($request->has('language_code')) {
            $validData = array(Ref::LANG_EN, Ref::LANG_FR, Ref::LANG_DEBUG);
            $languageCode = $request->get('language_code');

            if (in_array($languageCode, $validData)) {
                session()->put('local', $languageCode);
            }
        }
        return redirect()->back();
    }

    /**
     * Display the last CGU accepted by the jmaker.
     * @return Factory|View
     * @throws Exception
     */
    public function getLegalCGU()
    {
        $jmaker = \jmaker();
        $cgu = CGUService::getCurrentCGUForJmaker($jmaker);

        $language_id = $jmaker->language_id;

        if (empty($language_id)) {
            $language_id = Ref::LANG_FR;
        }

        $cguContent = $cgu->contents()->where('language_id', '=', $language_id)->first();
        $htmlContent = "";

        if (!empty($cguContent)) {
            $htmlContent = $cguContent->html_content;
        }

        return view('jobmaker.cgu.index', compact('htmlContent'));
    }

    /**
     * Update Legal CGU
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function postLegalCGU(Request $request)
    {
        if ($request->ajax()) {

            $jmaker = \jmaker();

            $cguUuid = $request->get('cgu_uuid');

            $lastCGUID = CGUService::getCurrentCGUForJmaker($jmaker)->uuid;

            if ($lastCGUID == $cguUuid) {

                CGU::findOrFail($cguUuid);

                $jmakerCGU = JmakerCgu::where('jmaker_uuid','=',$jmaker->uuid)->where('cgu_uuid','=',$cguUuid)->first();

                if(!$jmakerCGU) {
                    $jmakerCGU = new JmakerCgu();
                }

                $jmakerCGU->jmaker_uuid = $jmaker->uuid;
                $jmakerCGU->cgu_uuid = $cguUuid;
                $jmakerCGU->accepted_at = Carbon::now();
                $jmakerCGU->save();

                return response()->json([
                    'success' => true,
                ]);

            } else {
                return response()->json([
                    'success' => false,
                ]);
            }
        }

        return response()->json([
            'success' => false,
        ]);
    }

    /**
     * @param Request $request
     */
    public function displayCgu(Request $request){

        $cgu = CGU::where('uuid',$request->get('cgu'))->first();
        $jmaker = \jmaker();
        $language_id = $jmaker->language_id;

        if (empty($language_id)) {
            $language_id = Ref::LANG_FR;
        }

        $cguContent = $cgu->contents()->where('language_id', '=', $language_id)->first();
        $htmlContent = "";

        if (!empty($cguContent)) {
            $htmlContent = $cguContent->html_content;
        }

        $htmlContent .="<script>
                        $(document).ready(function () {
                         $('.modal-content').css('max-height','800px');
                         $('.modal-content').css('width','110%');
                         $('.modal-content').css('overflow','auto');
                         $('.modal-content').css('margin-top','12%');
                        });
                      </script>";
        return view('jobmaker.cgu.modal-cgu', compact('htmlContent'));

    }
}
