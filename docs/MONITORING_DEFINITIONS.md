# Monitoring Definitions

Phase 7 monitoring queries PostgreSQL transactional records through `MonitoringService`. Filament pages receive prepared values and never reproduce KPI formulas.

## Monitoring Period

Selected month uses first through last calendar day. Weekly Akad buckets are fixed:

- M1: day 1–7
- M2: day 8–14
- M3: day 15–21
- M4: day 22–last day

M1 + M2 + M3 + M4 must equal monthly Akad realization.

## Target Akad

Target records store `period_month` as first day of month.

- Branch filter without project: explicit branch-level target where `project_id` is null.
- Specific project filter: project-level target for that project.
- All Branches: sum explicit branch-level targets only. Project targets are not included.
- Missing or zero target: achievement displays neutral `-`; missing target is not zero achievement.

Branch targets and project targets remain independent. Database partial/unique indexes prevent duplicate scope/month records.

## Achievement

`Realisasi Akad / Target Akad x 100`, rounded to one decimal, only when `target > 0`. A missing or zero target renders `-` and never computes.

## Authoritative Bank Process

Exactly one `bank_processes` row per Sales Case with `is_authoritative = true`, enforced by a partial unique index. Approval semantics (APPROVED + SP3K number/date) are maintained by `RecordBankResponseAction`. Monitoring never infers authority from latest response, bank, or SP3K number.

## Monitoring Is Read-Only

Monitoring queries and pages never mutate transactional workflow. Akad readiness is an operational snapshot; it does not gate Akad creation.

## Realisasi Akad

Count `akad_records` whose `akad_date` falls in selected month and whose Sales Case belongs to authorized branch/project scope. BAST existence or status does not affect this KPI.

## SP3K Stock

Count Sales Cases, maximum one per Sales Case, where all conditions hold:

- `financing_type = KPR_SUBSIDI`
- `case_status = ACTIVE`
- authoritative Bank Process has `response_type = APPROVED`
- authoritative Bank Process has non-null `sp3k_number` and `sp3k_date`
- no `akad_records` row exists

CASH, closed/superseded cases, non-authoritative attempts, rejected attempts, and cases with Akad are excluded. SP3K number is not identity; duplicate numbers on different Sales Cases count independently.

## SP3K Aging

Current date minus authoritative `sp3k_date`, only for current SP3K Stock:

- 0–7 days
- 8–14 days
- 15–30 days
- more than 30 days

Cases with Akad are excluded from every bucket.

## SP3K Units with Kendala

Count current SP3K Stock Sales Cases with at least one explicit readiness issue category. One Sales Case contributes at most one unit to this KPI.

## Total Open Kendala

Sum explicit open categories across current SP3K Stock. One Sales Case can contribute up to four categories:

- BANGUNAN: `building_status = ISSUE`
- DP_KONSUMEN: `dp_status = INCOMPLETE`
- UTILITAS: electricity or water is `NOT_INSTALLED`; both still form one Utilitas category
- KONSUMEN: `consumer_status = ISSUE`

This count can exceed SP3K Units with Kendala.

## Readiness Data Incomplete

Count current SP3K Stock Sales Cases that have no readiness row or at least one required readiness status still `UNKNOWN`.

`UNKNOWN` never counts as a kendala. This KPI separates missing data from an explicit issue.

## BAST Monthly

Count `bast_records` whose `bast_date` falls in selected month and `status = COMPLETED`, filtered by authorized Sales Case branch/project scope. BAST does not alter Akad realization.

## Authorization

- Super Admin: unrestricted.
- HQ Admin: all monitoring; manages targets and readiness.
- Management: all monitoring, read-only.
- Auditor: all monitoring, read-only.
- Branch Admin: own branch monitoring and targets; edits own branch readiness before Akad.
- Branch Manager: own branch monitoring and targets, read-only.

Branch and project scope is validated server-side. Client filter values can only narrow authorized data.
