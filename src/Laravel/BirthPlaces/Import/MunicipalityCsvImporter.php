<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import;

use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\Municipality;

/**
 * Imports ANPR's comuni archive CSV into the municipalities table.
 * DATACESSAZIONE in the source is inclusive (the last valid day);
 * our [validFrom, validTo) domain convention is exclusive, so it's
 * converted to the following day - except the "9999-12-31" sentinel
 * (still active), which becomes an open-ended null.
 */
final class MunicipalityCsvImporter
{
    private const CHUNK_SIZE = 500;

    private const STILL_ACTIVE_SENTINEL = '9999-12-31';

    public function import(string $csvContents): int
    {
        $rows = $this->parseCsv($csvContents);
        $count = 0;

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            $records = array_map($this->toRecord(...), $chunk);

            // toBase() rather than the Eloquent-magic-forwarded
            // upsert(): plain PHPStan (no Larastan) can't resolve
            // __callStatic-forwarded methods, and toBase() gives the
            // real, directly-typed query builder method instead.
            Municipality::query()->toBase()->upsert(
                $records,
                ['code', 'valid_from'],
                ['name', 'province', 'istat_code', 'valid_to'],
            );

            $count += count($records);
        }

        return $count;
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

        while (($row = fgetcsv($stream)) !== false) {
            /** @var list<string> $row */
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
