<?php

return [
    'database' => [
        // Path to the dedicated SQLite file backing the birthplace
        // repository. Never the host application's own database -
        // installing/using this package doesn't touch it. Set this
        // to ':memory:' (e.g. in tests) for an ephemeral database.
        'path' => storage_path('app/codicefiscale/places.sqlite'),
    ],

    // Sources for `codice-fiscale:update-places`. Never fetched
    // during normal validation - only when this command is run.
    'sources' => [
        'municipalities' => 'https://www.anagrafenazionale.interno.it/wp-content/uploads/ANPR_archivio_comuni.csv',
        // ISTAT's "Elenco comuni soppressi e non ricostituiti" report
        // (Situas report 128): fills the suppressed comuni ANPR lacks
        // (ADR-0015). Fetched page by page from the Situas web app's
        // paged endpoint; the fixed early date keeps every suppression
        // since 1861 in the result.
        'suppressed_municipalities' => 'https://situas.istat.it/ShibO2Module/api/Report/Spool/1861-03-17/128',
        'countries' => 'https://www.anagrafenazionale.interno.it/wp-content/uploads/tabella_2_statiesteri.xlsx',
        // ISTAT's "Storico Paesi esteri" report: fills the foreign codes
        // MAECI lacks (ADR-0014). The fixed early pdata keeps ceased
        // states in the result.
        'historical_countries' => 'https://situas-servizi.istat.it/publish/reportspooljson?pfun=469&pdata=01/01/1990',
    ],
];
