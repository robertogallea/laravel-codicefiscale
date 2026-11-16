# ISTAT's suppressed-comuni report supplements ANPR as a second domestic source

ANPR's comuni archive lacks 489 municipality codes that 2.3.1 resolved, all of them comuni suppressed between 1866 and 1923 (`A073` Agliate, merged into Carate Brianza in 1869; `D035` Corpi Santi di Milano), so 3.x reported `unknown_birth_place` for them (#124). ISTAT's Situas report 128 "Elenco comuni soppressi e non ricostituiti" (`reportspooljson?pfun=128&pdata=17/03/1861`) carries every one of them, with cadastral code, ISTAT code, province and suppression date. `codice-fiscale:update-places` downloads it alongside ANPR, and its rows fill only the codes ANPR lacks.

## The merge rule

**ANPR wins at the code level**: a report-128 code that ANPR carries is skipped entirely, never merged era by era. The two sources disagree on the end date for 1,432 of the 1,945 codes they share. Report 128 also gives no institution date. A last-writer-wins upsert on `(code, valid_from)`, which is what ADR-0014 does for foreign rows, would therefore overwrite ANPR eras or add phantom ones next to them. A code split between two successors appears once per successor, with the same date (`A156` Albiano Magra → Aulla and Podenzana), and is imported once.

## Eras are dated, so these codes are known but not attributable

Each row becomes one era: `validFrom` 1861-03-17 (the start of ANPR's archive, since ISTAT gives no institution date) and `validTo` the date the suppression took effect. Two-digit birth years resolve only to 19xx or 20xx, and in practice to no earlier than about 1906 (ADR-0006's maximum age). So for every one of these codes except the single 1923 suppression, `Validator` reports `birth_place_not_valid_on_date` instead of `unknown_birth_place`, and `ParsedCodiceFiscale::birthPlace()` stays null. That is what ADR-0011 intends: nobody born when such a codice fiscale could be issued was born there. It is also how 3.x already treats ANPR's own 52 codes suppressed before 1900 (`A659` Bargano, 1878).

v2 parity for these codes therefore means **the code is known** (`eras()` is non-empty), not that a codice fiscale carrying it validates. 2.3.1 accepted such codici fiscali only because it never compared a birthplace with a birth date.

## Considered options

- **Open-ended eras**, so `Validator` accepts these codes as 2.3.1 did: rejected. It invents validity the source contradicts, and it would treat these comuni differently from ANPR's own suppressed ones.
- **Adding 18xx candidate years** so that `A073` with `80` resolves to 1880: rejected. A codice fiscale has no century digit, and a 146-year-old holder fails ADR-0006's plausibility rule for every living person. That is a separate decision from this source.
- **Bundling the 489 codes in ADR-0012's fixed list**: rejected. A downloadable source carries them, so they fail ADR-0012's admission rule.

## Consequences

- **Reliability**: the public `reportspooljson` endpoint sometimes drops the connection while sending the full payload (about 850 KB). The import retries before failing, and a failed download leaves the previously imported rows in place.
- **Licence**: downloaded by each installation and never redistributed (ADR-0003), like report 469 (ADR-0014).
- **Names** come from ISTAT as published, as for report 469. Where 2.3.1 disagrees (`B411` Calice Ossolano, `B412` Calignano, which 2.3.1 shifts by one row), ISTAT is right. The Geoform/Groma "Allegato A" code list agrees with ISTAT on both.
