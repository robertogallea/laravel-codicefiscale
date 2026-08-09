# AGENTS.md

Guidance for AI coding agents working in this repository.

## Project

`robertogallea/laravel-codicefiscale` — Italian codice fiscale (tax code) parsing, generation, and validation for Laravel. Currently on the 2.x line; a 3.x rewrite is being designed (see `CONTEXT.md` and `docs/adr/`).

## Conventions

- PHP ^8.2, PSR-4 autoloading under `src/`.
- Tests: PHPUnit today; new 3.x tests are written in Pest (see `docs/adr/` for the reasoning split).
- Style: enforced via `.php-cs-fixer.php`; run before committing.
- Read `CONTEXT.md` and relevant `docs/adr/*.md` before making domain-level changes — see `docs/agents/domain.md` for the full consumer rules.
- Commit messages never mention authors — no `Co-Authored-By:` trailers or similar attribution lines.

## Agent skills

### Issue tracker

Issues live on GitHub (`robertogallea/laravel-codicefiscale`), via the `gh` CLI. See `docs/agents/issue-tracker.md`.

### Triage labels

Five canonical roles map mostly onto new labels, reusing `question` for needs-info and `wontfix` as-is. See `docs/agents/triage-labels.md`.

### Domain docs

Single-context: `CONTEXT.md` + `docs/adr/` at the repo root. See `docs/agents/domain.md`.
