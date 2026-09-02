# Graph Report - Database-Oasis  (2026-09-02)

## Corpus Check
- 277 files · ~132,024 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 6497 nodes · 20848 edges · 318 communities (154 shown, 144 thin omitted)
- Extraction: 89% EXTRACTED · 11% INFERRED · 0% AMBIGUOUS · INFERRED: 2381 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `f2c0fd47`
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
- SalesCaseStatus
- i
- Filament\Actions\Action
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
- find
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
- child
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
- finish
- addCommands
- support.js
- n
- columns/select.js
- facet
- prop
- r
- o
- parse
- constructor
- tables.js
- nodeAt
- Rh
- advance
- notifications.js
- y
- te
- vd
- slice
- keydown
- Cn
- r
- _update
- Branch
- components/select.js
- fn
- apply
- selectOption
- isHorizontal
- parse
- eq
- add
- updateElements
- fn
- echo.js
- ae
- ml
- fn
- slider.js
- find
- jt
- file-upload.js
- ir
- closeDropdown
- W
- PhaseThreePsjbTest
- toString
- BiCheckResult
- .slice
- filament/app.js
- getDatasetMeta
- pt
- getContext
- E
- _each
- Ft
- dx
- _update
- Bank
- sliceDoc
- color-picker.js
- fn
- W
- date-time-picker.js
- selectOption
- q
- r
- oe
- _handleEvent
- se
- Mt
- addSingleBadge
- Testing Best Practices skill
- require-dev
- actions/actions.js
- t
- schemas.js
- ut
- O
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
- Wi
- keywords
- post-autoload-dump
- .opencode/opencode.json
- c
- xn
- dev
- graphify.js
- O
- nr
- et

## God Nodes (most connected - your core abstractions)
1. `User` - 234 edges
2. `constructor()` - 151 edges
3. `update()` - 148 edges
4. `SalesCase` - 103 edges
5. `Branch` - 94 edges
6. `resolve()` - 94 edges
7. `y()` - 93 edges
8. `_update()` - 87 edges
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

## Communities (318 total, 144 thin omitted)

### Community 0 - "composer.json"
Cohesion: 0.14
Nodes (13): autoload-dev, psr-4, description, extra, laravel, dont-discover, license, minimum-stability (+5 more)

### Community 1 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.08
Nodes (14): BankFactory, static, BranchFactory, static, ConsumerFactory, ProjectFactory, static, PsjbFactory (+6 more)

### Community 2 - "User"
Cohesion: 0.03
Nodes (20): self, User, BankPolicy, BankProcessPolicy, BiCheckPolicy, BranchPolicy, ConsumerPolicy, DocumentSubmissionPolicy (+12 more)

### Community 3 - "Master Build Pack - Database Oasis"
Cohesion: 0.14
Nodes (30): AGENTS.md - Laravel Boost + Graphify Guidelines, .ai/rules Project Rules, Code Quality Toolchain (Pint, PHPStan, PHPUnit), Graphify Knowledge Graph Workflow, Laravel Boost MCP Tools, CLAUDE.md - Laravel Boost Guidelines, compose.yaml app service (Laravel app), compose.yaml postgres service (PostgreSQL 17) (+22 more)

### Community 4 - "Illuminate\Database\Migrations\Migration"
Cohesion: 0.07
Nodes (3): Illuminate\Database\Migrations\Migration, Illuminate\Database\Schema\Blueprint, Illuminate\Support\Facades\Schema

### Community 5 - "scripts"
Cohesion: 0.14
Nodes (14): scripts, analyse, format, post-create-project-cmd, post-update-cmd, pre-package-uninstall, test, Illuminate\\Foundation\\ComposerScripts::prePackageUninstall (+6 more)

### Community 6 - "AdminPanelProvider.php"
Cohesion: 0.09
Nodes (18): AppServiceProvider, AdminPanelProvider, Filament\Http\Middleware\Authenticate, Filament\Http\Middleware\AuthenticateSession, Filament\Http\Middleware\DisableBladeIconComponents, Filament\Http\Middleware\DispatchServingFilamentEvent, Filament\Pages\Dashboard, Filament\PanelProvider (+10 more)

### Community 7 - "Filament\Tables\Table"
Cohesion: 0.04
Nodes (58): BankProcessForm, BankProcessesTable, BankResource, ManageBanks, BiChecksTable, BranchResource, ManageBranches, ConsumerResource (+50 more)

### Community 8 - "Unit"
Cohesion: 0.06
Nodes (9): SalesCaseForm, Project, Unit, UnitPolicy, UtilityStatus, static, SalesCaseFactory, UnitFactory (+1 more)

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
Cohesion: 0.04
Nodes (19): BiCheckForm, PsjbForm, BankProcess, self, DocumentSubmission, Model, Psjb, SalesCase (+11 more)

### Community 13 - "e"
Cohesion: 0.05
Nodes (51): alpha(), apply(), co(), color(), darken(), desaturate(), e(), fo() (+43 more)

### Community 14 - "code-editor.js"
Cohesion: 0.01
Nodes (137): Ac(), addActive(), addCompletion(), addCompletions(), addNamespace(), addNamespaceObject(), Ag(), Ar() (+129 more)

### Community 15 - "rich-editor.js"
Cohesion: 0.01
Nodes (211): aa(), add(), addExtensions(), addHackNode(), addNodeMark(), addTextblockHacks(), an(), applyAspectRatio() (+203 more)

### Community 16 - "components/chart.js"
Cohesion: 0.01
Nodes (123): abutsStart(), ac(), addControllers(), addPlugins(), addScales(), Ag(), cc(), Cl() (+115 more)

### Community 17 - "Filament\Resources\Pages\CreateRecord"
Cohesion: 0.04
Nodes (32): BankProcessResource, CreateBankProcess, ListBankProcesses, BiCheckResource, CreateBiCheck, ListBiChecks, CreateConsumer, EditConsumer (+24 more)

### Community 18 - "Convention Detection Checklist"
Cohesion: 0.20
Nodes (11): Convention Detection Checklist, 49 Laravel convention dimensions, Advanced Query Best Practices, Correlated subquery pattern, Eloquent Best Practices, Attribute casts, Global scope tradeoff, Local query scopes (+3 more)

### Community 19 - "constructor"
Cohesion: 0.02
Nodes (158): active(), add(), addChunk(), addEventListener(), addInfoPane(), addInner(), addWindowListeners(), adjust() (+150 more)

### Community 20 - "SalesCaseStatus"
Cohesion: 0.06
Nodes (37): AdvanceCashCaseToPpjbAction, CancelPsjbAction, CancelSalesCaseAction, CloseSalesCaseAction, CreateDocumentSubmissionAction, CreatePsjbAction, CreateSalesCaseAction, MarkSalesCaseMundurAction (+29 more)

### Community 21 - "i"
Cohesion: 0.04
Nodes (161): a$(), aa(), addElement(), as(), AX(), b1(), balance(), baseIndent() (+153 more)

### Community 22 - "Filament\Actions\Action"
Cohesion: 0.13
Nodes (10): CaseWorkflowActions, Action, ViewSalesCase, BankProcessesRelationManager, Action, Action, PsjbsRelationManager, Filament\Actions\Action (+2 more)

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
Cohesion: 0.03
Nodes (97): th(), aa(), active(), ai(), alpha(), an(), _animateOptions(), ao() (+89 more)

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
Nodes (105): Ac(), af(), ai(), ao(), ar(), bf(), bl(), bo() (+97 more)

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
Cohesion: 0.04
Nodes (122): Ad(), after(), al(), ay(), Bd(), before(), bl(), Bs() (+114 more)

### Community 147 - "fromObject"
Cohesion: 0.04
Nodes (104): ae(), after(), Am(), before(), bm(), buildFormatParser(), C(), _cachedScopes() (+96 more)

### Community 150 - "find"
Cohesion: 0.06
Nodes (47): addGlobalAttributes(), addInputRules(), addMark(), Ah(), Ax(), childAfter(), childBefore(), dispatchTransaction() (+39 more)

### Community 158 - "constructor"
Cohesion: 0.05
Nodes (54): bd(), bg(), chartOptionScopes(), constructor(), Cs(), data(), dd(), Ec() (+46 more)

### Community 164 - "draw"
Cohesion: 0.04
Nodes (77): acquireContext(), adjustHitBoxes(), afterDraw(), ar(), Be(), beforeDatasetDraw(), beforeDatasetsDraw(), beforeDraw() (+69 more)

### Community 168 - "child"
Cohesion: 0.06
Nodes (109): Ac(), addKeyboardShortcuts(), ag(), allowsMarks(), blockRange(), bu(), canReplace(), canReplaceWith() (+101 more)

### Community 205 - "isHorizontal"
Cohesion: 0.05
Nodes (61): afterAutoSkip(), buildLookupTable(), buildTicks(), calculateCircumference(), calculateLabelRotation(), _calculatePadding(), _circumference(), _computeGridLineItems() (+53 more)

### Community 206 - "r"
Cohesion: 0.04
Nodes (137): _0(), addAttributes(), addNodeView(), addOptions(), addPasteRules(), addProseMirrorPlugins(), af(), au() (+129 more)

### Community 207 - "get"
Cohesion: 0.04
Nodes (91): addBlockWidget(), addBreak(), addComposition(), addDelimiter(), addInlineWidget(), addLine(), addLineStart(), addLineStartIfNotCovered() (+83 more)

### Community 208 - "finish"
Cohesion: 0.08
Nodes (35): addChild(), addGaps(), addLeafElement(), addNode(), applyChanges(), ATXHeading(), balanced(), _c() (+27 more)

### Community 209 - "addCommands"
Cohesion: 0.14
Nodes (24): addCommands(), addStoredMark(), createChecked(), ensureMarks(), handleExit(), i1(), insertText(), isActive() (+16 more)

### Community 210 - "support.js"
Cohesion: 0.05
Nodes (75): acquireScrollLock(), ai(), e(), Bi(), br(), Bt(), ca(), close() (+67 more)

### Community 211 - "n"
Cohesion: 0.07
Nodes (97): _a(), ad(), An(), bc(), Bt(), cd(), ee(), ue() (+89 more)

### Community 212 - "columns/select.js"
Cohesion: 0.07
Nodes (39): A(), An(), applyDisabledState(), b(), Bt(), Cn(), disable(), Dn() (+31 more)

### Community 213 - "facet"
Cohesion: 0.04
Nodes (83): accept(), Ah(), applyTransaction(), asSingle(), baseTheme(), blur(), bu(), build() (+75 more)

### Community 214 - "prop"
Cohesion: 0.05
Nodes (76): AQ(), atLastNode(), au(), child(), childAfter(), childBefore(), cursor(), cursorAt() (+68 more)

### Community 215 - "r"
Cohesion: 0.04
Nodes (93): _a(), ad(), average(), beforeLayout(), bf(), bi(), Bn(), Bo() (+85 more)

### Community 216 - "o"
Cohesion: 0.06
Nodes (66): A(), add(), al(), ar(), bs(), _computeLabelArea(), Cs(), da() (+58 more)

### Community 217 - "parse"
Cohesion: 0.06
Nodes (44): br(), Cn(), determineDataLimits(), ds(), el(), endOf(), formats(), ga() (+36 more)

### Community 218 - "constructor"
Cohesion: 0.07
Nodes (40): Ot(), apply(), bn(), bo(), _cachedScopes(), chartOptionScopes(), configure(), constructor() (+32 more)

### Community 219 - "tables.js"
Cohesion: 0.08
Nodes (69): A(), ae(), areRecordsPartiallySelected(), areRecordsSelected(), areRecordsToggleable(), B(), be(), C() (+61 more)

### Community 220 - "nodeAt"
Cohesion: 0.22
Nodes (22): AS(), cellsInRect(), colCount(), dS(), fS(), insert(), isRowSelection(), Ms() (+14 more)

### Community 221 - "Rh"
Cohesion: 0.17
Nodes (17): activeForPoint(), addBlock(), addLineDeco(), blankContent(), boundChange(), comparePoint(), compareRange(), ensureLine() (+9 more)

### Community 222 - "advance"
Cohesion: 0.05
Nodes (68): acceptToken(), addActions(), advance(), advanceFully(), advanceStack(), allActions(), allows(), break() (+60 more)

### Community 223 - "notifications.js"
Cohesion: 0.06
Nodes (31): actions(), button(), c(), close(), configureAnimations(), configureTransitions(), constructor(), danger() (+23 more)

### Community 224 - "y"
Cohesion: 0.16
Nodes (57): al(), at(), Be(), Cr(), Ct(), da(), de(), dr() (+49 more)

### Community 225 - "te"
Cohesion: 0.05
Nodes (8): Bn(), br(), ji(), qd(), Ri(), te(), Vi(), Xc()

### Community 226 - "vd"
Cohesion: 0.17
Nodes (22): Ae(), as(), ci(), ff(), A(), B(), Ot(), rt() (+14 more)

### Community 227 - "slice"
Cohesion: 0.05
Nodes (60): addChanges(), addSelection(), addToSet(), childString(), composeDesc(), decompose(), decomposeLeft(), decomposeRight() (+52 more)

### Community 228 - "keydown"
Cohesion: 0.07
Nodes (35): bd(), between(), Bh(), clearDelayedAndroidKey(), d0(), De(), delayAndroidKey(), Dg() (+27 more)

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
Cohesion: 0.11
Nodes (6): Branch, Consumer, PhaseFourFilamentTest, PhaseTwoCaseWorkflowTest, PhaseTwoConsumerTest, PhaseTwoSalesCaseTest

### Community 233 - "components/select.js"
Cohesion: 0.08
Nodes (40): A(), An(), applyDisabledState(), b(), Cn(), D(), disable(), Dn() (+32 more)

### Community 234 - "fn"
Cohesion: 0.14
Nodes (41): ae(), At(), bi(), bn(), Ce(), ci(), ct(), de() (+33 more)

### Community 235 - "apply"
Cohesion: 0.09
Nodes (45): ak(), apply(), applyInner(), applyTransaction(), at(), bk(), c(), bp() (+37 more)

### Community 236 - "selectOption"
Cohesion: 0.15
Nodes (33): addSingleSelectionDisplay(), closeDropdown(), constructor(), createOptionElement(), deferPositionDropdown(), destroy(), filterOptions(), focusNextOption() (+25 more)

### Community 237 - "isHorizontal"
Cohesion: 0.09
Nodes (37): afterAutoSkip(), buildLookupTable(), buildTicks(), _calculatePadding(), Cl(), _computeGridLineItems(), _computeLabelItems(), _computeLabelSizes() (+29 more)

### Community 238 - "parse"
Cohesion: 0.07
Nodes (48): aa(), ah(), B(), br(), bs(), cd(), Ci(), createResolver() (+40 more)

### Community 239 - "eq"
Cohesion: 0.06
Nodes (51): addNode(), ao(), append(), Cc(), deleteNode(), deleteRange(), destroyBetween(), destroyRest() (+43 more)

### Community 240 - "add"
Cohesion: 0.09
Nodes (27): add(), addEventListener(), au(), bindResponsiveEvents(), Ch(), Ds(), eu(), getAllParsedValues() (+19 more)

### Community 241 - "updateElements"
Cohesion: 0.04
Nodes (79): af(), afterDatasetsUpdate(), applyStack(), at(), Ba(), _calculateBarIndexPixels(), _calculateBarValuePixels(), _computeAngle() (+71 more)

### Community 242 - "fn"
Cohesion: 0.15
Nodes (22): themeClasses(), Ck(), De(), fn(), Gh(), ip(), Ja(), Jh() (+14 more)

### Community 243 - "echo.js"
Cohesion: 0.08
Nodes (22): a(), ar(), Ce(), cr(), De(), Dt(), Fe(), H() (+14 more)

### Community 244 - "ae"
Cohesion: 0.09
Nodes (34): ae(), Ao(), as(), B(), Kt(), cs(), Ee(), Ge() (+26 more)

### Community 245 - "ml"
Cohesion: 0.13
Nodes (22): bl(), calculateLabelRotation(), ci(), fa(), fn(), getIndexAngle(), getPointPosition(), jr() (+14 more)

### Community 246 - "fn"
Cohesion: 0.13
Nodes (33): aa(), At(), ba(), cr(), da(), de(), dt(), ei() (+25 more)

### Community 247 - "slider.js"
Cohesion: 0.12
Nodes (29): ar(), Be(), Ce(), De(), _e(), Ee(), er(), Fe() (+21 more)

### Community 248 - "find"
Cohesion: 0.09
Nodes (33): activateHover(), baseDirAt(), bidiIn(), bidiSpans(), bidiSpansAt(), bP(), cd(), checkHover() (+25 more)

### Community 249 - "jt"
Cohesion: 0.15
Nodes (31): ae(), At(), bi(), bn(), ci(), ct(), de(), di() (+23 more)

### Community 250 - "file-upload.js"
Cohesion: 0.07
Nodes (12): hc(), cm(), constructor(), define(), dm(), getExtension(), _getTestState(), gm() (+4 more)

### Community 251 - "ir"
Cohesion: 0.15
Nodes (26): ir(), at(), be(), ce(), de(), Dt(), ee(), Et() (+18 more)

### Community 252 - "closeDropdown"
Cohesion: 0.18
Nodes (27): closeDropdown(), constructor(), createOptionElement(), deferPositionDropdown(), destroy(), filterOptions(), focusNextOption(), focusPreviousOption() (+19 more)

### Community 253 - "W"
Cohesion: 0.08
Nodes (35): Hn(), addEventListener(), bindEvents(), bindResponsiveEvents(), bindUserEvents(), buildOrUpdateScales(), _checkEventBindings(), ensureScalesHaveIDs() (+27 more)

### Community 255 - "toString"
Cohesion: 0.06
Nodes (58): addAll(), addDOM(), addElement(), addElementByRule(), addTextNode(), addToSet(), allowedMarks(), allowsMarkType() (+50 more)

### Community 256 - "BiCheckResult"
Cohesion: 0.19
Nodes (6): BiCheckResult, BiCheck, self, BiCheckFactory, static, PhaseThreeBiCheckTest

### Community 257 - ".slice"
Cohesion: 0.06
Nodes (51): accepts(), addInner(), addMaps(), addStep(), addTransform(), appendMap(), appendMapping(), appendMappingInverted() (+43 more)

### Community 258 - "filament/app.js"
Cohesion: 0.14
Nodes (16): B(), close(), E(), F(), G(), init(), P(), setUpResizeObserver() (+8 more)

### Community 259 - "getDatasetMeta"
Cohesion: 0.09
Nodes (31): Ae(), afterDatasetsUpdate(), Dn(), _drawDataset(), first(), getDatasetMeta(), _handleEvent(), hide() (+23 more)

### Community 260 - "pt"
Cohesion: 0.21
Nodes (12): dataset(), ka(), nearest(), nn(), point(), pt(), _r(), Si() (+4 more)

### Community 261 - "getContext"
Cohesion: 0.07
Nodes (32): acquireContext(), Bt(), Ca(), data(), datasetAnimationScopeKeys(), datasetElementScopeKeys(), Dl(), dr() (+24 more)

### Community 262 - "E"
Cohesion: 0.09
Nodes (26): aspectRatio(), bu(), contains(), E(), Ea(), eh(), ff(), getBasePosition() (+18 more)

### Community 263 - "_each"
Cohesion: 0.06
Nodes (37): addControllers(), addElements(), addPlugins(), addScales(), bi(), buildOrUpdateControllers(), buildOrUpdateElements(), clear() (+29 more)

### Community 264 - "Ft"
Cohesion: 0.23
Nodes (14): ei(), Ft(), Ie(), Le(), ln(), ni(), oe(), on() (+6 more)

### Community 265 - "dx"
Cohesion: 0.17
Nodes (20): Ei(), Aa(), Ba(), Bi(), Jr(), ls(), mr(), Na() (+12 more)

### Community 266 - "_update"
Cohesion: 0.09
Nodes (37): Image(), afterBuildTicks(), afterCalculateLabelRotation(), afterDataLimits(), afterFit(), afterSetDimensions(), afterTickToLabelConversion(), afterUpdate() (+29 more)

### Community 267 - "Bank"
Cohesion: 0.28
Nodes (3): BankResponseType, Bank, PhaseFourBankWorkflowTest

### Community 268 - "sliceDoc"
Cohesion: 0.14
Nodes (21): aO(), charCategorizer(), Fc(), flatten(), getCursor(), getDeco(), gT(), highlight() (+13 more)

### Community 269 - "color-picker.js"
Cohesion: 0.13
Nodes (4): [g](), style(), update(), [x]()

### Community 270 - "fn"
Cohesion: 0.24
Nodes (16): Ce(), ei(), fn(), Ie(), i(), Kt(), ln(), ni() (+8 more)

### Community 271 - "W"
Cohesion: 0.07
Nodes (33): bi(), cO(), configure(), define(), domEventHandlers(), domEventObservers(), EY(), fromClass() (+25 more)

### Community 272 - "date-time-picker.js"
Cohesion: 0.26
Nodes (8): d(), e(), i(), m(), r(), s(), t(), rr()

### Community 273 - "selectOption"
Cohesion: 0.24
Nodes (13): addBadgesForSelectedOptions(), addSingleBadge(), addSingleSelectionDisplay(), bt(), createBadgeElement(), createRemoveButton(), getLabelForSingleSelection(), getSelectedOptionLabel() (+5 more)

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

### Community 278 - "se"
Cohesion: 0.25
Nodes (11): ae(), A(), E(), Ct(), lt(), ot(), se(), st() (+3 more)

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

### Community 286 - "ut"
Cohesion: 0.42
Nodes (9): Ft(), fe(), ft(), le(), oe(), P(), ut(), Vt() (+1 more)

### Community 287 - "O"
Cohesion: 0.20
Nodes (36): b(), $c(), X(), ca(), me(), D(), _e(), se() (+28 more)

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
Cohesion: 0.33
Nodes (6): Ae(), Bt(), ne(), Pt(), ue(), jt()

### Community 295 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 296 - "yl"
Cohesion: 0.40
Nodes (5): Bp(), om(), Op(), rl(), yl()

### Community 297 - "clickPercent"
Cohesion: 0.60
Nodes (5): clickPercent(), getPosition(), mouseUp(), movePlayhead(), timelineClicked()

### Community 298 - "Wi"
Cohesion: 0.24
Nodes (13): Rd(), $a(), Fx(), $h(), Ha(), ik(), jx(), Kx() (+5 more)

### Community 299 - "keywords"
Cohesion: 0.50
Nodes (4): keywords, filament, laravel, sales-operations

### Community 300 - "post-autoload-dump"
Cohesion: 0.50
Nodes (4): post-autoload-dump, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump, @php artisan filament:upgrade, @php artisan package:discover --ansi

### Community 301 - ".opencode/opencode.json"
Cohesion: 0.50
Nodes (3): plugin, $schema, ./plugins/graphify.js

### Community 302 - "c"
Cohesion: 0.67
Nodes (4): c(), o(), p(), s()

### Community 303 - "xn"
Cohesion: 0.33
Nodes (11): ge(), gi(), gn(), he(), $i(), lt(), mn(), wi() (+3 more)

### Community 304 - "dev"
Cohesion: 0.67
Nodes (3): dev, Composer\\Config::disableProcessTimeout, @php artisan dev

### Community 315 - "O"
Cohesion: 0.20
Nodes (10): aspectRatio(), getValueForDistanceFromCenter(), invalidate(), Mn(), O(), releaseContext(), rgbString(), unregister() (+2 more)

### Community 316 - "nr"
Cohesion: 0.29
Nodes (8): gn(), _i(), Kn(), nr(), path(), rr(), Ui(), wr()

### Community 317 - "et"
Cohesion: 0.50
Nodes (4): et(), he(), me(), Y()

## Knowledge Gaps
- **149 isolated node(s):** `php`, `$schema`, `./plugins/graphify.js`, `Controller`, `$schema` (+144 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 1080 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **144 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `update()` connect `constructor` to `pt`, `dx`, `sliceDoc`, `code-editor.js`, `W`, `resolve`, `rich-editor.js`, `components/chart.js`, `i`, `O`, `markdown-editor.js`, `Wi`, `r`, `get`, `n`, `facet`, `prop`, `advance`, `te`, `slice`, `keydown`, `echo.js`, `find`?**
  _High betweenness centrality (0.060) - this node is a cross-community bridge._
- **Why does `Wi()` connect `Wi` to `components/select.js`, `code-editor.js`, `rich-editor.js`, `constructor`, `columns/select.js`?**
  _High betweenness centrality (0.030) - this node is a cross-community bridge._
- **Why does `dx()` connect `dx` to `.slice`, `pt`, `code-editor.js`, `rich-editor.js`, `se`, `markdown-editor.js`, `draw`, `r`, `get`, `support.js`, `n`, `prop`, `r`, `o`, `y`, `keydown`, `components/select.js`, `apply`, `add`, `ml`, `jt`?**
  _High betweenness centrality (0.027) - this node is a cross-community bridge._
- **Are the 17 inferred relationships involving `constructor()` (e.g. with `a()` and `h()`) actually correct?**
  _`constructor()` has 17 INFERRED edges - model-reasoned connections that need verification._
- **Are the 26 inferred relationships involving `update()` (e.g. with `Pr()` and `a()`) actually correct?**
  _`update()` has 26 INFERRED edges - model-reasoned connections that need verification._
- **What connects `php`, `$schema`, `./plugins/graphify.js` to the rest of the system?**
  _149 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `composer.json` be split into smaller, more focused modules?**
  _Cohesion score 0.14285714285714285 - nodes in this community are weakly interconnected._