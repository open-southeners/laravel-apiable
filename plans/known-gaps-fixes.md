# laravel-apiable known-gaps fix round

Fix the apiable entries from project-rezero's `KNOWN_GAPS.md`
(`/Users/d8vjork/Projects/OpenSoutheners/project-rezero/KNOWN_GAPS.md`, `## apiable` section).
Work happens in THIS repo (`/Users/d8vjork/Projects/OpenSoutheners/OSS/laravel-apiable`,
branch `fix/known-gaps` off `main`).

**Context / constraints**

- The testbed app at `/Users/d8vjork/Projects/OpenSoutheners/project-rezero` consumes this
  package via path-repo symlink. Its suite pins several gaps (merged-operator filter
  corruption on `due_at`/`estimate_minutes`, comma multi-value fallback, garbage-Accept
  non-406) and its `IssueController` carries commented workarounds (plain-array
  `allowInclude()` instead of `AllowedInclude` objects; explicit `'*'` on `scoped()`).
  Pins are EXPECTED to fail as fixes land; a final sweep updates rezero separately.
- Baselines (after the formatting-pass commit): `vendor/bin/phpunit -c phpunit.dist.xml`
  → 208 tests / 755 assertions green; `vendor/bin/pint --test` clean;
  `vendor/bin/phpstan analyse` → OK (0 errors, via `phpstan-baseline.neon`). Laravel 13.4 +
  testbench 11 already resolved. Units must keep all three green — phpstan: prefer real types;
  a regenerated baseline is acceptable only if the delta is justified in the report.
- Two config keys were pre-added (already committed): `responses.pagination.type`
  (`length-aware|simple|cursor`) and `responses.max_include_depth` (3). Units READ these —
  nobody edits `config/apiable.php`.
- **No unit edits `CHANGELOG.md`** — report entry text; the docs unit writes it.
- **NEVER run `git stash` / `git checkout -- ` / `git reset` on the shared working tree**
  (parallel agents were clobbered by this in a previous round). For before/after comparisons,
  copy files to your scratchpad directory instead.
- Commit style (for the splitter later): plain imperative, matching existing history.

## Units

### U1 — Includes: object-cast bug + max depth  [owns src/Http/Concerns/AllowsIncludes.php, src/Http/AllowedInclude.php, src/Http/ApplyIncludesToQuery.php]
- **`allowInclude()` drops all but the last `AllowedInclude`**: `array_merge($this->allowedIncludes,
  (array) $relationship)` object-casts `AllowedInclude` instances into one mangled protected-
  property key, so consecutive calls overwrite each other — `allowing([AllowedInclude::make('a'),
  AllowedInclude::make('b')])` keeps only `b`. Fix: detect `AllowedInclude` (Arrayable?) and
  merge its `toArray()`/values properly; plain strings/arrays keep working.
- **`max_include_depth`**: implement the documented-but-missing cap — reject/drop include paths
  nested deeper than `config('apiable.responses.max_include_depth', 3)` (dots = depth). Follow
  the package's existing behavior pattern for disallowed params (silently dropped unless
  `requests.validate_params` — be consistent; check how other pipes treat invalid input).
- Tests: multiple `AllowedInclude` objects through `allowing()` all work (the exact rezero
  shape); mixed strings+objects; `_count` suffix unaffected; depth cap on/off.

### U2 — Filters cluster  [owns src/Http/Concerns/AllowsFilters.php, src/Http/ApplyFiltersToQuery.php, src/Http/QueryParamsValidator.php, src/Http/AllowedFilter.php, src/Http/DefaultFilter.php if needed]
Four ledger bugs + one behavior fix, heavily interacting — one unit:
- **Merged same-attribute operators corrupt the query**: registering two operators on one
  attribute (gte+lte on `due_at`) makes `array_merge_recursive` turn `operator` into an array;
  `ApplyFiltersToQuery::wrapIfRelatedQuery()`/the operator resolution then reads a list index,
  `match()` misses, and Laravel's `invalidOperator()` rewrites to `WHERE attr = 1`. Fix so an
  attribute can carry MULTIPLE allowed operators and `filter[attr][gte]=`/`filter[attr][lte]=`
  (and plain `filter[attr]=` with a default operator) each apply the right one. This is the
  worst bug in the package — range filtering is unusable.
- **Comma multi-values on value-restricted filters**: `filter[status]=todo,done` against
  `AllowedFilter::exact('status', [...])` compares the whole joined string, never matches,
  silently drops to defaults. Fix: split on commas BEFORE pattern validation (the dead
  comma-split branch in `QueryParamsValidator` documents the intent) and OR the values as the
  existing multi-value path already does for unrestricted filters.
- **`scoped()` default `'1'` pattern**: docs show `AllowedFilter::scoped('between')` working
  with named args (`filter[between][min]=10`); the `'1'` truthy default never matches real
  argument values so the scope silently never runs. Fix: named-argument scope values should
  default to unrestricted (`'*'`) — a plain boolean scope (`filter[overdue]=1`) can keep the
  truthy pattern; differentiate by the shape of the incoming value (assoc array of args vs
  scalar), or change the default and keep explicit patterns working.
- **Dead operator-validation callback** (`userAllowedFilters()` second callback): `$modifiers`
  is never associative so the `array_intersect` never contributes. Fix it to actually validate
  operator keys (aligning with the merged-operator fix above) or remove it — whichever the
  merged-operator design makes correct.
- **`QueryParamsValidator` failures throw plain `Exception`**: when `requests.validate_params`
  is on, throw an `HttpException` 400 (or a package exception rendering as a JSON:API 400)
  instead — a client input error must not 500. Keep the silent-drop path (validate_params off)
  unchanged.
- Tests: range gte+lte on one attribute (rezero pin shape) returns correct rows; each operator
  still works solo; comma multi-values on restricted + unrestricted filters; scoped named-args
  with default registration; scoped truthy filter; validate_params on → 400 not 500.

### U3 — Error rendering + testing DX  [owns src/Handler.php, src/Testing/**]
- **Non-debug title overwrite** (`Handler.php` ~110): with `APP_DEBUG=false`, every error title
  becomes "Internal server error." regardless of status — a 403 loses "This action is
  unauthorized.". Fix: only substitute the generic title for 5xx; 4xx keep their real
  title/detail (still no trace when debug off).
- **`AssertableJsonApi` helpers don't mark keys interacted**: every scoped closure using only
  the package's own helpers (`hasAttribute`, `hasType`, `hasRelationshipWith`, …) fails
  Laravel's interaction check without a trailing `->etc()`. Fix the helpers (in
  src/Testing/Concerns/*) to mark the keys they touch as interacted so consumer closures work
  without `->etc()`; keep `->etc()` harmless. Check `.claude/plans/assertable-jsonapi-
  modernization.md` if present for the maintainer's intended direction — align, don't fight it.
- Tests: 4xx title preserved with debug off, 5xx generic; trace present only with debug on;
  an assertJsonApi scoped closure using only package helpers passes WITHOUT ->etc()
  (new test), existing tests with ->etc() unaffected.

### U4 — Pagination strategies  [owns src/Http/JsonApiResponse.php, src/Http/JsonApiPaginator.php, src/Builder.php]
- Implement the documented-but-missing `simplePaginating()` and `cursorPaginating()` on
  `JsonApiResponse`, plus honoring the pre-added `responses.pagination.type` config as the
  default strategy. Simple → `simplePaginate` (no total; JSON:API links without last);
  cursor → `cursorPaginate` (cursor-based `page[cursor]` param — decide the param name,
  document; links use cursors). Keep `jsonApiPaginate`/length-aware the default; `paginateUsing`
  closure still overrides everything. Check CLAUDE.md's descriptions of these APIs — match the
  documented names/semantics where sane.
- Tests: each strategy returns a valid JSON:API document with correct links/meta shape;
  config default respected; fluent method overrides config; page size + default_size still
  honored.

### U5 — Docs generator mechanical fixes  [owns src/Console/ApiableDocsCommand.php, src/Documentation/**]
- **Path doubling**: exporters return keys pre-joined with the output path, `handle()`
  re-prepends relative paths → `docs/api/docs/api/openapi.yaml`. Fix so relative `--path`
  works (single join point).
- **Duplicate `sort`/`include` param keys**: `QueryParam::fromSortAttribute()`/`fromIncludeAttribute()`
  hardcode the key — multiple attributes emit duplicate entries; merge/uniquify into one
  parameter listing all values.
- **cURL snippets**: use the endpoint's real HTTP method (`-X POST` + a body placeholder for
  writes) instead of always `-G`.
- **Appends key**: `appends[App\Models\Project]` → use the runtime JSON:API type slug
  (`appends[projects]`) via the resource-type map.
- Tests: extend tests/Documentation/* — relative path output lands once; generated openapi
  has one sort/include param per endpoint; write-endpoint snippet uses the method; appends key
  uses the type slug. NOTE (defer, do not implement): fluent-style controllers still get no
  query-param docs (attribute-scanning only) — report for CURRENT_ISSUES.
- Rezero's committed `docs/api/` output can be regenerated in the sweep — don't touch it.

### U6 — Docs + changelog + CURRENT_ISSUES  [sequential, after U1–U5]
- Fix documented-but-nonexistent APIs across README/docs/CLAUDE.md: `buildLengthAwarePaginator`
  macro, `->list()`, `AllowedSort::field()` in examples — replace with real APIs (now including
  the U4 pagination methods, which docs already describe — verify wording matches what shipped).
  Requirements drift: docs claiming PHP 8.1+/Laravel 10+ vs composer PHP ^8.2 / illuminate
  ^12||^13 — align. Document `appends[<type>]=` param shape explicitly; document
  `max_include_depth` + pagination `type` config; note `formatting.force` ⇒ no 406 (documented
  behavior, not a bug). Document the multi-operator filter + comma multi-value semantics.
- `CURRENT_ISSUES.md` (create at repo root, sibling-repo format): fluent-controller param docs
  gap (generator reads only attributes); `IteratesResultsAfterQuery` appends post-processing
  TODO (known hot spot); Scout search + fast-paginate untested by the testbed; anything U1–U5
  reported as latent.
- `CHANGELOG.md` `[Unreleased]`: reader-facing entries for the whole round (reconstruct from
  `git diff main` + unit reports).
- Final serial verification: phpunit, pint, phpstan (report final counts). Then rezero check:
  `cd /Users/d8vjork/Projects/OpenSoutheners/project-rezero && php artisan test` — expected
  failures ONLY in apiable gap pins (IssuesApiTest merged-operator + comma multi-value pins;
  possibly others). List each with cause; fix nothing there.

### U7 — Commit split  [plain imperative style, no trailers, no push; untracked user files stay untracked]

### U8 — Rezero sweep  [separate repo, after U7]
- IssueController: replace the plain-array `allowInclude()` workaround with the now-working
  `AllowedInclude` objects through `allowing()` (keep behavior identical); drop the explicit
  `'*'` on `scoped('dueBetween')` if the new default covers it; remove stale workaround
  comments.
- Flip pinned tests: due_at/estimate_minutes range filters now filter correctly; comma
  multi-values now match; add a multi-operator range assertion. Keep the garbage-Accept
  non-406 pin (documented `formatting.force` behavior, not a bug — update its comment to cite
  the now-official docs).
- Optionally adopt one new pagination strategy somewhere small (e.g. comments index with
  `simplePaginating()`) + test.
- Regenerate `docs/api/` with the fixed generator (relative path now fine) — commit the diff.
- KNOWN_GAPS.md Status lines (with package commit hashes) on every fixed apiable entry;
  entries that became documented-behavior (no-406) get a "docs clarified upstream" status.
  CURRENT_ISSUES.md resolved note. Full suite green; pint clean; 2-4 commits, no push.

## Dependency graph

U0 (done: pint pass + config keys) → {U1, U2, U3, U4, U5 parallel} → U6 → U7 → U8

## Deferred (not this round)

- Fluent-controller query-param docs generation (needs runtime introspection design).
- Scout search / fast-paginate coverage (no deps in testbed).
- `IteratesResultsAfterQuery` performance refactor (pre-existing TODO).
