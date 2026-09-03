# Graph Report - Database Oasis  (2026-09-03)

## Corpus Check
- 341 files · ~91,740 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1930 nodes · 5281 edges · 270 communities (104 shown, 166 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 94 edges (avg confidence: 0.83)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `95e40926`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- composer.json
- PhaseFiveWorkflowTest
- User
- Master Build Pack - Database Oasis
- Illuminate\Database\Migrations\Migration
- scripts
- AdminPanelProvider.php
- Filament\Tables\Table
- Filament\Actions\Action
- Testing Best Practices (skill)
- package.json
- CI quality job (ubuntu-latest, PHP 8.4, Node 22)
- Illuminate\Database\Eloquent\Relations\BelongsTo
- Filament\Schemas\Schema
- SalesCase
- UnitResource.php
- Filament\Resources\Pages\CreateRecord
- Filament\Resources\Pages\ListRecords
- Convention Detection Checklist
- Filament\Resources\Pages\EditRecord
- PhaseZeroFoundationTest.php
- PhaseSixWorkspaceTest
- Scenario walkthroughs
- command
- Queue and Job Best Practices
- Security Best Practices
- AkadTarget
- DatabaseSeeder.php
- Test Value Review Checklist
- laravel-best-practices skill
- tailwindcss-development skill
- Routing and Controller Best Practices
- AkadRecord
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
- PhaseSixWorkspaceTest.php
- On-demand notifications (Notification::route)
- Explicit HTTP error handling (throw/successful/notFound)
- .createCase
- Retry only safe operations (idempotency keys)
- Separate content and delivery mail tests
- DeveloperPpjb
- Honest migration rollbacks (down())
- Deployed migrations are immutable
- Design indexes for real queries
- Stage changes affecting existing rows
- Back off transient failures ($tries/$backoff)
- Bus::batch job batching
- Job failed() terminal handling
- SalesCaseStatus.php
- Implicit route model binding
- Resource-oriented controller organization
- Resource routes (resource/apiResource)
- Bound work inside the scheduled task
- runInBackground scheduled tasks
- Psjb
- withoutOverlapping lock
- CSRF protection (@csrf / X-XSRF-TOKEN)
- Bind query parameters (no SQL interpolation)
- Illuminate\Database\Eloquent\Factories\Factory
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
- PhaseSevenMonitoringTest
- SalesCaseResource
- SalesCaseFactory.php
- PhaseZeroFoundationTest
- Illuminate\Database\Eloquent\Builder
- Sp3kMonitoring.php
- BiCheckResource
- ProjectPolicy
- PhaseSevenMonitoringTest.php
- UnitPolicy
- PhaseTwoBranchIsolationTest
- Consumer
- BranchPolicy
- ConsumerPolicy
- PhaseSevenFilamentTest
- UserPolicy
- Monitoring Definitions
- ExampleTest
- .status
- SalesCasePolicy
- Branch
- CaseWorkflowActions.php
- DocumentSubmissionPolicy.php
- .current
- AkadTargetPolicy
- Illuminate\Support\Facades\Schema
- DeveloperPpjbPolicy.php
- Illuminate\Database\Schema\Blueprint
- PhaseThreePsjbTest
- BiCheck
- Bank
- SalesCaseForm
- Testing Best Practices skill
- require-dev
- config
- setup
- require
- psr-4
- keywords
- post-autoload-dump
- .opencode/opencode.json
- dev
- graphify.js

## God Nodes (most connected - your core abstractions)
1. `User` - 372 edges
2. `SalesCase` - 204 edges
3. `Branch` - 144 edges
4. `Project` - 85 edges
5. `Unit` - 77 edges
6. `Consumer` - 70 edges
7. `Bank` - 64 edges
8. `DocumentSubmission` - 44 edges
9. `DeveloperPpjb` - 41 edges
10. `AkadRecord` - 38 edges

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

## Communities (270 total, 166 thin omitted)

### Community 0 - "composer.json"
Cohesion: 0.14
Nodes (13): autoload-dev, psr-4, description, extra, laravel, dont-discover, license, minimum-stability (+5 more)

### Community 2 - "User"
Cohesion: 0.06
Nodes (9): DocumentSubmissionForm, User, AkadRecordPolicy, BankProcessPolicy, BastRecordPolicy, BiCheckPolicy, CaseNotePolicy, PsjbPolicy (+1 more)

### Community 3 - "Master Build Pack - Database Oasis"
Cohesion: 0.14
Nodes (30): AGENTS.md - Laravel Boost + Graphify Guidelines, .ai/rules Project Rules, Code Quality Toolchain (Pint, PHPStan, PHPUnit), Graphify Knowledge Graph Workflow, Laravel Boost MCP Tools, CLAUDE.md - Laravel Boost Guidelines, compose.yaml app service (Laravel app), compose.yaml postgres service (PostgreSQL 17) (+22 more)

### Community 5 - "scripts"
Cohesion: 0.14
Nodes (14): scripts, analyse, format, post-create-project-cmd, post-update-cmd, pre-package-uninstall, test, Illuminate\\Foundation\\ComposerScripts::prePackageUninstall (+6 more)

### Community 6 - "AdminPanelProvider.php"
Cohesion: 0.09
Nodes (19): AppServiceProvider, AdminPanelProvider, Filament\Http\Middleware\Authenticate, Filament\Http\Middleware\AuthenticateSession, Filament\Http\Middleware\DisableBladeIconComponents, Filament\Http\Middleware\DispatchServingFilamentEvent, Filament\Pages\Dashboard, Filament\Panel (+11 more)

### Community 7 - "Filament\Tables\Table"
Cohesion: 0.07
Nodes (21): AkadRecordsTable, AkadTargetsTable, BankProcessesTable, BastRecordsTable, ConsumersTable, DeveloperPpjbsTable, DocumentSubmissionsTable, ProjectsTable (+13 more)

### Community 8 - "Filament\Actions\Action"
Cohesion: 0.06
Nodes (26): CaseWorkflowActions, Action, Action, WorkspaceActions, AkadRelationManager, BankProcessesRelationManager, Action, BastRelationManager (+18 more)

### Community 9 - "Testing Best Practices (skill)"
Cohesion: 0.21
Nodes (12): Assertions (rule), Endpoint Tests (rule), How to Find Test Framework Features (rule), Fakes, Mocks, and Determinism (rule), Naming and Structure (rule), Test Suite Performance (rule), Reviewing Tests (rule), Security Tests (rule) (+4 more)

### Community 10 - "package.json"
Cohesion: 0.10
Nodes (20): concurrently, @laravel/multiplex, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite (+12 more)

### Community 11 - "CI quality job (ubuntu-latest, PHP 8.4, Node 22)"
Cohesion: 0.20
Nodes (10): Migration Best Practices (Laravel rules), composer audit dependency audit, Convention and Style Best Practices (Laravel rules), php artisan test --compact step, CI quality job (ubuntu-latest, PHP 8.4, Node 22), CI workflow (.github/workflows/ci.yml), composer audit step (--locked), migrate / migrate:rollback / migrate verification steps (+2 more)

### Community 12 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.06
Nodes (9): BankProcess, self, CaseNote, DocumentSubmission, BankProcessFactory, static, CaseNoteFactory, Illuminate\Database\Eloquent\Relations\BelongsTo (+1 more)

### Community 13 - "Filament\Schemas\Schema"
Cohesion: 0.10
Nodes (22): AkadRecordResource, AkadTargetResource, AkadTargetForm, BankProcessResource, BankProcessForm, BankResource, BastRecordResource, BranchResource (+14 more)

### Community 14 - "SalesCase"
Cohesion: 0.07
Nodes (6): BiCheckForm, PsjbForm, SalesCase, SalesCaseTimelineService, Illuminate\Database\Eloquent\Relations\HasOne, SalesCaseStage

### Community 15 - "UnitResource.php"
Cohesion: 0.12
Nodes (8): EditUnit, ViewUnit, SalesCasesRelationManager, UnitForm, UnitInfolist, UnitsTable, UnitResource, Filament\Resources\Pages\ViewRecord

### Community 16 - "Filament\Resources\Pages\CreateRecord"
Cohesion: 0.08
Nodes (14): CreateAkadRecord, CreateAkadTarget, CreateBankProcess, CreateBastRecord, CreateConsumer, CreateDeveloperPpjb, CreateDocumentSubmission, CreateProject (+6 more)

### Community 17 - "Filament\Resources\Pages\ListRecords"
Cohesion: 0.06
Nodes (20): ListAkadRecords, ListAkadTargets, ListBankProcesses, ManageBanks, ListBastRecords, ListBiChecks, ManageBranches, ListConsumers (+12 more)

### Community 18 - "Convention Detection Checklist"
Cohesion: 0.20
Nodes (11): Convention Detection Checklist, 49 Laravel convention dimensions, Advanced Query Best Practices, Correlated subquery pattern, Eloquent Best Practices, Attribute casts, Global scope tradeoff, Local query scopes (+3 more)

### Community 19 - "Filament\Resources\Pages\EditRecord"
Cohesion: 0.11
Nodes (9): EditAkadTarget, EditConsumer, EditProject, EditSalesCase, EditUser, Filament\Actions\ForceDeleteAction, Filament\Actions\RestoreAction, Filament\Actions\ViewAction (+1 more)

### Community 20 - "PhaseZeroFoundationTest.php"
Cohesion: 0.50
Nodes (3): Filament\Auth\Pages\Login, Filament\Facades\Filament, Illuminate\Support\Facades\Hash

### Community 22 - "Scenario walkthroughs"
Cohesion: 0.12
Nodes (16): 10. Branch isolation, 1. KPR normal (completed chain), 2. Multiple bank: BTN rejected → BRI approved, 3. CASH chain with zero bank records, 4. Pindah Kavling (K-20 → K-15), 5. Mundur, unit reused by new consumer, 6. SP3K without kendala, 7. SP3K with multiple kendala (+8 more)

### Community 23 - "command"
Cohesion: 0.20
Nodes (9): command, enabled, type, mcp, laravel-boost, $schema, artisan, boost:mcp (+1 more)

### Community 24 - "Queue and Job Best Practices"
Cohesion: 0.22
Nodes (8): Atomic locks for race conditions, Caching Best Practices, Queue and Job Best Practices, Progressive backoff for transient failures, Bus::batch group coordination, ShouldBeUnique dispatch deduplication, Task Scheduling Best Practices, withoutOverlapping() overlap prevention

### Community 25 - "Security Best Practices"
Cohesion: 0.25
Nodes (9): Configuration Best Practices, Encrypted environment files, env() only in config files, Error Handling Best Practices, Exception report()/render() methods, Security Best Practices, CSRF protection in Blade forms, Mass assignment protection via $fillable (+1 more)

### Community 26 - "AkadTarget"
Cohesion: 0.11
Nodes (5): AkadTarget, CarbonImmutable, TimelineItem, Carbon\CarbonInterface, AkadTargetFactory

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

### Community 32 - "AkadRecord"
Cohesion: 0.11
Nodes (7): BastRecordForm, AkadRecord, BastRecord, Model, BastRecordFactory, Illuminate\Database\Eloquent\Concerns\HasUlids, Illuminate\Database\Eloquent\SoftDeletes

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

### Community 144 - "PhaseSixWorkspaceTest.php"
Cohesion: 0.17
Nodes (23): CancelDeveloperPpjbAction, CancelPsjbAction, CompleteCashPemberkasanAction, CreateAkadAction, CreateBastAction, CreateDeveloperPpjbAction, CreateDocumentSubmissionAction, CreatePsjbAction (+15 more)

### Community 150 - "DeveloperPpjb"
Cohesion: 0.12
Nodes (4): AkadRecordForm, DeveloperPpjbForm, DeveloperPpjb, DeveloperPpjbFactory

### Community 158 - "SalesCaseStatus.php"
Cohesion: 0.17
Nodes (9): CreateCaseNoteAction, isBeyond(), order(), self, Filament\Support\Contracts\HasLabel, Illuminate\Database\UniqueConstraintViolationException, Illuminate\Support\Facades\DB, Illuminate\Support\Facades\Gate (+1 more)

### Community 164 - "Psjb"
Cohesion: 0.07
Nodes (7): Psjb, DocumentSubmissionFactory, static, static, PsjbFactory, Illuminate\Database\Eloquent\Relations\HasMany, PsjbStatus

### Community 168 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.06
Nodes (22): AkadRecordFactory, BankFactory, static, BiCheckFactory, BiCheckResult, static, BranchFactory, static (+14 more)

### Community 205 - "PhaseSevenMonitoringTest"
Cohesion: 0.13
Nodes (6): AkadReadiness, BastStatus, PhaseSevenMonitoringTest, BankResponseType, FinancingType, UserRole

### Community 206 - "SalesCaseResource"
Cohesion: 0.13
Nodes (6): SalesCasesRelationManager, SalesCaseResource, SalesCaseInfolist, Filament\Actions\ActionGroup, Filament\Infolists\Components\TextEntry, Filament\Schemas\Components\View

### Community 207 - "SalesCaseFactory.php"
Cohesion: 0.33
Nodes (3): SalesCaseStatus, static, SalesCaseFactory

### Community 209 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.12
Nodes (7): MonitoringPeriod, MonitoringScope, MonitoringService, CarbonImmutable, Carbon\CarbonImmutable, Illuminate\Database\Eloquent\Builder, KendalaCategory

### Community 210 - "Sp3kMonitoring.php"
Cohesion: 0.19
Nodes (8): AkadMonitoring, Monitoring, Sp3kMonitoring, Filament\Pages\Page, Filament\Tables\Concerns\InteractsWithTable, Filament\Tables\Contracts\HasTable, Livewire\Attributes\Url, Sp3kAgingBucket

### Community 213 - "PhaseSevenMonitoringTest.php"
Cohesion: 0.16
Nodes (4): AkadReadinessFactory, Illuminate\Auth\Access\AuthorizationException, Illuminate\Support\Carbon, Illuminate\Validation\Rule

### Community 218 - "Consumer"
Cohesion: 0.11
Nodes (4): Consumer, PhaseTwoCaseWorkflowTest, PhaseTwoConsumerTest, PhaseTwoSalesCaseTest

### Community 224 - "Monitoring Definitions"
Cohesion: 0.13
Nodes (14): Achievement, Authoritative Bank Process, Authorization, BAST Monthly, Monitoring Definitions, Monitoring Is Read-Only, Monitoring Period, Readiness Data Incomplete (+6 more)

### Community 232 - "Branch"
Cohesion: 0.08
Nodes (8): BiChecksTable, Branch, Project, Unit, Filament\Tables\Filters\Filter, PhaseFiveFilamentTest, PhaseFourFilamentTest, PhaseOneMasterDataTest

### Community 233 - "CaseWorkflowActions.php"
Cohesion: 0.17
Nodes (6): CancelSalesCaseAction, SalesCaseStatus, CloseSalesCaseAction, SalesCaseStatus, MarkSalesCaseRejectedAction, SalesCaseStatus

### Community 256 - "BiCheck"
Cohesion: 0.22
Nodes (4): BiCheck, self, PhaseThreeBiCheckTest, BiCheckResult

### Community 267 - "Bank"
Cohesion: 0.12
Nodes (8): Bank, BankPolicy, BankResponseType, UserRole, UatDemoSeeder, PhaseFourBankWorkflowTest, BankResponseType, FinancingType

### Community 281 - "Testing Best Practices skill"
Cohesion: 0.24
Nodes (10): Assertions rules (testing skill), Endpoint Tests rules (testing skill), Finding Test Framework Features rules (testing skill), Fakes, Mocks, and Determinism rules (testing skill), Naming and Structure rules (testing skill), Test Suite Performance rules (testing skill), Reviewing Tests rules (testing skill), Security Tests rules (testing skill) (+2 more)

### Community 282 - "require-dev"
Cohesion: 0.20
Nodes (10): require-dev, fakerphp/faker, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, mockery/mockery (+2 more)

### Community 288 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 289 - "setup"
Cohesion: 0.29
Nodes (7): post-root-package-install, setup, composer install, @php artisan db:seed --force, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 291 - "require"
Cohesion: 0.33
Nodes (6): require, filament/filament, laravel/framework, laravel/tinker, php, spatie/laravel-permission

### Community 295 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 299 - "keywords"
Cohesion: 0.50
Nodes (4): keywords, filament, laravel, sales-operations

### Community 300 - "post-autoload-dump"
Cohesion: 0.50
Nodes (4): post-autoload-dump, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump, @php artisan filament:upgrade, @php artisan package:discover --ansi

### Community 304 - "dev"
Cohesion: 0.67
Nodes (3): dev, Composer\\Config::disableProcessTimeout, @php artisan dev

## Knowledge Gaps
- **175 isolated node(s):** `php`, `$schema`, `Controller`, `$schema`, `name` (+170 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **166 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `BiCheck`, `PhaseFiveWorkflowTest`, `AdminPanelProvider.php`, `Filament\Tables\Table`, `Filament\Actions\Action`, `Bank`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Filament\Schemas\Schema`, `SalesCase`, `UnitResource.php`, `PhaseSixWorkspaceTest.php`, `Filament\Resources\Pages\CreateRecord`, `Filament\Resources\Pages\ListRecords`, `Filament\Resources\Pages\EditRecord`, `.createCase`, `PhaseSixWorkspaceTest`, `DeveloperPpjb`, `PhaseZeroFoundationTest.php`, `SalesCaseForm`, `SalesCaseStatus.php`, `AkadRecord`, `Psjb`, `Illuminate\Database\Eloquent\Factories\Factory`, `PhaseSevenMonitoringTest`, `SalesCaseResource`, `SalesCaseFactory.php`, `PhaseZeroFoundationTest`, `Illuminate\Database\Eloquent\Builder`, `Sp3kMonitoring.php`, `BiCheckResource`, `ProjectPolicy`, `PhaseSevenMonitoringTest.php`, `UnitPolicy`, `PhaseTwoBranchIsolationTest`, `Consumer`, `BranchPolicy`, `ConsumerPolicy`, `PhaseSevenFilamentTest`, `UserPolicy`, `SalesCasePolicy`, `Branch`, `CaseWorkflowActions.php`, `DocumentSubmissionPolicy.php`, `.current`, `AkadTargetPolicy`, `DeveloperPpjbPolicy.php`, `PhaseThreePsjbTest`?**
  _High betweenness centrality (0.257) - this node is a cross-community bridge._
- **Why does `SalesCase` connect `SalesCase` to `BiCheck`, `PhaseFiveWorkflowTest`, `User`, `Filament\Actions\Action`, `Bank`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `PhaseSixWorkspaceTest.php`, `.createCase`, `PhaseSixWorkspaceTest`, `DeveloperPpjb`, `AkadTarget`, `SalesCaseStatus.php`, `AkadRecord`, `Psjb`, `Illuminate\Database\Eloquent\Factories\Factory`, `PhaseSevenMonitoringTest`, `SalesCaseResource`, `SalesCaseFactory.php`, `Illuminate\Database\Eloquent\Builder`, `Sp3kMonitoring.php`, `PhaseSevenMonitoringTest.php`, `PhaseTwoBranchIsolationTest`, `Consumer`, `PhaseSevenFilamentTest`, `SalesCasePolicy`, `Branch`, `CaseWorkflowActions.php`, `PhaseThreePsjbTest`?**
  _High betweenness centrality (0.071) - this node is a cross-community bridge._
- **Why does `Branch` connect `Branch` to `BiCheck`, `PhaseFiveWorkflowTest`, `Filament\Tables\Table`, `Filament\Actions\Action`, `Bank`, `Filament\Schemas\Schema`, `PhaseSixWorkspaceTest.php`, `.createCase`, `PhaseSixWorkspaceTest`, `AkadTarget`, `SalesCaseStatus.php`, `AkadRecord`, `Psjb`, `Illuminate\Database\Eloquent\Factories\Factory`, `PhaseSevenMonitoringTest`, `Illuminate\Database\Eloquent\Builder`, `Sp3kMonitoring.php`, `PhaseSevenMonitoringTest.php`, `PhaseTwoBranchIsolationTest`, `Consumer`, `BranchPolicy`, `PhaseSevenFilamentTest`, `PhaseThreePsjbTest`?**
  _High betweenness centrality (0.034) - this node is a cross-community bridge._
- **What connects `php`, `$schema`, `Controller` to the rest of the system?**
  _175 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `composer.json` be split into smaller, more focused modules?**
  _Cohesion score 0.14285714285714285 - nodes in this community are weakly interconnected._
- **Should `User` be split into smaller, more focused modules?**
  _Cohesion score 0.060129509713228495 - nodes in this community are weakly interconnected._
- **Should `Master Build Pack - Database Oasis` be split into smaller, more focused modules?**
  _Cohesion score 0.1425287356321839 - nodes in this community are weakly interconnected._