<?php

namespace App\Models\Storages;

use App\Models\Articles\Article;
use App\Models\Storages\Content;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class Storage extends Model
{
    use \Kalnoy\Nestedset\NodeTrait;

    protected $appends = [
        'editPath',
        'isDeletable',
        'path',
        'indentedName',
    ];

    protected $guarded = [
        'id',
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
            if (! $model->user_id) {
                $model->user_id = auth()->user()->id;
            }

            $model->setFullName();

            return true;
        });

        static::updating(function($model)
        {
            if (Arr::has($model->attributes, 'name')) {
                $model->setFullName();
            }

            return true;
        });

        static::updated(function($model)
        {
            self::fixTree($model);
            $model->setChildrenFullName();

            return true;
        });
    }

    public static function reset(int $userId)
    {
        Article::where('user_id', $userId)
            ->update([
                'storage_id' => null,
            ]);
    }

    public function isDeletable() : bool
    {
        return (! $this->articles()->exists() && ! $this->descendants()->exists() && ! $this->contents()->exists());
    }

    public function getArticleStatsAttribute()
    {
        $ids = $this->descendants()->pluck('id');
        $ids[] = $this->id;

        $stats = DB::table('articles')
            ->select(DB::raw('COUNT(id) AS count'), DB::raw('SUM(unit_price) AS price'))
            ->where('user_id', $this->user_id)
            ->whereIn('storage_id',$ids)
            ->whereNull('sold_at')
            ->first();

        $stats->count_formatted = number_format($stats->count, 0, '', '.');
        $stats->price_formatted = number_format($stats->price, 2, ',', '.');

        return $stats;
    }

    public function getIndentedNameAttribute()
    {
        return str_repeat('&nbsp;', $this->depth * 4) . $this->name;
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
        return ($this->id ? route($this->baseRoute() . '.' . $action, ['storage' => $this->id]) : '');
    }

    protected function baseRoute() : string
    {
        return 'storages';
    }

    public function getIsDeletableAttribute()
    {
        return $this->isDeletable();
    }

    public function setDescendantsFullName()
    {
        foreach ($this->descendants as $key => $descendant) {
            $descendant->setFullName()
                ->save();
        }
    }

    public function setChildrenFullName()
    {
        foreach ($this->children as $key => $child) {
            $child->setFullName()
                ->save();
        }
    }

    public function setFullName() : self
    {
        $prefix = join('/', $this->ancestors()->defaultOrder()->pluck('name')->toArray());
        $this->attributes['full_name'] = ($prefix ? $prefix . '/' : '') . $this->attributes['name'];

        return $this;
    }

    public function articles() : HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function contents() : HasMany
    {
        return $this->hasMany(Content::class);
    }

}
