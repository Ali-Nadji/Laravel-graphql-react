<?php namespace App\Mail\Partner;


use App\Mail\Mailable;
use Jenssegers\Date\Date;
use Models\Db\Jmaker\Jmaker;
use Models\Db\Languages\Languages;


/**
 * Notification send to user if his last action is 2 month older
 *
 */
class PartnerToUser extends Mailable
{
    /**
     *
     * @var Jmaker
     */
    public $jmaker;

    /**
     * @var string
     */
    public $cta_url;

    /**
     * Client information
     * @var array
     */
    public $clientInfo;

    /**
     * @var Personnalise message
     */
    public $dedicatedMessage;

    /**
     *
     * @var string
     */
    public $textView = 'emails.partner.partnerToUser_text';
    /**
     *
     * @var string
     */
    public $view = 'emails.partner.partnerToUser';

    /**
     * @param user
     */
    function __construct($userParam, $customSubject, $dedicatedMessage)
    {
        //Init param
        $this->dedicatedMessage =  $dedicatedMessage;
        $this->jmaker = Jmaker::findOrFail($userParam);
        $invitation = $this->jmaker->invitation()->first();

        $language_id = $this->jmaker->language()->first()->id;

        //Build language for Jobmaker user
        languageJobmaker($invitation, $this->jmaker->language()->first()->id);

        $client = $this->jmaker->client()->first();

        //Gestion de l'utilisateur faisant l'invitation
        $customizations = $client->customizations();
        $customization = $customizations->where("language_id",$language_id)->first();
        if($customization) {
            $params = $customization->params;
        } else {
            $params = [];
        }

        $this->clientInfo = [
            'name' => $client->name,
            'url' => empty($params['url']) ? null : $params['url'],
            'img' => empty($params['img']) ? null : route('media-show', $params['img']),
        ];

        Date::setLocale(Languages::find($language_id)->locale);

        $this->subject($customSubject);

        // Gestion du nom de l'expediteur
        $prescriber = $invitation->prescriber;
        $from = 'Jobmaker';
        if ($prescriber) {
            $from = $prescriber->name . ' via Jobmaker';
        }

        $this->to($this->jmaker->email, $this->jmaker->username);
        $this->from('contact@jobmaker.fr', $from);
        if ($prescriber) {
            $this->replyTo($prescriber->email);
        } else {
            $this->replyTo('contact@jobmaker.fr');
        }
    }

    /**
     * Build email
     */
    function build()
    {
        $this->cta_url = route('jobmaker.dashboard') . '?' . http_build_query(['_mail' => $this->getUuid(), '_cta' => 'partnerToUser']);
        $this->renderPixel();
    }
}