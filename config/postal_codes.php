<?php

return [
    'download_url' => env(
        'JAPAN_POST_POSTAL_CODE_URL',
        'https://www.post.japanpost.jp/service/search/zipcode/download/utf/zip/utf_ken_all.zip',
    ),
    'csv_filename' => 'utf_ken_all.csv',
    'minimum_records' => 100_000,
    'minimum_prefectures' => 47,
    'maximum_archive_bytes' => 20 * 1024 * 1024,
    'maximum_csv_bytes' => 64 * 1024 * 1024,
    'import_batch_size' => 1_000,
    'lock_seconds' => 3_600,
    'http' => [
        'timeout_seconds' => 60,
        'retry_times' => 3,
        'retry_sleep_milliseconds' => 1_000,
    ],
];
