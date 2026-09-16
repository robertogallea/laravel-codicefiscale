<?php

use Illuminate\Support\Facades\File;
use Laravel\Boost\Install\SkillComposer;
use Laravel\Boost\Install\ThirdPartyPackage;
use Laravel\Roster\ProjectManager;

/**
 * Testbench's skeleton app has no vendor/ directory of its own, but Boost's
 * third-party discovery (via Laravel Roster since Boost 2.9) reads the
 * project's composer.lock for installed packages, composer.json to tell
 * direct dependencies from transitive ones, and base_path('vendor') for
 * each package's files - none of it through PHP autoloading. So we fake a
 * minimal "consuming app" view of this package inside the skeleton,
 * exercised only for the duration of each test.
 */
beforeEach(function () {
    $this->fakeVendorPath = base_path('vendor/robertogallea/laravel-codicefiscale');
    File::ensureDirectoryExists(dirname($this->fakeVendorPath));

    if (! file_exists($this->fakeVendorPath)) {
        symlink(realpath(__DIR__.'/../../'), $this->fakeVendorPath);
    }

    $this->composerJsonPath = base_path('composer.json');
    $this->originalComposerJson = file_get_contents($this->composerJsonPath);

    $composerData = json_decode($this->originalComposerJson, true);
    $composerData['require']['robertogallea/laravel-codicefiscale'] = '*';
    file_put_contents($this->composerJsonPath, json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    // The skeleton ships no composer.lock; Roster needs one to consider a
    // package installed at all.
    $this->composerLockPath = base_path('composer.lock');
    $this->originalComposerLock = file_exists($this->composerLockPath) ? file_get_contents($this->composerLockPath) : null;
    file_put_contents($this->composerLockPath, json_encode([
        'packages' => [['name' => 'robertogallea/laravel-codicefiscale', 'version' => 'dev-main']],
        'packages-dev' => [],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
});

afterEach(function () {
    file_put_contents($this->composerJsonPath, $this->originalComposerJson);

    if ($this->originalComposerLock === null) {
        File::delete($this->composerLockPath);
    } else {
        file_put_contents($this->composerLockPath, $this->originalComposerLock);
    }

    if (is_link($this->fakeVendorPath)) {
        unlink($this->fakeVendorPath);
    }
});

test("boost discovers the package's guideline and skill directories", function () {
    $package = ThirdPartyPackage::discover(app(ProjectManager::class))->get('robertogallea/laravel-codicefiscale');

    expect($package)->not->toBeNull()
        ->and($package->hasGuidelines)->toBeTrue()
        ->and($package->hasSkills)->toBeTrue();
});

/**
 * Boost's own BoostServiceProvider::shouldRun() deliberately disables command
 * registration whenever app()->runningUnitTests() is true - by design, so its
 * browser-log middleware/routes never leak into a consumer's test suite. That
 * makes `artisan('boost:list-skills')` permanently unavailable under
 * Testbench, so we resolve SkillComposer directly instead - it's Boost's own
 * discovery/parsing class, just reached without going through the command.
 */
test('SkillComposer discovers and parses both package skills', function () {
    $skills = app(SkillComposer::class)->skills();

    expect($skills->has('using-laravel-codicefiscale'))->toBeTrue()
        ->and($skills->has('upgrading-laravel-codicefiscale-from-v2'))->toBeTrue()
        ->and($skills->get('using-laravel-codicefiscale')->package)->toBe('robertogallea/laravel-codicefiscale')
        ->and($skills->get('upgrading-laravel-codicefiscale-from-v2')->package)->toBe('robertogallea/laravel-codicefiscale');
});
