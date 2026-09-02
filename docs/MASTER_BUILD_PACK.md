**DATABASE OASIS**

**Master Product, Architecture & Build Pack**

Internal Sales Operations Database - Marison Regency

| **Target domain** | databaseoasis.marison.id                |
|-------------------|-----------------------------------------|
| **Platform**      | Web app internal, desktop-first         |
| **Stack**         | Laravel + Filament + PostgreSQL         |
| **Dokumen**       | MVP-first, siap dipakai AI coding agent |

Versi 1.0 - September 2026

# 0. Cara Pakai Dokumen Ini

> **Tujuan utama:** Dokumen ini sengaja dibuat ringkas. Gunakan sebagai sumber kebenaran untuk product scope, data model, business rules, migration, dan instruksi AI coding agent. Jangan menambah fitur di luar MVP sebelum pilot Jepara stabil.

1. Kunci keputusan produk di Bab 1-8.

2. Buat repository kosong dan berikan Bab 13 (Master Prompt) ke AI coding agent.

3. Kerjakan per fase. Agent harus berhenti setelah setiap fase untuk verifikasi.

4. Pilot pertama: Jepara. Jalankan paralel dengan Google Sheets sampai angka bisa direkonsiliasi.

5. Setelah pilot lolos, rollout cabang lain bertahap.

# 1. Product Definition

Database Oasis adalah aplikasi internal terpisah dari Oasis CRM. Oasis menangani lead/marketing; Database Oasis menangani administrasi transaksi dari konsumen sampai BAST.

| **Area**        | **Oasis CRM**                                    | **Database Oasis**                                      |
|-----------------|--------------------------------------------------|---------------------------------------------------------|
| Fokus           | Lead, follow-up, survey, booking, sales activity | Konsumen, BI, PSJB, pemberkasan, bank, PPJB, akad, BAST |
| Pengguna utama  | Marketing / sales                                | Admin cabang, HQ, pimpinan                              |
| Source of truth | CRM                                              | SQL database                                            |
| Google Sheets   | Bukan fokus                                      | Mirror/report selama transisi                           |

## 1.1 Masalah yang diselesaikan

- Satu kavling dapat berganti konsumen tanpa mengubah histori konsumen lama.

- Satu konsumen dapat pindah kavling dan tetap memiliki histori transaksi yang utuh.

- Satu sales case dapat memiliki beberapa pemberkasan dan beberapa bank attempt.

- Reject bank tidak otomatis menutup sales case; pengajuan dapat dilanjutkan ke bank lain.

- CASH bukan lagi nilai palsu pada nomor SP3K.

- Nomor SP3K/PPJB/BAST tidak dipakai sebagai primary key.

- Monitoring tidak lagi bergantung pada INDEX/FILTER/XLOOKUP/COUNTIF antar-sheet.

- Perubahan data dapat ditelusuri melalui audit log.

## 1.2 Sasaran MVP

- Bisa dipakai admin cabang untuk input transaksi harian.

- HQ melihat semua cabang secara real-time.

- Flow lengkap: Konsumen -> BI -> PSJB -> Pemberkasan -> Proses Bank -> PPJB -> Akad -> BAST.

- Dashboard minimum: Pipeline, Akad, SP3K, Aging, Kendala.

- Import data legacy dan rekonsiliasi tersedia.

- Google Sheets menjadi mirror satu arah dari database.

## 1.3 Non-goals V1

- WhatsApp automation

- Accounting/ERP

- Construction management

- Customer portal

- Mobile native app

- Advanced document generation

- Full integration ke Oasis CRM

# 2. Core Workflow & Identitas

> **Prinsip terpenting:** Satu perjalanan seorang konsumen terhadap satu unit = satu sales_case_id. Semua proses berikutnya menempel ke sales_case_id tersebut.

```text
Consumer + Unit
|
v
Sales Case
|
+-- BI Checking
+-- PSJB
+-- Pemberkasan (1..n)
+-- Bank Process (1..n)
+-- PPJB Developer
+-- Akad
+-- BAST
```

## 2.1 Internal ID

- Gunakan ULID sebagai primary key untuk seluruh tabel utama.

- Admin tidak pernah mengetik atau melihat internal ID dalam workflow normal.

- NIK, nomor SP3K, nomor PPJB, nomor Akad, nomor BAST adalah atribut bisnis, bukan primary key.

- Field legacy ID disimpan hanya untuk migrasi/audit.

## 2.2 Pergantian kavling/konsumen

| **Situasi**                     | **Aturan**                                                                               |
|---------------------------------|------------------------------------------------------------------------------------------|
| Konsumen pindah K20 -> K15     | Tutup case lama sebagai PINDAH_KAVLING; buat case baru untuk K15; link previous_case_id. |
| K20 ganti konsumen Sri -> Budi | Case Sri tetap historis; buat case baru Budi untuk K20.                                  |
| Bank reject lalu pindah bank    | Tetap satu sales case; buat submission/bank process baru.                                |
| CASH                            | sales_case.financing_type=CASH; tidak membutuhkan fake SP3K.                             |

# 3. Roles & Permissions

| **Role**       | **Scope**         | **Hak utama**                           |
|----------------|-------------------|-----------------------------------------|
| Super Admin    | Semua             | Semua konfigurasi dan data              |
| HQ Admin       | Semua cabang      | Operasional, koreksi, import, reporting |
| Branch Admin   | Cabang sendiri    | Input/edit transaksi cabang             |
| Branch Manager | Cabang sendiri    | Read + reporting                        |
| Management     | Semua             | Read-only dashboard/report              |
| Auditor        | Semua sesuai izin | Read-only + audit log                   |

> **Branch isolation:** Setiap query operasional harus otomatis scoped ke branch user. Branch Admin Jepara tidak boleh melihat atau mengedit cabang lain tanpa permission eksplisit.

# 4. Data Model / ERD

Schema dibuat minimum namun extensible. Field tambahan boleh dibuat hanya jika dibutuhkan oleh form legacy atau business rule yang sudah disepakati.

| **Entity**           | **Minimum fields**                                                                                                            | **Catatan**                           |
|----------------------|-------------------------------------------------------------------------------------------------------------------------------|---------------------------------------|
| branches             | id ULID; code; name; city; province; is_active                                                                                | 1 -> n projects/users                |
| projects             | id; branch_id; code; name; location; status                                                                                   | 1 -> n units                         |
| units                | id; project_id; unit_code; block; number; status; building_progress; electricity_status; water_status                         | unique(project_id, unit_code)         |
| consumers            | id; nik; name; phone; email; birth/date; address; occupation; income; notes                                                   | NIK indexed, not PK                   |
| sales_cases          | id; branch_id; project_id; unit_id; consumer_id; financing_type; current_stage; case_status; previous_case_id; PIC; dates     | tulang punggung transaksi             |
| bi_checks            | id; sales_case_id; check_date; result; description                                                                            | 1 sales_case -> n BI checks          |
| psjbs                | id; sales_case_id; psjb_date; document_number; coordinator_id; status; notes                                                  | 1 sales_case -> n PSJB bila perlu    |
| document_submissions | id; sales_case_id; psjb_id; sequence; received_by_bank_at; bank_id; status; notes                                             | multiple submission allowed           |
| bank_processes       | id; sales_case_id; document_submission_id; bank_id; response_type; response_date; sp3k_number; sp3k_date; credit_limit; tenor | multiple bank attempt allowed         |
| developer_ppjbs      | id; sales_case_id; bank_process_id nullable; document_number; document_date; notes                                            | duplicate business number => warning |
| akad_records         | id; sales_case_id; developer_ppjb_id nullable; document_number; akad_date; kualitas_akad; kendala fields; notes               | legacy may have missing upstream      |
| bast_records         | id; sales_case_id; akad_id; bast_number; bast_date; status; notes                                                             | BAST normal flow requires Akad        |
| case_events          | id; sales_case_id; event_type; event_at; payload; created_by                                                                  | business timeline                     |
| audit_logs           | id; user_id; entity_type; entity_id; action; old_value; new_value; ip; created_at                                             | immutable audit trail                 |
| legacy_mappings      | id; branch_id; source_sheet; source_row; legacy_id; entity_type; entity_id; import_batch_id                                   | traceability import                   |
| migration_batches    | id; branch_id; source; started_at; status; metrics; warnings; errors                                                          | dry-run/import/reconcile              |
| banks                | id; code; name; is_active                                                                                                     | master bank                           |
| users                | id; branch_id nullable; name; email; password; is_active                                                                      | RBAC                                  |

## 4.1 Relationship map

```text
Branch 1--n Project 1--n Unit
Consumer 1--n SalesCase n--1 Unit
SalesCase 1--n BI_Check
SalesCase 1--n PSJB
SalesCase 1--n Document_Submission
Document_Submission 1--n Bank_Process
SalesCase 1--n Developer_PPJB
SalesCase 1--n Akad
SalesCase 1--n BAST
SalesCase 1--n Case_Event
```

# 5. Business Rules

| **ID** | **Rule**                                                                                                                    |
|--------|-----------------------------------------------------------------------------------------------------------------------------|
| BR-01  | Satu active sales case untuk kombinasi unit + transaksi aktif; duplicate active unit harus warning/block sesuai permission. |
| BR-02  | Pindah kavling membuat sales case baru dan menutup case lama sebagai PINDAH_KAVLING.                                        |
| BR-03  | Mundur menutup sales case. Data historis tidak dihapus.                                                                     |
| BR-04  | Bank REJECTED tidak otomatis membuat sales_case=REJECT.                                                                     |
| BR-05  | CASH menggunakan financing_type=CASH; SP3K boleh null.                                                                      |
| BR-06  | BAST normal flow membutuhkan Akad. Legacy override hanya HQ/Super Admin.                                                    |
| BR-07  | Current stage dihitung oleh service/action, bukan input bebas user.                                                         |
| BR-08  | Semua perubahan transaksi penting ditulis ke audit_logs.                                                                    |
| BR-09  | Soft delete untuk data transaksi. Restore hanya role berizin.                                                               |
| BR-10  | Nomor dokumen duplicate menampilkan warning; jangan otomatis dianggap primary key.                                          |
| BR-11  | Kendala unit dan jumlah kategori kendala harus dibedakan pada dashboard.                                                    |
| BR-12  | Semua metric dashboard menggunakan MonitoringService yang sama.                                                             |

## 5.1 Status dan stage

| **Jenis**      | **Nilai minimum**                                                                           |
|----------------|---------------------------------------------------------------------------------------------|
| case_status    | ACTIVE, COMPLETED, MUNDUR, REJECT, PINDAH_KAVLING, CANCELLED                                |
| current_stage  | DATA_KONSUMEN, BI_CHECKING, PSJB, PEMBERKASAN, PROSES_BANK, PPJB_DEV, AKAD, BAST, COMPLETED |
| financing_type | KPR_SUBSIDI, CASH                                                                           |
| bank response  | PROCESS, APPROVED, REJECTED, REVISION                                                       |
| BI result      | CLEAR, REVIEW, REJECT, OTHER                                                                |

# 6. UI / UX Specification

> **Design direction:** Gunakan Filament apa adanya sebanyak mungkin. Jangan membuat design system custom pada MVP. Target: familiar, compact, cepat, desktop-first.

## 6.1 Navigation

```text
Dashboard
Operasional
- Konsumen
- Sales Cases
- Unit / Kavling
Proses Penjualan
- BI Checking
- PSJB
- Pemberkasan
- Proses Bank
- PPJB Developer
- Akad
- BAST
Monitoring
- Pipeline
- Akad
- SP3K
- Aging & Kendala
Master Data
- Cabang
- Proyek
- Bank
- Users
System
- Import Legacy
- Google Sheets Sync
- Audit Log
```

## 6.2 Sales Case detail

- Header: nama konsumen, project/unit, status, stage, financing type, aging.

- Stepper: Data -> BI -> PSJB -> Berkas -> Bank -> PPJB -> Akad -> BAST.

- Tabs: Overview, Timeline, Documents, Audit.

- Quick action: Add Process. User memilih BI/PSJB/Pemberkasan/Bank Response/PPJB/Akad/BAST/Pindah/Mundur/Reject.

- Form menggunakan drawer/modal Filament dan menyembunyikan internal ID.

## 6.3 Minimum dashboard

| **Dashboard** | **Metric**                                           |
|---------------|------------------------------------------------------|
| Pipeline      | Jumlah per current_stage + aging                     |
| Akad          | Target, realisasi, M1-M4, per branch/project         |
| SP3K          | Approved, stock belum akad, ready, terkendala, aging |
| Kendala       | Jumlah unit terkendala vs jumlah kategori kendala    |
| BAST          | Jumlah BAST bulan berjalan + aging dari akad         |

# 7. Technical Architecture

| **Layer**        | **Pilihan MVP**                                       |
|------------------|-------------------------------------------------------|
| Backend          | PHP 8.4+ / Laravel                                    |
| Admin UI         | Filament                                              |
| Database         | PostgreSQL                                            |
| Auth/RBAC        | Laravel auth + role/permission package yang stabil    |
| Queue            | Database queue pada MVP; Redis optional setelah perlu |
| Storage          | S3-compatible untuk attachment; local dev storage     |
| Spreadsheet      | Google Sheets API, one-way export/mirror              |
| Deploy           | Docker + Nginx; staging dan production terpisah       |
| Monitoring error | Laravel logs; Sentry optional production              |

```text
Admin Browser
|
v
Laravel + Filament
|
+--> PostgreSQL [SOURCE OF TRUTH]
+--> Queue Jobs
+--> Object Storage
+--> Google Sheets API --> Sheets Mirror
+--> Dashboard / Reports
```

## 7.1 Engineering rules

- Business logic di Actions/Services, bukan menumpuk di Filament Resource.

- Gunakan DB transactions untuk pindah kavling, close case, create downstream records.

- Gunakan foreign key constraints dan indexes.

- Gunakan policies untuk branch isolation.

- Tidak ada credential di repository.

- No destructive migration tanpa backup/explicit approval.

- Semua status menggunakan enum.

# 8. Google Sheets Strategy

> **Arah sinkronisasi:** Database -> Google Sheets. Hindari two-way sync pada target akhir supaya tidak ada konflik sumber data.

- Saat transisi, Sheets tetap dipakai sebagai compatibility/report layer.

- ringkasan_data baru dibuat oleh backend sebagai materialized summary/export, bukan XLOOKUP antar-sheet.

- Monitoring aplikasi query SQL langsung, tidak query Google Sheets.

- Sync harus idempotent dan memiliki halaman status/retry.

# 9. Legacy Migration Strategy

| Extract -> Normalize -> Map -> Dry Run -> Review -> Import -> Reconcile |
|-------------------------------------------------------------------------------|

| **Fitur migration** | **Requirement**                                                             |
|---------------------|-----------------------------------------------------------------------------|
| Dry run             | Tidak menulis ke production; tampilkan jumlah row, warning, critical issue. |
| Legacy mapping      | Simpan source sheet + row + legacy ID -> entity baru.                      |
| Confidence          | HIGH / MEDIUM / LOW untuk relasi legacy.                                    |
| Incomplete chain    | Boleh import Akad/BAST historical tanpa membuat data upstream palsu.        |
| Reconciliation      | Bandingkan count dan exception per stage.                                   |
| Pilot               | Jepara terlebih dahulu karena aktif dan kasus data kompleks.                |

## 9.1 Cutover

1. Stage A - Import legacy; app read-only untuk validasi.

2. Stage B - Input transaksi baru lewat app; Sheets tetap mirror.

3. Stage C - App menjadi source of truth; Sheets read/report only.

4. Stage D - Monitoring lama dapat dipensiunkan setelah rekonsiliasi stabil.

# 10. Development Plan

| **Phase** | **Nama**              | **Output**                                                           |
|-----------|-----------------------|----------------------------------------------------------------------|
| 0         | Bootstrap             | Laravel, Filament, PostgreSQL, auth, RBAC, Docker, CI, health check. |
| 1         | Master Data           | Branches, projects, units, banks, users, branch isolation.           |
| 2         | Consumer & Sales Case | Consumers, sales_cases, status/stage, pindah, mundur, reject.        |
| 3         | BI & PSJB             | BI checks, PSJB, timeline.                                           |
| 4         | Pemberkasan & Bank    | Multiple submission, multiple bank, SP3K, CASH.                      |
| 5         | PPJB, Akad, BAST      | Downstream transaction + validation chain.                           |
| 6         | Case Workspace        | Stepper, timeline, quick actions, global search.                     |
| 7         | Monitoring            | Pipeline, Akad M1-M4, SP3K, aging, kendala.                          |
| 8         | Legacy Import         | Dry run, mapping, reconciliation, pilot Jepara.                      |
| 9         | Sheets Mirror         | Google Sheets export/mirror + sync dashboard.                        |
| 10        | Pilot & Hardening     | Jepara parallel run, exception fixes, performance, security.         |
| 11        | Rollout               | Cabang lain bertahap setelah Jepara stabil.                          |

## 10.1 Definition of Done setiap fase

- Migrations pass

- Automated tests pass

- Pint clean

- PHPStan clean

- Browser smoke test pass

- No console error

- Docs/changelog updated

- Agent berhenti dan melaporkan sebelum lanjut fase berikutnya

# 11. Critical Acceptance Tests

1. Same consumer -> different units tidak boleh cross-link.

2. Different consumer -> same unit menyimpan histori masing-masing.

3. Pindah kavling membuat sales case baru dan link previous_case_id.

4. Multiple PSJB/pemberkasan/bank attempts tetap terkait case yang benar.

5. Reject bank -> bank lain -> Approved tetap satu sales case aktif.

6. 30 transaksi CASH tidak collision pada satu key.

7. Duplicate SP3K/PPJB number hanya warning dan tidak cross-link.

8. Historical Akad tanpa PPJB dapat diimport dengan flag incomplete legacy.

9. Historical BAST tanpa chain lengkap tidak memalsukan upstream.

10. Branch user tidak bisa melihat/edit branch lain.

11. Dashboard Akad M1-M4 selalu sama dengan query akad_records pada periode yang sama.

12. SP3K Stock tidak menghitung case yang sudah Akad.

# 12. Deployment & Operations

| **Item**           | **Minimum**                                                              |
|--------------------|--------------------------------------------------------------------------|
| Environment        | local / staging / production                                             |
| HTTPS              | wajib production                                                         |
| Backup DB          | daily; retention 30 harian + 12 bulanan                                  |
| Restore test       | periodik dan terdokumentasi                                              |
| Logging            | auth, import, sync, permission violation, exception                      |
| Performance target | table <2s; search <1s; dashboard <3s untuk typical request            |
| Scale target       | 10-20 cabang; 50k consumers; 100k+ process records; ~50 concurrent users |

# 13. Master Prompt untuk AI Coding Agent

Copy seluruh blok berikut ke coding agent saat repository siap. Agent harus bekerja fase-per-fase dan berhenti setelah verifikasi.

```text
You are the principal coding agent for "Database Oasis", an internal sales operations web app for Marison Regency.
GOAL
Build a simple, production-oriented Laravel + Filament + PostgreSQL application that replaces Google Sheets as the transactional source of truth for the workflow: Consumer -> BI Checking -> PSJB -> Document Submission -> Bank Process -> Developer PPJB -> Akad -> BAST.
CORE DOMAIN RULE
One consumer journey against one unit is exactly one sales_case. Every operational record must be linked to sales_case_id. Do not use NIK, unit code, SP3K number, PPJB number, or BAST number as relational primary keys. Use ULIDs for internal IDs.
ARCHITECTURE
- PHP 8.4+ / Laravel
- Filament admin UI
- PostgreSQL
- Business logic in Actions/Services
- Policies for authorization and branch isolation
- DB transactions for multi-step mutations
- Enums for statuses
- Soft-delete transaction records where appropriate
- Audit log for material data changes
- Google Sheets is never the database. Future sync is one-way: DB -> Sheets.
MVP ENTITIES
branches, projects, units, consumers, sales_cases, bi_checks, psjbs, document_submissions, banks, bank_processes, developer_ppjbs, akad_records, bast_records, case_events, audit_logs, migration_batches, legacy_mappings, users/roles/permissions.
CRITICAL BUSINESS RULES
1. Moving unit closes old case as PINDAH_KAVLING and creates a new linked case.
2. Consumer replacement on one unit creates a new case; old history is immutable.
3. Multiple document submissions and bank attempts are allowed.
4. Bank rejection does not automatically close the sales case.
5. CASH is financing_type=CASH and does not use fake SP3K.
6. Business document numbers are attributes, not primary keys. Duplicate document numbers produce warnings, not cross-links.
7. Normal BAST flow requires Akad. Historical import may use explicit legacy incomplete-chain override.
8. current_stage is maintained by domain logic, not arbitrary user input.
9. Branch users are strictly scoped to their branch.
10. Monitoring metrics come from one centralized MonitoringService.
UI/UX
Use Filament conventions. Do not create a custom design system for MVP. Desktop-first, compact admin tables, filters, badges, relation managers, actions, modals/drawers, searchable pages, global search. Main workflow should be driven by Sales Case detail with a process stepper, timeline, and Add Process quick action. Internal IDs must be hidden from normal users.
ENGINEERING RULES
- Do not broaden scope without explicit instruction.
- Do not create destructive migrations.
- Do not suppress tests to make CI pass.
- Do not put all business logic in Filament resources/pages.
- Do not silently change domain rules.
- Add automated tests for each domain rule.
- Keep migrations reversible.
- Use indexes and foreign keys.
- Keep credentials out of repository.
- Prefer the simplest maintainable implementation.
WORKFLOW
Work one phase at a time. For every phase:
1. Inspect existing repository first.
2. State a short implementation plan.
3. Implement only that phase.
4. Run migrations/tests/lint/static analysis.
5. Perform an application smoke test where possible.
6. Report changed files, decisions, test results, and known issues.
7. STOP and wait for approval before starting the next phase.
DEFINITION OF DONE
- migrations pass
- automated tests pass
- Laravel Pint clean
- PHPStan clean
- no obvious browser/console errors
- documentation/changelog updated
PHASES
0 Bootstrap
1 Master Data
2 Consumer & Sales Case
3 BI & PSJB
4 Document Submission & Bank
5 PPJB, Akad, BAST
6 Sales Case Workspace
7 Monitoring
8 Legacy Import
9 Google Sheets Mirror
10 Jepara Pilot & Hardening
11 Rollout
START ONLY WITH PHASE 0. Do not implement Phase 1 until I approve Phase 0.
```

# 14. Phase 0 - Exact Scope

| **Area**           | **Requirement**                                                  |
|--------------------|------------------------------------------------------------------|
| Repository         | Fresh Laravel project; sensible README and environment example.  |
| Filament           | Admin panel installed and reachable.                             |
| Database           | PostgreSQL configured; migrations run.                           |
| Auth               | Login/logout; inactive user blocked.                             |
| RBAC               | Role/permission foundation ready; seed initial Super Admin role. |
| Code quality       | Pint + PHPStan/Larastan + PHPUnit/Pest, documented commands.     |
| Docker             | Local app + PostgreSQL; optional mailcatcher not required.       |
| CI                 | Install, lint, static analysis, tests.                           |
| Health             | /up or equivalent health endpoint.                               |
| Observability      | structured Laravel logging baseline.                             |
| No domain CRUD yet | Do NOT build Branch/Project/Consumer tables in Phase 0.          |

## 14.1 Phase 0 acceptance checklist

- Fresh clone can boot with documented setup steps.

- Admin login page works.

- Automated test suite runs successfully.

- Pint and PHPStan pass.

- PostgreSQL connection works.

- CI pipeline passes.

- No domain feature beyond foundation has been implemented.

# 15. Backlog Setelah MVP

- Oasis CRM integration

- Google Workspace SSO

- WhatsApp/email notifications

- Document generation/e-sign

- Customer portal

- PWA/mobile

- Advanced BI

- Scheduled management reports

- Public/internal API expansion

# 16. Keputusan yang Sudah Dikunci

> **Jangan diperdebatkan ulang pada coding phase kecuali ada blocker teknis nyata:** Laravel + Filament + PostgreSQL; sales_case_id sebagai backbone; ULID internal IDs; app sebagai source of truth; Google Sheets one-way mirror; pilot Jepara; phase-by-phase coding agent workflow.
