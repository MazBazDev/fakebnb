<?php

return [
    'enabled' => env('GEOCODING_ENABLED', true),
    'timeout' => (int) env('GEOCODING_TIMEOUT', 5),
    'retries' => (int) env('GEOCODING_RETRIES', 1),
    'retry_sleep' => (int) env('GEOCODING_RETRY_SLEEP', 200),
];
