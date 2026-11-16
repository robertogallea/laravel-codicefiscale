<?php

namespace Robertogallea\CodiceFiscale\Data;

/**
 * Retired birthplace codes that no downloadable source carries
 * (ADR-0012): codes of ceased states, territories and dependencies,
 * and retired codes of countries later recoded. Codici fiscali issued
 * with them remain valid, so they must still resolve; ANPR, MAECI and
 * ISTAT all omit them. A closed set of historical facts, never a
 * snapshot of a downloadable dataset - an entry leaves this list as
 * soon as a source `codice-fiscale:update-places` downloads carries it.
 *
 * Names are those 2.3.1's default decoder returned. Source note for
 * every entry: **unconfirmed** - no independent public source (the
 * Agenzia delle Entrate archive search is not reachable as a public
 * page, ISTAT does not carry these codes) confirmed the code-to-name
 * pairing as of 2026-11-16 (#122).
 *
 * Country codes follow ADR-0013: (1) a retired code of a country that
 * still exists takes that country's current code; (2) a ceased entity
 * with its own former ISO alpha-3 code takes that code; (3) otherwise,
 * the code of the state the territory belongs to today.
 *
 * Eras are maximally wide, the same convention as MAECI rows
 * (ADR-0014): 1900-01-01, open-ended.
 */
final class RetiredBirthPlaces
{
    private const VALID_FROM = '1900-01-01';

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
