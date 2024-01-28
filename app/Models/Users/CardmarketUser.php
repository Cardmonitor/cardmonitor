<?php

namespace App\Models\Users;

use Carbon\Carbon;
use App\Support\Locale;
use Illuminate\Support\Arr;
use App\Enums\ExternalIds\ExernalType;
use Illuminate\Database\Eloquent\Model;

class CardmarketUser extends Model
{
    protected $appends = [
        'lastname',
    ];

    protected $guarded = [];

    public static function updateOrCreateFromCardmarket(array $cardmarket_user) : self
    {
        $values = [
            'cardmarket_user_id' => $cardmarket_user['idUser'],
            'username' => $cardmarket_user['username'],
            'registered_at' => new Carbon($cardmarket_user['registrationDate']),

            'is_commercial' => $cardmarket_user['isCommercial'],
            'is_seller' => $cardmarket_user['isSeller'],

            'firstname' => $cardmarket_user['name']['firstName'],
            'name' => $cardmarket_user['address']['name'],
            'extra' => $cardmarket_user['address']['extra'] ?? '',
            'street' => $cardmarket_user['address']['street'],
            'zip' => $cardmarket_user['address']['zip'],
            'city' => $cardmarket_user['address']['city'],
            'country' => $cardmarket_user['address']['country'] ?? '',
            'phone' => $cardmarket_user['phone'] ?? '',
            'email' => $cardmarket_user['email'],

            'vat' => $cardmarket_user['vat'] ?? '',
            'legalinformation' => $cardmarket_user['legalInformation'] ?? '',

            'risk_group' => $cardmarket_user['riskGroup'],
            'loss_percentage' => $cardmarket_user['lossPercentage'],

            'unsent_shipments' => $cardmarket_user['unsentShipments'],
            'reputation' => $cardmarket_user['reputation'],
            'ships_fast' => $cardmarket_user['shipsFast'],
            'sell_count' => $cardmarket_user['sellCount'],
            'sold_items' => $cardmarket_user['soldItems'],
            'avg_shipping_time' => $cardmarket_user['avgShippingTime'],
            'is_on_vacation' => $cardmarket_user['onVacation'],
        ];

        return self::updateOrCreate([
            'source_slug' => ExernalType::CARDMARKET->value,
            'source_id' => $cardmarket_user['idUser'],
        ], $values);
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
