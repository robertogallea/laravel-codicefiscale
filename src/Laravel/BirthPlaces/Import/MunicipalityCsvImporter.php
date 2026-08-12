<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import;

use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\Municipality;
use Robertogallea\CodiceFiscale\Support\PlaceNameNormalizer;

/**
 * Imports ANPR's comuni archive CSV into the municipalities table.
 * DATACESSAZIONE in the source is inclusive (the last valid day);
 * our [validFrom, validTo) domain convention is exclusive, so it's
 * converted to the following day - except the "9999-12-31" sentinel
 * (still active), which becomes an open-ended null.
 */
final class MunicipalityCsvImporter
{
    use UpsertsInChunks;

    private const CHUNK_SIZE = 500;

    private const STILL_ACTIVE_SENTINEL = '9999-12-31';

    public function __construct(
        private readonly PlaceNameNormalizer $normalizer = new PlaceNameNormalizer(),
    ) {
    }

    public function import(string $csvContents): int
    {
        // A handful of long-superseded, pre-1928-merger comuni carry ANPR's
        // "ND" ("non disponibile") placeholder in CODCATASTALE instead of a
        // real cadastral code - not a usable BirthPlaceCode, so they're
        // skipped here rather than persisted and later choking
        // BirthPlaceCode::from() in Municipality::toBirthPlace().
        $rows = array_values(array_filter(
            $this->parseCsv($csvContents),
            static fn (array $row): bool => BirthPlaceCode::tryFrom($row['CODCATASTALE']) !== null,
        ));

        $count = $this->upsertInChunks(
            $rows,
            $this->toRecord(...),
            Municipality::class,
            ['code', 'valid_from'],
            ['name', 'province', 'istat_code', 'valid_to', 'name_normalized'],
            self::CHUNK_SIZE,
        );

        $this->pruneInvalidRows();

        return $count;
    }

    /**
     * Self-healing cleanup for rows that reached the table before this
     * validation existed (a places.sqlite imported by a pre-fix version of
     * this package): delete any persisted row whose code no longer passes
     * BirthPlaceCode's format. Runs on every import, not just once, so an
     * already-corrupted install heals itself the next time
     * codice-fiscale:update-places runs.
     */
    private function pruneInvalidRows(): void
    {
        $invalidIds = Municipality::query()
            ->get(['id', 'code'])
            ->reject(static fn (Municipality $row): bool => BirthPlaceCode::tryFrom($row->code) !== null)
            ->pluck('id');

        if ($invalidIds->isNotEmpty()) {
            Municipality::query()->whereIn('id', $invalidIds)->delete();
        }
    }

    /** @return list<array<string, string>> */
    private function parseCsv(string $contents): array
    {
        $stream = fopen('php://memory', 'r+');

        if ($stream === false) {
            throw new \RuntimeException('Unable to open an in-memory stream to parse the comuni CSV.');
        }

        fwrite($stream, $contents);
        rewind($stream);

        $header = fgetcsv($stream);

        if ($header === false) {
            fclose($stream);

            throw new \RuntimeException('The comuni CSV has no header row.');
        }

        /** @var list<string> $header */
        $rows = [];
        $lineNumber = 1;

        while (($row = fgetcsv($stream)) !== false) {
            /** @var list<string> $row */
            $lineNumber++;

            if (count($row) !== count($header)) {
                fclose($stream);

                throw new \RuntimeException(
                    "Comuni CSV line {$lineNumber} has ".count($row).' columns, expected '.count($header).'.'
                );
            }

            $rows[] = array_combine($header, $row);
        }

        fclose($stream);

        return $rows;
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string|null>
     */
    private function toRecord(array $row): array
    {
        return [
            'code' => $row['CODCATASTALE'],
            'name' => $row['DENOMINAZIONE_IT'],
            'province' => $row['SIGLAPROVINCIA'],
            'istat_code' => $row['CODISTAT'],
            'valid_from' => $row['DATAISTITUZIONE'],
            'valid_to' => $this->exclusiveValidTo($row['DATACESSAZIONE']),
            'name_normalized' => $this->normalizer->normalize($row['DENOMINAZIONE_IT']),
        ];
    }

    private function exclusiveValidTo(string $inclusiveValidTo): ?string
    {
        if ($inclusiveValidTo === self::STILL_ACTIVE_SENTINEL) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $inclusiveValidTo);

        if ($date === false) {
            throw new \RuntimeException("\"$inclusiveValidTo\" is not a valid DATACESSAZIONE date.");
        }

        return $date->modify('+1 day')->format('Y-m-d');
    }
}
