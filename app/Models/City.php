<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    protected $fillable = ['prefecture_id', 'name', 'name_kana'];

    public function prefecture(): BelongsTo
    {
        return $this->belongsTo(Prefecture::class);
    }
}
