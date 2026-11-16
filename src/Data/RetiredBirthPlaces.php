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
     * code => [name, province, name note, province note] (#124),
     * grouped by the ADR-0012 province rule applied.
     *
     * Names are 2.3.1's, checked against the Geoform/Groma "Allegato A -
     * Elenco Codici Catastali dei singoli Comuni" (2013). That list cuts
     * long names short and drops apostrophes, so a name it confirms keeps
     * 2.3.1's full spelling. Each province note names its source: ANPR,
     * a law, or OpenStreetMap (OSM) locating the place. A note starting
     * with "unconfirmed" rests on an identification no source settles,
     * and is listed for maintainer review.
     *
     * @var array<string, array{string, string, string, string}>
     */
    private const DOMESTIC = [
        // 1. A source records the place itself, under another code:
        //    the province of ANPR's last era for it.
        'B930' => ["CA' SANTO SPIRITO DELLA BAINSIZZA", 'GO', 'Allegato A', 'ANPR I355 Santo Spirito della Bainsizza'],
        'G380' => ['PAUGNANO', 'PL', 'Allegato A', 'ANPR F485 Paugnano'],
        'H237' => ['RUPINGRANDE', 'TS', 'Allegato A', 'ANPR F378 Rupingrande'],
        'M306' => ['COLLE ISARCO', 'BZ', 'maintainer (2026-11-16); not in Allegato A (2013)', 'ANPR B145 Colle Isarco; maintainer (2026-11-16): merged into Brennero in 1929'],
        'F554' => ['MONTEMAGGIORE', 'PU', 'Allegato A', 'maintainer (2026-11-16): Montemaggiore, PU; ANPR F555 Montemaggiore al Metauro'],
        'M222' => ['CANALE', 'CN', 'Allegato A', 'maintainer (2026-11-16): Canale, CN, whose current code is ANPR B573'],
        'M219' => ['BONO', 'SS', 'Allegato A', 'maintainer (2026-11-16): Bono, SS, whose current code is ANPR A977'],

        // 2. The province the place lies in today.
        'A362' => ['ARCAGNANO', 'MI', 'Allegato A', 'Legge 1862 n. 1009: merged into Carpiano (MI)'],
        'M205' => ['ZUNICO', 'MI', 'Allegato A', 'Legge 1862 n. 1009: merged into Carpiano (MI)'],
        'I149' => ['SAN SAVINO', 'CR', 'Allegato A', 'Legge 1862 n. 1009: merged into Due Miglia, now part of Cremona'],
        'C026' => ['CASCINE', 'CR', 'Allegato A', 'maintainer (2026-11-16): now part of Palazzo Pignano'],
        'D904' => ['GANDINI', 'CR', 'Allegato A', 'maintainer (2026-11-16): now part of Palazzo Pignano'],
        'D979' => ['GERE DEL PESCE', 'CR', 'Allegato A', 'maintainer (2026-11-16): now a frazione of Stagno Lombardo'],
        'I966' => ['STRACONDOLO', 'CR', 'Allegato A', 'maintainer (2026-11-16): now a frazione of Stagno Lombardo'],
        'M224' => ['CASANOVA DEL MORBASCO', 'CR', 'Allegato A', 'OSM: Casanova del Morbasco, Sesto ed Uniti'],
        'M252' => ['CREDERA CON ROVERETO', 'CR', 'Allegato A', 'maintainer (2026-11-16): Credera con Rovereto, CR'],
        'C042' => ['CASTAGNATE OLONA', 'VA', 'Allegato A', 'maintainer (2026-11-16): a variant of Castegnate Olona (ISTAT C054), now part of Castellanza'],
        'E383' => ['JERAGO', 'VA', 'Allegato A', 'OSM: Jerago, Jerago con Orago'],
        'E384' => ['JERAGO CON BESNATE', 'VA', 'Allegato A', 'OSM: Jerago, Jerago con Orago, and Besnate, both VA'],
        'F684' => ['MONTONATE', 'VA', 'Allegato A', 'OSM: Montonate, Mornago'],
        'G501' => ['PESSANO', 'MI', 'Allegato A', 'OSM: Pessano, Pessano con Bornago'],
        'D129' => ['CRANA', 'VB', 'Allegato A', 'maintainer (2026-11-16): now a frazione of Santa Maria Maggiore'],
        'D178' => ['CRODO MOZZIO', 'VB', 'Allegato A and the maintainer; 2.3.1 says CROGNALETO', 'ANPR D177 Crodo and F790 Mozzio; Crodo lies in VB'],
        'H051' => ['PRESTINONE', 'VB', 'Allegato A', 'OSM: Prestinone, Craveggia'],
        'I366' => ['SANTO STEFANO AL MARE CON CASTELLARO', 'IM', 'Allegato A', 'ANPR I365 Santo Stefano al Mare, which has no era from 1928 to 1954'],
        'L600' => ['VALLECROSIA CON SAN BIAGIO DELLA CIMA', 'IM', 'Allegato A', 'ANPR L599 Vallecrosia'],
        'E005' => ['GHILARZA-ABBASANTA', 'OR', 'Allegato A', 'ANPR E004 Ghilarza and A007 Abbasanta'],
        'E097' => ['GORIANO VALLI', 'AQ', 'Allegato A', 'OSM: Goriano Valli, Tione degli Abruzzi'],
        'I055' => ["SAN NICANDRO D'AQUILA", 'AQ', 'Allegato A', "maintainer (2026-11-16): now a frazione of Prata d'Ansidonia"],
        'D687' => ['FOPPA', 'PV', 'Allegato A', 'maintainer (2026-11-16): Foppa, PV'],
        'H993' => ['SAN MARTINO IN VALLE', 'FI', 'Allegato A', "unconfirmed: location not established; Greve in Chianti is OSM's only match"],
        'M215' => ['BALBIDO', 'TN', 'Allegato A', 'OSM: Balbido, Bleggio Superiore'],
        'M216' => ['BALLINO', 'TN', 'Allegato A', 'OSM: Ballino, Fiavè'],
        'M217' => ['BARCESINO', 'TN', 'Allegato A', 'OSM: Barcesino, Ledro'],
        'M218' => ['BONDONE STORO', 'TN', 'Allegato A', 'OSM: Storo; Bondone, its neighbour, is TN too'],
        'M220' => ['BRANCOLINO', 'TN', 'Allegato A', 'OSM: Brancolino, Nogaredo'],
        'M221' => ['BRUSINO', 'TN', 'Allegato A', 'OSM: Brusino, Cavedine'],
        'M223' => ['CARES', 'TN', 'Allegato A', 'OSM: Cares, Comano Terme'],
        'M225' => ['CASTELPIETRA', 'TN', 'Allegato A', 'maintainer (2026-11-16): now part of Primiero San Martino di Castrozza'],
        'M226' => ['CAVRASTO', 'TN', 'Allegato A', 'OSM: Cavrasto, Bleggio Superiore'],
        'M227' => ['COMIGHELLO', 'TN', 'Allegato A', 'OSM: Comighello, Comano Terme'],
        'M228' => ['DASINDO', 'TN', 'Allegato A', 'OSM: Dasindo, Comano Terme'],
        'M229' => ['DUVREDO', 'TN', 'Allegato A', 'OSM: Duvredo, Comano Terme'],
        'M230' => ['FAVRIO', 'TN', 'Allegato A', 'OSM: Favrio, Fiavè'],
        'M231' => ['GODENZO', 'TN', 'Allegato A', 'OSM: Godenzo, Comano Terme'],
        'M232' => ["LAGUNA MUSTE'", 'TN', 'Allegato A', 'OSM: Laguna-Mustè, Dro'],
        'M233' => ['LARIDO', 'TN', 'Allegato A', 'OSM: Larido, Bleggio Superiore'],
        'M234' => ['LASES', 'TN', 'Allegato A', 'OSM: Lases, Lona-Lases'],
        'M235' => ['LONA', 'TN', 'Allegato A', 'OSM: Lona, Lona-Lases'],
        'M236' => ['MADICE', 'TN', 'Allegato A', 'OSM: Madice, Bleggio Superiore'],
        'M237' => ['MALA', 'TN', 'Allegato A', "OSM: Mala, Sant'Orsola Terme"],
        'M238' => ['MENAS', 'TN', 'Allegato A', 'OSM: Menas, Mezzana'],
        'M239' => ['MONTEVACCINO', 'TN', 'Allegato A', 'OSM: Montevaccino, Trento'],
        'M240' => ["ORTISE'", 'TN', 'Allegato A', 'OSM: Ortisè, Mezzana'],
        'M241' => ['POIA', 'TN', 'Allegato A', 'OSM: Poia, Comano Terme'],
        'M242' => ['RANGO', 'TN', 'Allegato A', 'Tuttitalia: Balbido-Rango, Bleggio Superiore'],
        'M243' => ['RONCHI DI ALA', 'TN', 'Allegato A', 'OSM: Ronchi, Ala'],
        'M244' => ['SACCO', 'TN', 'Allegato A', 'maintainer (2026-11-16): now a frazione of Rovereto (not Sacco, SA)'],
        'M245' => ['SAVIGNANO', 'TN', 'Allegato A', 'OSM: Savignano, Pomarolo'],
        'M246' => ['STRAVINO', 'TN', 'Allegato A', 'OSM: Stravino, Cavedine'],
        'M247' => ['TIGNERONE', 'TN', 'Allegato A', 'OSM: Tignerone, Comano Terme'],
        'M248' => ['VARANO', 'TN', 'Allegato A', 'OSM: Varano, Mori'],
        'M249' => ['VIGO CAVEDINE', 'TN', 'Allegato A', 'OSM: Vigo Cavedine, Cavedine'],
        'M250' => ['VIGO LOMASO', 'TN', 'Allegato A', 'OSM: Vigo Lomaso, Comano Terme'],
        'M251' => ['VILLAMONTAGNA', 'TN', 'Allegato A', 'OSM: Villamontagna, Trento'],

        // 3. Now outside Italy: the Italian province that administered
        //    it, with ANPR's historical sigla.
        'A141' => ['ALBARO VESCOVA', 'PL', 'Allegato A', 'maintainer (2026-11-16): Albaro Vescovà, now Skofije, Koper; Capodistria was PL (ANPR B665)'],
        'D507' => ['FASANA', 'PL', 'Allegato A', 'OSM: Fasana/Fazana, Istria; Istria was the province of Pola (ANPR G778 PL)'],
        'M296' => ["PIEMONTE D'ISTRIA", 'PL', 'Allegato A', 'Istria was the province of Pola (ANPR G915 Portole, E183 Grisignana: PL)'],
        'M293' => ['VEGLIA', 'FU', 'Allegato A', 'maintainer (2026-11-16): the former province of Fiume (ANPR sigla FU, as for M254 Valsantamarina)'],
        'M310' => ['SEBENICO', 'ZA', 'maintainer (2026-11-16); not in Allegato A (2013)', 'unconfirmed: Governatorato di Dalmazia 1941-1943 (maintainer (2026-11-16)); Zara rather than Spalato per mirrored Wikipedia pages; no ANPR comune'],
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
