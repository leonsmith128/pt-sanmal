<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cell caching for XLSX exports
    |--------------------------------------------------------------------------
    |
    | PhpSpreadsheet keeps worksheet cells in memory by default. Batch caching
    | keeps a small working set in memory and spills the rest to a dedicated
    | file cache store. The threshold is a number of cached cells, not bytes.
    |
    */

    'cache' => [
        'driver' => 'batch',

        'batch' => [
            'memory_limit' => 5000,
        ],

        'illuminate' => [
            'store' => 'excel',
        ],

        // Keep cached cells available for the complete export request.
        'default_ttl' => 10800,
    ],
];
