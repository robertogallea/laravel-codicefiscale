<?php

use Robertogallea\CodiceFiscale\Contracts\BirthPlaceRepository;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;

/*
 * The network layer of the v2-parity test (see V2ParityTest): imports
 * the live sources and checks that every 2.3.1 code resolves, except
 * the known gaps - which must still *not* resolve, so a gap closed by
 * a source change or another fix is removed from known-gaps.txt rather
 * than lingering there. Excluded from the default run (phpunit.xml);
 * run with `vendor/bin/pest --group=network`, as the weekly
 * "V2 parity" workflow does.
 */
test('after importing the live sources, every 2.3.1 code resolves except the known gaps', function () {
    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);

    $repository = app(BirthPlaceRepository::class);
    $gaps = v2ParityKnownGaps();
    $unresolved = [];
    $staleGaps = [];

    foreach (v2ParityCodes('codes.txt') as $code) {
        $resolves = $repository->eras(BirthPlaceCode::from($code)) !== [];

        if (! $resolves && ! isset($gaps[$code])) {
            $unresolved[] = $code;
        }

        if ($resolves && isset($gaps[$code])) {
            $staleGaps[] = "$code {$gaps[$code]}";
        }
    }

    expect($unresolved)->toBe([], 'These 2.3.1 codes no longer resolve: '.implode(', ', $unresolved))
        ->and($staleGaps)->toBe([], 'These known gaps now resolve; remove them from known-gaps.txt: '.implode(', ', $staleGaps));
})->group('network');
