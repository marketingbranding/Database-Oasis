# Graph Report - Database-Oasis  (2026-09-02)

## Corpus Check
- 158 files · ~117,361 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 808 nodes · 1089 edges · 205 communities (44 shown, 150 thin omitted)
- Extraction: 93% EXTRACTED · 7% INFERRED · 0% AMBIGUOUS · INFERRED: 74 edges (avg confidence: 0.83)
- Token cost: 75,400 input · 40,700 output

## Community Hubs (Navigation)
- Composer Configuration
- Domain Enums & Models
- User & Branch Access Control
- Project Docs & Agent Rules
- Database Migrations
- Composer Scripts & Tooling
- Panel & Auth Bootstrap
- Filament Table Definitions
- Phase 1 Feature Tests
- Testing Skill Rules (.agents)
- JS Build Dependencies
- Skill Rule Files (.claude)
- Project Domain & Policy
- Filament Form Schemas
- Master Data Resources
- Resource Branch Scoping
- Eloquent Base & ULID
- Filament List Pages
- Laravel Best-Practice Rules
- Filament Edit Pages
- RBAC Roles & Login
- Bank Domain & Policy
- Unit Domain & Policy
- Agent Tool Configuration
- Misc Cluster 24
- Misc Cluster 25
- Misc Cluster 26
- Spatie Permission Seeding
- Misc Cluster 28
- Misc Cluster 29
- Misc Cluster 30
- Misc Cluster 31
- Misc Cluster 32
- Misc Cluster 33
- Misc Cluster 34
- Misc Cluster 35
- Misc Cluster 36
- Misc Cluster 37
- Misc Cluster 38
- Misc Cluster 39
- Misc Cluster 40
- Misc Cluster 41
- Misc Cluster 42
- Misc Cluster 43
- Misc Cluster 44
- Misc Cluster 45
- Misc Cluster 46
- Misc Cluster 47
- Misc Cluster 48
- Misc Cluster 49
- Misc Cluster 50
- Misc Cluster 51
- Misc Cluster 52
- Misc Cluster 53
- Misc Cluster 54
- Misc Cluster 55
- Misc Cluster 56
- Misc Cluster 57
- Misc Cluster 58
- Misc Cluster 59
- Misc Cluster 60
- Misc Cluster 61
- Misc Cluster 62
- Misc Cluster 63
- Misc Cluster 64
- Misc Cluster 65
- Misc Cluster 66
- Misc Cluster 67
- Misc Cluster 68
- Misc Cluster 69
- Misc Cluster 70
- Misc Cluster 71
- Misc Cluster 72
- Misc Cluster 73
- Misc Cluster 74
- Misc Cluster 75
- Misc Cluster 76
- Misc Cluster 77
- Misc Cluster 78
- Misc Cluster 79
- Misc Cluster 80
- Misc Cluster 81
- Misc Cluster 82
- Misc Cluster 83
- Misc Cluster 84
- Misc Cluster 85
- Misc Cluster 86
- Misc Cluster 87
- Misc Cluster 88
- Misc Cluster 89
- Misc Cluster 90
- Misc Cluster 91
- Misc Cluster 92
- Misc Cluster 93
- Misc Cluster 94
- Misc Cluster 95
- Misc Cluster 96
- Misc Cluster 97
- Misc Cluster 98
- Misc Cluster 99
- Misc Cluster 100
- Misc Cluster 101
- Misc Cluster 103
- Misc Cluster 104
- Misc Cluster 105
- Misc Cluster 106
- Misc Cluster 107
- Misc Cluster 108
- Misc Cluster 109
- Misc Cluster 110
- Misc Cluster 111
- Misc Cluster 112
- Misc Cluster 113
- Misc Cluster 114
- Misc Cluster 115
- Misc Cluster 116
- Misc Cluster 117
- Misc Cluster 118
- Misc Cluster 119
- Misc Cluster 120
- Misc Cluster 121
- Misc Cluster 122
- Misc Cluster 123
- Misc Cluster 124
- Misc Cluster 125
- Misc Cluster 126
- Misc Cluster 127
- Misc Cluster 128
- Misc Cluster 129
- Misc Cluster 130
- Misc Cluster 131
- Misc Cluster 133
- Misc Cluster 134
- Misc Cluster 135
- Misc Cluster 136
- Misc Cluster 137
- Misc Cluster 138
- Misc Cluster 139
- Misc Cluster 140
- Misc Cluster 141
- Misc Cluster 142
- Misc Cluster 143
- Misc Cluster 144
- Misc Cluster 145
- Misc Cluster 146
- Misc Cluster 147
- Misc Cluster 148
- Misc Cluster 149
- Misc Cluster 150
- Misc Cluster 151
- Misc Cluster 152
- Misc Cluster 153
- Misc Cluster 154
- Misc Cluster 155
- Misc Cluster 156
- Misc Cluster 157
- Misc Cluster 158
- Misc Cluster 159
- Misc Cluster 160
- Misc Cluster 161
- Misc Cluster 162
- Misc Cluster 163
- Misc Cluster 164
- Misc Cluster 165
- Misc Cluster 166
- Misc Cluster 167
- Misc Cluster 168
- Misc Cluster 169
- Misc Cluster 170
- Misc Cluster 171
- Misc Cluster 172
- Misc Cluster 173
- Misc Cluster 174
- Misc Cluster 175
- Misc Cluster 176
- Misc Cluster 177
- Misc Cluster 178
- Misc Cluster 179
- Misc Cluster 180
- Misc Cluster 181
- Misc Cluster 182
- Misc Cluster 183
- Misc Cluster 184
- Misc Cluster 185
- Misc Cluster 186
- Misc Cluster 187
- Misc Cluster 188
- Misc Cluster 189
- Misc Cluster 190
- Misc Cluster 191
- Misc Cluster 192
- Misc Cluster 193
- Misc Cluster 194
- Misc Cluster 195

## God Nodes (most connected - your core abstractions)
1. `User` - 78 edges
2. `Branch` - 30 edges
3. `PhaseOneMasterDataTest` - 25 edges
4. `Project` - 24 edges
5. `laravel-best-practices skill` - 21 edges
6. `Unit` - 18 edges
7. `Master Build Pack - Database Oasis` - 18 edges
8. `UserRole` - 14 edges
9. `Bank` - 13 edges
10. `PhaseZeroFoundationTest` - 12 edges

## Surprising Connections (you probably didn't know these)
- `Document Reality, Never Judge (consistency first)` --semantically_similar_to--> `Consistency First (testing skill)`  [INFERRED] [semantically similar]
  .claude/skills/infer-conventions/SKILL.md → .agents/skills/testing-best-practices/SKILL.md
- `Consistency First (laravel skill)` --semantically_similar_to--> `Consistency First (testing skill)`  [INFERRED] [semantically similar]
  .claude/skills/laravel-best-practices/SKILL.md → .agents/skills/testing-best-practices/SKILL.md
- `Collaborator Isolation Fork (Mockery vs real integration)` --semantically_similar_to--> `Mockery Usage Conventions`  [INFERRED] [semantically similar]
  .claude/skills/infer-conventions/references/checklist.md → .agents/skills/testing-best-practices/rules/isolation.md
- `AGENTS.md - Laravel Boost + Graphify Guidelines` --semantically_similar_to--> `CLAUDE.md - Laravel Boost Guidelines`  [INFERRED] [semantically similar]
  AGENTS.md → CLAUDE.md
- `Testing Framework Fork (Pest vs PHPUnit)` --conceptually_related_to--> `Testing Best Practices (skill)`  [INFERRED]
  .claude/skills/infer-conventions/references/checklist.md → .agents/skills/testing-best-practices/SKILL.md

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **Laravel Best Practices Rule Corpus** — _agents_skills_laravel_best_practices_skill, _agents_skills_laravel_best_practices_rules_db_performance, _agents_skills_laravel_best_practices_rules_advanced_queries, _agents_skills_laravel_best_practices_rules_eloquent, _agents_skills_laravel_best_practices_rules_security, _agents_skills_laravel_best_practices_rules_validation, _agents_skills_laravel_best_practices_rules_routing, _agents_skills_laravel_best_practices_rules_migrations, _agents_skills_laravel_best_practices_rules_queue_jobs, _agents_skills_laravel_best_practices_rules_caching, _agents_skills_laravel_best_practices_rules_http_client, _agents_skills_laravel_best_practices_rules_error_handling, _agents_skills_laravel_best_practices_rules_events_notifications, _agents_skills_laravel_best_practices_rules_mail, _agents_skills_laravel_best_practices_rules_scheduling, _agents_skills_laravel_best_practices_rules_collections, _agents_skills_laravel_best_practices_rules_blade_views, _agents_skills_laravel_best_practices_rules_config, _agents_skills_laravel_best_practices_rules_style, _agents_skills_laravel_best_practices_rules_architecture [EXTRACTED 1.00]
- **Convention Inference Workflow** — _agents_skills_infer_conventions_skill, _agents_skills_infer_conventions_references_checklist, _agents_skills_infer_conventions_skill_record_rule_tool, _agents_skills_infer_conventions_skill_ai_rules_directory [EXTRACTED 0.95]
- **After-Commit Transactional Dispatch Pattern** — _agents_skills_laravel_best_practices_rules_events_notifications_dispatch_after_commit, _agents_skills_laravel_best_practices_rules_events_notifications_queued_notifications, _agents_skills_laravel_best_practices_rules_mail_queued_mailables [INFERRED 0.85]
- **Testing Best Practices Rule Index** — _agents_skills_testing_best_practices_skill_testing_best_practices, _agents_skills_testing_best_practices_rules_assertions_assertions, _agents_skills_testing_best_practices_rules_endpoint_tests_endpoint_tests, _agents_skills_testing_best_practices_rules_finding_features_how_to_find_test_framework_features, _agents_skills_testing_best_practices_rules_isolation_fakes_mocks_and_determinism, _agents_skills_testing_best_practices_rules_naming_naming_and_structure, _agents_skills_testing_best_practices_rules_performance_test_suite_performance, _agents_skills_testing_best_practices_rules_review_reviewing_tests, _agents_skills_testing_best_practices_rules_security_security_tests, _agents_skills_testing_best_practices_rules_test_data_factories_and_test_data [EXTRACTED 1.00]
- **Laravel Best Practices Rule Index** — _claude_skills_laravel_best_practices_skill_laravel_best_practices, _claude_skills_laravel_best_practices_rules_advanced_queries_advanced_query_best_practices, _claude_skills_laravel_best_practices_rules_architecture_architecture_best_practices, _claude_skills_laravel_best_practices_rules_blade_views_blade_and_view_best_practices, _claude_skills_laravel_best_practices_rules_caching_caching_best_practices, _claude_skills_laravel_best_practices_rules_collections_collection_best_practices, _claude_skills_laravel_best_practices_rules_config_configuration_best_practices, _claude_skills_laravel_best_practices_rules_db_performance_database_performance_best_practices, _claude_skills_laravel_best_practices_rules_eloquent_eloquent_best_practices [EXTRACTED 1.00]
- **N+1 Query Prevention Pattern** — _claude_skills_laravel_best_practices_rules_db_performance_eager_loading, _claude_skills_laravel_best_practices_rules_db_performance_prevent_lazy_loading, _claude_skills_laravel_best_practices_rules_db_performance_select_needed_columns, _claude_skills_laravel_best_practices_rules_db_performance_with_count, _claude_skills_laravel_best_practices_rules_advanced_queries_correlated_subquery_select [INFERRED 0.85]
- **Testing Best Practices skill rule set** — _claude_skills_testing_best_practices_skill_testing_skill, _claude_skills_testing_best_practices_rules_finding_features_finding_features_rules, _claude_skills_testing_best_practices_rules_naming_naming_rules, _claude_skills_testing_best_practices_rules_assertions_assertions_rules, _claude_skills_testing_best_practices_rules_endpoint_tests_endpoint_tests_rules, _claude_skills_testing_best_practices_rules_test_data_test_data_rules, _claude_skills_testing_best_practices_rules_isolation_isolation_rules, _claude_skills_testing_best_practices_rules_security_security_tests_rules, _claude_skills_testing_best_practices_rules_performance_performance_rules, _claude_skills_testing_best_practices_rules_review_review_rules [EXTRACTED 1.00]
- **Delay dispatch until database commit pattern** — _claude_skills_laravel_best_practices_rules_events_notifications_should_dispatch_after_commit, _claude_skills_laravel_best_practices_rules_events_notifications_notification_after_commit, _claude_skills_laravel_best_practices_rules_mail_mailable_after_commit [INFERRED 0.85]
- **CI quality pipeline steps** — _github_workflows_ci_ci_quality_job, _github_workflows_ci_postgres_service, _github_workflows_ci_migrate_rollback_cycle, _github_workflows_ci_pint_test_step, _github_workflows_ci_composer_audit_step, _github_workflows_ci_artisan_test_step [EXTRACTED 1.00]
- **Database Oasis Core Domain Model** — docs_master_build_pack_database_oasis, docs_master_build_pack_sales_case_backbone, docs_master_build_pack_business_rules, docs_master_build_pack_rbac_roles, docs_master_build_pack_branch_isolation [EXTRACTED 1.00]
- **Phase 0 Bootstrap Foundation** — docs_phase_0_prompt_phase_0_bootstrap, docs_master_build_pack_tech_stack, docs_master_build_pack_health_endpoint, docs_master_build_pack_rbac_roles, agents_code_quality_toolchain, compose_app, compose_postgres [INFERRED 0.95]
- **Legacy Migration & Sheets Cutover** — docs_master_build_pack_legacy_migration, docs_master_build_pack_google_sheets_mirror, docs_master_build_pack_pilot_jepara [EXTRACTED 1.00]

## Communities (205 total, 150 thin omitted)

### Community 0 - "Composer Configuration"
Cohesion: 0.04
Nodes (45): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+37 more)

### Community 1 - "Domain Enums & Models"
Cohesion: 0.07
Nodes (15): ProjectStatus, UnitStatus, UtilityStatus, BankFactory, static, BranchFactory, static, UnitFactory (+7 more)

### Community 2 - "User & Branch Access Control"
Cohesion: 0.09
Nodes (6): User, BranchPolicy, UserPolicy, Illuminate\Foundation\Auth\User, self, PhaseZeroFoundationTest

### Community 3 - "Project Docs & Agent Rules"
Cohesion: 0.14
Nodes (30): AGENTS.md - Laravel Boost + Graphify Guidelines, .ai/rules Project Rules, Code Quality Toolchain (Pint, PHPStan, PHPUnit), Graphify Knowledge Graph Workflow, Laravel Boost MCP Tools, CLAUDE.md - Laravel Boost Guidelines, compose.yaml app service (Laravel app), compose.yaml postgres service (PostgreSQL 17) (+22 more)

### Community 4 - "Database Migrations"
Cohesion: 0.10
Nodes (3): Illuminate\Database\Migrations\Migration, Illuminate\Database\Schema\Blueprint, Illuminate\Support\Facades\Schema

### Community 5 - "Composer Scripts & Tooling"
Cohesion: 0.07
Nodes (28): scripts, analyse, dev, format, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd (+20 more)

### Community 6 - "Panel & Auth Bootstrap"
Cohesion: 0.09
Nodes (19): AppServiceProvider, AdminPanelProvider, Filament\Http\Middleware\Authenticate, Filament\Http\Middleware\AuthenticateSession, Filament\Http\Middleware\DisableBladeIconComponents, Filament\Http\Middleware\DispatchServingFilamentEvent, Filament\Pages\Dashboard, Filament\Panel (+11 more)

### Community 7 - "Filament Table Definitions"
Cohesion: 0.20
Nodes (13): ProjectsTable, UnitsTable, UsersTable, Filament\Actions\BulkActionGroup, Filament\Actions\DeleteAction, Filament\Actions\DeleteBulkAction, Filament\Actions\EditAction, Filament\Forms\Components\Toggle (+5 more)

### Community 9 - "Testing Skill Rules (.agents)"
Cohesion: 0.11
Nodes (21): Assertions (rule), Endpoint Tests (rule), How to Find Test Framework Features (rule), Fakes, Mocks, and Determinism (rule), Naming and Structure (rule), Test Suite Performance (rule), Reviewing Tests (rule), Security Tests (rule) (+13 more)

### Community 10 - "JS Build Dependencies"
Cohesion: 0.10
Nodes (20): concurrently, @laravel/multiplex, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite (+12 more)

### Community 11 - "Skill Rule Files (.claude)"
Cohesion: 0.11
Nodes (20): Migration Best Practices (Laravel rules), composer audit dependency audit, Convention and Style Best Practices (Laravel rules), Assertions rules (testing skill), Endpoint Tests rules (testing skill), Finding Test Framework Features rules (testing skill), Fakes, Mocks, and Determinism rules (testing skill), Naming and Structure rules (testing skill) (+12 more)

### Community 12 - "Project Domain & Policy"
Cohesion: 0.14
Nodes (6): Project, ProjectPolicy, ProjectFactory, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Relations\HasMany

### Community 13 - "Filament Form Schemas"
Cohesion: 0.19
Nodes (9): ProjectForm, UnitForm, UserForm, Filament\Forms\Components\Select, Filament\Forms\Components\TextInput, Filament\Schemas\Components\Utilities\Get, Filament\Schemas\Schema, Illuminate\Validation\Rules\Exists (+1 more)

### Community 14 - "Master Data Resources"
Cohesion: 0.16
Nodes (5): BankResource, ManageBanks, BranchResource, ManageBranches, Filament\Resources\Pages\ManageRecords

### Community 15 - "Resource Branch Scoping"
Cohesion: 0.36
Nodes (8): ProjectResource, UnitResource, UserResource, BackedEnum, Filament\Resources\Resource, Filament\Support\Icons\Heroicon, Illuminate\Database\Eloquent\Builder, UnitEnum

### Community 16 - "Eloquent Base & ULID"
Cohesion: 0.17
Nodes (8): Model, Filament\Models\Contracts\FilamentUser, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Concerns\HasUlids, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo, Illuminate\Notifications\Notifiable, Spatie\Permission\Traits\HasRoles

### Community 17 - "Filament List Pages"
Cohesion: 0.24
Nodes (5): ListProjects, ListUnits, ListUsers, Filament\Actions\CreateAction, Filament\Resources\Pages\ListRecords

### Community 18 - "Laravel Best-Practice Rules"
Cohesion: 0.20
Nodes (11): Convention Detection Checklist, 49 Laravel convention dimensions, Advanced Query Best Practices, Correlated subquery pattern, Eloquent Best Practices, Attribute casts, Global scope tradeoff, Local query scopes (+3 more)

### Community 19 - "Filament Edit Pages"
Cohesion: 0.24
Nodes (4): EditProject, EditUnit, EditUser, Filament\Resources\Pages\EditRecord

### Community 20 - "RBAC Roles & Login"
Cohesion: 0.25
Nodes (6): UserRole, Filament\Auth\Pages\Login, Filament\Facades\Filament, Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Support\Facades\Gate, Livewire\Livewire

### Community 23 - "Agent Tool Configuration"
Cohesion: 0.20
Nodes (9): command, enabled, type, mcp, laravel-boost, $schema, artisan, boost:mcp (+1 more)

### Community 24 - "Misc Cluster 24"
Cohesion: 0.22
Nodes (8): Atomic locks for race conditions, Caching Best Practices, Queue and Job Best Practices, Progressive backoff for transient failures, Bus::batch group coordination, ShouldBeUnique dispatch deduplication, Task Scheduling Best Practices, withoutOverlapping() overlap prevention

### Community 25 - "Misc Cluster 25"
Cohesion: 0.25
Nodes (9): Configuration Best Practices, Encrypted environment files, env() only in config files, Error Handling Best Practices, Exception report()/render() methods, Security Best Practices, CSRF protection in Blade forms, Mass assignment protection via $fillable (+1 more)

### Community 26 - "Misc Cluster 26"
Cohesion: 0.31
Nodes (4): CreateProject, CreateUnit, CreateUser, Filament\Resources\Pages\CreateRecord

### Community 27 - "Spatie Permission Seeding"
Cohesion: 0.28
Nodes (6): DatabaseSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder, Spatie\Permission\DefaultTeamResolver, Spatie\Permission\Models\Permission, Spatie\Permission\Models\Role

### Community 28 - "Misc Cluster 28"
Cohesion: 0.25
Nodes (7): Assert the Complete Result, Endpoint Coverage Matrix, Tenant Isolation: 404 Over 403, Time and Randomness Control, Test Name as Specification, Test Value Review Checklist, Cross-Tenant Access Test

### Community 29 - "Misc Cluster 29"
Cohesion: 0.29
Nodes (7): HTTP Client Best Practices, Explicit HTTP client timeouts, Idempotency keys for safe retries, Convention and Style Best Practices, Laravel naming convention table, laravel-best-practices skill, testing-best-practices skill

### Community 30 - "Misc Cluster 30"
Cohesion: 0.33
Nodes (6): Blade and View Best Practices, Blade components with attribute bags, tailwindcss-development skill, Tailwind v4 CSS-first @theme configuration, dark: variant support, gap utilities for sibling spacing

### Community 31 - "Misc Cluster 31"
Cohesion: 0.33
Nodes (6): Routing and Controller Best Practices, Resource controller organization, Implicit route model binding, Authorization of protected actions, Validation and Forms Best Practices, Form Request validation boundary

### Community 32 - "Misc Cluster 32"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Testing\TestCase, ExampleTest, TestCase

### Community 33 - "Misc Cluster 33"
Cohesion: 0.50
Nodes (5): infer-conventions skill, .ai/rules shared rules directory, Record decisions, not defaults test, record-rule Boost MCP tool, Consistency First principle

### Community 34 - "Misc Cluster 34"
Cohesion: 0.40
Nodes (5): Architecture Best Practices, Action class pattern, Constructor dependency injection preference, Context request-scoped data, defer() post-response work

### Community 35 - "Misc Cluster 35"
Cohesion: 0.50
Nodes (5): Collection Best Practices, lazy() vs cursor() iteration tradeoffs, Database Performance Best Practices, N+1 query prevention via eager loading, Model::preventLazyLoading development guard

### Community 36 - "Misc Cluster 36"
Cohesion: 0.50
Nodes (5): Events and Notifications Best Practices, ShouldDispatchAfterCommit, Queued notifications, Mail Best Practices, Queued mailables with afterCommit()

### Community 37 - "Misc Cluster 37"
Cohesion: 0.40
Nodes (4): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware, Illuminate\Http\Request

### Community 38 - "Misc Cluster 38"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 39 - "Misc Cluster 39"
Cohesion: 0.50
Nodes (4): Routing and Controller Best Practices (Laravel rules), Validation and Forms Best Practices (Laravel rules), Validation message testing per rule, Data providers for parameterized cases

### Community 41 - "Misc Cluster 41"
Cohesion: 1.00
Nodes (3): Consistency First (testing skill), Document Reality, Never Judge (consistency first), Consistency First (laravel skill)

### Community 43 - "Misc Cluster 43"
Cohesion: 0.67
Nodes (3): Action/Service Structure Fork, Business Logic Location Fork, Action Classes for Focused Business Operations

### Community 44 - "Misc Cluster 44"
Cohesion: 0.67
Nodes (3): Correlated Subquery with addSelect(), Eager Loading Before Iterating (N+1), Local Scopes for Reusable Queries

### Community 45 - "Misc Cluster 45"
Cohesion: 0.67
Nodes (3): cursor() vs lazy() Tradeoff, lazyById() for Safe Iteration While Updating, Process Large Data Sets Incrementally (chunk/chunkById/cursor/lazy)

### Community 46 - "Misc Cluster 46"
Cohesion: 0.67
Nodes (3): Notification afterCommit dispatch, ShouldDispatchAfterCommit, Mailable afterCommit dispatch

### Community 47 - "Misc Cluster 47"
Cohesion: 0.67
Nodes (3): Delivery mode assertions (assertQueued vs assertSent), Framework fakes for facades, Global fakes in base TestCase setUp

### Community 48 - "Misc Cluster 48"
Cohesion: 0.67
Nodes (3): Controllers focused on HTTP concerns, Validate and store uploads safely, Form request extraction at the boundary

## Knowledge Gaps
- **150 isolated node(s):** `php`, `Controller`, `$schema`, `name`, `type` (+145 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 413 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **150 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User & Branch Access Control` to `Domain Enums & Models`, `Panel & Auth Bootstrap`, `Filament Table Definitions`, `Phase 1 Feature Tests`, `Project Domain & Policy`, `Filament Form Schemas`, `Resource Branch Scoping`, `Eloquent Base & ULID`, `RBAC Roles & Login`, `Bank Domain & Policy`, `Unit Domain & Policy`, `Misc Cluster 26`?**
  _High betweenness centrality (0.072) - this node is a cross-community bridge._
- **Why does `UserRole` connect `RBAC Roles & Login` to `User & Branch Access Control`, `Panel & Auth Bootstrap`, `Project Domain & Policy`, `Eloquent Base & ULID`, `Bank Domain & Policy`, `Unit Domain & Policy`, `Spatie Permission Seeding`?**
  _High betweenness centrality (0.011) - this node is a cross-community bridge._
- **Why does `Branch` connect `Phase 1 Feature Tests` to `Domain Enums & Models`, `User & Branch Access Control`, `Filament Table Definitions`, `Project Domain & Policy`, `Eloquent Base & ULID`, `RBAC Roles & Login`, `Bank Domain & Policy`, `Unit Domain & Policy`?**
  _High betweenness centrality (0.011) - this node is a cross-community bridge._
- **What connects `php`, `Controller`, `$schema` to the rest of the system?**
  _150 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Composer Configuration` be split into smaller, more focused modules?**
  _Cohesion score 0.043478260869565216 - nodes in this community are weakly interconnected._
- **Should `Domain Enums & Models` be split into smaller, more focused modules?**
  _Cohesion score 0.07396870554765292 - nodes in this community are weakly interconnected._
- **Should `User & Branch Access Control` be split into smaller, more focused modules?**
  _Cohesion score 0.09462365591397849 - nodes in this community are weakly interconnected._