<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import;

use Illuminate\Database\Eloquent\Model;

/**
 * Shared "map each row to a record, then upsert in chunks" shape
 * both importers otherwise duplicated verbatim - the two sources'
 * own row shapes and field mappings stay separate (toRecord()),
 * only the chunk/upsert mechanics are shared here.
 */
trait UpsertsInChunks
{
    /**
     * @param  list<array<string, string>>  $rows
     * @param  callable(array<string, string>): array<string, string|null>  $toRecord
     * @param  class-string<Model>  $modelClass
     * @param  list<string>  $uniqueBy
     * @param  list<string>  $update
     * @param  positive-int  $chunkSize
     */
    private function upsertInChunks(
        array $rows,
        callable $toRecord,
        string $modelClass,
        array $uniqueBy,
        array $update,
        int $chunkSize,
    ): int {
        $count = 0;

        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            $records = array_map($toRecord, $chunk);

            // toBase() rather than the Eloquent-magic-forwarded
            // upsert(): plain PHPStan (no Larastan) can't resolve
            // __callStatic-forwarded methods, and toBase() gives the
            // real, directly-typed query builder method instead.
            $modelClass::query()->toBase()->upsert($records, $uniqueBy, $update);

            $count += count($records);
        }

        return $count;
    }
}
