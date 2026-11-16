<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

/**
 * How codice-fiscale:update-places retries a source download: a few
 * attempts, a pause between them, and only for failures that a retry
 * can fix - a dropped connection, a server error, or a Situas report
 * page cut short. A client error (4xx) fails at once.
 */
final class DownloadRetryPolicy
{
    public const ATTEMPTS = 3;

    public const DELAY_MILLISECONDS = 2000;

    public static function isTransient(\Throwable $exception): bool
    {
        return $exception instanceof ConnectionException
            || $exception instanceof IncompleteReportPage
            || ($exception instanceof RequestException && $exception->response->serverError());
    }
}
