# Municipality and country records are two separate tables, not one wide table

Domestic (Italian) birthplace records carry province/ISTAT-code/real validity history from ANPR; foreign records carry an ISO 3166-1 alpha-3 code and no real history, from MAECI — different shapes, different source files, different update cadence. 3.0 uses two Eloquent models/tables (`Municipality`, `ForeignCountry`), unified behind one `CompositeBirthPlaceRepository`, mapping to two DTOs (`DomesticBirthPlace`, `ForeignBirthPlace`) that implement a shared `BirthPlace` interface.

A single wide table would null out half its columns depending on domestic vs. foreign; two focused tables map directly onto the two focused source files and avoid modeling a field (`country`) that's meaningless for the majority (domestic) case.
