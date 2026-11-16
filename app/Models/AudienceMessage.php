<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AudienceMessage extends Model
{
    protected $fillable = ['user_id', 'title', 'body', 'filters', 'status', 'review_token', 'excluded_count', 'queued_at'];

    protected function casts(): array
    {
        return ['filters' => 'array', 'queued_at' => 'datetime'];
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(MessageRecipient::class);
    }
}
