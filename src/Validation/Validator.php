<?php

namespace Robertogallea\CodiceFiscale\Validation;

use Robertogallea\CodiceFiscale\CodiceFiscale;
use Robertogallea\CodiceFiscale\Contracts\BirthPlaceRepository;
use Robertogallea\CodiceFiscale\Enums\ValidationError;
use Robertogallea\CodiceFiscale\Generation\Checksum;
use Robertogallea\CodiceFiscale\Parsing\Parser;

final class Validator
{
    private readonly Parser $parser;

    public function __construct(
        private readonly BirthPlaceRepository $birthPlaceRepository,
        private readonly Checksum $checksum = new Checksum(),
        ?Parser $parser = null,
    ) {
        $this->parser = $parser ?? new Parser($birthPlaceRepository);
    }

    /**
     * Structural well-formedness only. Takes a raw string, not a
     * CodiceFiscale, since a CodiceFiscale instance can only ever be
     * constructed already-well-formed - there is no other way to
     * observe a genuine format failure.
     */
    public function validateFormat(string $value): ValidationResult
    {
        return CodiceFiscale::tryFrom($value) === null
            ? new ValidationResult([ValidationError::InvalidFormat])
            : new ValidationResult([]);
    }

    public function validateChecksum(CodiceFiscale $cf): ValidationResult
    {
        return $this->checksum->verify($cf->value())
            ? new ValidationResult([])
            : new ValidationResult([ValidationError::InvalidChecksum]);
    }

    public function validateSemantics(CodiceFiscale $cf): ValidationResult
    {
        $parsed = $this->parser->parse($cf);
        $birthDate = $parsed->birthDate();
        $errors = [];

        if ($birthDate === null) {
            $errors[] = ValidationError::InvalidDate;
        }

        if (! $this->birthPlaceRepository->existedEver($parsed->birthPlaceCode())) {
            $errors[] = ValidationError::UnknownBirthPlace;
        } elseif ($birthDate !== null && $this->birthPlaceRepository->find($parsed->birthPlaceCode(), $birthDate) === null) {
            $errors[] = ValidationError::BirthPlaceNotValidOnDate;
        }

        return new ValidationResult($errors);
    }

    /**
     * Format gates: a malformed string is never passed to checksum or
     * semantic checks (there's no safe way to slice it). Once format
     * passes, checksum and semantics run independently of each other
     * and both contribute to the same result.
     */
    public function validate(string $value): ValidationResult
    {
        $formatResult = $this->validateFormat($value);

        if (! $formatResult->valid()) {
            return $formatResult;
        }

        $cf = CodiceFiscale::from($value);

        return new ValidationResult([
            ...$this->validateChecksum($cf)->errors(),
            ...$this->validateSemantics($cf)->errors(),
        ]);
    }
}
