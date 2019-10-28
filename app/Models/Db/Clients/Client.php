<?php namespace Models\Db\Clients;

use Carbon\Carbon;
use FrenchFrogs\App\Models\Db\Reference;
use FrenchFrogs\Laravel\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Models\Db\Deprecated\Partner;
use Models\Db\Jmaker\Jmaker;
use Models\Db\Jmaker\JmakerInvitation;
use Models\Db\json;
use Models\Db\Languages\Languages;
use Models\Db\Operator\Operator;
use Models\Db\Prescriber\Prescriber;
use Models\Db\text;
use Ref;


/**
 * Class Client
 * @property string $uuid
 * @property string $client_status_rid
 * @property string $client_type_rid
 * @property string $operator_uuid
 * @property string $language_group_uuid
 * @property string $name
 * @property mixed $addresses
 * @property string $legal_agent
 * @property string $address
 * @property string $zipcode
 * @property string $city
 * @property string $siret
 * @property string $comment
 * @property mixed $adaptation
 * @property bool $chatEnabled
 * @property bool $whiteListEnabled
 * @property bool $enableMultipleInvitation
 * @property bool $enableB2BasB2C
 * @property bool $enableEditMail
 * @property bool $meetingDateMandatory
 * @property bool $remember_me
 * @property string $client_status
 * @property Carbon $active_at
 * @property Carbon $prospect_at
 * @property Carbon $client_at
 * @property Carbon $expired_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @package Models\Db
 */
class Client extends Model
{
	use SoftDeletes;

    /**
     * Disable auto incrementing
     * @var bool
     */
    public $incrementing = false;

	protected $table = 'client';
	
	protected $primaryKey = 'uuid';

	protected $casts = [
	    "uuid" => "string",
        "operator_uuid" => "string",
        "language_group_uuid" => "string",
        "addresses" => "json",
        "adaptation" => "json",
        "chatEnabled" => "boolean",
        "whiteListEnabled" => "boolean",
        "meetingDateMandatory" => "boolean",
        "remember_me" => "boolean",
        "enableEditMail" => "boolean",
	];
	
	
	protected $dates = [
	    "active_at",
	    "prospect_at",
	    "client_at",
	    "expired_at",
	    "created_at",
	    "updated_at",
	    "deleted_at"
	];


    /**
     * Has many Languages
     * @return BelongsToMany
     */
    public function languages()
    {
        return $this->belongsToMany(Languages::class, 'client_language', 'client_uuid', 'language_id');
    }

    /**
     * ClientCustomization
     * @return HasMany
     */
    public function customizations()
    {
        return $this->hasMany(ClientCustomization::class, "client_uuid", "uuid");
    }

	/**
     *
     *
     * @return HasMany
     */
    public function contacts()
    {
        return $this->hasMany(ClientContact::class, "client_uuid", "uuid");
	}

    /**
     * Return all client's contracts
     * @return HasMany
     */
    public function contracts()
    {
        return $this->hasMany(ClientContract::class, "client_uuid", "uuid");
    }

	/**
     *
     *
     * @return HasMany
     */
    public function invoices()
    {
        return $this->hasMany(ClientInvoice::class, "client_uuid", "uuid");
	}
	
	
	/**
	 * Status
     *
     * @return HasOne
	 */
	public function status()
	{
		return $this->hasOne(Reference::class, 'reference_id', 'client_status_rid');
	}


    /**
     *
     *
     * @return BelongsTo
     */
    public function type()
    {
        return $this->belongsTo(Reference::class, "client_type_rid", "reference_id");
    }


    /**
     * Who is the Jobmaker sales
     *
     * @return BelongsTo
     */
    public function operator()
    {
        return $this->belongsTo(Operator::class, "operator_uuid", "uuid");
	}
	
	
	/**
	 * Return tru if client i s a suspect
	 *
	 */
	public function isSuspect()
	{
        return $this->client_status_rid == Ref::CLIENT_STATUS_SUSPECT;
	}
	
	
	/**
	 * Return tru if client i s a suspect
	 *
	 */
	public function isActive()
	{
        return $this->client_status_rid == Ref::CLIENT_STATUS_ACTIVE;
	}
	
	
	/**
	 * Return tru if client i s a suspect
	 *
	 */
	public function isProspect()
	{
        return $this->client_status_rid == Ref::CLIENT_STATUS_PROSPECT;
	}
	
	
	/**
	 * Return tru if client i s a suspect
	 *
	 */
	public function isClient()
	{
        return $this->client_status_rid == Ref::CLIENT_STATUS_CLIENT;
	}
	
	
	/**
	 * Return all prescriber linked with this client
	 */
	public function prescribers()
	{
        return $this->hasMany(Prescriber::class, "client_uuid", "uuid");
	}

    /**
     * Return all jmakers linked with this client
     */
    public function jmakers()
    {
        return $this->hasMany(Jmaker::class, "client_uuid", "uuid");
    }

    /**
     *
     *
     * @return BelongsTo
     */
	public function partner()
    {
        return $this->belongsTo(Partner::class, 'uuid', 'client_uuid');
    }


    /**
     *
     *
     * @return HasMany
     */
    public function campaigns()
    {
        return $this->hasMany(ClientCampaign::class, "client_uuid", "uuid");
    }


    /**
     *
     *
     * @return HasMany
     */
    public function jmakerInvitations()
    {
        return $this->hasMany(JmakerInvitation::class, "client_uuid", "uuid");
    }


    /**
     *
     *
     * @return HasMany
     */
    public function partners()
    {
        return $this->hasMany(Partner::class, "client_uuid", "uuid");
    }

    /**
     * Metric Jmaker
     *
     * @return HasOne
     */
    public function clientMetricJmaker()
    {
        return $this->hasOne(ClientMetricJmaker::class, 'client_uuid', 'uuid');
    }

    /**
     * Metric Jmaker
     *
     * @return hasMany
     */
    public function clientMetricJmakerHistory()
    {
        return $this->hasMany(ClientMetricJmakerHistory::class, 'client_uuid', 'uuid');
    }

    /**
     * Return all client's templates
     * @return HasMany
     */
    public function templates()
    {
        return $this->hasMany(ClientTemplate::class, "client_uuid", "uuid");
    }
}