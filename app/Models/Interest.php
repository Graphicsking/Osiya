<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interest extends Model
{
    public const OPTIONS = ['Volunteer', 'Share an idea', 'Community conversations', 'Campaign updates'];

    protected $fillable = ['submission_id', 'name', 'phone', 'interest', 'consented_at'];

    protected function casts(): array
    {
        return ['consented_at' => 'datetime'];
    }
}
