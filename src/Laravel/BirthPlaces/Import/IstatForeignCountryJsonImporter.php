<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import;

use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\CountryCode;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\ForeignCountry;
use Robertogallea\CodiceFiscale\Support\PlaceNameNormalizer;

/**
 * Imports ISTAT's Situas report 469 "Storico Paesi esteri" (JSON) into
 * the foreign_countries table, as a supplement to MAECI (ADR-0014).
 *
 * ISTAT gives no validity dates, so every row gets the same maximally
 * wide era as MAECI and the retired list: 1900-01-01, open-ended. That
 * shared (code, valid_from) key is what lets the import order decide
 * which source wins a code.
 */
final class IstatForeignCountryJsonImporter
{
    use UpsertsInChunks;

    private const CHUNK_SIZE = 100;

    private const VALID_FROM = '1900-01-01';

    private const HISTORICAL_TYPES = ['Stato storico', 'Territorio storico'];

    public function __construct(
        private readonly PlaceNameNormalizer $normalizer = new PlaceNameNormalizer(),
    ) {
    }

    /**
     * Imports the rows for entities that still exist (TIPO_PAESE
     * Stato, Territorio, Territorio/Altro).
     */
    public function importCurrent(string $json): int
    {
        return $this->upsert(array_values(array_filter(
            $this->rows($json),
            static fn (array $row): bool => ! $row['historical'],
        )));
    }

    /**
     * Imports the rows for ceased entities (TIPO_PAESE Stato storico,
     * Territorio storico). Run after MAECI: where MAECI has reassigned
     * a historical code to a successor (Z501, Antille olandesi ->
     * Aruba), the codice fiscale still means the historical entity,
     * so these rows must overwrite MAECI's (ADR-0014).
     */
    public function importHistorical(string $json): int
    {
        return $this->upsert(array_values(array_filter(
            $this->rows($json),
            static fn (array $row): bool => $row['historical'],
        )));
    }

    /**
     * @param  list<array{code: string, name: string, country: string, historical: bool}>  $rows
     */
    private function upsert(array $rows): int
    {
        return $this->upsertInChunks(
            $rows,
            $this->toRecord(...),
            ForeignCountry::class,
            ['code', 'valid_from'],
            ['name', 'country_code', 'valid_to', 'name_normalized'],
            self::CHUNK_SIZE,
        );
    }

    /**
     * @return list<array{code: string, name: string, country: string, historical: bool}>
     */
    private function rows(string $json): array
    {
        $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($decoded) || ! isset($decoded['resultset']) || ! is_array($decoded['resultset'])) {
            throw new \RuntimeException('The ISTAT foreign-countries report has no "resultset" array.');
        }

        $rows = [];

        foreach ($decoded['resultset'] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $code = $this->field($row, 'COD_AT');
            $country = $this->field($row, 'ISO_3166_ALPHA3');

            if (BirthPlaceCode::tryFrom($code) === null || CountryCode::tryFrom($country) === null) {
                continue;
            }

            $rows[] = [
                'code' => $code,
                'name' => $this->field($row, 'DENOMINAZIONE_IT'),
                'country' => $country,
                'historical' => in_array($this->field($row, 'TIPO_PAESE'), self::HISTORICAL_TYPES, true),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<mixed>  $row
     */
    private function field(array $row, string $key): string
    {
        $value = $row[$key] ?? null;

        // Values carry stray whitespace, non-breaking spaces included
        // (Z501's COD_AT is "Z501\u{00A0}"), which trim() alone misses.
        return is_string($value) ? (string) preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $value) : '';
    }

    /**
     * @param  array{code: string, name: string, country: string, historical: bool}  $row
     * @return array<string, string|null>
     */
    private function toRecord(array $row): array
    {
        return [
            'code' => $row['code'],
            'name' => $row['name'],
            'country_code' => $row['country'],
            'valid_from' => self::VALID_FROM,
            'valid_to' => null,
            'name_normalized' => $this->normalizer->normalize($row['name']),
        ];
    }
}
