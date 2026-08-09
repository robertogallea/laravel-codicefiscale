<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Robertogallea\CodiceFiscale\CodiceFiscale;
use Robertogallea\CodiceFiscale\Exceptions\InvalidCodiceFiscaleException;
use Tests\Support\CastTestModel;

beforeEach(function () {
    Schema::create('cast_test_models', function (Blueprint $table) {
        $table->id();
        $table->string('fiscal_code')->nullable();
    });
});

test('a structurally well-formed value round-trips to a CodiceFiscale instance', function () {
    $model = CastTestModel::create(['fiscal_code' => 'RSSMRA95E05F205Z']);
    $fresh = CastTestModel::find($model->id);

    expect($fresh->fiscal_code)->toBeInstanceOf(CodiceFiscale::class)
        ->and($fresh->fiscal_code->value())->toBe('RSSMRA95E05F205Z');
});

test('assigning a CodiceFiscale instance directly also round-trips correctly', function () {
    $model = CastTestModel::create(['fiscal_code' => CodiceFiscale::from('RSSMRA95E05F205Z')]);
    $fresh = CastTestModel::find($model->id);

    expect($fresh->fiscal_code->value())->toBe('RSSMRA95E05F205Z');
});

test('setting a structurally-invalid value throws immediately on assignment', function () {
    expect(fn () => CastTestModel::create(['fiscal_code' => 'not-a-real-code']))
        ->toThrow(InvalidCodiceFiscaleException::class);

    // Nothing was ever written to the database - fail-fast means fail
    // before the insert, not a partially-written row.
    expect(CastTestModel::count())->toBe(0);
});

test('setting a non-string, non-CodiceFiscale value throws rather than leaking a raw TypeError', function () {
    // CastsAttributes::set()'s $value is untyped (mixed) at the real
    // interface level - PHP never enforces a docblock generic at
    // runtime, so a caller can still assign something else entirely
    // (e.g. via mass assignment from untrusted array/request data).
    expect(fn () => CastTestModel::create(['fiscal_code' => ['not', 'a', 'string']]))
        ->toThrow(InvalidCodiceFiscaleException::class);
});

test('a null value round-trips to null without invoking validation', function () {
    $model = CastTestModel::create(['fiscal_code' => null]);
    $fresh = CastTestModel::find($model->id);

    expect($fresh->fiscal_code)->toBeNull();
});

test('reading a model whose column already holds an invalid string returns null, not an exception', function () {
    // Bypassing the cast entirely, exactly like a legacy row, a seeder,
    // or a direct write outside this package would.
    $id = DB::table('cast_test_models')->insertGetId(['fiscal_code' => 'not-a-real-code']);

    $model = CastTestModel::find($id);

    expect($model->fiscal_code)->toBeNull();
});
