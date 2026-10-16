# Project instructions

## Scope and references

- Build **Panel operativo de incidencias** with Laravel, PHP, MySQL, Inertia + React, TypeScript, and Tailwind. No Next.js.
- Implement only the requested step. Prioritize a small, complete solution within the assessment's approximately 90-minute scope; do not add authentication, teams, advanced permissions, notifications, Docker, deployment, or integrations unless requested.
- Read `ai-process/technical-assessment-scope.md` at the start of development and consult relevant sections for each task. Distinguish assessment requirements from project decisions and optional recommendations. Verify completed work against the applicable acceptance criteria.
- Reuse references already read when unchanged. Consult the original `docs/prueba_tecnica_mid_senior.pdf` for ambiguity or suspected omissions. Report discrepancies and resolve the affected requirement; continue independent work where possible.
- Keep these paths unchanged. Do not rewrite the scope to match implementation or change it without a requested correction.

## Conventions and architecture

- Use English for identifiers, database names, route names, tests, code comments, PHPDoc/JSDoc, and Conventional Commits. Use Spanish for UI, validation messages, documentation, and explanations; keep this file in English.
- Follow existing Laravel/frontend conventions and reuse components. Inspect relevant sibling files before adding a new pattern.
- Apply SOLID pragmatically, KISS, and YAGNI. Use conventional Laravel structure, focused controllers, Form Requests, Eloquent relationships/scopes, and focused actions/services where useful. Avoid duplicate layers, generic repositories, unnecessary interfaces, full DDD, CQRS, or a separate API.
- Ordinary files/subdirectories needed within existing application areas are authorized. Ask before introducing dependencies, new top-level architecture, or unrelated features.
- Use explicit PHP parameter/return types, braces for control flow, and constructor property promotion where appropriate. Use TitleCase enum cases and PHPDoc for generics/array shapes.
- Type React props, forms, and server data. Do not use `any`, unjustified casts, disabled checks, or blanket suppressions to hide defects.
- Explain non-obvious rules, constraints, and important side effects in English. Do not comment every function or repeat obvious names/types. Keep comments accurate.

## Database and audit integrity

- MySQL is the source of truth. Target the local MySQL 5.7 setup; confirm the actual server version through the connection before relying on version-specific behavior. Do not infer the server version from the client or change the server silently.
- Use local connection settings from `.env`; describe generic MySQL setup in documentation without naming local server-management software. Never print or log credentials.
- Use migrations, InnoDB, compatible foreign key types, `utf8mb4`, and a collation supported by the verified server. Define lengths, nullability, defaults, metadata types/casts, priorities, and deletion policies deliberately; align validation with schema constraints.
- Do not rely on newer SQL features or unenforced constraints. Keep applied/shared migrations intact; add migrations for subsequent schema changes.
- Justify indexes from actual status, priority, date, and relationship queries. Paginate lists, avoid N+1 queries and redundant indexes, and do not load every incident's full history in list views.
- Centralize English status/priority values, preferably with backed enums; map UI labels to Spanish. Define the transition matrix and validate it server-side. Minimum flow: open -> under review -> blocked or resolved. Any implemented reopening requires an explicit reopening log.
- Persist each critical incident change and its audit entry in one transaction. For concurrent status changes, reload/lock the row inside the transaction, validate the current transition, and record the actual previous state. Roll back both writes on failure.
- Record previous/new values, actor, action, and timestamp. Without authentication, document the supplied/demo actor without implying verified identity. Keep timestamps consistent, preferably UTC, and order logs deterministically with an ID tie-breaker.
- Distinguish state-changing logs from unrelated actions. Preserve audit evidence; do not silently delete/rewrite history or add cascading history removal without justification.
- Use bound queries/Eloquent, explicit mass-assignment rules, backend validation, and CSRF protection. Avoid unsafe HTML rendering.
- Normal pending migrations with `php artisan migrate` are permitted in development after verifying the connection and reviewing their effects. Do not run data cleanup, tests, or destructive resets (`migrate:fresh`, `migrate:refresh`, `db:wipe`, or equivalents) against `prueba_optimaretail`. Destructive development operations require explicit authorization.

## Diagnosis and cache

- Keep reproducible normal seed data separate from the three required inconsistent cases: resolved without resolution log, state differing from latest state-changing log, and duplicate/incoherent log.
- Provide a documented diagnosis entry point, preferably Artisan, with a no-write dry-run. Report affected IDs, evidence, proposed fixes, and cases needing manual review; explain the detection queries.
- Do not assume current status or latest log is always authoritative or invent historical actors/timestamps. Identify corrective entries as repairs made now, preserving provenance and limitations.
- Recheck data before repair, use transactions, and make repairs safe to repeat without duplicate corrective entries. Leave ambiguous cases for documented manual review.
- Do not introduce Redis or business-data caching initially. Query MySQL directly for lists, counters, transitions, and diagnosis. Keep the existing framework cache driver if suitable and ensure any required database cache tables/migrations exist without duplicating them.
- If business caching is later justified and agreed, document keys, filter/pagination dimensions, TTL, and invalidation after commit, including repair commands. Never use cached state to decide transitions/repairs or routinely flush all caches.

## Frontend and framework tools

- Use Laravel routes and `Inertia::render()` with focused, typed React components in the existing pages directory. Use existing Inertia forms/navigation and Wayfinder imports from `@/actions` or `@/routes`.
- Keep state ownership clear, preserve filters in URL parameters where practical, and enforce transitions on the backend. Reuse server-provided allowed actions rather than duplicating business rules.
- Provide accessible labels, keyboard access, mobile/desktop layouts, visible validation, empty/loading states, and protection against duplicate submission. Status must not rely on color alone. Implement at least two assessment interactions; prefer quick status filtering and confirmation before blocking/resolving.
- Never hand-edit generated Wayfinder files, build assets, `vendor/`, or `node_modules/`. Fix source configuration and regenerate through project tooling.
- Confirm relevant package versions from `composer show`, `npm list`, or lockfiles; `package.json` ranges are not resolved versions. Recheck only when dependencies change or the needed API is uncertain.
- Prefer available Boost tools for schema, read-only queries, URLs, recent browser logs, and version-matched documentation. Consult `search-docs` for unfamiliar/version-sensitive Laravel or Inertia behavior, reusing sufficient prior results.
- Read applicable `.ai/rules/index.md` rules and relevant project skills (such as `testing-best-practices` or `inertia-react-development`) when present; reuse unchanged guidance. If unavailable, use installed CLI tools, repository code, or official documentation without inventing results.
- Prefer Artisan generators with `--no-interaction` and correct options. Check help when syntax is uncertain. Prefer existing commands/tests over ad hoc scripts; quote Tinker code safely if needed.

## Tests and completion checks

- Retain the configured Pest/PHPUnit framework. Use factories and deterministic fixtures; prefer feature tests for HTTP, Inertia, and persistence, and unit tests for isolated rules.
- Add meaningful regression tests with each behavior change: success, validation failures, filters, valid/invalid transitions, reopening if supported, audit data, rollback, comments, and diagnosis. Repairs need dry-run, ambiguity, correction, and repeat-run coverage.
- Database tests must use MySQL `prueba_optimaretail_test`. Keep connection settings only in Git-ignored `.env.testing`, without database overrides in `phpunit.xml`. Verify the effective target before database-resetting test hooks run; fail safely if it is not the test database.
- Isolated test resets are permitted after checking the target. SQLite-only results do not prove MySQL constraint or locking behavior.
- Use existing frontend testing tools for changed interactions where available; otherwise record focused manual checks. Do not install a testing stack or delete tests to obtain a pass without approval.
- Run affected tests during development. Run the full suite before delivery or when shared behavior changes; do not repeat unchanged passing checks without a concrete reason.
- Use existing scripts for relevant lint, formatting, PHPStan, TypeScript, and build checks. Format changed PHP with the installed Pint configuration; preserve the existing `--dirty --format agent` workflow when supported. Build after frontend integration changes. Format modified Markdown with the configured formatter.
- Documentation/style-only edits do not need new behavior tests. Never claim a check passed if not run; report failures, environment blockers, and pending manual review.

## Documentation and prompt log

Maintain the following as needed; updates within these paths are authorized:

- `README.md`: accurate generic installation, requirements, environment setup, migration/seed, startup, test/diagnosis commands, and key decisions.
- `docs/development.md`: concise implementation progress, technical decisions, operational details, and verification results.
- `ai-process/ai-process.md`: non-chronological overview of AI tools/contributions, confirmed manual decisions, and actual errors/corrections; update at the end of relevant project phases and link to detailed records instead of duplicating them.
- `ai-process/prompts.md`: chronological programming/architecture requests and outcomes only.
- `ai-process/technical-assessment-scope.md` and `docs/prueba_tecnica_mid_senior.pdf`: scope reference and original source, protected as described above.

- Append one concise prompt entry when implementing/refactoring code, fixing a programming defect, or making an architectural decision. Include date/title, actual prompt or labeled faithful summary, changes/reasons, and user-confirmed manual decisions or pending review.
- Exclude Git requests, setup/environment/configuration changes, check runs, read-only reviews, documentation/agent edits, command transcripts, check results, and commit hashes. For mixed tasks log only programming/architecture; put relevant verification in `docs/development.md`.
- Append without rewriting history. Read only the tail needed to avoid duplicates; do not reload the entire growing prompt log for every task. Do not recursively log documentation maintenance.
- Never invent prompts, contributions, manual reviews, results, or errors. Redact secrets/personal data. Report logging failures. Keep implemented and planned behavior distinct; avoid duplicate journals.

## Git and efficient workflow

- Use focused Conventional Commits in English (`feat`, `fix`, `test`, `refactor`, `docs`, `chore`, `style`, optional scope). Commit only when requested; amend/push/history changes need explicit authorization. Inspect staging and preserve unrelated user changes.
- Keep `.env`, `.env.testing`, credentials, local IDE settings, dependencies, and generated assets out of Git; track safe `.env.example`, `composer.lock`, and `package-lock.json`.
- Search with `rg` and inspect only relevant files/ranges. Reuse unchanged versions, documentation, decisions, and check results already in context. Avoid repeatedly auditing the entire repository for a scoped change.
- Start with the smallest coherent implementation and its meaningful tests. Batch independent reads/checks when safe; keep dependent edits sequential. Investigate a failed check before rerunning it, and expand investigation only to resolve a specific risk.
- Keep tool output and replies concise: report changes, checks/results, and unresolved issues; do not paste full files, logs, diffs, or repeated plans unless requested or needed to explain a failure.
- Do not prescribe weaker correctness or skip required validation to save tokens. These rules guide workflow; they do not override the model/reasoning level selected by the user.
- Ask only when ambiguity materially affects scope, data, or architecture. Continue routine authorized work without repeated confirmation.
