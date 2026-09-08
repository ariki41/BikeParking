<?php

namespace App\Exceptions;

use RuntimeException;

class YolpApiException extends RuntimeException
{
    public const CATEGORY_TIMEOUT = 'timeout';

    public const CATEGORY_CONNECTION = 'connection';

    public const CATEGORY_RESPONSE = 'response';

    public function __construct(
        private readonly string $category,
        ?\Throwable $previous = null,
    ) {
        parent::__construct('YOLP API request failed.', previous: $previous);
    }

    public function category(): string
    {
        return $this->category;
    }

    public function userMessage(): string
    {
        return '位置情報サービスに接続できません。時間をおいて、もう一度お試しください。';
    }
}
