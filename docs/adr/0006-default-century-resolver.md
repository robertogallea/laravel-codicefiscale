# Century ambiguity gets a built-in default resolver instead of forcing every caller to supply one

A codice fiscale's two-digit birth year is inherently ambiguous (e.g. `26` could mean 1926 or 2026); 2.x resolved this with a heuristic it itself acknowledged as broken. `Parser::parse()` applies `AgeBasedCenturyResolver(maxAge: 120)` automatically when no explicit `CenturyResolver` is supplied, while still exposing `possibleBirthYears()` and accepting a swappable resolver for callers who need different behavior.

Most callers just want a birthdate and shouldn't be forced to reason about omocodia-era ambiguity on day one; a documented, swappable default is more honest than either silently guessing (2.x) or refusing to resolve at all.
