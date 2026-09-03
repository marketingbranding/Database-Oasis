# UAT Guide (Phase 7.5)

Manual UAT runs against the real Filament UI with demo data seeded through the real domain actions (no fake rows). Demo data covers every Phase 0–7 scenario.

## Starting the environment

```sh
cp .env.example .env            # if not present
docker compose build
docker compose run --rm app composer install
docker compose run --rm app php artisan key:generate
docker compose run --rm app php artisan migrate:fresh --seed
docker compose run --rm app php artisan db:seed --class='Database\Seeders\UatDemoSeeder'
docker compose up -d
```

Open `http://127.0.0.1:8000/admin/login`.

The seeder is development-only. It refuses to run in production and is not referenced by `DatabaseSeeder`. If it is run twice it warns and exits instead of duplicating data.

## Demo credentials

Password for every account: `password`

| Email | Role | Scope |
|---|---|---|
| super@uat.test | Super Admin | unrestricted |
| hq@uat.test | HQ Admin | all branches, operational |
| jepara.admin@uat.test | Branch Admin | Jepara only |
| jepara.manager@uat.test | Branch Manager | Jepara only, read-only |
| semarang.admin@uat.test | Branch Admin | Semarang only |
| management@uat.test | Management | all branches, read-only |
| auditor@uat.test | Auditor | all branches, read-only |

Branches: Jepara (projects Marison Regency A/B), Semarang (Semarang Indah). Banks: BTN, BRI, BCA.

## Scenario walkthroughs

For every case open `Operasional → Sales Cases`, use the Konsumen search to find the case, open it, and check the process summary cards, financing-aware stepper, and vertical timeline (oldest at the top, newest at the bottom).

### 1. KPR normal (completed chain)

Case: consumer **Budi Santoso**, unit A-01.

1. Open the workspace. Stepper shows every stage **done**, status COMPLETED.
2. Timeline order: BI Clear → PSJB → Submission BCA → Response Process → Response Approved (SP3K-UAT-001) → PPJB → Akad (AKAD-UAT-001) → BAST.
3. Unit A-01 status is TERJUAL.
4. No quick actions are visible (case completed).

Expected: full chain rendered from real records; no fabricated data.

### 2. Multiple bank: BTN rejected → BRI approved

Case: **Citra Lestari**, unit A-02.

1. Timeline groups attempts: "Pemberkasan #1 — BTN" followed by its PROCESS and REJECTED responses, then "Pemberkasan #2 — BRI" followed by its APPROVED response with SP3K-UAT-002. Each response is tagged with its own attempt.
2. Case stays ACTIVE at stage PPJB_DEV. Unit stays BOOKING.
3. Monitoring (`Monitoring → SP3K & Kendala`) lists the case once; Bank column shows **Bank BRI**, not BTN.
4. Readiness is fully CLEAR: Kendala count 0.

### 3. CASH chain with zero bank records

Case: **Dedi Pratama**, unit A-03.

1. Timeline reads top-to-bottom: Sales Case dibuat → PSJB dibuat → **Pemberkasan CASH selesai** → PPJB Developer dibuat → Akad → BAST. **No** BI Checking, bank, or SP3K entries.
2. Stepper shows only: Data Konsumen, PSJB, Pemberkasan, PPJB Developer, Akad, BAST, Completed — BI Checking and Proses Bank are not part of the CASH stepper.
3. Financing badge shows CASH; process summary shows Pemberkasan CASH status, no meaningless Bank/Response/SP3K placeholders.
4. `Bank Processes` resource contains no rows for this case; monitoring SP3K table does not list it.

### 4. Pindah Kavling (K-20 → K-15)

Cases: **Eko Prasetyo**.

1. Search "Eko" — one case shown for unit **K-15**.
2. Open it: section "Pindah Kavling" links to the previous case (unit K-20, PINDAH_KAVLING).
3. Open the old case: status PINDAH_KAVLING with transfer reason, history intact, unit K-20 back to TERSEDIA, unit K-15 BOOKING.

### 5. Mundur, unit reused by new consumer

Cases: **Fajar Nugroho** (closed) and **Gilang Ramadhan** (active), both unit A-05.

1. Fajar: status MUNDUR with reason, unit A-05 TERSEDIA.
2. Gilang: active new case on the same unit, own history, no link to Fajar's identity.

### 6. SP3K without kendala

Case: **Citra Lestari** (same as scenario 2).

1. Workspace "Akad Readiness" shows Progress Bangunan 100%, Bangunan Clear, DP Lengkap, Listrik Terpasang, Air Terpasang, Konsumen Clear.
2. Jumlah Kendala = 0.
3. Monitoring cards: SP3K Units with Kendala does not increase because of this case.

### 7. SP3K with multiple kendala

Case: **Hana Wijaya**, unit A-06.

1. Readiness: Bangunan Ada Kendala (55%), DP Belum Lengkap, Listrik Belum Terpasang, Konsumen Ada Kendala + note.
2. Jumlah Kendala = 4 (Bangunan, DP Konsumen, Utilitas via listrik, Konsumen).
3. Monitoring → SP3K & Kendala: Total Open Kendala increases by 4 while SP3K Units with Kendala increases by only 1 for this case.
4. Kendala breakdown counts the case under Bangunan, DP Konsumen, Utilitas, and Konsumen.

### 8. UNKNOWN readiness is not kendala

Case: **Indra Kusuma**, unit A-07 (all statuses "Belum Diisi") and **Joko Susilo**, unit A-08 (no readiness data at all).

1. Both workspaces show UNKNOWN badges and Jumlah Kendala = 0.
2. Monitoring: **Readiness Data Incomplete** counts both; SP3K Units with Kendala and Total Open Kendala do not.
3. Use workspace action **Update Akad Readiness** to fill real values and watch the incomplete metric drop.

### 9. Duplicate document numbers stay independent

Cases: **Kusnadi Hartono** (A-09) and **Lina Marlina** (A-10).

1. Both carry PSJB number `PSJB-UAT-DUP`, SP3K number `SP3K-UAT-DUP`, PPJB number `PPJB-UAT-DUP`.
2. Global search for `SP3K-UAT-DUP` returns **two** Sales Cases; each links back to its own consumer/unit only.
3. Open both workspaces: histories are separate; neither case appears in the other's timeline.
4. Monitoring counts both cases in SP3K Stock (2 units), never merged.

### 10. Branch isolation

1. **jepara.admin@uat.test**: Sales Cases list shows only Jepara cases; Semarang's **Mulyadi Setiawan** is absent. Monitoring defaults to Jepara; branch selector lists only Jepara.
2. **semarang.admin@uat.test**: sees only Semarang.
3. **jepara.manager@uat.test**: same Jepara scope, no edit/create actions on records, no Target Akad mutation.
4. **management@uat.test** / **auditor@uat.test**: monitoring across all branches (Jepara target 10, Semarang target 5), read-only everywhere; no Target Akad create/edit, no readiness editing, no operational case actions.
5. **hq@uat.test**: sees both branches, can set targets (`Monitoring → Target Akad`), can edit readiness on any branch.
6. **super@uat.test**: unrestricted, including Master Data and Users.

### Monitoring month view

`Monitoring → Monitoring` with any HQ-type account, current month:

- Target Akad (Jepara filter) = 10; Semarang = 5; Semua Cabang = 15.
- Realisasi Akad = 2 (Budi + Dedi, both akad-dated this month). Achievement = 20% for Jepara.
- M1–M4: both akad dates fall in the current day bucket (today's date determines which of M1–M4 increments; the four buckets still sum to 2).
- BAST Bulan Ini = 2.
- SP3K Stock = 6 (A-02, A-06, A-07, A-08, A-09, A-10).
- SP3K Units with Kendala = 1 (Hana). Total Open Kendala = 4.
- Readiness Data Incomplete = 4 (Indra + Joko + Kusnadi + Lina; the two duplicate-number cases have no readiness data yet).

Click an aging bucket or a kendala category card — the SP3K table opens pre-filtered with the same cases.

## Sanity checks after testing changes

```sh
php artisan test --compact
```

Expected: all Phase 0–7 tests pass (185 tests).
