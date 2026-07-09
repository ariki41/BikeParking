<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Postalcode extends Model
{
    protected $fillable = ['postalcode', 'city_id', 'name', 'name_kana'];

    public function scopeGetAddress($query, $postalcode)
    {
        return $query->where('postalcode', $postalcode)
            ->join('cities', 'cities.id', '=', 'postalcodes.city_id')
            ->join('prefectures', 'cities.prefecture_id', '=', 'prefectures.id')
            ->select('prefectures.name as prefecture', 'cities.name as city', 'postalcodes.name as town');
    }

    public function scopeGetPostalcodeId($query, $postalcode)
    {
        return $query->where('postalcode', $postalcode)
            ->select('id');
    }

    public function scopeGetPostalcode($query, $id)
    {
        return $query->findOrFail($id);
    }
}
