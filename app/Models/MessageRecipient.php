<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageRecipient extends Model
{
    protected $fillable = ['interest_id', 'name', 'phone', 'status', 'provider_id', 'error'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(AudienceMessage::class, 'audience_message_id');
    }
}
