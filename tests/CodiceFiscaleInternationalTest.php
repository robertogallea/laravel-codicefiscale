<?php

namespace Tests;
use PHPUnit\Framework\Attributes\Test;
use robertogallea\LaravelCodiceFiscale\CodiceFiscale;

class CodiceFiscaleInternationalTest extends TestCase
{
    #[Test]
    public function it_detects_international_cf_when_place_code_starts_with_Z()
    {
        // USA (Z404)
        $cfString = CodiceFiscale::generate('Mario', 'Rossi', '1990-01-02', 'Z404', 'M');

        $cf = new CodiceFiscale();
        $cf->parse($cfString);

        $this->assertTrue($cf->isInternational());
        $this->assertFalse($cf->isItalian());
    }

    #[Test]
    public function it_detects_italian_cf_when_place_code_does_not_start_with_Z()
    {
        // Roma (H501)
        $cfString = CodiceFiscale::generate('Mario', 'Rossi', '1990-01-02', 'H501', 'M');

        $cf = new CodiceFiscale();
        $cf->parse($cfString);

        $this->assertFalse($cf->isInternational());
        $this->assertTrue($cf->isItalian());
    }
}
