# Retired birthplace codes that no downloadable source carries ship as a fixed list in the package

ANPR and MAECI are snapshots that drop retired birthplace codes (ceased states such as `Z135` USSR, retired codes of recoded countries such as `Z136` Georgia, Trentino municipalities in the `M215`–`M252` block), yet codici fiscali issued with them remain valid and 2.3.1 resolved them; v2 parity is a hard requirement (#122, #123, #124). For codes that **no source `codice-fiscale:update-places` downloads** carries, the package ships a fixed list as a plain PHP data class in the framework-agnostic core, which the Laravel importer writes into `municipalities`/`foreign_countries` on every `update-places` run, so lookups, `eras()` and `search()` treat them like any other row.

This is not the case ADR-0003 rules out. ADR-0003's risk is redistributing a *converted copy of a live government dataset* with unverified terms; these entries are closed historical facts — codes that are retired, never reassigned, will never change, and appear in no dataset at all — and 2.x shipped the same facts for years. Unlike ADR-0007's ten Faker facts, the list is bounded by a rule rather than a number.

## The admission rule

An entry belongs in the list only when **both** hold:

1. it is a retired birthplace code, and
2. no source `update-places` downloads carries it.

When a downloaded source also carries a code, the source wins. For foreign codes, the list is written before the sources on the same `(code, valid_from)` key, so the source's row overwrites the bundled one. For domestic codes, a source's eras never share a `valid_from` with the bundled row, so the bundled row is dropped whenever any domestic source carries the code. Such an entry no longer meets rule 2 and is removed from the list in a later release.

Each entry records a source note. The 2.3.1 list alone is not enough (it contains plain errors, e.g. `B411`/`B412` shifted by one row, and `D178` named CROGNALETO instead of CRODO MOZZIO), so each code→name pairing is confirmed independently where a source is reachable: the Agenzia delle Entrate archive search, an ISTAT report, or, for domestic codes, the Geoform/Groma "Allegato A – Elenco Codici Catastali dei singoli Comuni" (2013), which confirms 68 of the 70 domestic entries. Allegato A cuts names at about 20 characters and drops apostrophes, so a confirmed entry keeps 2.3.1's full spelling unless Allegato A gives a different name. An entry no such source confirms is marked `unconfirmed` and listed for maintainer review in the pull request that adds it; it ships, because v2 parity needs the code to resolve, but its name is known to rest on 2.3.1 alone.

## Domestic entries (#124)

There are 70 domestic entries, in three groups. Most of the 28 scattered codes (including `M306` Colle Isarco) are comuni from the 1920s–1950s mergers that ANPR omits (`I366` Santo Stefano al Mare con Castellaro, `E005` Ghilarza-Abbasanta). Then there is the `M215`–`M252` Trentino block, whose places survive only as frazioni (`M215` Balbido, now in Bleggio Superiore). Finally there are places in territory now outside Italy (`M293` Veglia, `M296` Piemonte d'Istria, `M310` Sebenico, `B930` Ca' Santo Spirito della Bainsizza). No source gives their dates, and only some of them ever had an ISTAT code.

- **Era**: maximally wide, `validFrom` 1861-03-17 and open-ended, for the same reason ADR-0014 gives for foreign rows. A curated end date that turns out wrong falsely rejects a real codice fiscale, and people born in the 1928–1950 merged comuni can still be alive and hold these codes. So a domestic entry is always attributable, unlike ISTAT-dated suppressed comuni (ADR-0015). A side effect is that `find(code, today)` and `search(name, today)` return these entries as if they were current comuni (`M219` Bono next to Bono SS). Core never uses either method (it reads `eras()`), and the foreign fixed list already behaves the same way (`Z135` under `find('Z135', today)`).
- **Province** is never null. Like ADR-0013's country code, it is assigned by a fixed rule, in priority order:
  1. the province a source records for the place, or for the comune that absorbed it (`B930` → `GO`, as ANPR records `I355` Santo Spirito della Bainsizza);
  2. otherwise, for a place now in Italy, the province it lies in today (Trentino block → `TN`, `D178` → `VB`);
  3. otherwise, for a place now outside Italy, the Italian province that administered it, using ANPR's historical sigla (`M293` Veglia → `FU`, `M296` → `PL`).

  A province that no source confirms is marked `unconfirmed`, as names are. `M310` Sebenico has no ANPR sigla to reuse, so its province rests on research and is marked `unconfirmed`.
- **ISTAT code**: the empty string. `DomesticBirthPlace::istatCode()` keeps its `string` type, and an empty value means "no ISTAT code; fixed-list entry". Making it `?string` was rejected for the same reason ADR-0013 rejected `?CountryCode`: it would break existing callers' static analysis. A placeholder such as `000000` was rejected because it can be mistaken for a real code.

## Considered options

- **Download the list from this repository or a separate data package at `update-places` time**: rejected — the same redistribution act as bundling, plus a network dependency.
- **Scrape the Agenzia delle Entrate archive**: rejected — no known bulk download; an interactive search with unknown terms is a fragile import source.
- **Read-time fallback repository** consulted when the database misses: rejected — retired codes would resolve before `update-places` ever ran while current ones would not, and `search()` would need a second merge path.
- **Accept a parity gap** for codes with no source: rejected — v2 parity is a hard requirement.
- **Bundle everything 2.3.1 had that 3.x lacks**, including codes ISTAT does publish: rejected — a programmatically derived bulk list is exactly what ADR-0003 and ADR-0007 warn against.
