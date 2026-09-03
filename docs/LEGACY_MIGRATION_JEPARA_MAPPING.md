# Legacy Migration — Jepara Mapping Specification (Phase 8A)

Specifikasi ini adalah sumber kebenaran mapping data legacy Jepara ke Database Oasis PostgreSQL. Dibuat sebelum import apa pun (Phase 8B). Semua keputusan di sini terikat pada prinsip: **PostgreSQL adalah source of truth baru; spreadsheet adalah input historis yang tidak konsisten, duplikatif, dan tidak otoritatif tentang identitas relasional.**

## 1. Sumber input

- Format: direktori CSV (satu file per sheet, nama file = nama sheet) atau satu file XLSX.
- Lokasi sumber real: `storage/app/private/legacy-audit/` (diabaikan Git).
- Reader: `App\LegacyMigration\LegacySourceReader` (streaming; formula terdeteksi, tidak dievaluasi).
- Sheet transaksi minimal: `data_konsumen`, `bi_checking`, `psjb`, `pemberkasan`, `proses_bank`, `ppjb_dev`, `akad`, `bast`.
- Sheet rekonsiliasi (bukan sumber transaksi): `ringkasan_data`, `table_rekapan`.
- Alias header didefinisikan di `App\LegacyMigration\LegacyNormalizer::HEADER_ALIASES`. Header asli dipertahankan di `original`.

## 2. Sheet → target table

| Legacy sheet | Target table | Catatan |
| --- | --- | --- |
| data_konsumen | consumers + sales_cases | Satu baris = satu kandidat Sales Case (consumer + unit). |
| bi_checking | bi_checks | Append-only; banyak check per case diizinkan. |
| psjb | psjbs | Urutan tanggal/context menentukan lifecycle; nomor bukan identitas. |
| pemberkasan | document_submissions | KPR → type BANK; CASH → type CASH_INTERNAL (bank_id NULL). |
| proses_bank | bank_processes | Setiap attempt dipetakan terpisah; otoritatif = APPROVED + SP3K valid + kronologi + pemakaian downstream. |
| ppjb_dev | developer_ppjbs | KPR → bank_process_id dari approval otoritatif; CASH → NULL. |
| akad | akad_records | Satu per Sales Case (V1). |
| bast | bast_records | Satu per Sales Case/Akad. |

## 3. Kolom → field (ringkas)

| Legacy column (alias) | Target field | Aturan |
| --- | --- | --- |
| nik / no_ktp | consumers.nik | String 16 digit; trim + buang pemisah; **jangan** numerik; leading zero dipertahankan; invalid → exception. |
| nama | consumers.name | Original dipertahankan; `name_normalized` (lowercase, collapse spasi) hanya untuk perbandingan. |
| telepon / no_hp | consumers.phone | Original dipertahankan; comparison dinormalisasi digit + prefix 62→0; **tidak** identitas tunggal. |
| project / blok / kavling | units (project_id, unit_code) | Normalisasi: uppercase, strip, `BLOCK-UNIT`; key = `PROJECT|UNIT-CODE`. |
| pembiayaan | sales_cases.financing_type | KPR_SUBSIDI / CASH. Literal `CASH` di kolom SP3K = placeholder, bukan financing by itself (lihat §8). |
| status | sales_cases.case_status | Lihat §6. |
| tanggal_* | kolom tanggal terkait | Parse deterministik (`Y-m-d`, `d/m/Y`, `d-m-Y`, `d.m.Y`, `j/n/Y`, `j-n-Y`). Invalid → `INVALID_DATE`; **jangan** substitusi created_at. |
| nomor_psjb/sp3k/ppjb/akad/bast | kolom nomor | Atribut saja; **tidak pernah** jadi foreign key. |

## 4. Identity rules

### Consumer
1. NIK valid 16 digit → key `nik:<nik>`, confidence **EXACT**.
2. NIK invalid/kosong + legacy link → key `legacy:<id>`, confidence **HIGH**.
3. Nama sama muncul >1 tanpa NIK valid → confidence **AMBIGUOUS** (key tetap terpisah; **tidak pernah** digabung hanya karena nama sama), exception `CONSUMER_IDENTITY_AMBIGUOUS` / `CONSUMER_NIK_MISSING` / `CONSUMER_NIK_INVALID`.
4. Tanpa semuanya → key per-baris, confidence **MEDIUM**.

### Unit
- Key `PROJECT|UNIT-CODE` ternormalisasi. Identitas **bukan** konsumen pemilik.
- Confidence HIGH bila project+unit ada; kosong → `UNIT_NOT_FOUND`, confidence UNRESOLVED.
- Deteksi: unit terjual berulang, reuse setelah MUNDUR, pindah kavling, beberapa kandidat aktif bersamaan (`MULTIPLE_ACTIVE_UNIT_CANDIDATES`), variasi ejaan via normalisasi.

### Sales Case
- Definisi: **satu perjalanan konsumen untuk satu unit fisik** — bukan per dokumen, bukan global per konsumen.
- Kandidat dibangun dari `data_konsumen` (consumer + unit + row), lalu baris proses lain ditempel via linkage: legacy_id → NIK → composite (nama+telepon) **dan** unit sama.
- Confidence = confidence consumer; UNRESOLVED bila unit kosong.

## 5. Normalisasi ringkas

- Teks: trim; comparison = lowercase + collapse spasi + ASCII.
- NIK: buang spasi/titik/strip; validasi `^\d{16}$`.
- Tanggal: format daftar di atas; `DateTimeImmutable`; invalid → exception; kosong → null.
- Dokumen: trim saja; **tidak** dedup berdasarkan nomor sama.

## 6. Lifecycle status mapping

| Nilai legacy `status` | Target |
| --- | --- |
| mengandung `PINDAH` | PINDAH_KAVLING |
| mengandung `MUNDUR` | MUNDUR |
| `REJECT` / `REJECTED` | REJECT |
| `COMPLETE` / `COMPLETED` / `SELESAI` | COMPLETED |
| lainnya / kosong | ACTIVE |

Nilai status lain yang tidak dikenal pada BI/Proses Bank → `UNKNOWN_STATUS_VALUE`.

## 7. Pindah kavling

- Trigger: consumer sama + unit berbeda pada kandidat berurutan → exception `POTENTIAL_PINDAH_KAVLING`.
- Mapping target (Phase 8B): case lama `PINDAH_KAVLING`; case baru `previous_case_id` = lama + `transfer_reason` dari evidence. Bila evidence kurang → flag, jangan agresif.

## 8. CASH

- Financing `CASH` bila kolom financing `CASH` **atau** SP3K berisi literal `CASH` (maka exception `CASH_FAKE_SP3K` — placeholder, bukan SP3K).
- Alur target: PSJB → CASH_INTERNAL Pemberkasan (bank_id NULL) → PPJB (bank_process_id NULL) → Akad → BAST. Tidak ada BI wajib, tidak ada Bank Process/SP3K fabrikasi.

## 9. Multi-bank attempt

- Pemberkasan dengan >1 bank pada satu case → duplicate classification `MULTIPLE_BANK_ATTEMPT`.
- REJECTED **tidak** membuat case REJECT; REJECT hanya bila status keseluruhan menyatakan itu.

## 10. Authoritative SP3K rule

Otoritatif = APPROVED + SP3K number/date valid + kronologi + dipakai downstream (PPJB/Akad). Bukan baris terakhir yang diinsert. Bila >1 kandidat → `MULTIPLE_AUTHORITATIVE_APPROVAL_CANDIDATES`.

## 11. Duplicate treatment

Klasifikasi: `EXACT_ROW_DUPLICATE`, `SAME_DOCUMENT_NUMBER_DIFFERENT_CASE`, `REISSUE` (PSJB/PPJB multi-baris pada satu case), `MULTIPLE_BANK_ATTEMPT`, `CONFLICTING_RECORD`, `POSSIBLE_DATA_ENTRY_DUPLICATE`, `UNRESOLVED`. Nomor sama lintas case **tidak pernah** menggabungkan case.

## 12. Exception codes

Daftar lengkap: `App\LegacyMigration\AuditExceptionCode` (CONSUMER_NIK_MISSING … MISSING_REQUIRED_COLUMN). Semua exception machine-readable dengan sheet + row + pesan.

## 13. Rekonsiliasi

`ringkasan_data`/`table_rekapan` hanya baseline pembanding (akad/BAST/SP3K/active). Selisih dilaporkan, **tidak** dipaksa sama.

## 14. Unresolved policy

Baris yang tidak menempel ke satu kandidat Sales Case → masuk `unresolved_records.csv` + exception ORPHAN_*. Tidak diimport di Phase 8B tanpa keputusan manual.
