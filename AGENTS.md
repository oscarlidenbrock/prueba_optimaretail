# Project agent instructions

These instructions apply to this repository. Follow explicit user requests and implement only the current agreed task. The project-specific rules below refine the Laravel Boost guidance where they differ. Preserve this project-specific section if regenerating Boost guidance.

## Purpose and scope

Build **Panel operativo de incidencias**, a small application for a mid/senior technical assessment. Use `ai-process/technical-assessment-scope.md` as the operational reference for requirements throughout development. The original source is `docs/prueba_tecnica_mid_senior.pdf`.

- Required stack: Laravel, PHP, MySQL, Inertia + React, HTML, CSS, JavaScript/TypeScript, and Tailwind CSS.
- Do not use Next.js or replace MySQL with SQLite for application runtime.
- Favor a complete, coherent solution within the assessment's approximately 90-minute scope.
- Authentication, teams, advanced permissions, notifications, Docker, public deployment, and external integrations are not required. Do not add them unless requested.
- Inspect existing starter-kit routes and tests before adapting them. Do not silently remove unrelated functionality or failing tests.
- Work incrementally. Do not implement all remaining requirements when the user requests one step.

## Technical assessment scope

- Read `ai-process/technical-assessment-scope.md` before planning or implementing changes. Reuse the content already read if it has not changed.
- Treat the assessment requirements as acceptance criteria. Distinguish mandatory requirements from agreed project decisions and optional recommendations; do not turn optional suggestions into mandatory features.
- Identify the relevant requirements for each task and verify them when the task is complete. Record progress in `docs/development.md` rather than rewriting requirements to match implementation.
- Keep `docs/prueba_tecnica_mid_senior.pdf` unchanged as the original source. Consult it when requirements are unclear or the Markdown appears incomplete.
- Keep the operational Markdown in `ai-process/technical-assessment-scope.md` and the original PDF in `docs/prueba_tecnica_mid_senior.pdf`. Do not move both documents into `docs/`; these paths are relative to the repository root.
- If the Markdown and original PDF conflict, report the discrepancy and resolve the affected requirement before implementing it. Continue independent work when possible.
- Do not modify the scope unless the user requests a change or correction. If a referenced file is missing, report it and do not invent its contents.

## Language and naming

- Write identifiers in English: variables, functions, methods, classes, enums, components, props, database identifiers, route names, and test names.
- Write code comments, PHPDoc, and JSDoc in English.
- Write user-facing UI, validation messages, project documentation, and progress explanations in Spanish unless requested otherwise. Keep this instruction file in English.
- Write Conventional Commit messages in English.
- Follow Laravel and existing frontend naming conventions. Use descriptive names and avoid ambiguous abbreviations.
- Persist English status identifiers and map them to Spanish UI labels consistently; do not scatter translated values through business logic.

## Comments and types

- Explain non-obvious business rules, invariants, constraints, and reasons for decisions.
- Document important business operations and complex blocks, including side effects or exceptional cases when useful.
- Do not require a comment on every function or repeat what its name and types already express. Simple methods and components may be self-explanatory.
- Prefer PHPDoc/JSDoc for contracts that need explanation and short inline comments for local reasoning. Keep comments accurate as behavior changes.
- Use explicit PHP parameter and return types where compatible with framework contracts. Use PHPDoc for generics and array shapes that native types cannot express.
- Use TypeScript for new React code. Type props, form values, and server responses; avoid `any`, unjustified casts, and disabling checks to hide errors.

## Architecture

- Apply SOLID pragmatically, KISS, and YAGNI. Separate responsibilities without building unnecessary layers.
- Use conventional Laravel organization. Do not introduce full DDD, hexagonal architecture, CQRS, generic repositories, or interfaces with a single trivial implementation merely to demonstrate patterns.
- Keep controllers focused on HTTP orchestration and use Form Requests for meaningful request validation.
- Keep relationships and appropriate query scopes in Eloquent models.
- Extract business operations such as `CreateIncident` and `ChangeIncidentStatus` into focused action classes or services when they improve cohesion, reuse, or testability. Do not create both action and service layers for the same trivial operation.
- Use dependency injection where needed. Avoid duplicated business logic between controllers, commands, and frontend code.
- Use Laravel routes and Inertia responses; no separate REST API or API versioning is required for this application.
- Prefer existing dependencies and components. Get approval before adding dependencies or changing major architecture. Ordinary files and subdirectories within existing application areas needed for an authorized task do not require repeated approval.

## Assessment requirements

Implement these incrementally as requested:

- Main screen with application name and description, incident list, priority, status, date, requester or assignee, creation form, status actions, and history access.
- Responsive, understandable interface with clear indicators for open, under review, blocked, and resolved incidents.
- Tables `incidents`, `incident_logs`, and `incident_comments`, preserving the fields required by the assessment.
- Incident creation, filters by status/priority/free text, permitted state changes, history and comments, comment creation, and clear validation feedback.
- At least two frontend enhancements from the assessment. Prefer quick status filtering without full-page reload and confirmation before blocking or resolving; state counters are optional if time permits.
- Three deliberately inconsistent sample cases and a documented diagnosis/correction workflow with a dry-run option.

## Database design and compatibility

- Use MySQL as the source of truth for incidents, comments, and audit history. MAMP provides the local database server; do not assume its port or credentials.
- Confirm the actual server version through the connection, for example with `SELECT VERSION()`, before relying on version-specific SQL, JSON features, collations, or constraint enforcement. `mysql --version` identifies the client, not the server.
- Record the server version actually verified in the README. Do not claim compatibility with other versions without evidence or silently change the user's server.
- Use a transactional engine such as InnoDB for related application tables. Preserve foreign key compatibility, including ID types and signedness.
- Define column types, lengths, defaults, nullability, priority values, and relationship cardinality deliberately. Keep request validation consistent with schema limits.
- Use `utf8mb4` and a collation supported by the actual server. Do not assume newer default collations are available.
- Use migrations for schema changes and document any data-migration implications. Do not manually change schema outside migrations or edit applied shared migrations to disguise a schema change.
- Choose foreign key deletion behavior explicitly. Preserve audit history; do not add incident deletion or cascading history removal unless needed and justified.
- Use structured metadata with appropriate casts and a database type supported by the verified server. Document the meaning of audit actions and values.
- Keep timestamps consistent, preferably stored in UTC and formatted for presentation. Use deterministic ordering where timestamps tie.
- Design indexes from actual filtering, sorting, and relationship queries. Consider composite indexes only when their column order serves those queries; avoid indexing every field or adding overlapping indexes blindly.
- Paginate list queries, eager-load relationships when needed, and avoid loading full histories for every row in the main list.
- Keep normal seeders and deliberately inconsistent fixtures separate and reproducible. Ensure diagnostic fixtures represent their intended problems without weakening normal validation or schema integrity.
- Use a separate test database and verify its connection before tests that reset data. Never run `migrate:fresh`, `db:wipe`, or equivalent against development data without explicit authorization.
- In this local workspace, do not run migrations, cleanup, or tests against `prueba_optimaretail`, as explicitly requested by the user. Test operations must target `prueba_optimaretail_test`.

## Cache policy

- Application-level caching and Redis are not required by the assessment. Do not add them initially without a demonstrated need and an agreed scope change.
- Query MySQL directly for incident lists, counters, status validation, and inconsistency diagnosis while the application remains small.
- Distinguish framework cache infrastructure from caching business data. Inspect the configured cache driver before changing it; keep the existing driver if suitable.
- If the database cache driver is used, ensure its required migrations and setup steps are present. Do not create duplicate migrations for tables already provided by the starter kit.
- Do not use cached state to authorize status transitions or decide repairs. Read the current authoritative state within the database transaction and apply the concurrency safeguards below.
- If query caching is later justified, document cache keys, filter and pagination dimensions, TTL, and invalidation. Invalidate affected data only after a successful commit, including changes made by repair commands.
- Avoid global cache flushes as a routine fix. Do not introduce a second cache service or infrastructure dependency without approval.

## Data integrity and audit history

- Use migrations, appropriate foreign keys, explicit deletion behavior, and justified indexes for status, priority, and date queries. Avoid redundant indexes.
- Centralize statuses and priorities, preferably with backed PHP enums. Document the transition matrix and validate transitions on the server.
- Support the required flow: open -> under review -> blocked or resolved. Document any additional transitions. If reopening is implemented, record an explicit reopening action.
- Store every status change with previous value, new value, actor, and timestamp. Record other critical actions consistently and document the policy.
- Without authentication, use an explicit documented demo/operator identity or validated supplied name. Do not present it as verified authentication.
- Persist an incident change and its audit entry in one database transaction. Roll back both on failure.
- Protect status changes from concurrent stale writes, for example by reloading and locking the incident inside the transaction before checking the transition and writing the log. Record the actual previous state.
- Avoid silently rewriting or deleting audit history. Document any repair and preserve evidence of the original inconsistency.
- Define deterministic chronological ordering for logs, using an ID tie-breaker when timestamps match. Distinguish status-changing logs from comments or unrelated actions.
- Use bound SQL parameters or Eloquent. Validate input, retain CSRF protection, and avoid unsafe HTML rendering and unrestricted mass assignment.
- Paginate incident lists and avoid N+1 queries where relationships are displayed.

## Diagnosis and repair

- Separate normal demo seed data from deliberately inconsistent fixtures so both can be loaded intentionally and reproduced.
- Include: resolved incident without a resolution log; incident whose state disagrees with its latest status-changing log; duplicate or incoherent log.
- Provide a clearly documented diagnostic entry point, preferably an Artisan command, with a no-write dry-run option.
- Report affected IDs, detected problems, proposed actions, and cases requiring manual review.
- Do not infer that the current status or latest log must always be authoritative. State the evidence and repair policy.
- Do not invent historical actors or timestamps. If creating a corrective log, identify it as a repair performed now, with available provenance and limitations.
- Make corrections transactional and safe to repeat: a second run must not create duplicate repairs or unnecessary changes. Recheck the relevant data before applying a correction.
- Document the detection queries, why they detect each problem, and what cannot safely be corrected automatically.

## Frontend behavior

- Keep React components focused, with typed props and clear ownership of state.
- Use existing Inertia form/navigation patterns and Wayfinder. Keep filters in URL parameters where practical for reloads and shareable state.
- Do not duplicate the authoritative transition rules in multiple places; expose allowed actions from the backend when appropriate and always enforce them server-side.
- Provide accessible labels, keyboard-operable actions, visible validation errors, empty states, and loading/disabled states that prevent accidental duplicate submission.
- Do not communicate status with color alone. Check desktop and mobile layouts.
- Reuse existing UI components and Tailwind conventions. Do not add a second CSS framework.
- Do not hand-edit generated Wayfinder route/action files, build assets, `vendor/`, or `node_modules/`.

## Tests and verification

- Detect and retain the installed test framework; do not assume Pest or PHPUnit from this file alone.
- Add or update meaningful tests as functionality is implemented. Prefer feature tests for requests, database behavior, and Inertia responses; use unit tests for isolated business rules when useful.
- Cover success and relevant failures: invalid input, filtering, allowed and forbidden transitions, explicit reopening if supported, correct audit data, transaction rollback, comments, and diagnostic behavior.
- For repair operations, test dry-run does not write, ambiguity is reported, corrections are accurate, and repeated execution is idempotent.
- Use factories and deterministic fixtures. Isolate tests from development data and verify the test database configuration before running migrations or database-resetting tests.
- Use the dedicated MySQL database `prueba_optimaretail_test` for database-dependent tests. Keep test connection settings and credentials only in the Git-ignored `.env.testing`; do not override them in `phpunit.xml`. Verify the effective database before `RefreshDatabase` can run. SQLite-only results do not establish MySQL compatibility, locking, or constraint behavior.
- Test frontend interactions with the existing test setup where available. Do not install a new testing stack without approval. Otherwise record focused manual checks honestly.
- Styling-only or documentation-only changes do not require new behavioral tests.
- Run the narrowest affected tests while developing; run the full suite before delivery or after changes to shared behavior.
- Inspect available scripts before running lint, formatting, type checking, and builds. Run relevant checks for changed code, including a frontend build after frontend integration changes.
- Never claim tests, manual review, or build steps passed if they were not executed. Report blockers and remaining checks.

## Documentation and AI process

The user authorizes creating and updating these documents as part of development:

- `README.md`: accurate requirements, installation, environment setup, migrations/seeders, startup, tests, diagnosis commands, and key decisions.
- `docs/prueba_tecnica_mid_senior.pdf`: original assessment; keep it unchanged.
- `docs/development.md`: implementation progress, technical decisions, issues, and verification.
- `ai-process/technical-assessment-scope.md`: assessment requirements and clearly separated project decisions; follow the scope rules above.
- `/ai-process/ai-process.md`: AI tools, prompt summary, AI-assisted parts, confirmed manual decisions, and actual errors/corrections.
- `/ai-process/prompts.md`: chronological record of development requests and outcomes.

Keep `/ai-process` at repository root as required by the assessment. Reuse the exact documentation paths above; do not create a duplicate AI overview or development journal.

After each development task, append one concise entry to `ai-process/prompts.md` containing:

1. Date and task title.
2. The actual prompt or a clearly labeled faithful summary.
3. Changes made and relevant decisions.
4. Checks executed and their results, including any failures or checks not run.
5. Manual review confirmed by the user, or explicitly pending review.
6. A commit reference only if it exists and was verified; otherwise omit it or mark it pending.

- Log significant follow-up corrections. A documentation-only change may be included in the same task entry; do not recursively log the act of writing the log.
- Do not invent prompts, manual reviews, errors, tool use, timings, or commit hashes.
- Exclude secrets and personal data from prompts and documentation. Redact sensitive values rather than copying `.env` contents.
- Keep documentation concise, distinguish implemented from planned behavior, and update commands when implementation changes.

## Git and completion

- Use Conventional Commits in English: `feat`, `fix`, `test`, `refactor`, `docs`, `chore`, or `style`, with an optional meaningful scope.
- Keep changes and proposed commits focused on one coherent purpose.
- Create commits only when requested. Do not amend, push, force-push, or rewrite history without explicit authorization for that action.
- Inspect staged changes before committing. Preserve unrelated user changes and do not stage them automatically.
- Never commit credentials, `.env`, dependency directories, generated assets, or local IDE configuration. Keep `.env.example`, `composer.lock`, and `package-lock.json` tracked.
- Do not execute destructive database resets against development data without explicit authorization. Isolated test database resets are permitted after verifying the target.
- Finish each task with a short summary of changes, checks and results, and unresolved limitations. Provide a suggested commit message when requested.

## Tool availability

Use Boost tools and relevant project skills when available. If a referenced tool, skill, rule file, or script is unavailable, report the limitation when relevant and use installed CLI tools, repository source, or official version-matched documentation. Do not invent tool results or stop routine authorized work solely because an optional integration is missing.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: use `npm list --depth=0` (or inspect `package-lock.json`) for resolved versions. `package.json` may contain version ranges.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- Maintain `README.md`, `docs/`, and `ai-process/` as part of implementation tasks, as authorized by the project-specific rules above. Avoid unrelated documentation.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `rg -n -i '<relevant-topic>' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

# Inertia v3

- Use only the Inertia features needed for the task. Check documentation for the installed version; do not assume legacy APIs remain compatible.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders within the requested scope. Use `php artisan make:model --help` to check available options; do not interrupt routine work to offer unrelated additions.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- Confirm the configured test framework from `composer.json`, installed packages, and existing tests. Keep Pest if configured; otherwise keep PHPUnit. Do not switch frameworks. With Pest use `php artisan make:test --pest {name}`; with PHPUnit use the supported PHPUnit generator option after checking command help.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- If Pest is installed and configured, `vendor/bin/pest` may be used directly. Otherwise use the configured PHPUnit runner or `php artisan test`.
- Run the complete suite before final delivery or when shared behavior is affected, if the environment is available. Report results accurately; ask the user to run a command only when you cannot execute it.

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

</laravel-boost-guidelines>
