<?php namespace App\Mail\Jobmaker;


use App\Mail\Mailable;
use Models\Db\Jmaker\JmakerInvitation;
use Jenssegers\Date\Date;
use Models\Db\Languages\Languages;


/**
 *
 *
 */
class Invite extends Mailable
{
    /**
     *
     * @var JmakerInvitation
     */
    public $invitation;


    /**
     *
     *
     * @var array
     */
    public $partner;


    /**
     *
     * @var
     */
    public $cta_url;

    /**
     * @var
     */
    public $personnalMessage;


    /**
     *
     *
     */
    public $textView = 'emails.jobmaker.invite_text';


    /**
     *
     *
     */
    public $view = 'emails.jobmaker.invite';


    /**
     *
     *
     * @param JmakerInvitation
     */
    function __construct($invitation, $from_custom = null, $object_custom = null, $message_custom = null)
    {
        //Recuperation de l'invitation
        $invitation = $this->invitation = JmakerInvitation::where('token', $invitation)->firstOrFail();
        $language_id = $invitation->language_id;

        //Calcul du language
        languageJobmaker($invitation, $language_id);

        Date::setLocale(Languages::find($language_id)->locale);

        // Recuperation de la configuration de l'invitation
        $data = $this->invitation->data;

        //$data['campaign_uuid'];

        // MESSAGE
        $this->personnalMessage = empty($data['message']) ? '' : nl2br($data['message']);
        if (!is_null($message_custom)) {
            $this->personnalMessage = $message_custom;
        }
        $jmaker = $invitation->jmaker()->first();
        $client = $jmaker->client()->first();
        // Gestion de l'utilisateur faisant l'invitation
        $customizations = $client->customizations();
        $customization = $customizations->where("language_id",$language_id)->first();
        if($customization) {
            $params = $customization->params;

        } else {
            $params = [];
        }

        // Contenu du mail
        $mail_title = 'Jobmaker';
        $mail_content = __('ztT0cv');

        //dd($mail_content);
        // cas du rendu custommisé
        if (!empty($params['view_mail'])) {
            $this->view = $params['view_mail'];
        }

        //Get invitation's Meeting date if exist
        $meeting = $this->invitation->jmakerMeetings()->whereNull("deleted_at")->orderBy('created_at', 'desc')->first();
        // information personnalisé du mail
        if (isset($params['mail_content'])) {
            $params['mail_content'] = nl2br($params['mail_content']);
        }

        if(isset($params['mail_content_2']))
        {
            $params['mail_content_2'] = nl2br($params['mail_content_2']);
        }
        $this->partner = [
            'name' => $client->name,
            'url' => empty($params['url']) ? null : $params['url'],
            'img' => empty($params['img']) ? null : route('media-show', $params['img']),
            'mail_title' => empty($params['mail_title']) ? $mail_title : $params['mail_title'],
            'mail_content' => empty($params['mail_content']) ? $mail_content : $params['mail_content'],
            'mail_content_2' => empty($params['mail_content_2']) ? "" : $params['mail_content_2'],
            'meeting_date' => empty($meeting) ? null : sprintf(__('sBEoap'), (new Date($meeting->meeting_date))->format('j F Y')),
        ];

        $tmpFirstname =  "";
        $tmpLastname =  "";

        if(!empty($data['firstname'])){
            $tmpFirstname = $data['firstname'];
        }

        if(!empty($data['lastname'])){
            $tmpLastname = $data['lastname'];
        }
        
        $username = trim($tmpFirstname . ' ' . $tmpLastname);


        // cas du rendu custommisé
        if (!empty($params['view_mail'])) {
            $this->view = $params['view_mail'];
        }

        // Gestion du nom de l'expediteur
        $prescriber = $this->invitation->prescriber;
        $from = 'Jobmaker';
        if (empty($from_custom) && $prescriber) {
            $from = $prescriber->name . ' via Jobmaker';
        }
        $this->to($this->invitation->email, $username);

        //
        $this->from('invitation@jobmaker.fr', $from_custom? $from_custom : $from);
        $this->replyTo('invitation@jobmaker.fr');

        // SUBJECT
        $this->subject($object_custom ?: $client->name . __('utdzSo'));
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->renderPixel();
        $this->cta_url = route('invitation', [$this->invitation->token]) . '?' . http_build_query(['_mail' => $this->getUuid(), '_cta' => 'main']);
        return $this;
    }
}