<?php namespace App\Http\Controllers\Partner;

use App\Events\Jmakers\JmakerInvitedEvent;
use App\Http\Controllers\Controller;
use App\Mail\Jobmaker\Invite;
use App\Services\Client\ClientCampaignMetricJmakerService;
use App\Services\Client\ClientMetricJmakerService;
use App\Services\Client\ClientService;
use App\Services\Mail\MailService;
use App\Services\Prescriber\PrescriberMetricJmakerService;
use Carbon\Carbon;
use Exception;
use FrenchFrogs\Core\FrenchFrogsController;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use League\Csv\Reader;
use Models\Acl\Partner;
use Models\Db\Clients\ClientCampaign;
use Models\Db\Jmaker\Jmaker;
use Models\Db\Jmaker\JmakerInvitation;
use Models\Db\Languages\Languages;
use Ref;
use Validator;
use function prescriber;

/**
 * Interface partenaire front office
 *
 * Class DefaultController
 * @package App\Http\Controllers\Partner
 */
class PartnerMultipleInvitationController extends Controller
{

    use FrenchFrogsController;

    public function multipleInvitations(Request $request)
    {
        $client = prescriber()->client()->first();
        throw_if(empty($client), 'impossible de touver le client associé');

        if(!$client->enableMultipleInvitation) {
            return redirect(route('partner.home'));
        }

        $clientImgUrl = ClientService::getDefaultImgUrl($client);

        //True if admin false otherwise
        $partnerAdmin = prescriber()->can(Partner::PERMISSION_PARTNER_ADMIN);

        //Languages
        $languages = $client->languages()->orderBy('id', 'desc')->get();
        if ($languages->count() > 1) {
            $languages = $languages->transform(function (Languages $language) {
                return ['name' => __($language->translate_code), 'id' => $language->id];
            });
        }

        $campaigns = $client->campaigns->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE);
        if (!Gate::check(Partner::PERMISSION_PARTNER_ADMIN)) {
            $campaigns = prescriber()->campaigns()->get()->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE);
        }
        $campaigns = $campaigns->transform(function (ClientCampaign $campaign) {
            return ['name' => $campaign->name, 'uuid' => $campaign->uuid];
        });

        $message = "";
        $campaign = "";
        $language = "";
        $validator = null;
        $result = ['importDone'=> false,'errorCount' => 0,'invitationCount' => 0,'duplicateInvitationCount' => 0];

        if ($request->has('send')) {

            $data = $request->all();

            if ($languages->count() > 1) {
                $languageRule = collect($languages)->transform(function ($language) {
                    return $language['id'];
                })->toArray();

                $validator = Validator::make($request->all(), [
                    'campaign' => 'required',
                    'message' => 'required',
                    'language' => ['required', Rule::in($languageRule)]
                ], [
                    'required' => __('6DwfyO')
                ]);
            } else {

                $validator = Validator::make($request->all(), [
                    'campaign' => 'required',
                    'message' => 'required',
                ], [
                    'required' => __('6DwfyO')
                ]);
            }

            if (!$validator->fails()) {

                $message = $data['message'];
                $campaign = $data['campaign'];

                try {

                    // Gestion du fichier
                    $file = $request->file('file_csv');

                    if ($file instanceof UploadedFile && $file->isValid()) {

                        // netoyage des data
                        unset($data['file_csv']);

                        // read the file
                        $reader = Reader::createFromPath($file->getRealPath());

                        // traitement
                        transaction(function () use ($reader, $client, $data, &$result) {
                            // envoie des invitations individuelles

                            $contractClientUUID = $client->contracts()->first()->uuid;

                            collect($reader->fetchAll())
                                ->each(function ($row) use ($client, $data, &$result, $contractClientUUID) {

                                    // initialisation
                                    $email = $firstname = $lastname = null;

                                    foreach ($row as $value) {

                                        // si valeur vide, on passe
                                        if (empty($value)) {
                                            continue;
                                        }
                                        // si email on remplie
                                        if (is_null($lastname)) {
                                            $lastname = $value;
                                        } elseif (is_null($firstname)) {
                                            $firstname = $value;
                                        } elseif (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                            $email = $value;
                                        }
                                    }

                                    // si pas de mail, on ne continue pas
                                    if (empty($email) || empty($lastname) || empty($firstname)) {
                                        $result['errorCount'] = $result['errorCount'] + 1;
                                        return;
                                    }

                                    // On determine si on envoie le mail
                                    /** @var JmakerInvitation $invitation */
                                    $invitation = JmakerInvitation::where('email', $email)->firstOrNew([]);

                                    // cas de la création
                                    if (!$invitation->exists) {

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

                                        $invitation->language_id = $languageId;

                                        // Creatrion de l'invitation
                                        $invitation->uuid = generateNewUUID();
                                        $invitation->token = str_random(60);
                                        $invitation->campaign_uuid = $data['campaign'];
                                        $invitation->email = $email;
                                        $invitation->invited_by_prescriber_uuid = prescriber()->uuid;
                                        $invitation->data = $data + [
                                                'email' => $email,
                                                'firstname' => $firstname,
                                                'lastname' => $lastname,
                                            ];

                                        // si il y a une campagne, on l'attribu
                                        if ($client->partner) {
                                            $invitation->partner_id = $client->partner->id;
                                        }

                                        $jmaker = new Jmaker();
                                        $jmaker->uuid = generateNewUUID();
                                        $jmaker->client_uuid = $client->uuid;
                                        $jmaker->contract_uuid = $contractClientUUID;
                                        $jmaker->campaign_uuid = $data['campaign'];
                                        $jmaker->username = $firstname . ' ' . $lastname;
                                        $jmaker->firstname = $firstname;
                                        $jmaker->lastname = $lastname;
                                        $jmaker->email = $email;
                                        $jmaker->language_id = $languageId;
                                        $jmaker->prescriber_uuid = prescriber()->uuid;
                                        $jmaker->locked = false;
                                        $jmaker->expired = false;
                                        $jmaker->state = Ref::JMAKER_STATE_INVITED;
                                        $jmaker->credentials_expired = false;
                                        $jmaker->created_at = Carbon::now();
                                        $jmaker->registred_at = null;
                                        $jmaker->save();

                                        $invitation->jmaker_uuid = $jmaker->uuid;

                                        // sauvagrde de l'invitation
                                        $invitation->save();

                                        $result['invitationCount'] = $result['invitationCount'] + 1;

                                        // envoie du mail
                                        MailService::pushInDB(Invite::class, $invitation->email, $invitation->token);
                                        event(new JmakerInvitedEvent($invitation));
                                    } else {
                                        $result['duplicateInvitationCount'] = $result['duplicateInvitationCount'] + 1;
                                    }
                                });

                            ClientCampaignMetricJmakerService::updateJmakerMetrics($data['campaign']);
                            PrescriberMetricJmakerService::updateJmakerMetricsForAllPrescribers(prescriber()->uuid);
                            ClientMetricJmakerService::updateJmakerMetrics($client->uuid);

                            $result['importDone'] = true;
                        });

                    } else {
                        $validator->getMessageBag()->add('file_csv', 'Fichier CSV non conforme');
                    }

                } catch (Exception $e) {
                    $validator->getMessageBag()->add('file_csv', 'Fichier CSV non conforme');
                }
            }


        }

        return view('partnerV2.multipleInvitation',
            compact('client', 'clientImgUrl', 'partnerAdmin', 'campaigns', 'message', 'campaign', 'languages', 'language','result'))->withErrors($validator);;
    }
}