# Birthplace reference data is never bundled in the package; it's downloaded on demand

The richer `BirthPlace` model (municipality/country history) needs ANPR's comuni archive and MAECI's stati-esteri table. Neither source states an explicit redistribution license. The package ships no committed snapshot of this data — a `codice-fiscale:update-places` artisan command downloads both sources directly into the consuming application's own storage (SQLite file), on demand.

Bundling a converted copy of government data with unclear redistribution terms into every Packagist release would be redistributing it under our own unverified authority; having each installation fetch its own copy sidesteps that risk entirely, at the cost of a one-time setup step per install.

**Considered options**: Bundle a snapshot anyway, accepting the licensing ambiguity — rejected because it's an unnecessary and easily-avoided risk for a public open-source package.
