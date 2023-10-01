<?php

namespace App;

use App\Auth\Provider;
use App\Models\Apis\Api;
use App\Models\Items\Item;
use App\Models\Rules\Rule;
use App\Models\Orders\Order;
use App\Models\Articles\Article;
use App\Models\Storages\Storage;
use Kalnoy\Nestedset\Collection;
use App\Support\Users\CardmarketApi;
use Illuminate\Support\Facades\Mail;
use App\Models\Articles\StoringHistory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    protected $appends = [
        //
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'api_token',
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

    public function reset()
    {
        $this->update([
            'is_applying_rules' => false,
            'is_syncing_articles' => false,
            'is_syncing_orders' => false,
        ]);
    }

    public function getCardmarketApiAttribute() : CardmarketApi
    {
        return new CardmarketApi($this->api);
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

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

   public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function storingHistories(): HasMany
    {
        return $this->hasMany(StoringHistory::class, 'user_id');
    }

    public function orders() : HasMany
    {
        return $this->hasMany(Order::class)->where('is_purchase', false);
    }

    public function purchases() : HasMany
    {
        return $this->hasMany(Order::class)->where('is_purchase', true);
    }

    public function providers() : HasMany
    {
        return $this->hasMany(Provider::class, 'user_id');
    }

    public function dropbox() : HasOne
    {
        return $this->hasOne(Provider::class, 'user_id')->where('provider_type', 'dropbox')->take(1);
    }

    public function rules() : HasMany
    {
        return $this->hasMany(Rule::class);
    }

    public function storages() : HasMany
    {
        return $this->hasMany(Storage::class);
    }

    public function storagesForFilter(): Collection
    {
        return $this->storages()->withDepth()
            ->defaultOrder()
            ->isUploaded(false)
            ->get()->each(function ($storage, $key) {
                $storage->sort = $key;
            });
    }
}
