<?php

namespace App\Http\Controllers\Inside\Clients;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inside\ClientsController;
use App\Http\Controllers\Inside\PrescriberController;
use App\Mail\Partner\InvitePartner;
use App\Services\Mail\MailService;
use Doctrine\DBAL\Query\QueryBuilder;
use Exception;
use FrenchFrogs\Core\FrenchFrogsController;
use FrenchFrogs\Table\Table\Table;
use Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Models\Acl\Inside;
use Models\Acl\Partner;
use Models\Db\Clients\Client;
use Models\Db\Clients\ClientContract;
use Models\Db\Jmaker\Jmaker;
use Models\Db\Prescriber\Prescriber;
use Models\Db\Prescriber\PrescriberInvitation;
use Models\Db\Prescriber\PrescriberPermission;
use Ref;
use Throwable;
use Validator;
use function form;
use function generateNewUUID;
use function h;
use function js;
use function query;

/**
 * Class ClientDetailController
 * @package App\Http\Controllers\Inside
 */
class ClientDetailController extends Controller
{

    use FrenchFrogsController;

    /**
     * Get Client information
     * @param $clientUUID
     * @return Factory|View
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function getInformation($clientUUID)
    {
        $this->authorize(Inside::PERMISSION_CLIENT);
        $this->validate($this->request(), ['clientUUID' => 'required|exists:client,uuid']);
        // clients
        $client = Client::findOrNew($clientUUID);
        // liste des contacts
        $contacts = $this->tableContacts($clientUUID);
        $contracts = $this->tableContracts($clientUUID);
        // liste des proposition commerciales
        $invoices = $this->tableInvoices($clientUUID);

        // Liste des adresse fourni pour la facturation et les prooposition commerciales
        $addresses = $this->tableAddresses($clientUUID);

        // url de modification de la fiche cleint
        $edit = action_url(ClientsController::class, 'postIndex', $clientUUID);

        // recuperation des status
        $status = ref('client_status')->pairs();


        // Les choses à faire pour le passer inactive
        $next = [];
        if ($client->isSuspect()) {

            $next = [
                'status' => $status[Ref::CLIENT_STATUS_PROSPECT],
                'url' => action_url(ClientsController::class, 'postProspect', $client->uuid),
                'color' => 'bg-aqua'
            ];
        }

        // si fiche prospect
        if ($client->isProspect()) {
            $next = [
                'status' => $status[Ref::CLIENT_STATUS_ACTIVE],
                'url' => action_url(ClientsController::class, 'postActive', $client->uuid),
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
        $prescribers = $this->tablePrescribers($clientUUID);

        $metrics = query('client_metric_jmaker as mt')
            ->addSelect('mt.client_uuid')
            ->addSelectSum('(jmaker_state_invited)+(jmaker_state_onboarding)+(jmaker_state_active) as invitation_ct')
            ->addSelectSum('jmaker_state_active as active_ct')
            ->addSelectSum('(jmaker_ws_finished_1 + jmaker_ws_finished_2 + jmaker_ws_finished_3 + jmaker_ws_finished_4 + jmaker_ws_finished_5 + jmaker_ws_finished_6 + jmaker_ws_finished_7 + jmaker_ws_finished_8 + jmaker_ws_finished_9) as jmakerEngaged')
            ->addSelect('workshop_finished as completed_ct')
            ->addSelect('distinct_shared_ct as shared_ct')
            ->where('mt.client_uuid', $client->getKey());

        $kpi = $metrics->first();


        $jobmaker_url = action_url(ClientsController::class, 'getJMakersPage', $clientUUID);
        $culture_url = action_url(ClientsController::class, 'postCulture', $clientUUID);
        $status_url = action_url(ClientsController::class, 'postStatus', $clientUUID);

        // titre de la page
        h()->title($client->name . ' : infos');

        return view('inside.clients.information', compact('client', 'addresses', 'contacts','contracts', 'invoices',
            'edit', 'jobmaker_url', 'next', 'prescribers', 'kpi', 'culture_url','status_url'));
    }

    /**
     * List client contacts
     *
     * @param $clientUUID
     * @return Table
     * @throws AuthorizationException
     * @throws Exception
     */
    public function tableContacts($clientUUID)
    {
        $this->authorize(Inside::PERMISSION_CLIENT);

        // QUERY
        $query = query('client_contact as c', [
            'c.uuid as uuid',
            'c.client_uuid as client_uuid',
            'c.name',
            'r.name as type_name',
            'phones',
            'emails',
            'position_name',
            'c.updated_at',
        ])->join('reference as r', 'r.reference_id', 'contact_type_rid')
            ->where('client_uuid', $clientUUID);

        // TABLE
        $table = \table($query);
        $table->setConstructor(static::class, __FUNCTION__, $clientUUID)->enableRemote()->enableDatatable();
        $panel = $table->useDefaultPanel('Contacts')->getPanel();
        $panel->addButton('add_client', 'Ajouter', action_url(ContactController::class, 'postContact', $clientUUID))->enableRemote();
        $table->setIdField('uuid');


        // COLMUMN
        $table->addText('name', 'Nom')->setOrder('c.name')->setStrainerText('c.name');
        $table->addText('type_name', 'Type')->setStrainerSelect(ref('contact_type')->pairs(), 'c.contact_type_rid');
        $table->addText('position_name', 'Poste')->setStrainerText('position_name');
        $table->addText('emails', 'Emails')->setStrainerText('emails');
        $table->addDate('phones', 'Téléphones')->setStrainerText('phones');


        // ACTION
        $action = $table->addContainer('action', 'Action')->setWidth('80')->right();
        $action->addButtonEdit(action_url(ContactController::class, 'postContact', [$clientUUID, '%s']), 'uuid');
        return $table;
    }

    /**
     * List client contracts
     *
     * @param $clientUUID
     * @return Table
     * @throws AuthorizationException
     * @throws Exception
     */
    public function tableContracts($clientUUID)
    {
        $this->authorize(Inside::PERMISSION_CLIENT);
        $jmakers = Jmaker::where('client_uuid',$clientUUID)->get();
        $prescribers = Prescriber::where('client_uuid',$clientUUID)->get();
        // QUERY
        $query = query('client_contract as cc', [
            'cc.uuid as uuid',
            'cc.client_uuid as client_uuid',
            'cc.name',
            'cc.expired_at',
            raw('CONCAT('.count($jmakers).'., "/", cc.jmaker_limit) as jmaker_limit'),
            raw('CONCAT('.count($prescribers).'., "/", cc.prescriber_limit) as prescriber_limit'),
            'cc.created_at',
        ])
            ->where('cc.client_uuid', $clientUUID);
        // TABLE
        $table = \table($query);
        $table->setConstructor(static::class, __FUNCTION__, $clientUUID)->enableRemote()->enableDatatable();
        $panel = $table->useDefaultPanel('Contras')->getPanel();
        if(count($query->get()) == 0)
        {
            $panel->addButton('add_contract', 'Ajouter', action_url(ContractController::class, 'postContract', $clientUUID))->enableRemote();
        }
        $table->setIdField('uuid');

        // COLMUMN
        $table->addText('name', 'Nom')->setOrder('c.name')->setStrainerText('cc.name');
        $table->addDate('expired_at', 'Expire le')->setStrainerText('cc.expired_at');
        $table->addText('jmaker_limit', 'Quotas Jmaker')->setStrainerText( 'jmaker_limit')->center();
        $table->addText('prescriber_limit', 'Quotas Prescriber')->setStrainerText('prescriber_limit')->center();
        $table->addDate('created_at', 'Création le')->setStrainerText('created_at');

        // ACTION
        $action = $table->addContainer('action', 'Action')->setWidth('80')->right();
        $action->addButtonEdit(action_url(ContractController::class, 'postContract', [$clientUUID, '%s']), 'uuid');
        return $table;
    }
    /**
     * List contact address
     *
     * @param $clientUUID
     * @return Table
     * @throws AuthorizationException
     * @throws Exception
     */
    public function tableAddresses($clientUUID)
    {
        // VALIDATION
        $this->authorize(Inside::PERMISSION_CLIENT);

        $client = Client::findOrFail($clientUUID);

        // TABLE
        $table = table((array)$client->addresses);
        $table->setNenuphar(n(static::class, __FUNCTION__, [$clientUUID]))->enableRemote()->enableDatatable();
        $panel = $table->useDefaultPanel('Adresses')->getPanel();
        $panel->addButton('add_address', 'Ajouter', action_url(AddressController::class, 'postAddress', $clientUUID))->enableRemote();
        $table->setIdField('uuid');


        // COLMUMN
        $table->addText('company', 'Société');
        $table->addText('contact', 'Contact');
        $table->addText('address', 'Adresse');
        $table->addText('zipcode', 'Code postal');
        $table->addText('city', 'Ville');
        $table->addText('siret', 'Siret');

        // ACTION
        $action = $table->addContainer('action', 'Action')->setWidth('120')->right();

        $action->addButtonEdit(action_url(AddressController::class, 'postAddress', [$clientUUID, '%s']), 'uuid');
        return $table;
    }

    /**
     * List all invoice for one client
     *
     * @param $clientUUID
     * @return Table
     * @throws AuthorizationException
     * @throws Exception
     */
    public function tableInvoices($clientUUID)
    {
        // VALIDATION
        $this->authorize(Inside::PERMISSION_CLIENT);

        // QUERY
        $query = query('client_invoice as ci', [
            'ci.uuid as uuid',
            'ci.client_uuid as client_uuid',
            'invoice_status_rid',
            'r.name as status_name',
            'proposal',
            'amount_before_vat',
            'amount',
            'purchase_order_chrono',
            'purchase_order_at',
            'invoice_at',
            'topay_at',
            'invoice_chrono'
        ])->join('reference as r', 'r.reference_id', 'invoice_status_rid')
            ->whereNull('ci.deleted_at')
            ->where('client_uuid', $clientUUID);

        // TABLE
        $table = \table($query);
        $table->setConstructor(static::class, __FUNCTION__, $clientUUID)->enableRemote()->enableDatatable();
        $panel = $table->useDefaultPanel('Offres commerciales')->getPanel();
        $panel->addButton('add_invoice', 'Ajouter', action_url(InvoiceController::class, 'postInvoice', $clientUUID))->enableRemote();
        $table->setIdField('uuid');


        // COLMUMN
        $table->addText('status_name', 'Statut')->setStrainerSelect(ref('invoice_status')->pairs(), 'i.invoice_type_rid');
        $table->addText('purchase_order_chrono', '# Offre')->setOrder('i.purchase_order_chrono')->setStrainerText('i.purchase_order_chrono');
        $table->addText('invoice_chrono', '# Facture')->setOrder('i.invoice_chrono')->setStrainerText('i.invoice_chrono');
        $table->addText('amount', 'Montant TTC')->right()->addFilter('euro', function ($d) {
            return number_french($d) . '€';
        });
        $table->addDate('purchase_order_at', 'Crée le')->setOrder('i.purchase_order_at');
        $table->addDate('invoice_at', 'Facturé le')->setOrder('i.invoice_at');
        $table->addDate('topay_at', 'A payé le')->setOrder('i.topay_at');


        $process = $table->addContainer('process', '')->setWidth('80')->right();
        $process->addButtonRemote('invoice', 'Facture', action_url(InvoiceController::class, 'postInvoiceStatus', '%s'), 'uuid')
            ->setOptionAsPrimary()
            ->setVisibleCallback(function ($e, $d) {
                return $d['invoice_status_rid'] == Ref::INVOICE_STATUS_PURCHASE_ORDER && Gate::check(Inside::PERMISSION_CLIENT_PAID);
            });


        $process->addButtonRemote('paid', 'Payer', action_url(InvoiceController::class, 'postPaid', '%s'), 'uuid')
            ->setOptionAsWarning()
            ->setVisibleCallback(function ($e, $d) {
                return $d['invoice_status_rid'] == Ref::INVOICE_STATUS_INVOICE && Gate::check(Inside::PERMISSION_CLIENT_PAID);
            });


        // ACTION
        $action = $table->addContainer('action', 'Action')->setWidth('120')->right();

        $action->addButton('proposal', 'Proposition Commerciale', action_url(InvoiceController::class, 'getProposalPDF', '%s'), 'uuid')
            ->addAttribute('target', '_blank')
            ->icon('fa fa-comment')
            ->setVisibleCallback(function ($e, $d) {
                return !empty($d['purchase_order_chrono']);
            });


        $action->addButton('invoice', 'Facture', action_url(InvoiceController::class, 'getInvoicePDF', '%s'), 'uuid')
            ->addAttribute('target', '_blank')
            ->icon('fa fa-money')
            ->setVisibleCallback(function ($e, $d) {
                return !empty($d['invoice_chrono']);
            });


        $action->addButtonEdit(action_url(InvoiceController::class, 'postInvoice', [$clientUUID, '%s']), 'uuid');
        $action->addButtonDelete(action_url(InvoiceController::class, 'deleteInvoice', [$clientUUID, '%s']), 'uuid')
            ->setVisibleCallback(function ($e, $d) {
                return $d['invoice_status_rid'] == Ref::INVOICE_STATUS_PURCHASE_ORDER;
            });

        return $table;
    }

    /**
     * Build Prescribers table
     *
     * @param $ClientUUID
     * @return Table
     * @throws AuthorizationException
     * @throws Exception
     */
    public function tablePrescribers($ClientUUID)
    {
        $this->authorize(Inside::PERMISSION_CLIENT);

        $subPrescriberInvitation = query('prescriber_invitation as pi', [
            'pi.prescriber_uuid as prescriber_uuid',
            'm.opened_at',
            'm.sent_at'
        ])
            ->Join('mail as m', 'm.uuid', '=', 'pi.mail_uuid');

        $sub = query('jmaker_invitation as i')
            ->addSelect('invited_by_prescriber_uuid')
            ->addSelectCount('* as invitation_ct')
            ->addSelectSum('is_completed as invitation_completed_ct')
            ->whereNull('deleted_at')
            ->groupBy('invited_by_prescriber_uuid');

        // QUERY
        $query = query('prescriber as p', [
            'p.uuid',
            raw('if(ppp.prescriber_permission_id is null,0, 1) as admin'),
            'a.invitation_ct as invitation_ct',
            'a.invitation_completed_ct as invitation_completed_ct',
            'p.name',
            'p.last_dashboard_at',
            'p.lastname',
            'p.onboarded',
            'p.firstname',
            'email',
            'description',
            'loggedin_at',
            'b.opened_at as invite_opened_at',
            'b.sent_at as invite_send_at',
            raw("if(sent_at is null, 'Ajout','Invite') as typePrescriber")
        ])->leftJoin('prescriber_permission_prescriber as ppp', function ($join) {
                $join->on('ppp.prescriber_uuid', '=', 'p.uuid')->where('ppp.prescriber_permission_id', Partner::PERMISSION_PARTNER_ADMIN);
            })
            ->leftJoinQuery($sub, 'a', 'p.uuid', 'a.invited_by_prescriber_uuid')
            ->leftJoinQuery($subPrescriberInvitation, 'b', 'p.uuid', 'b.prescriber_uuid')

            ->where('p.client_uuid', $ClientUUID)
            ->where('p.deleted_at', null);

        // TABLE
        $table = \table($query);
        $table->setConstructor(static::class, __FUNCTION__, $ClientUUID)->enableRemote()->enableDatatable();
        $panel = $table->useDefaultPanel('Prescripteurs')->getPanel();
        if (Gate::check(Inside::PERMISSION_USER)) {
            $panel->addButton('link_user', 'Relier un prescripteur sans client sur un client (historique)', action_url(ClientsController::class, 'postLink', $ClientUUID))
                ->icon('fa fa-link')
                ->setOptionAsDanger()
                ->enableRemote();
        }
        $panel->addButton('add_users', 'Ajouter', action_url(static::class, 'postPrescriber', $ClientUUID))->enableRemote();
        $panel->addButton('invite_partner', 'Inviter', action_url(static::class, 'postInvitePrescriber', $ClientUUID))->enableRemote();

        // COLMUMN
        $table->addText('lastname', 'Nom')->setOrder('lastname')->setStrainerText('lastname');
        $table->addText('firstname', 'Prénom')->setOrder('firstname')->setStrainerText('firstname');
        $table->addText('email', 'Email')->setOrder('email')->setStrainerText('email');
        $table->addText('description', 'Description')->setOrder('description')->setStrainerText('description');
        $table->addDate('typePrescriber', 'Type')->setOrder('typePrescriber');
        $table->addDate('invite_send_at', 'Invit sent')->setOrder('invite_send_at');
        $table->addDate('invite_opened_at', 'Invit Opened')->setOrder('invite_opened_at');
        $table->addDate('typePrescriber', 'Type')->setOrder('typePrescriber');
        $table->addDate('last_dashboard_at', 'Actif le.')->setOrder('last_dashboard_at');
        $table->addBoolean('admin', 'Admin')->setOrder('admin');
        //$table->addBoolean('onboarded', 'Onboarded cplt')->setOrder('onboarded');
        $table->addText('invitation_ct', 'Invit (J)')->setOrder('invitation_ct');
        $table->addText('invitation_completed_ct', 'Invit (J) actif')->setOrder('invitation_completed_ct');

        // ACTION
        $action = $table->addContainer('action', 'Action')->setWidth('160')->right();

        if (Gate::check(Inside::PERMISSION_USER)) {
            // connexion sur interface utrilisateur
            $action->addButton('linkme', 'Interface', action_url(PrescriberController::class, 'getPrescriberConnect', '%s'), 'uuid')
                ->addAttribute('target', '_blank')
                ->icon('fa fa-external-link')
                ->setOptionAsSuccess();
            $action->addButtonRemote('permission', 'Permissions', action_url(static::class, 'postPermissions', '%s'), 'uuid')->icon('fa fa-gavel');
            $action->addButtonRemote('password', 'Mot de passe', action_url(PrescriberController::class, 'postPassword', '%s'), 'uuid')->icon('fa fa-key')->setOptionAsWarning();
        }

        $action->addButtonEdit(action_url(static::class, 'postPrescriber', [$ClientUUID, '%s']), 'uuid');

        return $table;
    }

    /**
     * Create or update Prescriber
     * @param $clientUUID
     * @param null $prescriberUUID
     * @return mixed
     * @throws AuthorizationException
     * @throws ValidationException
     * @throws Throwable
     */
    public function postPrescriber($clientUUID, $prescriberUUID = null)
    {
        $this->authorize(Inside::PERMISSION_CLIENT);
        $this->validate($request = $this->request(), [
            'clientUUID' => 'required|exists:client,uuid',
            'prescriberUUID' => [
                Rule::exists('prescriber', 'uuid')
                    ->where(function (Builder $query) use ($clientUUID) {
                        $query->where('client_uuid', $clientUUID);
                    })
            ]
        ]);

        // Recuperation du client
        $client = Client::findOrNew($clientUUID);

        // Recuperation de l'utilisateur
        /** @var Prescriber $prescriber */
        $prescriber = $client->prescribers()->findOrNew($prescriberUUID);

        // FORM
        $form = form()->enableRemote();

        if ($prescriber->exists) {
            $form->setLegend("Modification prescripteur");
        } else {
            $form->setLegend("Ajout d'un Prescripteur sans invitation");
        }

        $form->addText('lastname', 'Nom');
        $form->addText('firstname', 'Prénom');
        $form->addEmail('email', 'Email')->addValidator('unique', function ($v) use ($prescriber) {
            $rule = Rule::unique('prescriber', 'email')
                ->where('interface_rid', Ref::INTERFACE_PARTNER)
                ->where(function ($query) use ($prescriber) {
                    /** @var QueryBuilder $query */
                    $query->where('uuid', '<>', $prescriber->uuid);
                });

            return !Validator::make(func_get_args(), [$rule])->fails();
        }, [], 'Un compte paretenaire existe déjà avec cet email');

        // si creation on ajout le champs password!
        if (!$prescriber->exists) {
            $form->addText('password', 'Mot de passe');
        }

        $form->addText('description', 'Description', false);
        $form->addSubmit('Enregistrer');

        // enregistrement
        if ($request->has('Enregistrer')) {
            $form->valid($request->all());
            if ($form->isValid()) {
                $data = $form->getFilteredAliasValues();
                try {

                    transaction(function () use ($prescriber, $client, $data) {

                        if (!$prescriber->exists) {
                            $attach = true;
                            $prescriber->uuid = generateNewUUID();
                            $prescriber->client_uuid = $client->uuid;

                            /** @var ClientContract $contract */
                            $contract = $client->contacts()->first();
                            if ($contract) {
                                $prescriber->contract_uuid = $contract->uuid;
                            }

                            $prescriber->interface_rid = Ref::INTERFACE_PARTNER;
                            $prescriber->user_interface_id = 'partner';
                            $prescriber->password = bcrypt($data['password']);
                            //prescriber add instead invite need to be flaged as Onboarded.
                            $prescriber->onboarded = true;
                        }

                        // Maj de l'utilisateur
                        $prescriber->email = $data['email'];
                        $prescriber->lastname = $data['lastname'];
                        $prescriber->firstname = $data['firstname'];
                        $prescriber->name = $data['firstname'] . " " . $data['lastname'];
                        $prescriber->description = $data['description'];
                        $prescriber->save();

                    });

                    js()->success()->closeRemoteModal()->reloadDataTable();
                } catch (Exception $e) {
                    js()->error($e->getMessage());
                }
            }
        } elseif ($prescriber->exists) {
            $form->populate($prescriber->toArray());
        } else {
            $form->populate(['password' => str_random(8)]);
        }

        return response()->modal($form);
    }

    /**
     * Invite a new prescriber
     * @param $clientUUID
     * @param null $prescriberUUID
     * @return mixed
     * @throws AuthorizationException
     * @throws ValidationException
     * @throws Throwable
     */
    public function postInvitePrescriber($clientUUID, $prescriberUUID = null)
    {
        $this->authorize(Inside::PERMISSION_CLIENT);
        $this->validate($request = $this->request(), [
            'clientUUID' => 'required|exists:client,uuid'
        ]);

        // Recuperation du client
        $client = Client::findOrNew($clientUUID);

        //Recuperation de l'utilisateur
        /** @var Prescriber $prescriber */
        $prescriber = $client->prescribers()->findOrNew($prescriberUUID);

        // FORM
        $form = form()->enableRemote();
        $form->setLegend('Invitation prescripteur');
        $form->addEmail('email', 'Email')->addValidator('unique', function ($v) use ($prescriber) {
            $rule = Rule::unique('prescriber', 'email')
                ->where('interface_rid', Ref::INTERFACE_PARTNER);
            //->where(function ($query) use ($prescriber) {
            //    $query->whereRaw('uuid <> ?',$prescriber->uuid);
            //});
            return !Validator::make(func_get_args(), [$rule])->fails();
        }, [], 'Un compte paretenaire existe déjà avec cet email');
        $form->addText('lastname', 'Nom');
        $form->addText('firstname', 'Prénom');
        $form->addSeparator();
        $form->addTextarea('message_custom', 'Message personnalisé', false)
            ->addAttribute('rows', 8)
            ->addAttribute('maxlength', 800);
        $form->addSeparator();

        /** @var Collection $prescribersList */
        $prescribersList = query('prescriber as p', [
            'p.uuid',
            'p.name as name',
        ])->Join('prescriber_permission_prescriber as ppp', function ($join) {
                $join->on('ppp.prescriber_uuid', '=', 'p.uuid')->where('ppp.prescriber_permission_id', Partner::PERMISSION_PARTNER_ADMIN);
            })
            ->where('p.client_uuid', $clientUUID)->pluck('name', 'p.uuid');

        $form->addBoolean('isadmin', 'Admin');
        $prescribersList->prepend(' ', " ");
        $form->addSelect('admin_user_uuid', "Invité par l'admin", $prescribersList, false);

        $form->addSeparator();
        $campaigns = $client->campaigns()->orderBy('name')->pluck('name', raw('uuid'));
        $form->addSelect('campaign_uuid', 'Campagne', $campaigns, false)->enableMultiple();
        if(count($client->languages()->get())>1){
            $form->addSelect('langue', 'Langue', [Ref::LANG_FR => 'Français', Ref::LANG_EN => 'Anglais'], true);
        }

        $form->addSubmit("Envoyer");

        // Enregistrement
        if ($request->has('Envoyer')) {
            $form->valid($request->all());
            if ($form->isValid()) {
                $data = $form->getFilteredAliasValues();
                try {
                    transaction(function () use ($prescriber, $client, $data, $request) {
                        // Create new Prescrtiber
                        $prescriber->uuid = generateNewUUID();
                        $prescriber->client_uuid = $client->uuid;

                        /** @var ClientContract $contract */
                        $contract = $client->contracts()->first();
                        if ($contract) {
                            $prescriber->contract_uuid = $contract->uuid;
                        }

                        $prescriber->interface_rid = Ref::INTERFACE_PARTNER;
                        $prescriber->user_interface_id = 'partner';
                        $prescriber->onboarded = false;
                        $prescriber->email = $data['email'];
                        $prescriber->firstname = $data['firstname'];
                        $prescriber->lastname = $data['lastname'];
                        $defaultlanguage =  "LANG_FR";

                        if($client instanceof Client && $client->languages())
                        {
                            $defaultlanguage = $client->languages()->first()->id;
                        }

                        $prescriber->onboarded_at = null;
                        $prescriber->language = isset($data['langue']) ? $data['langue'] : $defaultlanguage;
                        $prescriber->name = $data['firstname'] . " " . $data['lastname'];
                        $prescriber->save();

                        if ($data['isadmin'] == true) {
                            $permission = array("partner_admin" => "partner.admin");
                            $prescriber->permissions()->sync($permission);

                        }
                        $prescriberInvitation = new PrescriberInvitation();
                        $prescriberInvitation->uuid = generateNewUUID();

                        if ($data['admin_user_uuid'] != null) {
                            $prescriberInvitation->invited_by_prescriber_uuid = $data['admin_user_uuid'];
                        }
                        $prescriberInvitation->token = str_random(60);
                        $prescriberInvitation->prescriber_uuid = $prescriber->uuid;

                        $param = array('message_custom' => $data['message_custom']);
                        $prescriberInvitation->data = $param;

                        $newCampaign = [];

                        if (is_array($data['campaign_uuid'])) {
                            $newCampaign = $data['campaign_uuid'];
                        }

                        $prescriber->campaigns()->sync($newCampaign);

                        $mail = MailService::pushInDB(InvitePartner::class,$prescriber->email, $prescriberInvitation->token);

                        $prescriberInvitation->mail_uuid = $mail->uuid;
                        $prescriberInvitation->save();

                    });

                    js()->success()->closeRemoteModal()->reloadDataTable();
                } catch (Exception $e) {
                    js()->error($e->getMessage());
                }
            }
        } elseif ($prescriber->exists) {
            $form->populate($prescriber->toArray());
        }

        return response()->modal($form);
    }


    /**
     * Edit Prescriber permissions
     * @param $prescriberUUID
     * @return mixed
     * @throws AuthorizationException
     * @throws ValidationException
     * @throws Throwable
     */
    public function postPermissions($prescriberUUID)
    {
        $this->authorize(Inside::PERMISSION_CLIENT);
        $this->validate($request = $this->request(),
            ['prescriberUUID' =>
                ['required',
                    Rule::exists('prescriber', 'uuid')
                        ->where(function (Builder $query) {
                            $query->where('interface_rid', Ref::INTERFACE_PARTNER);
                        })
                ]
            ]
        );

        // Récuperation du model
        $prescriber = Prescriber::findOrFail($prescriberUUID);

        // Formulaire
        $form = form()->enableRemote();
        $form->setLegend($prescriber->name);

        $form->addBoolean(str_replace('.', '_', Partner::PERMISSION_PARTNER_ADMIN), 'Administrateur?');
        $form->addSeparator();

        $form->addTitle('Parcours');
        $prescriberPermissions = PrescriberPermission::where('prescriber_permission_group_id', Partner::PERMISSION_GROUP_CAMPAIGN)
            ->where('id', '!=', Partner::PERMISSION_CAMPAIGN)
            ->pluck('name', 'id');

        foreach ($prescriberPermissions as $id => $name) {
            $form->addBoolean(str_replace('.', '_', $id), $name);
        }
        $form->addSubmit('Enregistrer');

        // enregistrement
        if ($request->has('Enregistrer')) {
            $form->valid($request->all());
            if ($form->isValid()) {
                $data = $form->getValues();
                try {

                    transaction(function () use ($prescriber, $data) {

                        // formatage des permissions
                        $permissions = collect($data)
                            ->filter(function ($v, $i) {
                                return $v;
                            })
                            ->transform(function ($v, $i) {
                                return str_replace('_', '.', $i);
                            });
                        $prescriber->permissions()->sync($permissions);
                    });

                    js()->success()->closeRemoteModal()->reloadDataTable();
                } catch (Exception $e) {
                    js()->error($e->getMessage());
                }
            }
        } else {

            $data = [];
            foreach ($prescriber->permissions()->pluck('id') as $id) {
                $data[str_replace('.', '_', $id)] = true;
            }

            //PERMISSION_PARTNER

            $form->populate($data);
        }

        return response()->modal($form);
    }



}
