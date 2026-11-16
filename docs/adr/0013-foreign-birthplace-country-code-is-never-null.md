# A foreign birthplace's country code is never null, even for entities with no ISO code of their own

`ForeignBirthPlace::country()` returns a non-null `CountryCode` (ADR-0005 described it as an ISO 3166-1 alpha-3 code from MAECI). Retired foreign codes from the fixed list (ADR-0012) include entities that never had an alpha-3 code of their own (`Z800` Dipendenze canadesi, `Z201` Arabia Meridionale Federazione, `Z238` Ryukyu). Rather than making `country()` nullable, every entry is assigned a code by a fixed rule, in priority order:

1. A retired code of a country that still exists → that country's current code (`Z136` → `GEO`, `Z230` → `MYS`).
2. A ceased entity that had its own former ISO alpha-3 code → that code (`SUN`, `YUG`, `CSK`, `DDR`, `SCG`, `YMD`, `PCZ`, `AFI`).
3. Otherwise → the state the territory belongs to **today** (`Z201` → `YEM`, `Z238` → `JPN`, `Z303` Basutoland → `LSO`, `Z800` → `CAN`).

`country()` therefore means "the most relevant ISO alpha-3 code", not "this entity's own current ISO 3166-1 code".

## Considered options

- **`?CountryCode`**: rejected — honest, but `->country()->value()` would fail at runtime for existing 3.x callers and break their static analysis, pushing v2 parity to 4.0.
- **A separate DTO without `country()`** (e.g. `HistoricalForeignBirthPlace`): rejected — callers checking `instanceof ForeignBirthPlace` would silently stop matching these codes, a worse break than a type error.
- **The state that administered the territory while the code was in use** for rule 3 (`Z201` → `GBR`, `Z238` → `USA`): rejected — it needs dated historical research per entry, the answer often changed during a code's lifetime, and callers mostly map to present-day geography.
