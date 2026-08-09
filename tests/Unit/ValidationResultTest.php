<?php

use Robertogallea\CodiceFiscale\Enums\ValidationError;
use Robertogallea\CodiceFiscale\Validation\ValidationResult;

test('ok() is valid with no errors', function () {
    $result = ValidationResult::ok();

    expect($result->valid())->toBeTrue()
        ->and($result->errors())->toBe([]);
});

test('withError() is invalid with exactly the given error', function () {
    $result = ValidationResult::withError(ValidationError::InvalidChecksum);

    expect($result->valid())->toBeFalse()
        ->and($result->errors())->toBe([ValidationError::InvalidChecksum]);
});
