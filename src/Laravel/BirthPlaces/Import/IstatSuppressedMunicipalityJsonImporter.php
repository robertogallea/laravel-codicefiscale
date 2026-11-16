<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import;

use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\Municipality;
use Robertogallea\CodiceFiscale\Support\PlaceNameNormalizer;

/**
 * Imports ISTAT's Situas report 128 "Elenco comuni soppressi e non
 * ricostituiti" (JSON) into the municipalities table, for the codes
 * ANPR lacks (ADR-0015).
 *
 * ANPR wins at the code level: a code it carries is skipped, and a row
 * this importer wrote for it on an earlier run is removed. Each
 * remaining code becomes one era: from 1861-03-17 (ISTAT gives no
 * institution date; ANPR's archive starts there) to
 * DATA_INIZIO_AMMINISTRATIVA, the date the suppression took effect,
 * which is already exclusive. A comune split between successors has one
 * row per successor, all with the same date, and is imported once.
 */
final class IstatSuppressedMunicipalityJsonImporter
{
    use DefersToHigherPrioritySources;
    use UpsertsInChunks;

    private const CHUNK_SIZE = 500;

    private const VALID_FROM = '1861-03-17';

    public function __construct(
        private readonly PlaceNameNormalizer $normalizer = new PlaceNameNormalizer(),
    ) {
    }

    /**
     * @param  array<string, list<string>>  $anprEras  code => valid_from of every era ANPR carries
     */
    public function import(string $json, array $anprEras = []): int
    {
        $rows = $this->rows($json);

        $this->deleteRowsSupersededBySource(array_keys($rows), $anprEras);

        return $this->upsertInChunks(
            array_values(array_filter(
                $rows,
                static fn (array $row): bool => ! isset($anprEras[$row['code']]),
            )),
            $this->toRecord(...),
            Municipality::class,
            ['code', 'valid_from'],
            ['name', 'province', 'istat_code', 'valid_to', 'name_normalized'],
            self::CHUNK_SIZE,
        );
    }

    /**
     * The era starts this report supplies, in the shape a lower-priority
     * source defers to: code => [valid_from].
     *
     * @return array<string, list<string>>
     */
    public function eraStarts(string $json): array
    {
        return array_map(static fn (): array => [self::VALID_FROM], $this->rows($json));
    }

    /**
     * @return array<string, array{code: string, name: string, province: string, istat_code: string, valid_to: string}>
     */
    private function rows(string $json): array
    {
        $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($decoded) || ! isset($decoded['resultset']) || ! is_array($decoded['resultset'])) {
            throw new \RuntimeException('The ISTAT suppressed-comuni report has no "resultset" array.');
        }

        $rows = [];

        foreach ($decoded['resultset'] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $code = $this->field($row, 'COD_CATASTO');
            $validTo = substr($this->field($row, 'DATA_INIZIO_AMMINISTRATIVA'), 0, 10);

            if (BirthPlaceCode::tryFrom($code) === null || preg_match('/^\d{4}-\d{2}-\d{2}$/', $validTo) !== 1) {
                continue;
            }

            $rows[$code] ??= [
                'code' => $code,
                'name' => $this->field($row, 'COMUNE'),
                'province' => $this->field($row, 'SIGLA_UTS'),
                'istat_code' => $this->field($row, 'PRO_COM_T'),
                'valid_to' => $validTo,
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

        return is_string($value) ? (string) preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $value) : '';
    }

    /**
     * @param  array{code: string, name: string, province: string, istat_code: string, valid_to: string}  $row
     * @return array<string, string|null>
     */
    private function toRecord(array $row): array
    {
        return [
            'code' => $row['code'],
            'name' => $row['name'],
            'province' => $row['province'],
            'istat_code' => $row['istat_code'],
            'valid_from' => self::VALID_FROM,
            'valid_to' => $row['valid_to'],
            'name_normalized' => $this->normalizer->normalize($row['name']),
        ];
    }
}
