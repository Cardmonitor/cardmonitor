<?php

namespace App;

use App\Models\Apis\Api;
use App\Models\Articles\Article;
use App\Models\Items\Item;
use App\Models\Orders\Order;
use App\Models\Rules\Rule;
use App\Models\Storages\Storage;
use App\Models\Users\Balance;
use App\Support\Users\CardmarketApi;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class User extends Authenticatable
{
    use Notifiable;

    protected $appends = [
        'balance_in_euro_formatted',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'api_token',
        'balance_in_cents',
        'credits',
        'email',
        'is_applying_rules',
        'is_syncing_articles',
        'is_syncing_orders',
        'locale',
        'name',
        'password',
        'prepared_message',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The booting method of the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function($model)
        {
            $model->prepared_message = "Hallo #BUYER_FIRSTNAME#,\r\nvielen Dank für deine Bestellung\r\n\r\n#PROBLEMS#\r\n\r\n#IMAGES#\r\n\r\nIch verschicke sie heute Nachmittag\r\n\r\nViele Grüße\r\n#SELLER_FIRSTNAME#";
            $model->locale = 'de';
        });

        static::created(function($model)
        {
            $model->setup();

            return true;
        });
    }

    public function canPay(int $amount_in_cents) : bool
    {
        if ($this->id == 1) {
            return true;
        }

        return ($this->balance_in_cents >= $amount_in_cents);
    }

    public function deposit(int $amount_in_cents)
    {

    }

    public function reset()
    {
        $this->update([
            'is_applying_rules' => false,
            'is_syncing_articles' => false,
            'is_syncing_orders' => false,
        ]);
    }

    public function withdraw(int $amount_in_cents, string $reason)
    {
        if ($this->id == 1) {
            $amount_in_cents = 0;
        }

        if (! $this->canPay($amount_in_cents)) {
            return false;
        }

        Balance::create([
            'user_id' => $this->id,
            'amount_in_cents' => $amount_in_cents,
            'type' => 'debit',
            'multiplier' => -1,
            'received_at' => now(),
            'charge_reason' => $reason,
        ]);
    }

    public function getCardmarketApiAttribute() : CardmarketApi
    {
        return new CardmarketApi($this->api);
    }

    public function getBalanceInEuroFormattedAttribute()
    {
        return number_format(($this->balance_in_cents / 100), 2, ',', '.');
    }

    public function setup() : void {
        $this->api()->create();
        Item::setup($this);

        Mail::to(config('app.mail'))
            ->queue(new \App\Mail\Users\Registered($this));
    }

    public function api() : HasOne
    {
        return $this->hasOne(Api::class, 'user_id');
    }

    // public function apis() : HasMany
    // {
    //     return $this->hasMany(Api::class);
    // }

    public function articles() : HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function balances() : HasMany
    {
        return $this->hasMany(Balance::class);
    }

    public function items() : HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function orders() : HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function rules() : HasMany
    {
        return $this->hasMany(Rule::class);
    }

    public function storages() : HasMany
    {
        return $this->hasMany(Storage::class);
    }
}
