<?php

namespace Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Robertogallea\CodiceFiscale\Laravel\Casts\CodiceFiscaleCast;

/**
 * A minimal model on the host app's own default connection - not our
 * dedicated 'codicefiscale' connection - since CodiceFiscaleCast is
 * meant to be used on a consuming application's own models.
 */
final class CastTestModel extends Model
{
    public $timestamps = false;

    protected $fillable = ['fiscal_code'];

    protected function casts(): array
    {
        return [
            'fiscal_code' => CodiceFiscaleCast::class,
        ];
    }
}
