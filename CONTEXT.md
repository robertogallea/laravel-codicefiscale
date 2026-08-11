# Laravel Codice Fiscale

Domain model for the 3.x rewrite of the Italian *codice fiscale* (tax code) parsing, generation, and validation package.

## Language

**BirthPlaceCode**:
The 4-character cadastral code embedded in a codice fiscale's positions 12-15 (e.g. `H501` for Roma). Assigned by the Italian tax authority; distinct from — and never equal to — the ISTAT code.
_Avoid_: place code, city code, comune code

**BirthPlace**:
A single time-bounded record describing a `BirthPlaceCode` during one era: name, province, ISTAT code, and the `[validFrom, validTo)` window during which that name/province combination held. A municipality that renamed or changed province produces multiple `BirthPlace` records sharing the same `BirthPlaceCode`, one per era.
_Avoid_: City, Municipality, Comune (as the class/API name — "comune" is fine in prose)

**Domestic birthplace**:
A `BirthPlace` for an Italian comune, sourced from ANPR's comuni archive. Has a real `[validFrom, validTo)` history, a province, and an ISTAT code.

**Omocodia**:
The condition where a computed codice fiscale collides with one already assigned to someone else, resolved by substituting a subset of 7 fixed digit positions (year digits, day digits, birthplace-code digits) with corresponding letters via a fixed digit→letter map, then recomputing the check character. Any of the 2⁷ = 128 subsets of those 7 positions may be substituted independently — omocodia is a per-position combination, not a cumulative single-dimension "level."
_Avoid_: homograph

**Canonical form**:
The one member of a person's 128 possible codice fiscali where none of the 7 omocodia-sensitive positions are letter-substituted. Reversing substitutions back to digits is a pure mechanical transform on the string itself — it needs no repository or generator.
_Avoid_: original code, base code

**Reference-date resolution**:
The default interpretation of a codice fiscale's two-digit birth year as the most plausible complete birth date as of a specified reference date. A candidate birth date after the reference date is not plausible. When both candidates are plausible, a `BirthPlaceCode` valid for only one candidate date selects that date; otherwise the younger candidate is selected. The codice fiscale itself remains inherently ambiguous; callers with additional knowledge may select a different candidate.

**Plausible birth date**:
A candidate date produced while resolving a two-digit birth year that is not after the reference date and does not imply an age over the configured maximum. When neither candidate is plausible, the default resolution has no birth date.

**Foreign birthplace**:
A `BirthPlace` for a country (`Z`-prefixed `BirthPlaceCode`), sourced from MAECI's stati-esteri table. Has an ISO 3166-1 alpha-3 code and no province; its `[validFrom, validTo)` is currently always maximally wide because the source table carries no genuine historical data — a country whose meaning changed over time (e.g. a code that once denoted one state and now denotes its successor) is not distinguishable by date in 3.0.
