# A birthplace code is judged by attributability, not by validity on the birth date

A codice fiscale's `BirthPlaceCode` reflects the comune as the tax authority recorded it **when the code was issued** (the system dates from 1973), not on the day the person was born: someone born in 1951 in San Felice (BZ, `H837`, ceased 1974-09-18) whose code was issued after the merger legitimately carries `I603` (Senale-San Felice, instituted 1974-09-18), and the Agenzia delle Entrate confirms such codes as valid. Until 3.0.1 `Validator`, `ParsedCodiceFiscale::birthPlace()` and the century tie-breaker all required an era-record valid *on* the birth date, rejecting (or returning no birthplace for) real, issued codes.

From 3.1 the rule is **attributability**: a code is accepted when at least one of its eras was valid on the birth date or on any later date, and `BirthPlaceNotValidOnDate` is reported only when every era of the code ended before the birth date - the one case in which nobody born on that date could have received it. The parsed birthplace is the *attributable era* (the era valid on the birth date, else the earliest one instituted after it). ANPR carries no merger genealogy, so the rule is purely temporal - "successor of" is deliberately not modelled.

## Considered options

- **Strict on-date check as opt-in** (lenient by default): rejected - the strict form is a false negative by construction, so no caller would want it, and it adds a configuration surface for nothing.
- **Dropping `BirthPlaceNotValidOnDate` entirely**, keeping only `UnknownBirthPlace`: rejected - "born after the comune ceased to exist" is a genuine, useful signal.

## Consequences

- `BirthPlaceRepository` gains `eras(BirthPlaceCode)`, returning every era-record of a code in chronological order; the attributability rule is computed once in the framework-agnostic core from that list, never in a repository implementation. `find(code, on)` and `search(name, on)` keep their "valid on that date" meaning.
- Adding a method to the public repository contract is a break for custom implementations, accepted as a 3.1.0 minor with an explicit CHANGELOG note rather than a 4.0.
- `ValidationError::BirthPlaceNotValidOnDate` keeps its name and `birth_place_not_valid_on_date` value (the code genuinely was not valid on that date); only its meaning narrows and the bundled `en`/`it` messages are reworded.
- ADR-0006's tie-breaker is amended to use attributability; this is a strict improvement (e.g. `H837` with year `26` now resolves to 1926 rather than the impossible 2026).
