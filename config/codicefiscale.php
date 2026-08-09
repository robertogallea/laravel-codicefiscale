<?php

return [
    'database' => [
        // Path to the dedicated SQLite file backing the birthplace
        // repository. Never the host application's own database -
        // installing/using this package doesn't touch it. Set this
        // to ':memory:' (e.g. in tests) for an ephemeral database.
        'path' => storage_path('app/codicefiscale/places.sqlite'),
    ],
];
