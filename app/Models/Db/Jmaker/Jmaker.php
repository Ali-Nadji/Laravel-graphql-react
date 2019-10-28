<?php namespace Models\Db\Jmaker;


use App\Mail\Jobmaker\ResetPassword;
use App\Services\Mail\MailService;
use Carbon\Carbon;
use FrenchFrogs\Laravel\Database\Eloquent\Model;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Models\Db\Clients\Client;
use Models\Db\Clients\ClientCampaign;
use Models\Db\Mission\Step;
use Models\Db\Deprecated\Partner;
use Models\Db\Deprecated\Payment;
use Models\Db\Deprecated\token;
use Models\Db\Languages\Languages;
use Models\Db\Mission\Sequence;
use Models\Db\Payment\Stripe;
use Models\Db\Prescriber\Prescriber;
use Models\Db\Way;


/**
 * Class Jmaker
 * @property string $uuid
 * @property string $client_uuid
 * @property string $prescriber_uuid
 * @property string $campaign_uuid
 * @property string $contract_uuid
 * @property int $old_id
 * @property string $username
 * @property string $username_canonical
 * @property string $email
 * @property string $state
 * @property string $language_id
 * @property bool $enabled
 * @property string $salt
 * @property string $password
 * @property Carbon $last_login
 * @property Carbon $last_page_at
 * @property bool $locked
 * @property bool $want_notification
 * @property bool $expired
 * @property Carbon $expires_at
 * @property string $confirmation_token
 * @property string $remember_token
 * @property Carbon $password_requested_at
 * @property bool $credentials_expired
 * @property Carbon $credentials_expire_at
 * @property string $firstname
 * @property string $lastname
 * @property Carbon $birthdate
 * @property string $city
 * @property string $country
 * @property string $stripe_id
 * @property int $partner_id
 * @property string $wizbii_referential
 * @property int $session_ct
 * @property int $workshop_started
 * @property int $workshop_finished
 * @property int $step_finished
 * @property int $work_experience
 * @property Carbon $last_session_at
 * @property Carbon $registred_at
 * @property string $wizbii_package
 * @property bool needComputeNotification
 * @property Carbon $created_at
 * @property Carbon $deleted_at
 * @package Models\Db\Jmaker
 */
class Jmaker extends Model implements \Illuminate\Contracts\Auth\Authenticatable, \Illuminate\Contracts\Auth\CanResetPassword
{
    use Authenticatable;
    use Notifiable;
    use CanResetPassword;
    use SoftDeletes;

    public $timestamps = false;

    /**
     * Disable auto incrementing
     * @var bool
     */
    public $incrementing = false;

    /**
     * Key type
     * @var string
     */
    public $keyType = 'string';
    /**
     * Primary key
     * @var string
     */
    protected $primaryKey = 'uuid';
    /**
     * Table name
     * @var string
     */
    protected $table = 'jmaker';

    protected $dates = [
        "last_page_at",
        "expires_at",
        'birthdate',
        "password_requested_at",
        "credentials_expire_at",
        "last_session_at",
        "created_at",
        "registred_at"
    ];


    protected $casts = [
        "want_notification" => 'boolean'
    ];

    /**
     * Return FosUser Language
     *
     * @return HasOne
     */
    public function language()
    {
        return $this->hasOne(Languages::class, 'id','language_id');
    }


    /**
     * Lien vers les steps
     *
     * @return BelongsToMany
     */
    public function steps()
    {
        return $this->belongsToMany(Step::class, 'jmaker_to_mission_step', 'jmaker_uuid', 'mission_step_id')->withTimestamps();
    }

    /**
     * Return the last meeting for the user
     */
    public function nextMeeting() {
        return $this->meetings()->whereNull('deleted_at')->orderBy('meeting_date','asc')->first();
    }

    public function meetings()
	{
		return $this->hasMany(JmakerMeeting::class, "jmaker_uuid", "uuid");
	}

    /**
     *
     *
     * @return HasMany
     */
    public function getNotifications()
    {
        return $this->hasMany(JmakerNotifications::class, 'jmaker_uuid');
    }

    /**
     *
     *
     * @return HasMany
     */
    public function stripes()
    {
        return $this->hasMany(Stripe::class, 'jmaker_uuid');
    }


    /**
     * Send the password reset notification.
     * Override
     * @return void
     * @param token
     */
    public function sendPasswordResetNotification($token)
    {
        MailService::pushInDB(ResetPassword::class, $this->email, route('front.change_password', [$token,$this->language()->first()->locale]), $this->uuid);
    }

    /**
     *
     *
     * @return BelongsTo
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }


    /**
     * Use to know if jmaker is B2C or not.
     * @return bool true if jmaker is a B2C false otherwise
     */
    public function isB2c()
    {

        $invitation = $this->invitation()->first();
        if(empty($invitation)) {
            return true;
        } else {
            /** @var Client $client */
            $client = $this->client()->first();
            if(empty($client)) {
                return true;
            } else {
                return $client->enableB2BasB2C;
            }
        }
    }

    /**
     * Return the current client associate with the Jmaker.
     * @return hasOne
     */
    public function client()
    {

        return $this->hasOne(Client::class,'uuid','client_uuid');
    }

    /**
     * Return the current campaign associate with the Jmaker.
     * @return hasOne
     */
    public function campaign()
    {

        return $this->hasOne(ClientCampaign::class,'uuid','campaign_uuid');
    }

    /**
     * Return the current prescriber associate with the Jmaker.
     * @return hasOne
     */
    public function prescriber()
    {
        return $this->hasOne(Prescriber::class, 'uuid', 'prescriber_uuid');

    }

        /**
     * Get lastWorkshopEvent
     * @return HasOne
     */
    public function lastWorkshopEvent()
    {
        return $this->hasOne(JmakerLastWorkshopEvent::class, 'jmaker_uuid','uuid');
    }


    /**
     * Get workshopNotification
     * @return hasMany
     */
    public function workshopNotification()
    {
        return $this->hasMany(JmakerNotificationWorkshop::class, 'jmaker_uuid','uuid');
    }


    /**
     * Return way
     *
     * @return HasOne
     */
    public function way()
    {
        return $this->hasOne(Way::class, 'jmaker_uuid');
    }


    /**
     *
     *
     * @return bool
     */
    public function hasPaymentSucceed()
    {
        return !empty($this->paymentSucceed());
    }


    /**
     * Return payment for user
     *
     * @return Payment
     */
    public function paymentSucceed()
    {
        return $this->payments()->where('status', 'succeeded')->first();
    }


    /**
     *
     *
     * @return HasMany
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'jmaker_uuid');
    }


    /**
     * @return $this
     */
    public function missionRunCompleted()
    {
        return $this->runs()->where('completed', 1);
    }


    /**
     *
     *
     * @return HasMany
     */
    public function runs()
    {
        return $this->hasMany(\Models\Db\Mission\Run::class, 'jmaker_uuid');
    }

    /**
     * Return le nombre de mission de la sequence de l'utilisateur
     *
     */
    public function getSequenceCount()
    {
        $sequence = $this->getSequenceContent();
        return empty($sequence) ? Sequence::MISSION_SEQUENCE_MAX_COUNT : array_sum($sequence);
    }


    /**
     * Return array sequence attribué au jobmaker
     *
     */
    public function getSequenceContent()
    {
        // inititliasation
        $sequence = null;
        $way = $this->way;
        // recuperation de la sequence par le way
        if ($way) {
            $sequence = $way->mission_sequence_sid;
        }
        // on recherche une sequence par rapport a un eventuel code promo
        if (is_null($sequence) && ($payment = $this->paymentSucceed())) {
            if ($voucher = $payment->voucher) {
                $sequence = $voucher->mission_sequence_sid;
            }
        }
        return Sequence::findOrFail($sequence ?: Sequence::SEQUENCE_DEFAULT)->sequence_content;
    }


    /**
     *
     *
     * @return HasOne
     */
    public function invitation()
    {
        return $this->hasOne(JmakerInvitation::class, 'jmaker_uuid');
    }


    /**
     * Return all SynthesisShare
     *
     * @return HasMany
     */
    public function synthesisShares()
    {
        return $this->hasMany(SynthesisShare::class, 'jmaker_uuid');
    }

    /**
     * Return all SynthesisShare
     *
     * @return HasMany
     */
    public function events()
    {
        return $this->hasMany(JmakerEvent::class, 'jmaker_uuid');
    }

    /**
     * Return the newt workshop notification
     * @return HasOne
     */
    public function jmakerNextNotificationWorkshop() {
        return $this->hasOne(JmakerNextNotificationWorkshop::class, 'jmaker_uuid');
    }

    /**
     * Return the newt meeting notification
     * @return HasOne
     */
    public function jmakerNextNotificationMeeting() {
        return $this->hasOne(JmakerNextNotificationMeeting::class, 'jmaker_uuid');
    }

}