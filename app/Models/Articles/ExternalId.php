<?php

namespace App\Models\Articles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalId extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'user_id',
        'external_type',
        'external_id',
        'external_updated_at',
        'imported_at',
        'exported_at',
        'sync_status',
        'sync_message',
    ];

    protected $dates = [
        'imported_at',
        'exported_at',
        'external_updated_at',
    ];

    protected $table = 'articles_external_ids';

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }
}
