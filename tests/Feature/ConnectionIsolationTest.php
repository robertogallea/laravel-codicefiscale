<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('the dedicated connection is registered automatically, distinct from the host app default', function () {
    expect(config('database.connections.codicefiscale'))->not->toBeNull()
        ->and(config('database.connections.codicefiscale.driver'))->toBe('sqlite')
        ->and(config('database.default'))->not->toBe('codicefiscale');
});

test("installing this package never touches the host application's own connection or its migrations", function () {
    $defaultConnection = config('database.default');

    // Our tables exist on our dedicated connection...
    expect(Schema::connection('codicefiscale')->hasTable('municipalities'))->toBeTrue()
        ->and(Schema::connection('codicefiscale')->hasTable('foreign_countries'))->toBeTrue();

    // ...and nowhere near the host app's own default connection.
    expect(Schema::connection($defaultConnection)->hasTable('municipalities'))->toBeFalse()
        ->and(Schema::connection($defaultConnection)->hasTable('foreign_countries'))->toBeFalse();

    // Our package's migrations are recorded against our own
    // connection's migrations table, not the host's.
    $ourMigrations = DB::connection('codicefiscale')->table('migrations')
        ->where('migration', 'like', '%municipalities%')
        ->orWhere('migration', 'like', '%foreign_countries%')
        ->count();
    expect($ourMigrations)->toBe(2);

    if (Schema::connection($defaultConnection)->hasTable('migrations')) {
        $leakedIntoDefault = DB::connection($defaultConnection)->table('migrations')
            ->where('migration', 'like', '%municipalities%')
            ->orWhere('migration', 'like', '%foreign_countries%')
            ->count();
        expect($leakedIntoDefault)->toBe(0);
    }
});

test('a bare "php artisan migrate" - exactly what a real host app runs for its own migrations - never touches our tables or bookkeeping', function () {
    $defaultConnection = config('database.default');

    // No --database flag: this is precisely the host's own ordinary
    // workflow, run *in addition to* our provider's own automatic
    // migration in boot(). Our migrations are deliberately never
    // registered via loadMigrationsFrom(), so the host's shared
    // migrate command has no way to know about - or touch - them.
    $this->artisan('migrate')->run();

    $leaked = Schema::connection($defaultConnection)->hasTable('migrations')
        ? DB::connection($defaultConnection)->table('migrations')
            ->where('migration', 'like', '%municipalities%')
            ->orWhere('migration', 'like', '%foreign_countries%')
            ->count()
        : 0;

    expect($leaked)->toBe(0);
});
