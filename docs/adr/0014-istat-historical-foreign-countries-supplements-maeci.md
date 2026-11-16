# ISTAT's historical foreign-countries report supplements MAECI as a second foreign source

MAECI's stati-esteri table lists only currently existing countries and leaves `CODAT` empty for many current territories, so 3.x lost 74 of 2.3.1's 275 foreign codes (#122, #123). ISTAT's Situas report 469 "Storico Paesi esteri" (`reportspooljson?pfun=469&pdata=01/01/1990`) carries the cadastral code (`COD_AT`, from Agenzia delle Entrate) and ISO alpha-3 for current territories (Gibilterra `Z113`, Groenlandia `Z402`…) **and** for ceased states (`Stato storico`: `Z135` USSR, `Z118` Jugoslavia…). `codice-fiscale:update-places` downloads it alongside MAECI, and ISTAT fills the codes MAECI lacks; it supplements MAECI rather than replacing it. The merge rule for codes **both** sources carry but disagree on (e.g. `Z501`: MAECI Aruba, ISTAT Antille olandesi) is decided in #123.

ISTAT keeps a state after it ceases while MAECI drops it, so a future dissolution stays resolvable with no new bundled entry. Because ISTAT now carries the six ceased states in #122's group A, ADR-0012's admission rule keeps them out of the bundled list.

## Consequences

- **Licence**: like ANPR and MAECI, ISTAT data is downloaded by each installation (ADR-0003) and never redistributed by the package, so its CC BY 4.0 terms and the third-party origin of `COD_AT` raise no redistribution question.
- **Eras**: ISTAT, like MAECI, gives no per-row validity dates, and neither does the fixed list (ADR-0012). Every foreign era is therefore maximally wide, including ceased states and retired codes, so under ADR-0011 a foreign code is always attributable. Curated dissolution dates were rejected: none are published for the tax authority's practice (was `Z118` used until 1992 or 2003? when did Georgia move from `Z136` to `Z254`?), and a wrong date would falsely reject real codici fiscali. 2.3.1 never checked dates for foreign codes either.
- **Names** come from the source: `Z118` is "Jugoslavia" per ISTAT, regardless of 2.3.1's two conflicting names.
