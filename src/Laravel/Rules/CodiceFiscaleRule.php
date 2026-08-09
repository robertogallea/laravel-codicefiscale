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
 *
 * A field named via matching() that is either absent from the request
 * data or holds a value that can't be parsed into what Matcher
 * expects (an unparseable date, an unrecognized gender/birthplace
 * code) is treated as not provided - PartialPerson's "skipped" bucket
 * - rather than forced into a spurious mismatch on a value that never
 * reached a comparable form.
 */
final class CodiceFiscaleRule implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    /** @var array<string, string> request field name, keyed by PersonField::name */
    private array $fieldNames = [];

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
        $this->fieldNames = array_filter([
            PersonField::FirstName->name => $firstName,
            PersonField::LastName->name => $lastName,
            PersonField::BirthDate->name => $birthDate,
            PersonField::BirthPlace->name => $birthPlace,
            PersonField::Gender->name => $gender,
        ], static fn (?string $value): bool => $value !== null);

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

        if ($this->fieldNames === []) {
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

    private function partialPersonFromData(): PartialPerson
    {
        return new PartialPerson(
            firstName: $this->stringFromData(PersonField::FirstName),
            lastName: $this->stringFromData(PersonField::LastName),
            birthDate: $this->birthDateFromData(),
            birthPlace: $this->birthPlaceFromData(),
            gender: $this->genderFromData(),
        );
    }

    private function stringFromData(PersonField $field): ?string
    {
        $fieldName = $this->fieldNames[$field->name] ?? null;

        if ($fieldName === null) {
            return null;
        }

        $raw = $this->data[$fieldName] ?? null;

        return is_string($raw) ? $raw : null;
    }

    private function birthDateFromData(): ?DateTimeImmutable
    {
        $raw = $this->stringFromData(PersonField::BirthDate);

        if ($raw === null) {
            return null;
        }

        try {
            return new DateTimeImmutable($raw);
        } catch (\Exception) {
            return null;
        }
    }

    private function birthPlaceFromData(): ?BirthPlaceCode
    {
        $raw = $this->stringFromData(PersonField::BirthPlace);

        return $raw === null ? null : BirthPlaceCode::tryFrom($raw);
    }

    private function genderFromData(): ?Gender
    {
        $raw = $this->stringFromData(PersonField::Gender);

        return $raw === null ? null : Gender::tryFrom(strtoupper($raw));
    }

    private function fieldName(PersonField $field): string
    {
        return $this->fieldNames[$field->name] ?? $field->name;
    }
}
