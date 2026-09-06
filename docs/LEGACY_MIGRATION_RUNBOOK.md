# Legacy Migration Runbook

Audit tetap read-only. Persistent import hanya melalui controlled Phase 8D command setelah plan AUTO disetujui.

## Prasyarat

- Sumber legacy Jepara dalam salah satu bentuk:
  - Direktori CSV: satu file `.csv` per sheet (`data_konsumen.csv`, `bi_checking.csv`, `psjb.csv`, `pemberkasan.csv`, `proses_bank.csv`, `ppjb_dev.csv`, `akad.csv`, `bast.csv`).
  - Satu file `.xlsx` dengan sheet bernama sama.
- Taruh di `storage/app/private/legacy-audit/` (diabaikan Git; berisi PII).
- Header mengikuti alias di `docs/LEGACY_MIGRATION_JEPARA_MAPPING.md` (header asli dipertahankan di laporan).

## Menjalankan audit (CLI saja — tidak ada UI Filament)

```sh
php artisan legacy:audit jepara storage/app/private/legacy-audit/jepara
```

- Branch lain ditolak (pilot Jepara only).
- Command **read-only**: tidak insert/update/delete tabel domain, tidak memanggil Actions.
- Output: `storage/app/private/legacy-audit/jepara/`
  - `summary.json` (semua hasil machine-readable)
  - `consumers.csv`, `units.csv`, `sales_cases.csv`, `document_mapping.csv`
  - `exceptions.csv`, `duplicate_analysis.csv`, `chronology_issues.csv`, `unresolved_records.csv`, `schema_inventory.csv`

Output kustom via `--output=/absolute/path` bila perlu.

## Membaca hasil

1. `summary.json → summary`: total kandidat consumers/units/sales cases, KPR vs CASH, pindah-kavling, duplikasi, kronologi, orphan.
2. `exceptions.csv`: kode machine-readable (lihat `AuditExceptionCode`); filter per sheet/row.
3. `sales_cases.csv`: kolom `confidence`, `previous_case_candidate`, `process_rows`, `dates` = dasar review Phase 8B.
4. `reconciliation` dalam `summary.json`: baseline legacy vs kandidat rekonstruksi; selisih normal, jangan dipaksa sama.

## Aturan

- Jangan commit source legacy maupun report berisi PII (sudah diabaikan oleh Git).
- Jangan mengubah aturan identity/duplicate tanpa memperbarui `docs/LEGACY_MIGRATION_JEPARA_MAPPING.md` dan test fixture.
## Controlled import

```sh
php artisan legacy:import <plan-id> --user-id=<operator-user-id>
```

Command wajib lulus preflight, membuat PostgreSQL custom-format backup di `storage/app/private/legacy-backups/`, meminta konfirmasi pada terminal interaktif, lalu menjalankan satu transaksi atomik. `--no-interaction` hanya untuk automation terkontrol; tidak melewati preflight atau backup. Plan yang sudah `COMPLETED` tidak dapat dijalankan lagi.

Restore manual dilakukan setelah aplikasi dihentikan dan database target kosong:

```sh
pg_restore --clean --if-exists --no-owner --no-privileges --dbname=<database> <recorded-backup-path>
```

Backup tidak dihapus otomatis. Verifikasi path dan target database sebelum restore.
