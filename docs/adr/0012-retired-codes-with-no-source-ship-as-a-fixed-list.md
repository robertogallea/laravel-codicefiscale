# Retired birthplace codes that no downloadable source carries ship as a fixed list in the package

ANPR and MAECI are snapshots that drop retired birthplace codes (ceased states such as `Z135` USSR, retired codes of recoded countries such as `Z136` Georgia, places in the `M215`–`M252` block that survive only as frazioni, most of them in Trentino), yet codici fiscali issued with them remain valid and 2.3.1 resolved them; v2 parity is a hard requirement (#122, #123, #124). For codes that **no source `codice-fiscale:update-places` downloads** carries, the package ships a fixed list as a plain PHP data class in the framework-agnostic core, which the Laravel importer writes into `municipalities`/`foreign_countries` on every `update-places` run, so lookups, `eras()` and `search()` treat them like any other row.

This is not the case ADR-0003 rules out. ADR-0003's risk is redistributing a *converted copy of a live government dataset* with unverified terms; these entries are closed historical facts — codes that are retired, never reassigned, will never change, and appear in no dataset at all — and 2.x shipped the same facts for years. Unlike ADR-0007's ten Faker facts, the list is bounded by a rule rather than a number.

## The admission rule

An entry belongs in the list only when **both** hold:

1. it is a retired birthplace code, and
2. no source `update-places` downloads carries it.

When a downloaded source also carries a code, the source wins. For foreign codes, the list is written before the sources on the same `(code, valid_from)` key, so the source's row overwrites the bundled one. For domestic codes, a source's eras never share a `valid_from` with the bundled row, so the bundled row is dropped whenever any domestic source carries the code. Such an entry no longer meets rule 2 and is removed from the list in a later release.

Each entry records a source note. The 2.3.1 list alone is not enough (it contains plain errors, e.g. `B411`/`B412` shifted by one row, and `D178` named CROGNALETO instead of CRODO MOZZIO), so each code→name pairing is confirmed independently where a source is reachable: the Agenzia delle Entrate archive search, an ISTAT report, or, for domestic codes, the maintainer or the Geoform/Groma "Allegato A – Elenco Codici Catastali dei singoli Comuni" (2013), which confirms 68 of the 70 domestic entries; the maintainer confirmed the other two (`M306` Colle Isarco, `M310` Sebenico). Allegato A cuts names at about 20 characters and drops apostrophes, so a confirmed entry keeps 2.3.1's full spelling unless Allegato A gives a different name. An entry no such source confirms is marked `unconfirmed` and listed for maintainer review in the pull request that adds it; it ships, because v2 parity needs the code to resolve, but its name is known to rest on 2.3.1 alone.

## Domestic entries (#124)

There are 70 domestic entries, in three groups. Most of the 28 scattered codes (including `M306` Colle Isarco) are comuni from mergers ANPR omits. Some are from the 1860s (`M205` Zunico, merged into Carpiano in 1862), others from the 1920s–1950s (`I366` Santo Stefano al Mare con Castellaro, `E005` Ghilarza-Abbasanta). Then there is the `M215`–`M252` block, whose places survive only as frazioni. Most of them are in Trentino (`M215` Balbido, now in Bleggio Superiore), but two are near Cremona (`M224` Casanova del Morbasco, `M252` Credera con Rovereto). The block also holds two second codes of comuni that still exist under their ANPR code (`M219` Bono, SS, `A977`; `M222` Canale, CN, `B573`), as `M306` is for Colle Isarco (`B145`). Finally there are places in territory now outside Italy (`M293` Veglia, `M296` Piemonte d'Istria, `M310` Sebenico, `B930` Ca' Santo Spirito della Bainsizza). No source gives their dates, and only some of them ever had an ISTAT code.

- **Era**: maximally wide, `validFrom` 1861-03-17 and open-ended, for the same reason ADR-0014 gives for foreign rows. A curated end date that turns out wrong falsely rejects a real codice fiscale, and people born in the 1928–1950 merged comuni can still be alive and hold these codes. So a domestic entry is always attributable, unlike ISTAT-dated suppressed comuni (ADR-0015). A side effect is that `find(code, today)` and `search(name, today)` return these entries as if they were current comuni (`M219` Bono next to `A977` Bono, both SS). Core never uses either method (it reads `eras()`), and the foreign fixed list already behaves the same way (`Z135` under `find('Z135', today)`).
- **Province** is never null. Like ADR-0013's country code, it is assigned by a fixed rule, in priority order:
  1. the province a source records for the place, or for the comune that absorbed it (`B930` → `GO`, as ANPR records `I355` Santo Spirito della Bainsizza; `M219` → `SS`, as ANPR records `A977` Bono);
  2. otherwise, for a place now in Italy, the province it lies in today (`M215` Balbido → `TN`, `M224` Casanova del Morbasco → `CR`, `D178` → `VB`);
  3. otherwise, for a place now outside Italy, the Italian province that administered it, using ANPR's historical sigla (`M293` Veglia → `FU`, `M296` → `PL`).

  Every entry records a source note for its province, as for its name, and a province no source confirms is marked `unconfirmed`. That includes a province resting on an identification of the place that no source settles (`H993` San Martino in Valle, whose location is not established). `M310` Sebenico has no ANPR sigla to reuse, so its province rests on research and is marked `unconfirmed`.
- **ISTAT code**: the empty string. `DomesticBirthPlace::istatCode()` keeps its `string` type, and an empty value means "no ISTAT code; fixed-list entry". Making it `?string` was rejected for the same reason ADR-0013 rejected `?CountryCode`: it would break existing callers' static analysis. A placeholder such as `000000` was rejected because it can be mistaken for a real code.

## Considered options

- **Download the list from this repository or a separate data package at `update-places` time**: rejected — the same redistribution act as bundling, plus a network dependency.
- **Scrape the Agenzia delle Entrate archive**: rejected — no known bulk download; an interactive search with unknown terms is a fragile import source.
- **Read-time fallback repository** consulted when the database misses: rejected — retired codes would resolve before `update-places` ever ran while current ones would not, and `search()` would need a second merge path.
- **Accept a parity gap** for codes with no source: rejected — v2 parity is a hard requirement.
- **Bundle everything 2.3.1 had that 3.x lacks**, including codes ISTAT does publish: rejected — a programmatically derived bulk list is exactly what ADR-0003 and ADR-0007 warn against.
