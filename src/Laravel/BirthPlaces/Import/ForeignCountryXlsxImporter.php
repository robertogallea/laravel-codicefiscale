<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import;

use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\ForeignCountry;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Xlsx\XlsxReader;

/**
 * Imports MAECI's stati-esteri xlsx table into the foreign_countries
 * table. Every row in the source currently spans 01/01/1900 to
 * 31/12/9999 (a "currently valid" snapshot, not real history) - the
 * sentinel end date becomes an open-ended null, same convention as
 * the municipalities importer.
 */
final class ForeignCountryXlsxImporter
{
    private const CHUNK_SIZE = 100;

    private const STILL_ACTIVE_SENTINEL_PREFIX = '31/12/9999';

    public function __construct(
        private readonly XlsxReader $reader = new XlsxReader(),
    ) {
    }

    public function import(string $xlsxContents): int
    {
        $rows = $this->reader->read($xlsxContents);
        $header = array_shift($rows);

        if ($header === null) {
            throw new \RuntimeException('The stati-esteri xlsx has no header row.');
        }

        $columnCount = count($header);

        $associativeRows = array_map(
            fn (array $row): array => array_combine($header, $this->padTo($columnCount, $row)),
            $rows,
        );

        // A handful of rows in the real source are special
        // administrative categories (e.g. "Riconosciuti non cittadini
        // (lettoni)") with no CODAT at all - not a real birthplace,
        // nothing to attach a BirthPlaceCode to.
        $withACode = array_values(array_filter(
            $associativeRows,
            static fn (array $row): bool => $row['CODAT'] !== ''
        ));

        $count = 0;
        foreach (array_chunk($withACode, self::CHUNK_SIZE) as $chunk) {
            $records = array_map($this->toRecord(...), $chunk);

            // toBase() rather than the Eloquent-magic-forwarded
            // upsert(): plain PHPStan (no Larastan) can't resolve
            // __callStatic-forwarded methods, and toBase() gives the
            // real, directly-typed query builder method instead.
            ForeignCountry::query()->toBase()->upsert(
                $records,
                ['code', 'valid_from'],
                ['name', 'country_code', 'valid_to'],
            );

            $count += count($records);
        }

        return $count;
    }

    /**
     * @param  list<string>  $row
     * @return list<string>
     */
    private function padTo(int $columnCount, array $row): array
    {
        return array_pad(array_slice($row, 0, $columnCount), $columnCount, '');
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string|null>
     */
    private function toRecord(array $row): array
    {
        return [
            'code' => $row['CODAT'],
            'name' => $row['DENOMINAZIONE'],
            'country_code' => $row['CODISO3166_1_ALPHA3'],
            'valid_from' => $this->parseDate($row['DATAINIZIOVALIDITA']),
            'valid_to' => $this->parseValidTo($row['DATAFINEVALIDITA']),
        ];
    }

    private function parseDate(string $ddmmyyyyHms): string
    {
        $date = \DateTimeImmutable::createFromFormat('d/m/Y H:i:s', $ddmmyyyyHms);

        if ($date === false) {
            throw new \RuntimeException("\"$ddmmyyyyHms\" is not a valid stati-esteri date.");
        }

        return $date->format('Y-m-d');
    }

    private function parseValidTo(string $ddmmyyyyHms): ?string
    {
        if (str_starts_with($ddmmyyyyHms, self::STILL_ACTIVE_SENTINEL_PREFIX)) {
            return null;
        }

        return $this->parseDate($ddmmyyyyHms);
    }
}
