<?php

return [
    'confirmation' => [
        'lifetime_hours' => 24,
    ],
    // 表記ゆれを正規化した名称が類似する施設だけを、この距離内で重複候補として知らせる。
    'duplicate_candidate_distance_meters' => 200,
    'search_map' => [
        'default_zoom' => 15,
        'min_zoom' => 0,
        'max_zoom' => 18,
    ],
    'sample_data' => [
        'postalcode_csv_path' => env('PARKING_SPOT_SAMPLE_POSTALCODE_CSV', storage_path('app/private/postalcode.csv')),
        'parking_spot_count' => 10_000,
        'insert_chunk_size' => 500,
        'location_buffer_per_prefecture' => 10,
    ],
];
