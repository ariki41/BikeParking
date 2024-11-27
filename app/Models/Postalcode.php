<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Postalcode extends Model
{
    protected $fillable = ['postalcode', 'city_id', 'name', 'name_kana'];
}
