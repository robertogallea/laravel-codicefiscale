# Two-digit birth years resolve from complete dates and historical birthplace evidence

A codice fiscale's two-digit birth year is inherently ambiguous (e.g. `26` could mean 1926 or 2026). The default parser interpretation is a **reference-date resolution**: it considers the two complete candidate birth dates as of a reference date, which defaults to today. A date after the reference date, over the configured maximum age (calculated as an exact age), or not a valid calendar date is not plausible.

When two plausible candidates remain, historical `BirthPlaceCode` validity is used as a tie-breaker: a code valid for exactly one candidate date selects that date. If the birthplace history does not distinguish them, the younger candidate is selected. When no plausible candidate remains, no birth date or birth year is resolved.

`Parser::parse()` will use a swappable `BirthDateResolver`, returning `?DateTimeImmutable` from a `BirthDateResolutionContext` that supplies the candidate dates, reference date, `BirthPlaceCode`, and `BirthPlaceRepository`. `birthYear()` and `birthDate()` are nullable; `possibleBirthYears()` remains available to callers that need the raw century ambiguity or have stronger domain knowledge.

The prior `CenturyResolver` contract accepted only two years and returned an `int`; it could not apply complete-date plausibility, distinguish leap-day candidates, report no plausible resolution, or use birthplace evidence. Since 3.x is not yet stable, replacing it is preferable to preserving a misleading extension point. A documented, swappable default remains more honest than silently guessing, while the nullable result avoids inventing a birth date where the codice fiscale alone gives no plausible answer.
