<?php

namespace Robertogallea\CodiceFiscale\Data;

/**
 * Retired birthplace codes that no downloadable source carries
 * (ADR-0012): domestic codes ANPR and ISTAT omit (see domestic()), and
 * codes of ceased states, territories and dependencies and retired
 * codes of countries later recoded. Codici fiscali issued with them
 * remain valid, so they must still resolve. A closed set of
 * historical facts, never a snapshot of a downloadable dataset - an
 * entry leaves this list as soon as a source
 * `codice-fiscale:update-places` downloads carries it.
 *
 * Foreign names are those 2.3.1's default decoder returned. Source
 * note for every foreign entry: **unconfirmed** - no independent
 * public source (the Agenzia delle Entrate archive search is not
 * reachable as a public page, ISTAT does not carry these codes)
 * confirmed the code-to-name pairing as of 2026-11-16 (#122).
 *
 * Country codes follow ADR-0013: (1) a retired code of a country that
 * still exists takes that country's current code; (2) a ceased entity
 * with its own former ISO alpha-3 code takes that code; (3) otherwise,
 * the code of the state the territory belongs to today.
 *
 * Foreign eras are maximally wide, the same convention as MAECI rows
 * (ADR-0014): 1900-01-01, open-ended.
 */
final class RetiredBirthPlaces
{
    private const VALID_FROM = '1900-01-01';

    private const DOMESTIC_VALID_FROM = '1861-03-17';

    /**
     * code => [name, ISO alpha-3], grouped by the ADR-0013 rule applied.
     *
     * @var array<string, array{string, string}>
     */
    private const FOREIGN = [
        // Retired codes of former Soviet republics and Malaysia, later recoded.
        'Z136' => ['GEORGIA', 'GEO'],
        'Z137' => ['ARMENIA', 'ARM'],
        'Z141' => ['AZERBAIGIAN', 'AZE'],
        'Z142' => ['KIRGHIZISTAN', 'KGZ'],
        'Z143' => ['UZBEKISTAN', 'UZB'],
        'Z147' => ['TAGIKISTAN', 'TJK'],
        'Z151' => ['TURKMENISTAN', 'TKM'],
        'Z152' => ['KAZAKISTAN', 'KAZ'],
        'Z230' => ['MALAYSIA', 'MYS'],

        // Ceased entities with their own former ISO 3166 alpha-3 code.
        'Z239' => ['SIKKIM', 'SKM'],
        'Z244' => ['VIETNAM DEL SUD', 'VNM'],
        'Z245' => ['VIETNAM DEL NORD', 'VDR'],
        'Z346' => ['TERRITORIO FRANCESE DEGLI AFAR E DEGLI ISSA', 'AFI'],
        'Z517' => ['PANAMA ZONA DEL CANALE', 'PCZ'],
        'Z712' => ['MIDWAY (ISOLE)', 'MID'],

        // No ISO code of their own: the state the territory belongs to today.
        'Z124' => ['NORMANNE (ISOLE) O ISOLE DEL CANALE', 'GBR'],
        'Z201' => ['ARABIA MERIDIONALE FEDERAZIONE', 'YEM'],
        'Z202' => ['ARABIA MERIDIONALE PROTETTORATO', 'YEM'],
        'Z218' => ['GAZA (TERRITORIO DI)', 'PSE'],
        'Z238' => ['RYUKYU (ISOLE)', 'JPN'],
        'Z303' => ['BASUTOLAND-SUD AFRICA BRITANNICO', 'LSO'],
        'Z304' => ['BECIUANIA-SUD AFRICA BRITANNICO', 'BWA'],
        'Z323' => ['IFNI', 'MAR'],
        'Z350' => ['TANGANICA', 'TZA'],
        'Z362' => ['SAHARA MERIDIONALE', 'ESH'],
        'Z363' => ['SAHARA SETTENTRIONALE', 'ESH'],
        'Z364' => ['BOPHUTHATSWANA', 'ZAF'],
        'Z365' => ['TRANSKEI', 'ZAF'],
        'Z367' => ['CISKEI', 'ZAF'],
        'Z500' => ['ANTILLE BRITANNICHE', 'GBR'],
        'Z521' => ['ANTILLE BRITANNICHE', 'GBR'],
        'Z701' => ['CAROLINE (ISOLE)', 'FSM'],
        'Z707' => ['IRIAN OCCIDENTALE', 'IDN'],
        'Z708' => ['MACQUARIE (ISOLE)', 'AUS'],
        'Z721' => ['ISOLE CILENE (PASQUA E SALA Y GOMEZ)', 'CHL'],
        'Z800' => ['DIPENDENZE CANADESI', 'CAN'],
        'Z801' => ['DIPENDENZE NORVEGESI ARTICHE', 'NOR'],
        'Z802' => ['DIPENDENZE SOVIETICHE', 'RUS'],
        'Z900' => ['DIPENDENZE AUSTRALIANE', 'AUS'],
        'Z901' => ['DIPENDENZE BRITANNICHE', 'GBR'],
        'Z902' => ['DIPENDENZE FRANCESI', 'FRA'],
        'Z903' => ['DIPENDENZE NEOZELANDESI', 'NZL'],
        'Z904' => ['DIPENDENZE NORVEGESI ANTARTICHE', 'NOR'],
        'Z905' => ['DIPENDENZE STATUNITENSI', 'USA'],
        'Z906' => ['DIPENDENZE SUDAFRICANE', 'ZAF'],
    ];

    /**
     * code => [name, province], grouped by the ADR-0012 province rule
     * applied (#124). Names are 2.3.1's, confirmed against the
     * Geoform/Groma "Allegato A - Elenco Codici Catastali dei singoli
     * Comuni" (2013), which truncates long names and drops apostrophes;
     * entries it lacks, or names it contradicts, are noted inline.
     * Provinces come from the source named in each group's heading;
     * an inline "unconfirmed" means the place's location itself rests
     * on research that no source settles, for maintainer review.
     *
     * @var array<string, array{string, string}>
     */
    private const DOMESTIC = [
        // 1. A source records the place itself, under another code:
        //    the province of ANPR's last era for it.
        'B930' => ["CA' SANTO SPIRITO DELLA BAINSIZZA", 'GO'], // ANPR I355
        'G380' => ['PAUGNANO', 'PL'],                         // ANPR F485
        'H237' => ['RUPINGRANDE', 'TS'],                      // ANPR F378
        'M306' => ['COLLE ISARCO', 'BZ'],                     // ANPR B145; name unconfirmed (not in Allegato A)
        'F554' => ['MONTEMAGGIORE', 'PA'],                    // Montemaggiore Belsito (ANPR F553) until 1863; unconfirmed

        // 2. The province the place lies in today. The 1862 law that
        //    suppressed Arcagnano, Zunico (into Carpiano, MI) and San
        //    Savino (into Due Miglia, now Cremona) locates those three;
        //    OpenStreetMap locates the rest.
        'A362' => ['ARCAGNANO', 'MI'],
        'M205' => ['ZUNICO', 'MI'],
        'I149' => ['SAN SAVINO', 'CR'],
        'C026' => ['CASCINE', 'CR'],                          // Cascine Gandini, Palazzo Pignano; unconfirmed
        'D904' => ['GANDINI', 'CR'],                          // Cascine Gandini, Palazzo Pignano; unconfirmed
        'D979' => ['GERE DEL PESCE', 'CR'],                   // Gerre del Pesce, Stagno Lombardo
        'I966' => ['STRACONDOLO', 'CR'],                      // Straconcolo, Stagno Lombardo
        'M224' => ['CASANOVA DEL MORBASCO', 'CR'],
        'M252' => ['CREDERA CON ROVERETO', 'CR'],             // Credera Rubbiano
        'C042' => ['CASTAGNATE OLONA', 'VA'],                 // Castegnate, Castellanza; unconfirmed
        'E383' => ['JERAGO', 'VA'],
        'E384' => ['JERAGO CON BESNATE', 'VA'],
        'F684' => ['MONTONATE', 'VA'],
        'G501' => ['PESSANO', 'MI'],
        'D129' => ['CRANA', 'VB'],
        'D178' => ['CRODO MOZZIO', 'VB'],                     // 2.3.1 says CROGNALETO; Allegato A and the maintainer: CRODO MOZZIO
        'H051' => ['PRESTINONE', 'VB'],
        'I366' => ['SANTO STEFANO AL MARE CON CASTELLARO', 'IM'],
        'L600' => ['VALLECROSIA CON SAN BIAGIO DELLA CIMA', 'IM'],
        'E005' => ['GHILARZA-ABBASANTA', 'OR'],
        'E097' => ['GORIANO VALLI', 'AQ'],
        'I055' => ["SAN NICANDRO D'AQUILA", 'AQ'],
        'D687' => ['FOPPA', 'MB'],                            // unconfirmed: location not established (Desio is OSM's first match)
        'H993' => ['SAN MARTINO IN VALLE', 'FI'],             // unconfirmed: location not established (Greve in Chianti is OSM's only match)
        'M215' => ['BALBIDO', 'TN'],
        'M216' => ['BALLINO', 'TN'],
        'M217' => ['BARCESINO', 'TN'],
        'M218' => ['BONDONE STORO', 'TN'],
        'M219' => ['BONO', 'TN'],                             // unconfirmed: placed by its neighbours in the block
        'M220' => ['BRANCOLINO', 'TN'],
        'M221' => ['BRUSINO', 'TN'],
        'M222' => ['CANALE', 'TN'],                           // unconfirmed: placed by its neighbours in the block
        'M223' => ['CARES', 'TN'],
        'M225' => ['CASTELPIETRA', 'TN'],                     // unconfirmed: placed by its neighbours in the block
        'M226' => ['CAVRASTO', 'TN'],
        'M227' => ['COMIGHELLO', 'TN'],
        'M228' => ['DASINDO', 'TN'],
        'M229' => ['DUVREDO', 'TN'],
        'M230' => ['FAVRIO', 'TN'],
        'M231' => ['GODENZO', 'TN'],
        'M232' => ["LAGUNA MUSTE'", 'TN'],
        'M233' => ['LARIDO', 'TN'],
        'M234' => ['LASES', 'TN'],
        'M235' => ['LONA', 'TN'],
        'M236' => ['MADICE', 'TN'],
        'M237' => ['MALA', 'TN'],
        'M238' => ['MENAS', 'TN'],
        'M239' => ['MONTEVACCINO', 'TN'],
        'M240' => ["ORTISE'", 'TN'],
        'M241' => ['POIA', 'TN'],
        'M242' => ['RANGO', 'TN'],
        'M243' => ['RONCHI DI ALA', 'TN'],
        'M244' => ['SACCO', 'TN'],
        'M245' => ['SAVIGNANO', 'TN'],
        'M246' => ['STRAVINO', 'TN'],
        'M247' => ['TIGNERONE', 'TN'],
        'M248' => ['VARANO', 'TN'],
        'M249' => ['VIGO CAVEDINE', 'TN'],
        'M250' => ['VIGO LOMASO', 'TN'],
        'M251' => ['VILLAMONTAGNA', 'TN'],

        // 3. Now outside Italy: the Italian province that administered
        //    it, with ANPR's historical sigla (Istria: Pola; Veglia:
        //    Fiume, 1941-1943; Sebenico: Zara, 1941-1943).
        'A141' => ['ALBARO VESCOVA', 'PL'],                   // Skofije, Koper; unconfirmed
        'D507' => ['FASANA', 'PL'],
        'M296' => ["PIEMONTE D'ISTRIA", 'PL'],
        'M293' => ['VEGLIA', 'FU'],
        'M310' => ['SEBENICO', 'ZA'],                         // name and province unconfirmed (not in Allegato A; no ANPR comune)
    ];

    /**
     * Retired domestic codes (#124): comuni ANPR omits - mostly
     * 1860s and 1920s-1950s mergers - places that survive only as
     * frazioni (the M215-M252 block), and places in territory ceded
     * after 1947. No source dates them, so each gets one maximally
     * wide era, 1861-03-17 (the start of ANPR's archive), open-ended,
     * and is always attributable; none has an ISTAT code, so
     * istatCode() is '' (ADR-0012).
     *
     * @return list<DomesticBirthPlace>
     */
    public static function domestic(): array
    {
        $validFrom = new \DateTimeImmutable(self::DOMESTIC_VALID_FROM);
        $places = [];

        foreach (self::DOMESTIC as $code => [$name, $province]) {
            $places[] = new DomesticBirthPlace(
                code: BirthPlaceCode::from($code),
                name: $name,
                province: $province,
                istatCode: '',
                validFrom: $validFrom,
            );
        }

        return $places;
    }

    /** @return list<ForeignBirthPlace> */
    public static function foreign(): array
    {
        $validFrom = new \DateTimeImmutable(self::VALID_FROM);
        $places = [];

        foreach (self::FOREIGN as $code => [$name, $country]) {
            $places[] = new ForeignBirthPlace(
                code: BirthPlaceCode::from($code),
                name: $name,
                country: CountryCode::from($country),
                validFrom: $validFrom,
            );
        }

        return $places;
    }
}
