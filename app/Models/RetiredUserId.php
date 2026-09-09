<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetiredUserId extends Model
{
    protected $fillable = [
        'user_id_hash',
    ];

    public static function hashFor(string $userId): string
    {
        // 退会済みIDの再利用を防ぎつつ、元のユーザーIDは保存しない。
        return hash_hmac('sha256', mb_strtolower($userId), (string) config('app.key'));
    }
}
