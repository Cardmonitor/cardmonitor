<?php

namespace App\Models\Users;

use App\Support\Locale;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CardmarketUser extends Model
{
    protected $appends = [
        'lastname',
    ];

    protected $guarded = [];

    public $incrementing = false;

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
            $model->id = $model->cardmarket_user_id;

            return true;
        });
    }

    public static function updateOrCreateFromCardmarket(array $cardmarketUser) : self
    {
        $values = [
            'cardmarket_user_id' => $cardmarketUser['idUser'],
            'username' => $cardmarketUser['username'],
            'registered_at' => new Carbon($cardmarketUser['registrationDate']),

            'is_commercial' => $cardmarketUser['isCommercial'],
            'is_seller' => $cardmarketUser['isSeller'],

            'firstname' => $cardmarketUser['name']['firstName'],
            'name' => $cardmarketUser['address']['name'],
            'extra' => $cardmarketUser['address']['extra'] ?? '',
            'street' => $cardmarketUser['address']['street'],
            'zip' => $cardmarketUser['address']['zip'],
            'city' => $cardmarketUser['address']['city'],
            'country' => $cardmarketUser['address']['country'] ?? '',
            'phone' => $cardmarketUser['phone'] ?? '',
            'email' => $cardmarketUser['email'],

            'vat' => $cardmarketUser['vat'] ?? '',
            'legalinformation' => $cardmarketUser['legalInformation'] ?? '',

            'risk_group' => $cardmarketUser['riskGroup'],
            'loss_percentage' => $cardmarketUser['lossPercentage'],

            'unsent_shipments' => $cardmarketUser['unsentShipments'],
            'reputation' => $cardmarketUser['reputation'],
            'ships_fast' => $cardmarketUser['shipsFast'],
            'sell_count' => $cardmarketUser['sellCount'],
            'sold_items' => $cardmarketUser['soldItems'],
            'avg_shipping_time' => $cardmarketUser['avgShippingTime'],
            'is_on_vacation' => $cardmarketUser['onVacation'],
        ];

        return self::updateOrCreate(['cardmarket_user_id' => $cardmarketUser['idUser']], $values);
    }

    public function getCountryAttribute() : string
    {
        return Locale::iso3166($this->attributes['country']);
    }

    public function getCountryNameAttribute() : string
    {
        return Arr::get(config('app.iso3166_names'), $this->country, $this->country);
    }

    public function getLastnameAttribute() : string
    {
        return trim(str_replace($this->firstname, '', $this->name));
    }
}
