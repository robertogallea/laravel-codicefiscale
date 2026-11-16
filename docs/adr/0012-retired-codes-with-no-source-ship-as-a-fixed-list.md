# Retired birthplace codes that no downloadable source carries ship as a fixed list in the package

ANPR and MAECI are snapshots that drop retired birthplace codes (ceased states such as `Z135` USSR, retired codes of recoded countries such as `Z136` Georgia, Trentino municipalities in the `M215`–`M252` block), yet codici fiscali issued with them remain valid and 2.3.1 resolved them; v2 parity is a hard requirement (#122, #123, #124). For codes that **no source `codice-fiscale:update-places` downloads** carries, the package ships a fixed list as a plain PHP data class in the framework-agnostic core, which the Laravel importer writes into `municipalities`/`foreign_countries` on every `update-places` run, so lookups, `eras()` and `search()` treat them like any other row.

This is not the case ADR-0003 rules out. ADR-0003's risk is redistributing a *converted copy of a live government dataset* with unverified terms; these entries are closed historical facts — codes that are retired, never reassigned, will never change, and appear in no dataset at all — and 2.x shipped the same facts for years. Unlike ADR-0007's ten Faker facts, the list is bounded by a rule rather than a number.

## The admission rule

An entry belongs in the list only when **both** hold:

1. it is a retired birthplace code, and
2. no source `update-places` downloads carries it.

When a downloaded source also carries a code, the source wins and the bundled entry is skipped for that code; such an entry no longer meets rule 2 and is removed from the list in a later release. Each entry records a source note: the 2.3.1 list alone is not enough (it contains plain errors, e.g. `B411`/`B412` shifted by one row), so each code→name pairing is confirmed independently, by the Agenzia delle Entrate archive search or an ISTAT report.

## Considered options

- **Download the list from this repository or a separate data package at `update-places` time**: rejected — the same redistribution act as bundling, plus a network dependency.
- **Scrape the Agenzia delle Entrate archive**: rejected — no known bulk download; an interactive search with unknown terms is a fragile import source.
- **Read-time fallback repository** consulted when the database misses: rejected — retired codes would resolve before `update-places` ever ran while current ones would not, and `search()` would need a second merge path.
- **Accept a parity gap** for codes with no source: rejected — v2 parity is a hard requirement.
- **Bundle everything 2.3.1 had that 3.x lacks**, including codes ISTAT does publish: rejected — a programmatically derived bulk list is exactly what ADR-0003 and ADR-0007 warn against.
