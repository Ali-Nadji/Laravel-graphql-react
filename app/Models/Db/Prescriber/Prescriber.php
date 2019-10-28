<?php namespace Models\Db\Prescriber;


use App\Mail\Partner\PrescriberResetPassword;
use App\Services\Mail\MailService;
use Carbon\Carbon;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Infrastructure\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;
use Models\Db\Clients\Client;
use Models\Db\Clients\ClientCampaign;
use Models\Db\Jmaker\SynthesisShare;

/**
 * Class Prescriber
 * @property string $uuid
 * @property string $client_uuid
 * @property string $contract_uuid
 * @property string $user_interface_id
 * @property string $interface_rid
 * @property string $media_uuid
 * @property string $name
 * @property string $lastname
 * @property string $firstname
 * @property string $email
 * @property Carbon $onboarded_at
 * @property Carbon $last_dashboard_at
 * @property bool $onboarded
 * @property json $phones
 * @property text $parameters
 * @property string $password
 * @property string $api_token
 * @property string $remember_token
 * @property string $language
 * @property Carbon $loggedin_at
 * @property Carbon $deleted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $description
 * @package Models\Db\Prescriber
 */
class Prescriber extends Model implements \Illuminate\Contracts\Auth\Authenticatable, \Illuminate\Contracts\Auth\CanResetPassword
{
    use Authenticatable;
    use SoftDeletes;
    use Notifiable;
    use CanResetPassword;
    use HasApiTokens;

    /**
     * Desactivate gard
     *
     * @var bool
     */
    protected static $unguarded = true;

    public $incrementing = false;

    public $keyType = 'string';
    protected $primaryKey = 'uuid';
    protected $table = 'prescriber';
    protected $hidden = [
        "password",
        "remember_token",
        "interface_rid",
        "phones",
        "parameters",
        "api_token"
    ];

    protected $casts = [
        "uuid" => 'string',
        "phones" => "json"
    ];

    protected $dates = [
        "loggedin_at",
        "deleted_at",
        "created_at",
        "updated_at"
    ];

    /**
     * Permission liée a l'utilisateur
     *
     * @return BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(PrescriberPermission::class, 'prescriber_permission_prescriber', 'prescriber_uuid', 'prescriber_permission_id');
    }


    /**
     * Return TRUE has the permission
     *
     * @return bool
     * @param ability
     */
    public function can($ability)
    {
        return $this->permissions->where('id', $ability)->isNotEmpty();
    }

    /**
     * Send the password reset notification.
     *
     * @return void
     * @param token
     */
    public function sendPasswordResetNotification($token)
    {
        MailService::pushInDB(PrescriberResetPassword::class, $this->email, route('partner.forgetPassword', $token), $this->uuid);
    }

    /**
     * Invitation
     *
     * @return HasOne
     */
    public function prescriberInvitation()
    {
        return $this->hasOne(PrescriberInvitation::class, 'prescriber_uuid', 'uuid');
    }

    /**
     * Synthesis
     *
     * @return HasMany
     */
    public function synthesisShare()
    {
        return $this->hasOne(SynthesisShare::class, 'prescriber_uuid', 'uuid');
    }

    /**
     * Return current Client
     * @return HasOne
     */
    public function client()
    {
        return $this->hasOne(Client::class,'uuid','client_uuid');
    }

    /**
     * Metric Jmaker
     *
     * @return HasOne
     */
    public function prescriberMetricJmaker()
    {
        return $this->hasOne(PrescriberMetricJmaker::class, 'prescriber_uuid', 'uuid');
    }

    /**
     * Metric Jmaker
     *
     * @return hasMany
     */
    public function prescriberMetricJmakerHistory()
    {
        return $this->hasMany(PrescriberMetricJmakerHistory::class, 'prescriber_uuid', 'uuid');
    }


    /**
     * Client campaigns
     * @return BelongsToMany
     */
    public function campaigns()
    {
        return $this->belongsToMany(ClientCampaign::class, 'client_campaign_x_prescriber', 'prescriber_uuid', 'client_campaign_uuid');
    }

    /**
     * PrescriberTemplate
     *
     * @return HasMany
     */
    public function prescriberTemplate()
    {
        return $this->hasMany(PrescriberTemplate::class, 'prescriber_uuid', 'uuid');
    }

}