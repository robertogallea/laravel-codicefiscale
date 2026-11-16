<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import;

/**
 * A Situas report page that arrived with a 2xx status but not as a
 * complete JSON page - typically cut short by a dropped connection.
 */
final class IncompleteReportPage extends \RuntimeException
{
}
