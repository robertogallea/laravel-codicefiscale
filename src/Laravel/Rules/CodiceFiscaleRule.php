<?php

namespace Robertogallea\CodiceFiscale\Laravel\Rules;

use Closure;
use DateTimeImmutable;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Robertogallea\CodiceFiscale\CodiceFiscale;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\PartialPerson;
use Robertogallea\CodiceFiscale\Enums\Gender;
use Robertogallea\CodiceFiscale\Enums\PersonField;
use Robertogallea\CodiceFiscale\Matching\Matcher;
use Robertogallea\CodiceFiscale\Validation\Validator;

/**
 * make() alone validates format/checksum/semantics via Validator -
 * exactly what the codice_fiscale string-rule alias does. matching()
 * additionally cross-checks the field against other request fields
 * (by field name, via DataAwareRule::setData()) through Matcher, so
 * a caller never has to encode multiple field names into one
 * pipe-delimited rule string.
 */
final class CodiceFiscaleRule implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    private ?string $firstNameField = null;

    private ?string $lastNameField = null;

    private ?string $birthDateField = null;

    private ?string $birthPlaceField = null;

    private ?string $genderField = null;

    public function __construct(
        private readonly Validator $validator,
        private readonly Matcher $matcher,
    ) {
    }

    public static function make(): self
    {
        return new self(app(Validator::class), app(Matcher::class));
    }

    public function matching(
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $birthDate = null,
        ?string $birthPlace = null,
        ?string $gender = null,
    ): self {
        $this->firstNameField = $firstName;
        $this->lastNameField = $lastName;
        $this->birthDateField = $birthDate;
        $this->birthPlaceField = $birthPlace;
        $this->genderField = $gender;

        return $this;
    }

    /** @param array<string, mixed> $data */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! $this->validator->validate($value)->valid()) {
            $fail('The :attribute is not a valid codice fiscale.');

            return;
        }

        if (! $this->hasMatchingFields()) {
            return;
        }

        $result = $this->matcher->match(CodiceFiscale::from($value), $this->partialPersonFromData());

        if ($result->mismatched() !== []) {
            $fail(sprintf(
                'The :attribute does not match the provided %s.',
                implode(', ', array_map($this->fieldName(...), $result->mismatched())),
            ));
        }
    }

    private function hasMatchingFields(): bool
    {
        return $this->firstNameField !== null
            || $this->lastNameField !== null
            || $this->birthDateField !== null
            || $this->birthPlaceField !== null
            || $this->genderField !== null;
    }

    private function partialPersonFromData(): PartialPerson
    {
        return new PartialPerson(
            firstName: $this->stringFromData($this->firstNameField),
            lastName: $this->stringFromData($this->lastNameField),
            birthDate: $this->birthDateFromData(),
            birthPlace: $this->birthPlaceFromData(),
            gender: $this->genderFromData(),
        );
    }

    private function stringFromData(?string $field): ?string
    {
        if ($field === null) {
            return null;
        }

        $raw = $this->data[$field] ?? null;

        return is_string($raw) ? $raw : null;
    }

    private function birthDateFromData(): ?DateTimeImmutable
    {
        $raw = $this->stringFromData($this->birthDateField);

        if ($raw === null) {
            return null;
        }

        try {
            return new DateTimeImmutable($raw);
        } catch (\Exception) {
            // An unparseable date can't be compared to anything -
            // treated the same as "not provided" (skipped by Matcher)
            // rather than forcing a spurious mismatch on a value
            // that never reached a comparable form.
            return null;
        }
    }

    private function birthPlaceFromData(): ?BirthPlaceCode
    {
        $raw = $this->stringFromData($this->birthPlaceField);

        return $raw === null ? null : BirthPlaceCode::tryFrom($raw);
    }

    private function genderFromData(): ?Gender
    {
        $raw = $this->stringFromData($this->genderField);

        return $raw === null ? null : Gender::tryFrom(strtoupper($raw));
    }

    private function fieldName(PersonField $field): string
    {
        return match ($field) {
            PersonField::FirstName => $this->firstNameField,
            PersonField::LastName => $this->lastNameField,
            PersonField::BirthDate => $this->birthDateField,
            PersonField::BirthPlace => $this->birthPlaceField,
            PersonField::Gender => $this->genderField,
        } ?? $field->name;
    }
}
