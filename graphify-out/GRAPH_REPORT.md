# Graph Report - Database Oasis  (2026-09-03)

## Corpus Check
- 352 files · ~98,290 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 2051 nodes · 5497 edges · 290 communities (112 shown, 178 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 94 edges (avg confidence: 0.83)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `15f9ca41`
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
- BackedEnum
- SalesCase
- UnitResource
- Filament\Resources\Pages\CreateRecord
- Filament\Resources\Pages\ListRecords
- Convention Detection Checklist
- SalesCaseResource
- Illuminate\Support\Str
- PhaseSixWorkspaceTest
- Scenario walkthroughs
- command
- Queue and Job Best Practices
- Security Best Practices
- Carbon\CarbonInterface
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
- UserRole.php
- On-demand notifications (Notification::route)
- Explicit HTTP error handling (throw/successful/notFound)
- .createCase
- Retry only safe operations (idempotency keys)
- Separate content and delivery mail tests
- JeparaLegacyAuditor
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
- Illuminate\Database\Eloquent\Factories\HasFactory
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
- Filament\Schemas\Schema
- SalesCaseFactory
- PhaseZeroFoundationTest
- Illuminate\Database\Eloquent\Builder
- PhaseEightALegacyAuditTest
- BiCheckResource
- ProjectPolicy
- Legacy Migration — Jepara Mapping Specification (Phase 8A)
- UnitPolicy
- PhaseTwoBranchIsolationTest
- Consumer
- BranchPolicy
- ConsumerPolicy
- PhaseSevenFilamentTest
- UserPolicy
- Monitoring Definitions
- PhaseTwoCaseWorkflowTest
- AuditLegacyWorkbook.php
- SalesCasePolicy
- DocumentSubmission
- SalesCaseResource.php
- LegacyNormalizer
- Model
- Branch
- BankProcessesRelationManager.php
- DocumentSubmissionPolicy.php
- LegacySourceReader
- .current
- PhaseTwoConsumerTest
- PsjbsRelationManager
- Illuminate\Support\Facades\Schema
- BankPolicy
- DeveloperPpjbPolicy.php
- Illuminate\Database\Schema\Blueprint
- PhaseThreePsjbTest
- BiCheck
- BiCheckForm
- PsjbFactory.php
- Legacy Migration Runbook — Phase 8A (Audit Only)
- Bank
- AkadRecordPolicy.php
- BankProcessPolicy.php
- BastRecordPolicy.php
- BiCheckPolicy.php
- CaseNotePolicy.php
- PsjbPolicy.php
- ProjectStatus.php
- LegacySourceReader.php
- Unit
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
2. `SalesCase` - 206 edges
3. `Branch` - 146 edges
4. `Project` - 87 edges
5. `Unit` - 79 edges
6. `Consumer` - 72 edges
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

## Communities (290 total, 178 thin omitted)

### Community 0 - "composer.json"
Cohesion: 0.14
Nodes (13): autoload-dev, psr-4, description, extra, laravel, dont-discover, license, minimum-stability (+5 more)

### Community 2 - "User"
Cohesion: 0.12
Nodes (3): User, AkadTargetPolicy, Illuminate\Foundation\Auth\User

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
Nodes (22): AkadRecordsTable, AkadTargetsTable, BankProcessesTable, BastRecordsTable, SalesCasesRelationManager, ConsumersTable, DeveloperPpjbsTable, DocumentSubmissionsTable (+14 more)

### Community 8 - "Filament\Actions\Action"
Cohesion: 0.11
Nodes (6): CaseWorkflowActions, Action, Action, WorkspaceActions, Filament\Actions\Action, Heroicon

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
Cohesion: 0.07
Nodes (4): AkadTarget, BankProcess, self, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 13 - "BackedEnum"
Cohesion: 0.20
Nodes (15): AkadTargetResource, BankProcessResource, BankResource, BastRecordResource, BranchResource, ConsumerResource, DeveloperPpjbResource, DocumentSubmissionResource (+7 more)

### Community 14 - "SalesCase"
Cohesion: 0.08
Nodes (5): PsjbForm, SalesCase, SalesCaseTimelineService, Illuminate\Database\Eloquent\Relations\HasOne, SalesCaseStage

### Community 15 - "UnitResource"
Cohesion: 0.17
Nodes (6): CreateUnit, EditUnit, ViewUnit, UnitResource, Filament\Actions\ActionGroup, Filament\Resources\Pages\ViewRecord

### Community 16 - "Filament\Resources\Pages\CreateRecord"
Cohesion: 0.07
Nodes (14): AkadRecordResource, CreateAkadRecord, CreateAkadTarget, CreateBankProcess, CreateBastRecord, CreateConsumer, CreateDeveloperPpjb, CreateDocumentSubmission (+6 more)

### Community 17 - "Filament\Resources\Pages\ListRecords"
Cohesion: 0.07
Nodes (19): ListAkadRecords, ListAkadTargets, ListBankProcesses, ManageBanks, ListBastRecords, ManageBranches, ListConsumers, ListDeveloperPpjbs (+11 more)

### Community 18 - "Convention Detection Checklist"
Cohesion: 0.20
Nodes (11): Convention Detection Checklist, 49 Laravel convention dimensions, Advanced Query Best Practices, Correlated subquery pattern, Eloquent Best Practices, Attribute casts, Global scope tradeoff, Local query scopes (+3 more)

### Community 19 - "SalesCaseResource"
Cohesion: 0.09
Nodes (10): EditAkadTarget, EditConsumer, EditProject, EditSalesCase, SalesCaseResource, EditUser, Filament\Actions\ForceDeleteAction, Filament\Actions\RestoreAction (+2 more)

### Community 20 - "Illuminate\Support\Str"
Cohesion: 0.16
Nodes (6): static, UserFactory, Illuminate\Support\Arr, Illuminate\Support\Facades\Hash, Illuminate\Support\Str, Pdo\Mysql

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

### Community 26 - "Carbon\CarbonInterface"
Cohesion: 0.26
Nodes (3): CarbonImmutable, TimelineItem, Carbon\CarbonInterface

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
Cohesion: 0.15
Nodes (3): AkadRecord, BastRecord, BastRecordFactory

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

### Community 144 - "UserRole.php"
Cohesion: 0.14
Nodes (12): Filament\Auth\Pages\Login, Filament\Facades\Filament, Illuminate\Auth\Access\AuthorizationException, Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, Illuminate\Support\Facades\File, Livewire\Livewire, OpenSpout\Common\Entity\Row (+4 more)

### Community 158 - "SalesCaseStatus.php"
Cohesion: 0.06
Nodes (41): CancelDeveloperPpjbAction, CancelPsjbAction, CancelSalesCaseAction, SalesCaseStatus, CloseSalesCaseAction, SalesCaseStatus, CompleteCashPemberkasanAction, CreateAkadAction (+33 more)

### Community 164 - "Illuminate\Database\Eloquent\Factories\HasFactory"
Cohesion: 0.14
Nodes (4): Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Database\Eloquent\SoftDeletes

### Community 168 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.06
Nodes (16): AkadReadinessFactory, AkadRecordFactory, AkadTargetFactory, BankFactory, static, BiCheckFactory, BiCheckResult, static (+8 more)

### Community 205 - "PhaseSevenMonitoringTest"
Cohesion: 0.13
Nodes (6): AkadReadiness, BastStatus, PhaseSevenMonitoringTest, BankResponseType, FinancingType, UserRole

### Community 206 - "Filament\Schemas\Schema"
Cohesion: 0.05
Nodes (15): AkadRecordForm, AkadTargetForm, BankProcessForm, BastRecordForm, ConsumerForm, DeveloperPpjbForm, DocumentSubmissionForm, ProjectForm (+7 more)

### Community 209 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.07
Nodes (15): AkadMonitoring, Monitoring, Sp3kMonitoring, MonitoringPeriod, MonitoringScope, MonitoringService, CarbonImmutable, Carbon\CarbonImmutable (+7 more)

### Community 211 - "BiCheckResource"
Cohesion: 0.24
Nodes (3): BiCheckResource, CreateBiCheck, ListBiChecks

### Community 213 - "Legacy Migration — Jepara Mapping Specification (Phase 8A)"
Cohesion: 0.11
Nodes (18): 10. Authoritative SP3K rule, 11. Duplicate treatment, 12. Exception codes, 13. Rekonsiliasi, 14. Unresolved policy, 1. Sumber input, 2. Sheet → target table, 3. Kolom → field (ringkas) (+10 more)

### Community 224 - "Monitoring Definitions"
Cohesion: 0.13
Nodes (14): Achievement, Authoritative Bank Process, Authorization, BAST Monthly, Monitoring Definitions, Monitoring Is Read-Only, Monitoring Period, Readiness Data Incomplete (+6 more)

### Community 226 - "AuditLegacyWorkbook.php"
Cohesion: 0.20
Nodes (7): AuditLegacyWorkbook, LegacyAuditReportWriter, Illuminate\Console\Attributes\Description, Illuminate\Console\Attributes\Signature, Illuminate\Console\Command, RuntimeException, Throwable

### Community 228 - "DocumentSubmission"
Cohesion: 0.15
Nodes (3): DocumentSubmission, BankProcessFactory, static

### Community 229 - "SalesCaseResource.php"
Cohesion: 0.24
Nodes (8): AkadRelationManager, BankProcessesRelationManager, Action, BastRelationManager, BiChecksRelationManager, CaseNotesRelationManager, DocumentSubmissionsRelationManager, Filament\Resources\RelationManagers\RelationManager

### Community 230 - "LegacyNormalizer"
Cohesion: 0.22
Nodes (3): LegacyNormalizer, DateTimeImmutable, DateTimeInterface

### Community 231 - "Model"
Cohesion: 0.18
Nodes (7): CaseNote, Model, Filament\Models\Contracts\FilamentUser, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Concerns\HasUlids, Illuminate\Notifications\Notifiable, Spatie\Permission\Traits\HasRoles

### Community 232 - "Branch"
Cohesion: 0.10
Nodes (6): BiChecksTable, PsjbsTable, Branch, Project, Filament\Tables\Filters\Filter, PhaseOneMasterDataTest

### Community 233 - "BankProcessesRelationManager.php"
Cohesion: 0.24
Nodes (10): Carbon\Carbon, Filament\Forms\Components\DatePicker, Filament\Forms\Components\Select, Filament\Forms\Components\Textarea, Filament\Forms\Components\TextInput, Filament\Notifications\Notification, Filament\Schemas\Components\Section, Filament\Schemas\Components\Utilities\Get (+2 more)

### Community 256 - "BiCheck"
Cohesion: 0.22
Nodes (4): BiCheck, self, PhaseThreeBiCheckTest, BiCheckResult

### Community 261 - "PsjbFactory.php"
Cohesion: 0.33
Nodes (3): static, PsjbFactory, PsjbStatus

### Community 266 - "Legacy Migration Runbook — Phase 8A (Audit Only)"
Cohesion: 0.33
Nodes (5): Aturan, Legacy Migration Runbook — Phase 8A (Audit Only), Membaca hasil, Menjalankan audit (CLI saja — tidak ada UI Filament), Prasyarat

### Community 267 - "Bank"
Cohesion: 0.15
Nodes (7): Bank, BankResponseType, UserRole, UatDemoSeeder, PhaseFourBankWorkflowTest, BankResponseType, FinancingType

### Community 275 - "LegacySourceReader.php"
Cohesion: 0.50
Nodes (3): DateInterval, OpenSpout\Common\Entity\Cell\FormulaCell, OpenSpout\Reader\XLSX\Reader

### Community 280 - "Unit"
Cohesion: 0.11
Nodes (4): SalesCaseForm, Unit, SalesCaseStatus, UnitFactory

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
- **195 isolated node(s):** `php`, `$schema`, `Controller`, `$schema`, `name` (+190 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **178 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `BiCheck`, `PhaseFiveWorkflowTest`, `BiCheckForm`, `PsjbFactory.php`, `AdminPanelProvider.php`, `Filament\Tables\Table`, `Filament\Actions\Action`, `Bank`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `BackedEnum`, `SalesCase`, `UnitResource`, `Filament\Resources\Pages\CreateRecord`, `Filament\Resources\Pages\ListRecords`, `AkadRecordPolicy.php`, `SalesCaseResource`, `BankProcessPolicy.php`, `BastRecordPolicy.php`, `BiCheckPolicy.php`, `CaseNotePolicy.php`, `Unit`, `PsjbPolicy.php`, `Illuminate\Support\Str`, `.createCase`, `PhaseSixWorkspaceTest`, `SalesCaseStatus.php`, `AkadRecord`, `Illuminate\Database\Eloquent\Factories\HasFactory`, `Illuminate\Database\Eloquent\Factories\Factory`, `PhaseSevenMonitoringTest`, `Filament\Schemas\Schema`, `PhaseZeroFoundationTest`, `Illuminate\Database\Eloquent\Builder`, `BiCheckResource`, `ProjectPolicy`, `UserRole.php`, `UnitPolicy`, `PhaseTwoBranchIsolationTest`, `Consumer`, `BranchPolicy`, `ConsumerPolicy`, `PhaseSevenFilamentTest`, `UserPolicy`, `PhaseTwoCaseWorkflowTest`, `SalesCasePolicy`, `DocumentSubmission`, `SalesCaseResource.php`, `Model`, `Branch`, `BankProcessesRelationManager.php`, `DocumentSubmissionPolicy.php`, `.current`, `PhaseTwoConsumerTest`, `PsjbsRelationManager`, `BankPolicy`, `DeveloperPpjbPolicy.php`, `PhaseThreePsjbTest`?**
  _High betweenness centrality (0.214) - this node is a cross-community bridge._
- **Why does `SalesCase` connect `SalesCase` to `BiCheck`, `PhaseFiveWorkflowTest`, `User`, `BiCheckForm`, `PsjbFactory.php`, `Filament\Actions\Action`, `Bank`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `UnitResource`, `UserRole.php`, `.createCase`, `PhaseSixWorkspaceTest`, `Unit`, `Carbon\CarbonInterface`, `SalesCaseStatus.php`, `AkadRecord`, `Illuminate\Database\Eloquent\Factories\HasFactory`, `Illuminate\Database\Eloquent\Factories\Factory`, `PhaseSevenMonitoringTest`, `Filament\Schemas\Schema`, `Illuminate\Database\Eloquent\Builder`, `PhaseEightALegacyAuditTest`, `PhaseTwoBranchIsolationTest`, `Consumer`, `PhaseSevenFilamentTest`, `PhaseTwoCaseWorkflowTest`, `SalesCasePolicy`, `DocumentSubmission`, `SalesCaseResource.php`, `Model`, `BankProcessesRelationManager.php`, `PhaseThreePsjbTest`?**
  _High betweenness centrality (0.097) - this node is a cross-community bridge._
- **Why does `Branch` connect `Branch` to `BiCheck`, `PhaseFiveWorkflowTest`, `Filament\Tables\Table`, `Bank`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `UserRole.php`, `ProjectStatus.php`, `.createCase`, `PhaseSixWorkspaceTest`, `Unit`, `SalesCaseStatus.php`, `Illuminate\Database\Eloquent\Factories\HasFactory`, `Illuminate\Database\Eloquent\Factories\Factory`, `PhaseSevenMonitoringTest`, `Illuminate\Database\Eloquent\Builder`, `PhaseEightALegacyAuditTest`, `PhaseTwoBranchIsolationTest`, `Consumer`, `BranchPolicy`, `PhaseSevenFilamentTest`, `PhaseTwoCaseWorkflowTest`, `Model`, `BankProcessesRelationManager.php`, `PhaseThreePsjbTest`?**
  _High betweenness centrality (0.034) - this node is a cross-community bridge._
- **What connects `php`, `$schema`, `Controller` to the rest of the system?**
  _195 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `composer.json` be split into smaller, more focused modules?**
  _Cohesion score 0.14285714285714285 - nodes in this community are weakly interconnected._
- **Should `User` be split into smaller, more focused modules?**
  _Cohesion score 0.12380952380952381 - nodes in this community are weakly interconnected._
- **Should `Master Build Pack - Database Oasis` be split into smaller, more focused modules?**
  _Cohesion score 0.1425287356321839 - nodes in this community are weakly interconnected._