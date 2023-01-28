<?php

namespace App\Models\Articles;

use App\User;
use App\Models\Articles\Article;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoringHistory extends Model
{
    use HasFactory;

    protected $appends = [
        'created_at_formatted',
        'path',

    ];

    protected $fillable = [
        'user_id',
    ];

    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at->format('d.m.Y H:i');
    }

    public function getPathAttribute()
    {
        return $this->path('show');
    }

    protected function path(string $action = '') : string
    {
        return ($this->id ? route($this->baseRoute() . '.' . $action, [
            'storing_history' => $this->id
        ]) : '');
    }

    protected function baseRoute() : string
    {
        return 'article.storing_history';
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'storing_history_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
