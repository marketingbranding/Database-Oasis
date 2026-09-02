# Graph Report - Database-Oasis  (2026-09-02)

## Corpus Check
- 250 files · ~128,343 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 6357 nodes · 20278 edges · 314 communities (149 shown, 145 thin omitted)
- Extraction: 88% EXTRACTED · 12% INFERRED · 0% AMBIGUOUS · INFERRED: 2381 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `05cb882d`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- composer.json
- Illuminate\Database\Eloquent\Factories\Factory
- User
- Master Build Pack - Database Oasis
- Illuminate\Database\Migrations\Migration
- scripts
- AdminPanelProvider.php
- Filament\Tables\Table
- Unit
- Testing Best Practices (skill)
- package.json
- CI quality job (ubuntu-latest, PHP 8.4, Node 22)
- SalesCase
- e
- code-editor.js
- rich-editor.js
- components/chart.js
- Filament\Resources\Pages\CreateRecord
- Convention Detection Checklist
- constructor
- BiCheckResult
- i
- CaseWorkflowActions
- command
- Queue and Job Best Practices
- Security Best Practices
- stat/chart.js
- DatabaseSeeder.php
- Test Value Review Checklist
- laravel-best-practices skill
- tailwindcss-development skill
- Routing and Controller Best Practices
- markdown-editor.js
- infer-conventions skill
- Architecture Best Practices
- Database Performance Best Practices
- Events and Notifications Best Practices
- bootstrap/app.php
- logging.php
- Validation and Forms Best Practices (Laravel rules)
- ExampleTest
- Consistency First (testing skill)
- Global Fakes in Base TestCase setUp
- Action Classes for Focused Business Operations
- Correlated Subquery with addSelect
- Process Large Data Sets Incrementally (chunk/chunkById/cursor/lazy)
- Notification afterCommit dispatch
- Framework fakes for facades
- Form request extraction at the boundary
- console.php
- laravel-boost
- Subject-Specific Assertion Selection
- Framework Feature First
- Mockery Usage Conventions
- Unexpected Key in Payload Test
- Detection Checklist (49 convention dimensions)
- Composite Index Design for the Query
- Atomic Locks (Cache::lock vs lockForUpdate)
- View Composers for Shared View Data
- Cache::memo() In-Execution Memoization
- Queued notifications (ShouldQueue + Queueable)
- Explicit HTTP timeouts (connectTimeout/timeout)
- Fake HTTP requests in tests (Http::fake/preventStrayRequests)
- Foreign-key constraints (constrained())
- RateLimited queue middleware
- ShouldBeUnique dispatch deduplication (uniqueId/uniqueFor)
- Authorize protected actions (gates/policies/form requests)
- Escape output in its context (Blade {{ }})
- Mass assignment control ($fillable/$guarded)
- Project naming conventions
- Tailwind v4 CSS-first configuration (@theme)
- Assert the complete result of a write
- Tenant isolation returns 404 not 403
- Controller.php
- Illuminate\Support\Facades\Route
- Feature Test First
- Arrange, Act, Assert
- Assert a Known Value
- assertSame Over assertEquals
- Named Response Assertions
- Which Layer Owns Which Test Case
- Policy-Level Authorization Testing
- Validation Message Testing
- LazilyRefreshDatabase Over RefreshDatabase
- Real Queries Against Real Records
- #[Group] for Selection, Not Structure
- Test File Layout Mirrors Class Path
- BCRYPT_ROUNDS=4 in Test Environment
- Parallel Testing with ParaTest
- Slow Test Profiling (--profile)
- Escaping User Content Test
- Injection into Dynamic Query Components Test
- Named Factory States Over Raw Attributes
- Each Test Makes Its Own Data
- Accessor/Mutator Fork (Attribute class vs legacy)
- DTO Pattern Fork (spatie/laravel-data vs readonly classes)
- Namespace Layout Fork (default skeleton vs domain modules)
- Validation Entry Point Fork
- Step 0 Architecture Map of app/ Tree
- Decisions, Not Defaults Test
- Glob Mapping to Most Specific Path
- Boost record-rule MCP Tool
- Correlated Subquery for Has-Many Ordering
- setRelation() for Inverse Relations
- whereHas() EXISTS vs whereIn() IN Subquery
- Concurrency::run() for Parallel Execution
- Constructor and Method Injection Over Service Location
- Context Facade for Request-Scoped Data
- Contracts at System Boundaries
- defer() for Post-Response Work
- Deterministic Sort Order with Unique Tie-Breaker
- Multibyte String Functions (mb_*)
- $attributes->merge() in Component Templates
- @aware for Nested Component Props
- Blade Fragments for Partial Rendering
- Components vs Includes
- @pushOnce for Per-Component Scripts
- Cache::flexible() Stale-While-Revalidate
- Cache::remember() for Cache-Aside Reads
- Cache Tags for Group Invalidation
- Failover Cache Store Configuration
- #[CollectedBy] for Custom Collection Classes
- Higher-Order Collection Messages
- toQuery() for Bulk Collection Operations
- App::environment() for Environment Checks
- Encrypted Environment Files and Secret Stores
- env() Only in Configuration Files
- Named Domain Values (enums and class constants)
- Model::preventLazyLoading() in Development
- Select Only Needed Columns
- withCount() Without Loading Relationships
- Global Scopes Sparingly
- Model-Aware Application Queries
- Precise Relationship Types with Concrete Return Types
- whereBelongsTo() for Relationship Queries
- Laravel Best Practices (skill)
- Exception context() method
- Exception report()/render() pattern
- Exception report throttling (throttle/Lottery/Limit)
- shouldRenderJsonWhen JSON policy for API routes
- ShouldntReport interface
- Event discovery for listeners
- resolve
- On-demand notifications (Notification::route)
- Explicit HTTP error handling (throw/successful/notFound)
- fromObject
- Retry only safe operations (idempotency keys)
- Separate content and delivery mail tests
- .forEach
- Honest migration rollbacks (down())
- Deployed migrations are immutable
- Design indexes for real queries
- Stage changes affecting existing rows
- Back off transient failures ($tries/$backoff)
- Bus::batch job batching
- Job failed() terminal handling
- constructor
- Implicit route model binding
- Resource-oriented controller organization
- Resource routes (resource/apiResource)
- Bound work inside the scheduled task
- runInBackground scheduled tasks
- draw
- withoutOverlapping lock
- CSRF protection (@csrf / X-XSRF-TOKEN)
- Bind query parameters (no SQL interpolation)
- .slice
- Comments explain why
- Idiomatic Laravel syntax
- Str/Arr/Number/Uri utilities
- after() cross-field validation
- Conditional rules (Rule::when/required_if/exclude_unless)
- Laravel Decision Rules
- Gap utilities for sibling spacing
- Tailwind CSS Development skill
- Tailwind v4 @import syntax
- Arrange, act, assert structure
- assertSame over assertEquals
- Named response assertions (assertNotFound over assertStatus 404)
- Endpoint coverage case matrix
- Search docs before hand-rolling test code
- LazilyRefreshDatabase over RefreshDatabase
- Real queries against real test-database records
- Time and randomness control (freezeTime/travelTo)
- Test file layout ({ClassName}Test.php)
- Parallel runs with ParaTest (php artisan test --parallel)
- Test environment settings (BCRYPT_ROUNDS/WithCachedConfig/withoutVite)
- Test review checklist
- Security boundary testing
- Named factory states over raw attributes
- Each test makes its own data
- Consistency first (project conventions win)
- Feature test first
- Test observable behavior and contracts
- isHorizontal
- r
- get
- slice
- n
- support.js
- n
- columns/select.js
- facet
- prop
- r
- o
- parse
- _update
- tables.js
- e
- t
- reduce
- notifications.js
- y
- te
- ce
- lP
- sliceDoc
- Cn
- r
- _update
- Branch
- components/select.js
- fn
- at
- selectOption
- isHorizontal
- parse
- go
- add
- updateElements
- replace
- echo.js
- ae
- draw
- fn
- slider.js
- find
- jt
- file-upload.js
- ir
- closeDropdown
- e
- PhaseThreePsjbTest
- addElementByRule
- apply
- invert
- filament/app.js
- getDatasetMeta
- c
- toString
- E
- W
- Ft
- dx
- _notify
- renderOptions
- g$
- color-picker.js
- fn
- cc
- date-time-picker.js
- selectOption
- q
- r
- oe
- _handleEvent
- N
- Mt
- addSingleBadge
- Testing Best Practices skill
- require-dev
- actions/actions.js
- t
- schemas.js
- T
- Pe
- config
- setup
- sl
- require
- st
- Pt
- components/actions.js
- psr-4
- yl
- clickPercent
- AppServiceProvider.php
- keywords
- post-autoload-dump
- .opencode/opencode.json
- c
- dev
- graphify.js

## God Nodes (most connected - your core abstractions)
1. `User` - 190 edges
2. `constructor()` - 151 edges
3. `update()` - 148 edges
4. `resolve()` - 94 edges
5. `y()` - 93 edges
6. `Branch` - 87 edges
7. `_update()` - 87 edges
8. `SalesCase` - 81 edges
9. `node()` - 79 edges
10. `te()` - 78 edges

## Surprising Connections (you probably didn't know these)
- `php artisan test --compact step` --references--> `Testing Best Practices skill`  [INFERRED]
  .github/workflows/ci.yml → .claude/skills/testing-best-practices/SKILL.md
- `Document Reality, Never Judge (consistency first)` --semantically_similar_to--> `Consistency First (testing skill)`  [INFERRED] [semantically similar]
  .claude/skills/infer-conventions/SKILL.md → .agents/skills/testing-best-practices/SKILL.md
- `Consistency First (laravel skill)` --semantically_similar_to--> `Consistency First (testing skill)`  [INFERRED] [semantically similar]
  .claude/skills/laravel-best-practices/SKILL.md → .agents/skills/testing-best-practices/SKILL.md
- `AGENTS.md - Laravel Boost + Graphify Guidelines` --semantically_similar_to--> `CLAUDE.md - Laravel Boost Guidelines`  [INFERRED] [semantically similar]
  AGENTS.md → CLAUDE.md
- `Collaborator Isolation Fork (Mockery vs real integration)` --semantically_similar_to--> `Mockery Usage Conventions`  [INFERRED] [semantically similar]
  .claude/skills/infer-conventions/references/checklist.md → .agents/skills/testing-best-practices/rules/isolation.md

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **Convention Inference Workflow** — _agents_skills_infer_conventions_skill, _agents_skills_infer_conventions_references_checklist, _agents_skills_infer_conventions_skill_record_rule_tool, _agents_skills_infer_conventions_skill_ai_rules_directory [EXTRACTED 0.95]
- **CI quality pipeline steps** — _github_workflows_ci_ci_quality_job, _github_workflows_ci_postgres_service, _github_workflows_ci_migrate_rollback_cycle, _github_workflows_ci_pint_test_step, _github_workflows_ci_composer_audit_step, _github_workflows_ci_artisan_test_step [EXTRACTED 1.00]
- **Database Oasis Core Domain Model** — docs_master_build_pack_database_oasis, docs_master_build_pack_sales_case_backbone, docs_master_build_pack_business_rules, docs_master_build_pack_rbac_roles, docs_master_build_pack_branch_isolation [EXTRACTED 1.00]
- **Laravel Best Practices Rule Corpus** — _agents_skills_laravel_best_practices_skill, _agents_skills_laravel_best_practices_rules_db_performance, _agents_skills_laravel_best_practices_rules_advanced_queries, _agents_skills_laravel_best_practices_rules_eloquent, _agents_skills_laravel_best_practices_rules_security, _agents_skills_laravel_best_practices_rules_validation, _agents_skills_laravel_best_practices_rules_routing, _agents_skills_laravel_best_practices_rules_migrations, _agents_skills_laravel_best_practices_rules_queue_jobs, _agents_skills_laravel_best_practices_rules_caching, _agents_skills_laravel_best_practices_rules_http_client, _agents_skills_laravel_best_practices_rules_error_handling, _agents_skills_laravel_best_practices_rules_events_notifications, _agents_skills_laravel_best_practices_rules_mail, _agents_skills_laravel_best_practices_rules_scheduling, _agents_skills_laravel_best_practices_rules_collections, _agents_skills_laravel_best_practices_rules_blade_views, _agents_skills_laravel_best_practices_rules_config, _agents_skills_laravel_best_practices_rules_style, _agents_skills_laravel_best_practices_rules_architecture [EXTRACTED 1.00]
- **Laravel Best Practices Rule Index** — _claude_skills_laravel_best_practices_skill_laravel_best_practices, _claude_skills_laravel_best_practices_rules_advanced_queries_advanced_query_best_practices, _claude_skills_laravel_best_practices_rules_architecture_architecture_best_practices, _claude_skills_laravel_best_practices_rules_blade_views_blade_and_view_best_practices, _claude_skills_laravel_best_practices_rules_caching_caching_best_practices, _claude_skills_laravel_best_practices_rules_collections_collection_best_practices, _claude_skills_laravel_best_practices_rules_config_configuration_best_practices, _claude_skills_laravel_best_practices_rules_db_performance_database_performance_best_practices, _claude_skills_laravel_best_practices_rules_eloquent_eloquent_best_practices [EXTRACTED 1.00]
- **Legacy Migration & Sheets Cutover** — docs_master_build_pack_legacy_migration, docs_master_build_pack_google_sheets_mirror, docs_master_build_pack_pilot_jepara [EXTRACTED 1.00]
- **Testing Best Practices Rule Index** — _agents_skills_testing_best_practices_skill_testing_best_practices, _agents_skills_testing_best_practices_rules_assertions_assertions, _agents_skills_testing_best_practices_rules_endpoint_tests_endpoint_tests, _agents_skills_testing_best_practices_rules_finding_features_how_to_find_test_framework_features, _agents_skills_testing_best_practices_rules_isolation_fakes_mocks_and_determinism, _agents_skills_testing_best_practices_rules_naming_naming_and_structure, _agents_skills_testing_best_practices_rules_performance_test_suite_performance, _agents_skills_testing_best_practices_rules_review_reviewing_tests, _agents_skills_testing_best_practices_rules_security_security_tests, _agents_skills_testing_best_practices_rules_test_data_factories_and_test_data [EXTRACTED 1.00]
- **Testing Best Practices skill rule set** — _claude_skills_testing_best_practices_skill_testing_skill, _claude_skills_testing_best_practices_rules_finding_features_finding_features_rules, _claude_skills_testing_best_practices_rules_naming_naming_rules, _claude_skills_testing_best_practices_rules_assertions_assertions_rules, _claude_skills_testing_best_practices_rules_endpoint_tests_endpoint_tests_rules, _claude_skills_testing_best_practices_rules_test_data_test_data_rules, _claude_skills_testing_best_practices_rules_isolation_isolation_rules, _claude_skills_testing_best_practices_rules_security_security_tests_rules, _claude_skills_testing_best_practices_rules_performance_performance_rules, _claude_skills_testing_best_practices_rules_review_review_rules [EXTRACTED 1.00]
- **After-Commit Transactional Dispatch Pattern** — _agents_skills_laravel_best_practices_rules_events_notifications_dispatch_after_commit, _agents_skills_laravel_best_practices_rules_events_notifications_queued_notifications, _agents_skills_laravel_best_practices_rules_mail_queued_mailables [INFERRED 0.85]
- **Delay dispatch until database commit pattern** — _claude_skills_laravel_best_practices_rules_events_notifications_should_dispatch_after_commit, _claude_skills_laravel_best_practices_rules_events_notifications_notification_after_commit, _claude_skills_laravel_best_practices_rules_mail_mailable_after_commit [INFERRED 0.85]
- **N+1 Query Prevention Pattern** — _claude_skills_laravel_best_practices_rules_db_performance_eager_loading, _claude_skills_laravel_best_practices_rules_db_performance_prevent_lazy_loading, _claude_skills_laravel_best_practices_rules_db_performance_select_needed_columns, _claude_skills_laravel_best_practices_rules_db_performance_with_count, _claude_skills_laravel_best_practices_rules_advanced_queries_correlated_subquery_select [INFERRED 0.85]
- **Phase 0 Bootstrap Foundation** — docs_phase_0_prompt_phase_0_bootstrap, docs_master_build_pack_tech_stack, docs_master_build_pack_health_endpoint, docs_master_build_pack_rbac_roles, agents_code_quality_toolchain, compose_app, compose_postgres [INFERRED 0.95]

## Communities (314 total, 145 thin omitted)

### Community 0 - "composer.json"
Cohesion: 0.14
Nodes (13): autoload-dev, psr-4, description, extra, laravel, dont-discover, license, minimum-stability (+5 more)

### Community 1 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.07
Nodes (16): UtilityStatus, BankFactory, static, BiCheckFactory, static, BranchFactory, static, ConsumerFactory (+8 more)

### Community 2 - "User"
Cohesion: 0.04
Nodes (17): self, User, BiCheckPolicy, BranchPolicy, ConsumerPolicy, PsjbPolicy, SalesCasePolicy, UnitPolicy (+9 more)

### Community 3 - "Master Build Pack - Database Oasis"
Cohesion: 0.14
Nodes (30): AGENTS.md - Laravel Boost + Graphify Guidelines, .ai/rules Project Rules, Code Quality Toolchain (Pint, PHPStan, PHPUnit), Graphify Knowledge Graph Workflow, Laravel Boost MCP Tools, CLAUDE.md - Laravel Boost Guidelines, compose.yaml app service (Laravel app), compose.yaml postgres service (PostgreSQL 17) (+22 more)

### Community 4 - "Illuminate\Database\Migrations\Migration"
Cohesion: 0.08
Nodes (3): Illuminate\Database\Migrations\Migration, Illuminate\Database\Schema\Blueprint, Illuminate\Support\Facades\Schema

### Community 5 - "scripts"
Cohesion: 0.14
Nodes (14): scripts, analyse, format, post-create-project-cmd, post-update-cmd, pre-package-uninstall, test, Illuminate\\Foundation\\ComposerScripts::prePackageUninstall (+6 more)

### Community 6 - "AdminPanelProvider.php"
Cohesion: 0.11
Nodes (16): AdminPanelProvider, Filament\Http\Middleware\Authenticate, Filament\Http\Middleware\AuthenticateSession, Filament\Http\Middleware\DisableBladeIconComponents, Filament\Http\Middleware\DispatchServingFilamentEvent, Filament\Pages\Dashboard, Filament\PanelProvider, Filament\Support\Colors\Color (+8 more)

### Community 7 - "Filament\Tables\Table"
Cohesion: 0.05
Nodes (53): BankResource, ManageBanks, BiCheckResource, BiChecksTable, BranchResource, ManageBranches, ConsumerResource, ConsumerForm (+45 more)

### Community 8 - "Unit"
Cohesion: 0.06
Nodes (11): SalesCaseForm, Bank, Model, Project, Unit, BankPolicy, ProjectPolicy, static (+3 more)

### Community 9 - "Testing Best Practices (skill)"
Cohesion: 0.21
Nodes (12): Assertions (rule), Endpoint Tests (rule), How to Find Test Framework Features (rule), Fakes, Mocks, and Determinism (rule), Naming and Structure (rule), Test Suite Performance (rule), Reviewing Tests (rule), Security Tests (rule) (+4 more)

### Community 10 - "package.json"
Cohesion: 0.10
Nodes (20): concurrently, @laravel/multiplex, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite (+12 more)

### Community 11 - "CI quality job (ubuntu-latest, PHP 8.4, Node 22)"
Cohesion: 0.20
Nodes (10): Migration Best Practices (Laravel rules), composer audit dependency audit, Convention and Style Best Practices (Laravel rules), php artisan test --compact step, CI quality job (ubuntu-latest, PHP 8.4, Node 22), CI workflow (.github/workflows/ci.yml), composer audit step (--locked), migrate / migrate:rollback / migrate verification steps (+2 more)

### Community 12 - "SalesCase"
Cohesion: 0.06
Nodes (13): BiCheckForm, PsjbForm, Psjb, SalesCase, PsjbStatus, static, PsjbFactory, Illuminate\Database\Eloquent\Attributes\Fillable (+5 more)

### Community 13 - "e"
Cohesion: 0.05
Nodes (51): alpha(), apply(), co(), color(), darken(), desaturate(), e(), fo() (+43 more)

### Community 14 - "code-editor.js"
Cohesion: 0.01
Nodes (121): Ac(), addActive(), addCompletion(), addCompletions(), addNamespace(), addNamespaceObject(), Ag(), Ar() (+113 more)

### Community 15 - "rich-editor.js"
Cohesion: 0.01
Nodes (190): aa(), accepts(), addExtensions(), addHackNode(), addNode(), addTextblockHacks(), applyAspectRatio(), applyConstraints() (+182 more)

### Community 16 - "components/chart.js"
Cohesion: 0.01
Nodes (123): abutsStart(), ac(), addControllers(), addPlugins(), addScales(), Ag(), cc(), Cl() (+115 more)

### Community 17 - "Filament\Resources\Pages\CreateRecord"
Cohesion: 0.04
Nodes (28): CreatePsjbAction, CreateBiCheck, ListBiChecks, CreateConsumer, EditConsumer, ListConsumers, CreateProject, EditProject (+20 more)

### Community 18 - "Convention Detection Checklist"
Cohesion: 0.20
Nodes (11): Convention Detection Checklist, 49 Laravel convention dimensions, Advanced Query Best Practices, Correlated subquery pattern, Eloquent Best Practices, Attribute casts, Global scope tradeoff, Local query scopes (+3 more)

### Community 19 - "constructor"
Cohesion: 0.02
Nodes (156): add(), addChunk(), addEventListener(), addInfoPane(), addInner(), addWindowListeners(), adjust(), al() (+148 more)

### Community 20 - "BiCheckResult"
Cohesion: 0.06
Nodes (34): CancelPsjbAction, CancelSalesCaseAction, CloseSalesCaseAction, CreateSalesCaseAction, MarkSalesCaseMundurAction, MarkSalesCaseRejectedAction, MoveSalesCaseUnitAction, RecordBiCheckAction (+26 more)

### Community 21 - "i"
Cohesion: 0.04
Nodes (140): aa(), addElement(), Ah(), b1(), balance(), balanced(), baseIndent(), baseIndentFor() (+132 more)

### Community 22 - "CaseWorkflowActions"
Cohesion: 0.16
Nodes (8): CaseWorkflowActions, Action, ViewSalesCase, Action, PsjbsRelationManager, Filament\Actions\Action, Filament\Resources\Pages\ViewRecord, Heroicon

### Community 23 - "command"
Cohesion: 0.20
Nodes (9): command, enabled, type, mcp, laravel-boost, $schema, artisan, boost:mcp (+1 more)

### Community 24 - "Queue and Job Best Practices"
Cohesion: 0.22
Nodes (8): Atomic locks for race conditions, Caching Best Practices, Queue and Job Best Practices, Progressive backoff for transient failures, Bus::batch group coordination, ShouldBeUnique dispatch deduplication, Task Scheduling Best Practices, withoutOverlapping() overlap prevention

### Community 25 - "Security Best Practices"
Cohesion: 0.25
Nodes (9): Configuration Best Practices, Encrypted environment files, env() only in config files, Error Handling Best Practices, Exception report()/render() methods, Security Best Practices, CSRF protection in Blade forms, Mass assignment protection via $fillable (+1 more)

### Community 26 - "stat/chart.js"
Cohesion: 0.02
Nodes (96): aa(), addControllers(), addPlugins(), addScales(), an(), applyStack(), ba(), beforeDatasetDraw() (+88 more)

### Community 27 - "DatabaseSeeder.php"
Cohesion: 0.28
Nodes (6): DatabaseSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder, Spatie\Permission\DefaultTeamResolver, Spatie\Permission\Models\Permission, Spatie\Permission\Models\Role

### Community 28 - "Test Value Review Checklist"
Cohesion: 0.25
Nodes (7): Assert the Complete Result, Endpoint Coverage Matrix, Tenant Isolation: 404 Over 403, Time and Randomness Control, Test Name as Specification, Test Value Review Checklist, Cross-Tenant Access Test

### Community 29 - "laravel-best-practices skill"
Cohesion: 0.29
Nodes (7): HTTP Client Best Practices, Explicit HTTP client timeouts, Idempotency keys for safe retries, Convention and Style Best Practices, Laravel naming convention table, laravel-best-practices skill, testing-best-practices skill

### Community 30 - "tailwindcss-development skill"
Cohesion: 0.33
Nodes (6): Blade and View Best Practices, Blade components with attribute bags, tailwindcss-development skill, Tailwind v4 CSS-first @theme configuration, dark: variant support, gap utilities for sibling spacing

### Community 31 - "Routing and Controller Best Practices"
Cohesion: 0.33
Nodes (6): Routing and Controller Best Practices, Resource controller organization, Implicit route model binding, Authorization of protected actions, Validation and Forms Best Practices, Form Request validation boundary

### Community 32 - "markdown-editor.js"
Cohesion: 0.04
Nodes (99): ad(), af(), ai(), al(), An(), ao(), bf(), bo() (+91 more)

### Community 33 - "infer-conventions skill"
Cohesion: 0.50
Nodes (5): infer-conventions skill, .ai/rules shared rules directory, Record decisions, not defaults test, record-rule Boost MCP tool, Consistency First principle

### Community 34 - "Architecture Best Practices"
Cohesion: 0.40
Nodes (5): Architecture Best Practices, Action class pattern, Constructor dependency injection preference, Context request-scoped data, defer() post-response work

### Community 35 - "Database Performance Best Practices"
Cohesion: 0.50
Nodes (5): Collection Best Practices, lazy() vs cursor() iteration tradeoffs, Database Performance Best Practices, N+1 query prevention via eager loading, Model::preventLazyLoading development guard

### Community 36 - "Events and Notifications Best Practices"
Cohesion: 0.50
Nodes (5): Events and Notifications Best Practices, ShouldDispatchAfterCommit, Queued notifications, Mail Best Practices, Queued mailables with afterCommit()

### Community 37 - "bootstrap/app.php"
Cohesion: 0.40
Nodes (4): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware, Illuminate\Http\Request

### Community 38 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 39 - "Validation and Forms Best Practices (Laravel rules)"
Cohesion: 0.50
Nodes (4): Routing and Controller Best Practices (Laravel rules), Validation and Forms Best Practices (Laravel rules), Validation message testing per rule, Data providers for parameterized cases

### Community 41 - "Consistency First (testing skill)"
Cohesion: 1.00
Nodes (3): Consistency First (testing skill), Document Reality, Never Judge (consistency first), Consistency First (laravel skill)

### Community 43 - "Action Classes for Focused Business Operations"
Cohesion: 0.67
Nodes (3): Action/Service Structure Fork, Business Logic Location Fork, Action Classes for Focused Business Operations

### Community 44 - "Correlated Subquery with addSelect"
Cohesion: 0.67
Nodes (3): Correlated Subquery with addSelect(), Eager Loading Before Iterating (N+1), Local Scopes for Reusable Queries

### Community 45 - "Process Large Data Sets Incrementally (chunk/chunkById/cursor/lazy)"
Cohesion: 0.67
Nodes (3): cursor() vs lazy() Tradeoff, lazyById() for Safe Iteration While Updating, Process Large Data Sets Incrementally (chunk/chunkById/cursor/lazy)

### Community 46 - "Notification afterCommit dispatch"
Cohesion: 0.67
Nodes (3): Notification afterCommit dispatch, ShouldDispatchAfterCommit, Mailable afterCommit dispatch

### Community 47 - "Framework fakes for facades"
Cohesion: 0.67
Nodes (3): Delivery mode assertions (assertQueued vs assertSent), Framework fakes for facades, Global fakes in base TestCase setUp

### Community 48 - "Form request extraction at the boundary"
Cohesion: 0.67
Nodes (3): Controllers focused on HTTP concerns, Validate and store uploads safely, Form request extraction at the boundary

### Community 137 - "Laravel Best Practices (skill)"
Cohesion: 0.12
Nodes (16): Advanced Query Best Practices (rule), Architecture Best Practices (rule), Blade and View Best Practices (rule), Caching Best Practices (rule), Collection Best Practices (rule), Configuration Best Practices (rule), Database Performance Best Practices (rule), Eloquent Best Practices (rule) (+8 more)

### Community 144 - "resolve"
Cohesion: 0.06
Nodes (109): addCommands(), addKeyboardShortcuts(), after(), al(), before(), blockRange(), Bs(), canReplace() (+101 more)

### Community 147 - "fromObject"
Cohesion: 0.04
Nodes (104): ae(), after(), Am(), before(), bm(), buildFormatParser(), C(), _cachedScopes() (+96 more)

### Community 150 - ".forEach"
Cohesion: 0.03
Nodes (107): addGlobalAttributes(), addInputRules(), addMark(), addPasteRules(), addStoredMark(), addToSet(), Ah(), ao() (+99 more)

### Community 158 - "constructor"
Cohesion: 0.05
Nodes (54): bd(), bg(), chartOptionScopes(), constructor(), Cs(), data(), dd(), Ec() (+46 more)

### Community 164 - "draw"
Cohesion: 0.04
Nodes (77): acquireContext(), adjustHitBoxes(), afterDraw(), ar(), Be(), beforeDatasetDraw(), beforeDatasetsDraw(), beforeDraw() (+69 more)

### Community 168 - ".slice"
Cohesion: 0.05
Nodes (98): Ac(), addNodeMark(), ag(), allowedMarks(), allowsMarks(), bu(), _c(), checkContent() (+90 more)

### Community 205 - "isHorizontal"
Cohesion: 0.05
Nodes (61): afterAutoSkip(), buildLookupTable(), buildTicks(), calculateCircumference(), calculateLabelRotation(), _calculatePadding(), _circumference(), _computeGridLineItems() (+53 more)

### Community 206 - "r"
Cohesion: 0.05
Nodes (90): _0(), af(), append(), au(), bl(), Cc(), clearIncompatible(), coordsAtPos() (+82 more)

### Community 207 - "get"
Cohesion: 0.04
Nodes (88): addBlockWidget(), addBreak(), addComposition(), addDelimiter(), addInlineWidget(), addLine(), addLineStart(), addLineStartIfNotCovered() (+80 more)

### Community 208 - "slice"
Cohesion: 0.04
Nodes (89): addChanges(), addChild(), addGaps(), addLeafElement(), addNode(), addSelection(), advance(), ATXHeading() (+81 more)

### Community 209 - "n"
Cohesion: 0.04
Nodes (89): Ad(), an(), ay(), $b(), Bd(), bt(), by(), cl() (+81 more)

### Community 210 - "support.js"
Cohesion: 0.05
Nodes (75): acquireScrollLock(), ai(), e(), Bi(), br(), Bt(), ca(), close() (+67 more)

### Community 211 - "n"
Cohesion: 0.09
Nodes (83): _a(), Ae(), ar(), as(), Ba(), ee(), ue(), u() (+75 more)

### Community 212 - "columns/select.js"
Cohesion: 0.07
Nodes (39): A(), An(), applyDisabledState(), b(), Bt(), Cn(), disable(), Dn() (+31 more)

### Community 213 - "facet"
Cohesion: 0.04
Nodes (75): accept(), active(), baseTheme(), between(), blur(), bu(), build(), ch() (+67 more)

### Community 214 - "prop"
Cohesion: 0.05
Nodes (73): AQ(), atLastNode(), au(), child(), childAfter(), childBefore(), continue(), cursor() (+65 more)

### Community 215 - "r"
Cohesion: 0.04
Nodes (93): _a(), ad(), average(), beforeLayout(), bf(), bi(), Bn(), Bo() (+85 more)

### Community 216 - "o"
Cohesion: 0.07
Nodes (58): A(), add(), ar(), aspectRatio(), bl(), br(), Cs(), da() (+50 more)

### Community 217 - "parse"
Cohesion: 0.07
Nodes (40): al(), determineDataLimits(), endOf(), formats(), getAllParsedValues(), getDataTimestamps(), _getLabelBounds(), getLabels() (+32 more)

### Community 218 - "_update"
Cohesion: 0.05
Nodes (79): Image(), Ot(), acquireContext(), addElements(), afterBuildTicks(), afterCalculateLabelRotation(), afterDataLimits(), afterFit() (+71 more)

### Community 219 - "tables.js"
Cohesion: 0.08
Nodes (69): A(), ae(), areRecordsPartiallySelected(), areRecordsSelected(), areRecordsToggleable(), B(), be(), C() (+61 more)

### Community 220 - "e"
Cohesion: 0.08
Nodes (55): add(), addNodeView(), addProseMirrorPlugins(), AS(), Bf(), Bm(), cellsInRect(), colCount() (+47 more)

### Community 221 - "t"
Cohesion: 0.06
Nodes (53): a$(), activeForPoint(), addBlock(), addLineDeco(), as(), blankContent(), boundChange(), chunkEnd() (+45 more)

### Community 222 - "reduce"
Cohesion: 0.06
Nodes (53): addActions(), advanceFully(), advanceStack(), allActions(), c0(), canShift(), close(), cO() (+45 more)

### Community 223 - "notifications.js"
Cohesion: 0.06
Nodes (31): actions(), button(), c(), close(), configureAnimations(), configureTransitions(), constructor(), danger() (+23 more)

### Community 224 - "y"
Cohesion: 0.17
Nodes (69): at(), b(), Be(), $c(), X(), ca(), Cr(), Ct() (+61 more)

### Community 225 - "te"
Cohesion: 0.05
Nodes (11): Bn(), Id(), ji(), on(), qd(), qi(), Ri(), te() (+3 more)

### Community 226 - "ce"
Cohesion: 0.10
Nodes (35): Ac(), bl(), ce(), cl(), Dc(), Do(), el(), Et() (+27 more)

### Community 227 - "lP"
Cohesion: 0.06
Nodes (50): activateHover(), addToSet(), applyEdits(), cd(), changeByRange(), changes(), childString(), computeBlockGapDeco() (+42 more)

### Community 228 - "sliceDoc"
Cohesion: 0.06
Nodes (50): aO(), bd(), Bh(), charCategorizer(), clearDelayedAndroidKey(), d0(), De(), delayAndroidKey() (+42 more)

### Community 229 - "Cn"
Cohesion: 0.13
Nodes (46): Cn(), b(), Be(), Ce(), De(), dn(), _e(), Fe() (+38 more)

### Community 230 - "r"
Cohesion: 0.15
Nodes (43): _a(), ar(), c(), f(), d(), di(), g(), Hi() (+35 more)

### Community 231 - "_update"
Cohesion: 0.04
Nodes (97): active(), addBox(), addElements(), afterBuildTicks(), afterCalculateLabelRotation(), afterDataLimits(), afterFit(), afterSetDimensions() (+89 more)

### Community 232 - "Branch"
Cohesion: 0.13
Nodes (5): Branch, Consumer, PhaseTwoCaseWorkflowTest, PhaseTwoConsumerTest, PhaseTwoSalesCaseTest

### Community 233 - "components/select.js"
Cohesion: 0.09
Nodes (36): A(), An(), b(), Cn(), D(), Dn(), dt(), E() (+28 more)

### Community 234 - "fn"
Cohesion: 0.14
Nodes (41): ae(), At(), bi(), bn(), Ce(), ci(), ct(), de() (+33 more)

### Community 235 - "at"
Cohesion: 0.10
Nodes (42): Rd(), $a(), ak(), at(), bk(), c(), bp(), Dk() (+34 more)

### Community 236 - "selectOption"
Cohesion: 0.15
Nodes (33): addSingleSelectionDisplay(), closeDropdown(), constructor(), createOptionElement(), deferPositionDropdown(), destroy(), filterOptions(), focusNextOption() (+25 more)

### Community 237 - "isHorizontal"
Cohesion: 0.08
Nodes (42): afterAutoSkip(), Bt(), buildLookupTable(), buildTicks(), calculateLabelRotation(), _calculatePadding(), Cl(), _computeGridLineItems() (+34 more)

### Community 238 - "parse"
Cohesion: 0.07
Nodes (48): aa(), ah(), B(), br(), bs(), cd(), Ci(), createResolver() (+40 more)

### Community 239 - "go"
Cohesion: 0.08
Nodes (33): th(), alpha(), ao(), be(), co(), darken(), desaturate(), es() (+25 more)

### Community 240 - "add"
Cohesion: 0.09
Nodes (27): add(), addEventListener(), au(), bindResponsiveEvents(), Ch(), Ds(), eu(), getAllParsedValues() (+19 more)

### Community 241 - "updateElements"
Cohesion: 0.04
Nodes (79): af(), afterDatasetsUpdate(), applyStack(), at(), Ba(), _calculateBarIndexPixels(), _calculateBarValuePixels(), _computeAngle() (+71 more)

### Community 242 - "replace"
Cohesion: 0.09
Nodes (37): addAttributes(), addOptions(), B0(), c1(), cf(), Ck(), d1(), De() (+29 more)

### Community 243 - "echo.js"
Cohesion: 0.08
Nodes (22): a(), ar(), Ce(), cr(), De(), Dt(), Fe(), H() (+14 more)

### Community 244 - "ae"
Cohesion: 0.09
Nodes (34): ae(), Ao(), as(), B(), Kt(), cs(), Ee(), Ge() (+26 more)

### Community 245 - "draw"
Cohesion: 0.11
Nodes (28): Ae(), _computeLabelArea(), Dl(), draw(), drawBackground(), _drawDataset(), _drawDatasets(), drawGrid() (+20 more)

### Community 246 - "fn"
Cohesion: 0.13
Nodes (33): aa(), At(), ba(), cr(), da(), de(), dt(), ei() (+25 more)

### Community 247 - "slider.js"
Cohesion: 0.13
Nodes (29): ar(), Be(), Ce(), _e(), Ee(), er(), et(), Fe() (+21 more)

### Community 248 - "find"
Cohesion: 0.10
Nodes (31): baseDirAt(), bidiIn(), bidiSpans(), bidiSpansAt(), bP(), checkHover(), coordsAt(), coordsAtPos() (+23 more)

### Community 249 - "jt"
Cohesion: 0.13
Nodes (40): ae(), At(), bi(), bn(), ci(), ct(), de(), di() (+32 more)

### Community 250 - "file-upload.js"
Cohesion: 0.07
Nodes (12): hc(), cm(), constructor(), define(), dm(), getExtension(), _getTestState(), gm() (+4 more)

### Community 251 - "ir"
Cohesion: 0.14
Nodes (24): De(), ir(), Ct(), Dt(), Et(), ge(), he(), ht() (+16 more)

### Community 252 - "closeDropdown"
Cohesion: 0.23
Nodes (17): applyDisabledState(), closeDropdown(), constructor(), destroy(), disable(), enable(), focusNextOption(), focusPreviousOption() (+9 more)

### Community 253 - "e"
Cohesion: 0.08
Nodes (36): Hn(), addEventListener(), apply(), bindEvents(), bindResponsiveEvents(), bindUserEvents(), bs(), _checkEventBindings() (+28 more)

### Community 255 - "addElementByRule"
Cohesion: 0.15
Nodes (24): addAll(), addDOM(), addElement(), addElementByRule(), addTextNode(), allowsMarkType(), closeExtra(), currentPos() (+16 more)

### Community 256 - "apply"
Cohesion: 0.11
Nodes (24): addInner(), apply(), applyInner(), applyTransaction(), fail(), filterTransaction(), findIndex(), fromReplace() (+16 more)

### Community 257 - "invert"
Cohesion: 0.12
Nodes (24): addMaps(), addStep(), addTransform(), appendMap(), appendMapping(), appendMappingInverted(), aw(), compress() (+16 more)

### Community 258 - "filament/app.js"
Cohesion: 0.14
Nodes (16): B(), close(), E(), F(), G(), init(), P(), setUpResizeObserver() (+8 more)

### Community 259 - "getDatasetMeta"
Cohesion: 0.10
Nodes (26): afterDatasetsUpdate(), buildOrUpdateControllers(), _destroyDatasetMeta(), getController(), getDatasetMeta(), getElement(), _handleEvent(), hide() (+18 more)

### Community 260 - "c"
Cohesion: 0.08
Nodes (36): themeClasses(), ai(), ci(), dataset(), Dn(), _e(), fa(), c() (+28 more)

### Community 261 - "toString"
Cohesion: 0.13
Nodes (21): Bc(), check(), checkAttrs(), endIndex(), getObj(), hasProtocol(), $i(), Ra() (+13 more)

### Community 262 - "E"
Cohesion: 0.09
Nodes (26): aspectRatio(), bu(), contains(), E(), Ea(), eh(), ff(), getBasePosition() (+18 more)

### Community 263 - "W"
Cohesion: 0.14
Nodes (16): bi(), buildOrUpdateScales(), clear(), _destroy(), di(), ensureScalesHaveIDs(), er(), getScale() (+8 more)

### Community 264 - "Ft"
Cohesion: 0.23
Nodes (14): ei(), Ft(), Ie(), Le(), ln(), ni(), oe(), on() (+6 more)

### Community 265 - "dx"
Cohesion: 0.20
Nodes (17): Ei(), Aa(), Bi(), Gr(), Jr(), Kr(), Na(), no() (+9 more)

### Community 266 - "_notify"
Cohesion: 0.13
Nodes (19): active(), _animateOptions(), cancel(), _createAnimations(), _createDescriptors(), _descriptors(), getPlugin(), getRange() (+11 more)

### Community 267 - "renderOptions"
Cohesion: 0.37
Nodes (13): createOptionElement(), deferPositionDropdown(), filterOptions(), handleSearch(), hideLoadingState(), openDropdown(), populateLabelRepositoryFromOptions(), positionDropdown() (+5 more)

### Community 268 - "g$"
Cohesion: 0.17
Nodes (16): acceptToken(), allows(), eh(), g$(), GO(), kY(), lc(), oh() (+8 more)

### Community 269 - "color-picker.js"
Cohesion: 0.13
Nodes (4): [g](), style(), update(), [x]()

### Community 270 - "fn"
Cohesion: 0.24
Nodes (16): Ce(), ei(), fn(), Ie(), i(), Kt(), ln(), ni() (+8 more)

### Community 271 - "cc"
Cohesion: 0.16
Nodes (14): attrs(), bi(), cc(), configure(), gQ(), JQ(), kr(), parseDialect() (+6 more)

### Community 272 - "date-time-picker.js"
Cohesion: 0.29
Nodes (7): d(), e(), i(), m(), r(), s(), t()

### Community 273 - "selectOption"
Cohesion: 0.20
Nodes (15): addBadgesForSelectedOptions(), addSingleBadge(), addSingleSelectionDisplay(), bt(), createBadgeElement(), createRemoveButton(), getLabelForSingleSelection(), getLabelsForMultipleSelection() (+7 more)

### Community 274 - "q"
Cohesion: 0.20
Nodes (12): H(), J(), L(), q(), k(), b(), d(), f() (+4 more)

### Community 275 - "r"
Cohesion: 0.17
Nodes (12): Be(), ei(), ii(), le(), ni(), oi(), r(), ri() (+4 more)

### Community 276 - "oe"
Cohesion: 0.21
Nodes (14): Ce(), Dp(), $e(), Fl(), mm(), oe(), pe(), q() (+6 more)

### Community 277 - "_handleEvent"
Cohesion: 0.28
Nodes (9): An(), _handleEvent(), _positionChanged(), setActiveElements(), updateHoverStyle(), _updateHoverStyles(), Ve(), vf() (+1 more)

### Community 278 - "N"
Cohesion: 0.33
Nodes (11): ae(), A(), E(), at(), be(), Gt(), i(), Jt() (+3 more)

### Community 279 - "Mt"
Cohesion: 0.24
Nodes (11): apply(), fs(), go(), Hr(), T(), ir(), it(), Mt() (+3 more)

### Community 280 - "addSingleBadge"
Cohesion: 0.33
Nodes (6): addBadgesForSelectedOptions(), addSingleBadge(), createBadgeElement(), createRemoveButton(), getLabelForSingleSelection(), getSelectedOptionLabel()

### Community 281 - "Testing Best Practices skill"
Cohesion: 0.24
Nodes (10): Assertions rules (testing skill), Endpoint Tests rules (testing skill), Finding Test Framework Features rules (testing skill), Fakes, Mocks, and Determinism rules (testing skill), Naming and Structure rules (testing skill), Test Suite Performance rules (testing skill), Reviewing Tests rules (testing skill), Security Tests rules (testing skill) (+2 more)

### Community 282 - "require-dev"
Cohesion: 0.20
Nodes (10): require-dev, fakerphp/faker, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, mockery/mockery (+2 more)

### Community 283 - "actions/actions.js"
Cohesion: 0.44
Nodes (8): closeModal(), generateModalId(), getActionNestingIndexFromModalId(), init(), openModal(), rememberPreviouslyFocusedElement(), restorePreviouslyFocusedElement(), syncActionModals()

### Community 284 - "t"
Cohesion: 0.22
Nodes (9): di(), e(), Ht(), Ie(), Re(), t(), w(), xr() (+1 more)

### Community 286 - "T"
Cohesion: 0.25
Nodes (16): Ft(), ce(), de(), fe(), ft(), kt(), le(), nt() (+8 more)

### Community 287 - "Pe"
Cohesion: 0.12
Nodes (30): bc(), cd(), me(), dd(), dt(), Ft(), Ie(), it() (+22 more)

### Community 288 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 289 - "setup"
Cohesion: 0.29
Nodes (7): post-root-package-install, setup, composer install, @php artisan db:seed --force, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 290 - "sl"
Cohesion: 0.33
Nodes (7): Cp(), da(), Gp(), kp(), Np(), sl(), Vp()

### Community 291 - "require"
Cohesion: 0.33
Nodes (6): require, filament/filament, laravel/framework, laravel/tinker, php, spatie/laravel-permission

### Community 292 - "st"
Cohesion: 0.47
Nodes (6): ca(), Ea(), nm(), st(), ya(), yt()

### Community 293 - "Pt"
Cohesion: 0.25
Nodes (8): Ae(), Bt(), ee(), ne(), Pt(), ue(), Z(), jt()

### Community 295 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 296 - "yl"
Cohesion: 0.40
Nodes (5): Bp(), om(), Op(), rl(), yl()

### Community 297 - "clickPercent"
Cohesion: 0.60
Nodes (5): clickPercent(), getPosition(), mouseUp(), movePlayhead(), timelineClicked()

### Community 299 - "keywords"
Cohesion: 0.50
Nodes (4): keywords, filament, laravel, sales-operations

### Community 300 - "post-autoload-dump"
Cohesion: 0.50
Nodes (4): post-autoload-dump, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump, @php artisan filament:upgrade, @php artisan package:discover --ansi

### Community 301 - ".opencode/opencode.json"
Cohesion: 0.50
Nodes (3): plugin, $schema, .opencode/plugins/graphify.js

### Community 302 - "c"
Cohesion: 0.67
Nodes (4): c(), o(), p(), s()

### Community 304 - "dev"
Cohesion: 0.67
Nodes (3): dev, Composer\\Config::disableProcessTimeout, @php artisan dev

## Knowledge Gaps
- **149 isolated node(s):** `php`, `$schema`, `.opencode/plugins/graphify.js`, `Controller`, `$schema` (+144 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 1067 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **145 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `update()` connect `constructor` to `c`, `dx`, `g$`, `code-editor.js`, `rich-editor.js`, `resolve`, `components/chart.js`, `i`, `markdown-editor.js`, `get`, `slice`, `n`, `n`, `facet`, `t`, `reduce`, `y`, `te`, `lP`, `sliceDoc`, `at`, `replace`, `echo.js`, `find`?**
  _High betweenness centrality (0.057) - this node is a cross-community bridge._
- **Why does `Wi()` connect `at` to `components/select.js`, `code-editor.js`, `rich-editor.js`, `constructor`, `columns/select.js`, `facet`?**
  _High betweenness centrality (0.034) - this node is a cross-community bridge._
- **Why does `Is()` connect `r` to `components/chart.js`, `rich-editor.js`?**
  _High betweenness centrality (0.021) - this node is a cross-community bridge._
- **Are the 17 inferred relationships involving `constructor()` (e.g. with `a()` and `h()`) actually correct?**
  _`constructor()` has 17 INFERRED edges - model-reasoned connections that need verification._
- **Are the 26 inferred relationships involving `update()` (e.g. with `Pr()` and `a()`) actually correct?**
  _`update()` has 26 INFERRED edges - model-reasoned connections that need verification._
- **Are the 2 inferred relationships involving `resolve()` (e.g. with `s()` and `i()`) actually correct?**
  _`resolve()` has 2 INFERRED edges - model-reasoned connections that need verification._
- **Are the 19 inferred relationships involving `y()` (e.g. with `$c()` and `D()`) actually correct?**
  _`y()` has 19 INFERRED edges - model-reasoned connections that need verification._