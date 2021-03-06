<?php

namespace App\Models\Rules;

use App\Models\Articles\Article;
use App\Models\Cards\Card;
use App\Models\Expansions\Expansion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class Rule extends Model
{
    const DECIMALS = 6;
    const PRICE_APPLY_IN_CENTS = 100;

    protected $appends = [
        'base_price_formatted',
        'editPath',
        'isDeletable',
        'min_price_common_formatted',
        'min_price_land_formatted',
        'min_price_masterpiece_formatted',
        'min_price_mythic_formatted',
        'min_price_rare_formatted',
        'min_price_special_formatted',
        'min_price_time_shifted_formatted',
        'min_price_tip_card_formatted',
        'min_price_token_formatted',
        'min_price_uncommon_formatted',
        'multiplier_formatted',
        'path',
        'price_above_formatted',
        'price_below_formatted',
    ];

    protected $guarded = [];

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
            if (! $model->user_id) {
                $model->user_id = auth()->user()->id;
            }

            if (! $model->base_price) {
                $model->base_price = 'price_trend';
            }

            $model->game_id = 1;
            $model->order_column = self::nextOrderColumn($model->user_id);

            return true;
        });

        static::deleting(function($model)
        {
            $model->articles()->update([
                'rule_id' => null,
                'price_rule' => null,
            ]);
        });
    }

    public static function findForCard(int $userId, Card $card) : self
    {
        $rule = self::where('active', true)
            ->where(function (Builder $query) use ($card) {
                $query->whereNull('expansion_id')
                    ->orWhere('expansion_id', $card->expansion_id);
            })
            ->where(function (Builder $query) use ($card) {
                $query->whereNull('rarity')
                    ->orWhere('rarity', $card->rarity);
            })
            ->orderBy('order_column', 'ASC')
            ->firstOrNew([
                'user_id' => $userId,
            ], []);

        return $rule;
    }

    public static function findForArticle(int $userId, array $attributes) : self
    {
        return self::where('active', true)
            ->where(function (Builder $query) use ($attributes) {
                $query->whereNull('expansion_id')
                    ->orWhere('expansion_id', $attributes['expansion_id']);
            })
            ->where(function (Builder $query) use ($attributes) {
                $query->whereNull('rarity')
                    ->orWhere('rarity', $attributes['rarity']);
            })
            ->where(function (Builder $query) use ($attributes) {
                $query->whereNull('condition')
                    ->orWhere('condition', $attributes['condition']);
            })
            ->where('is_foil', $attributes['is_foil'])
            ->where('is_altered', $attributes['is_altered'])
            ->where('is_signed', $attributes['is_signed'])
            ->where('is_playset', $attributes['is_playset'])
            ->orderBy('order_column', 'ASC')
            ->firstOrNew([
                'user_id' => $userId,
            ], []);
    }

    public function price(Card $card) : float
    {
        $base_price = $this->base_price;

        return (is_null($this->id) ? 0 : ($card->$base_price * $this->multiplier));
    }

    public static function nextOrderColumn(int $userId)
    {
        return self::where('user_id', $userId)->max('order_column') + 1;
    }

    public function apply(bool $sync = false) : int
    {
        // TODO: Update Article with rule price
        $query = Article::join('cards', 'cards.id', '=', 'articles.card_id')
            ->where('articles.user_id', $this->user_id)
            ->whereNull('articles.rule_id')
            ->whereNull('sold_at')
            ->where('articles.unit_price', '>=', $this->price_above)
            ->where('articles.unit_price', '<=', $this->price_below)
            ->isFoil($this->is_foil)
            ->isSigned($this->is_signed)
            ->isAltered($this->is_altered)
            ->isPlayset($this->is_playset);

        if ($this->expansion_id) {
            $query->where('cards.expansion_id', $this->expansion_id);
        }

        if ($this->rarity) {
            $query->where('cards.rarity', $this->rarity);
        }

        $attributes = [
            'articles.rule_id' => $this->id,
            'articles.rule_applied_at' => now(),
            'articles.price_rule' => DB::raw('ROUND(cards.' . $this->base_price . ' * ' . $this->multiplier . ', 2)'),
            // 'articles.rule_difference' => DB::raw('(cards.' . $this->base_price . ' * ' . $this->multiplier . ') - articles.unit_price'),
            // 'articles.rule_difference_percent' => DB::raw('((cards.' . $this->base_price . ' * ' . $this->multiplier . ') - articles.unit_price) / articles.unit_price * 100'),
        ];

        if ($sync) {
            $attributes['unit_price'] = DB::raw('ROUND(cards.' . $this->base_price . ' * ' . $this->multiplier . ', 2)');
        }

        return $query->update($attributes);
    }

    public static function reset(int $userId)
    {
        Article::where('user_id', $userId)
            ->update([
                'rule_id' => null,
                'price_rule' => null,
                'rule_applied_at' => null,
                'rule_difference' => 0,
                'rule_difference_percent' => 0,
            ]);
    }

    public function activate() : self {
        $this->active = true;

        return $this;
    }

    public function deactivate() : self {
        $this->active = false;

        return $this;
    }

    public function isActivated() : bool
    {
        return $this->active;
    }

    public function isDeletable() : bool
    {
        return true;
    }

    public function getArticleStatsAttribute()
    {
        $stats = DB::table('articles')
            ->select(DB::raw('COUNT(id) AS count'), DB::raw('SUM(unit_price) AS price'), DB::raw('SUM(price_rule) AS price_rule'))
            ->where('user_id', $this->user_id)
            ->where('rule_id', $this->id)
            ->whereNull('sold_at')
            ->first();

        $stats->count_formatted = number_format($stats->count, 0, '', '.');
        $stats->price_formatted = number_format($stats->price, 2, ',', '.');
        $stats->price_rule_formatted = number_format($stats->price_rule, 2, ',', '.');
        $stats->difference = $stats->price_rule - $stats->price;
        $stats->difference_percent = ($stats->price ? $stats->difference / $stats->price : 0);
        $stats->difference_percent_formatted = number_format(($stats->difference_percent * 100), 0, ',', '.');
        $stats->difference_icon = ($stats->difference == 0 ? '' : ($stats->difference > 0 ? '<i class="fas fa-arrow-up text-success"></i>' : '<i class="fas fa-arrow-down text-danger"></i>'));

        return $stats;
    }

    public function getBasePriceFormattedAttribute()
    {
        return Arr::get(Article::BASE_PRICES, $this->base_price, 'Preis');
    }

    public function setMultiplierFormattedAttribute($value)
    {
        $this->multiplier = number_format(str_replace(',', '.', $value), self::DECIMALS, '.', '');
        Arr::forget($this->attributes, 'multiplier_formatted');
    }

    public function getMultiplierFormattedAttribute()
    {
        return number_format($this->multiplier, 2, ',', '');
    }

    public function setPriceAboveFormattedAttribute($value)
    {
        $this->price_above = number_format(str_replace(',', '.', $value), self::DECIMALS, '.', '');
        Arr::forget($this->attributes, 'price_above_formatted');
    }

    public function getPriceAboveFormattedAttribute()
    {
        return number_format($this->price_above, 2, ',', '');
    }

    public function setPriceBelowFormattedAttribute($value)
    {
        $this->price_below = number_format(str_replace(',', '.', $value), self::DECIMALS, '.', '');
        Arr::forget($this->attributes, 'price_below_formatted');
    }

    public function getPriceBelowFormattedAttribute()
    {
        return number_format($this->price_below, 2, ',', '');
    }

    public function setMinPriceMasterpieceFormattedAttribute($value)
    {
        $this->min_price_masterpiece = number_format(str_replace(',', '.', $value), self::DECIMALS, '.', '');
        Arr::forget($this->attributes, 'min_price_masterpiece_formatted');
    }

    public function getMinPriceMasterpieceFormattedAttribute()
    {
        return number_format($this->min_price_masterpiece, 2, ',', '');
    }

    public function setMinPriceMythicFormattedAttribute($value)
    {
        $this->min_price_mythic = number_format(str_replace(',', '.', $value), self::DECIMALS, '.', '');
        Arr::forget($this->attributes, 'min_price_mythic_formatted');
    }

    public function getMinPriceMythicFormattedAttribute()
    {
        return number_format($this->min_price_mythic, 2, ',', '');
    }

    public function setMinPriceRareFormattedAttribute($value)
    {
        $this->min_price_rare = number_format(str_replace(',', '.', $value), self::DECIMALS, '.', '');
        Arr::forget($this->attributes, 'min_price_rare_formatted');
    }

    public function getMinPriceRareFormattedAttribute()
    {
        return number_format($this->min_price_rare, 2, ',', '');
    }

    public function setMinPriceSpecialFormattedAttribute($value)
    {
        $this->min_price_special = number_format(str_replace(',', '.', $value), self::DECIMALS, '.', '');
        Arr::forget($this->attributes, 'min_price_special_formatted');
    }

    public function getMinPriceSpecialFormattedAttribute()
    {
        return number_format($this->min_price_special, 2, ',', '');
    }

    public function setMinPriceTimeShiftedFormattedAttribute($value)
    {
        $this->min_price_time_shifted = number_format(str_replace(',', '.', $value), self::DECIMALS, '.', '');
        Arr::forget($this->attributes, 'min_price_time_shifted_formatted');
    }

    public function getMinPriceTimeShiftedFormattedAttribute()
    {
        return number_format($this->min_price_time_shifted, 2, ',', '');
    }

    public function setMinPriceUncommonFormattedAttribute($value)
    {
        $this->min_price_uncommon = number_format(str_replace(',', '.', $value), self::DECIMALS, '.', '');
        Arr::forget($this->attributes, 'min_price_uncommon_formatted');
    }

    public function getMinPriceUncommonFormattedAttribute()
    {
        return number_format($this->min_price_uncommon, 2, ',', '');
    }

    public function setMinPriceCommonFormattedAttribute($value)
    {
        $this->min_price_common = number_format(str_replace(',', '.', $value), self::DECIMALS, '.', '');
        Arr::forget($this->attributes, 'min_price_common_formatted');
    }

    public function getMinPriceCommonFormattedAttribute()
    {
        return number_format($this->min_price_common, 2, ',', '');
    }

    public function setMinPriceLandFormattedAttribute($value)
    {
        $this->min_price_land = number_format(str_replace(',', '.', $value), self::DECIMALS, '.', '');
        Arr::forget($this->attributes, 'min_price_land_formatted');
    }

    public function getMinPriceLandFormattedAttribute()
    {
        return number_format($this->min_price_land, 2, ',', '');
    }

    public function setMinPriceTokenFormattedAttribute($value)
    {
        $this->min_price_token = number_format(str_replace(',', '.', $value), self::DECIMALS, '.', '');
        Arr::forget($this->attributes, 'min_price_token_formatted');
    }

    public function getMinPriceTokenFormattedAttribute()
    {
        return number_format($this->min_price_token, 2, ',', '');
    }

    public function setMinPriceTipCardFormattedAttribute($value)
    {
        $this->min_price_tip_card = number_format(str_replace(',', '.', $value), self::DECIMALS, '.', '');
        Arr::forget($this->attributes, 'min_price_tip_card_formatted');
    }

    public function getMinPriceTipCardFormattedAttribute()
    {
        return number_format($this->min_price_tip_card, 2, ',', '');
    }

    public function getPathAttribute()
    {
        return $this->path('show');
    }

    public function getEditPathAttribute()
    {
        return $this->path('edit');
    }

    protected function path(string $action = '') : string
    {
        return ($this->id ? route($this->baseRoute() . '.' . $action, ['rule' => $this->id]) : '');
    }

    protected function baseRoute() : string
    {
        return 'rule';
    }


    public function getIsDeletableAttribute()
    {
        return $this->isDeletable();
    }

    public function articles() : HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function expansion() : BelongsTo
    {
        return $this->belongsTo(Expansion::class);
    }
}
