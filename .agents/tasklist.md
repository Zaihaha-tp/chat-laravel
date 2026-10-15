# TempChat Tasklist
Progress: 100%

## 1. Project Setup & Configuration
- [x] ✅ Task 1.1 — Verifikasi MySQL `chat-laravel` + `composer setup` `[Sedang]` (Selesai)
  - MySQL OK, DB `chat-laravel` ada; migrasi bawaan Ran (batch 1); `npm install` + `npm run build` sukses (manifest + assets di `public/build`)
- [x] ✅ Task 1.2 — Install HTMLPurifier via composer `[Mudah]` (Selesai)
  - `mews/purifier 3.4.4` + `ezyang/htmlpurifier` terinstal; `composer test` tetap 2 passed

## 2. Database & Migration
- [x] ✅ Task 2.1 — Migrasi `rooms` (code unique, expired_at) `[Mudah]` (Selesai)
  - `2026_10_15_000001_create_rooms_table.php`: code varchar(8) unique, expired_at indexed
- [x] ✅ Task 2.2 — Migrasi `room_users` (FK cascade, last_seen, typing_at) `[Mudah]` (Selesai)
  - `000002_create_room_users_table.php`: room_id FK rooms cascade, name(30), last_seen/typing_at nullable, index (room_id,name)
- [x] ✅ Task 2.3 — Migrasi `messages` (FK cascade room+user) `[Mudah]` (Selesai)
  - `000003_create_messages_table.php`: room_id FK rooms cascade, user_id FK room_users cascade, message(500), index (room_id,created_at)
  - Terverifikasi: `SHOW CREATE TABLE` konfirmasi semua FK `ON DELETE CASCADE`; pint passed; `composer test` 2 passed

## 3. Models & Relationships
- [x] ✅ Task 3.1 — Model Room/RoomUser/Message + relasi + scope online/typing `[Sedang]` (Selesai)
  - `Room` (generateUniqueCode 8-char tanpa 0/O/1/I, scope active/expired), `RoomUser` (scope online 30s / typing 3s), `Message` (scope forRoom/afterId); migrasi `is_system` untuk system message

## 4. Backend Logic & API
- [x] ✅ Task 4.1 — Create room + middleware expired `[Sedang]` (Selesai)
  - `RoomController@store/show`, `EnsureRoomIsActive` (404 / 410 `expired` view), alias `room.active` di `bootstrap/app.php`
- [x] ✅ Task 4.2 — Join via session + dedup nama ` (2)` `[Sedang]` (Selesai)
  - Session key `tempchat.{code}`; `join`/`leave` + system message joined/left
- [x] ✅ Task 4.3 — API messages + typing (POST write / GET status read-only) `[Sedang]` (Selesai)
  - `MessageController@index/store` (poll `?after=`, online list, expires_at), `TypingController@store/status` (status read-only, exclude self)

## 5. Security
- [x] ✅ Task 5.1 — Validasi (30/500), sanitasi purifier, CSRF, rate limit 1/dtk `[Sedang]` (Selesai)
  - `mews/purifier` clean HTML.Allowed kosong; render via textContent; Cache throttle 1 dtk (429); CSRF meta + axios header

## 6. Frontend & Blade
- [x] ✅ Task 6.1 — Homepage + room view ikut design.md (bubble sendiri violet) `[Sedang]` (Selesai)
  - `layouts/app`, `home`, `join`, `room`, `expired`; token #161614/#1D1D1B/#5FB5D6, JetBrains Mono+Inter, flat; bubble sendiri violet kanan, orang lain kiri + HH:mm + empty state + countdown (<1h merah)

## 7. JavaScript & Realtime
- [x] ✅ Task 7.1 — Polling 2 dtk, debounce 300ms, smart scroll, countdown `<1h` `[Sedang]` (Selesai)
  - `resources/js/room.js` (lazy via `app.js`); polling messages+typing/status; typing dots animasi; copy link toast; share WA/TG; sound toggle WebAudio beep

## 8. Additional Features
- [x] ✅ Task 8.1 — System message, share WA/Telegram, toast, sound toggle `[Sedang]` (Selesai)
  - Terimplementasi di tahap 6–7 (join/leave system message tersimpan, toast success/error/info)

## 9. Cleanup Command & Scheduler
- [x] ✅ Task 9.1 — Command hapus expired + scheduler `[Mudah]` (Selesai)
  - `rooms:cleanup` + `Schedule::command()->hourly()`; terverifikasi di `schedule:list` dan feature test

## 10. Final Polish & Bug Fixes
- [x] ✅ Task 10.1 — Pint, `composer test`, `npm run build`, instruksi jalan `[Mudah]` (Selesai)
  - pint clean; `composer test` 8 passed (termasuk `RoomTest` 6 test baru); `npm run build` sukses (chunk room terpisah)

## 11. Join-by-Code, Public List & Private Rooms (tambahan)
- [x] ✅ Task 11.1 — Migrasi `is_private` + scope public di `Room` `[Mudah]` (Selesai)
  - `000005_add_is_private_to_rooms_table.php`; `scopePublic()`; badge private di halaman room
- [x] ✅ Task 11.2 — Form join via kode + daftar public rooms di homepage `[Sedang]` (Selesai)
  - `POST /rooms/join` (normalisasi uppercase, 404/expired → error); homepage: form kode + list 20 public rooms aktif (online count, expires); checkbox private di form create
- [x] ✅ Task 11.3 — Test fitur baru + fix `ExampleTest` `[Sedang]` (Selesai)
  - 4 test baru (join OK, join gagal, list hanya public+aktif, create private); `ExampleTest` pakai `RefreshDatabase` karena homepage kini query DB
  - Terverifikasi: `composer test` 12 passed; pint passed; `npm run build` sukses
- [x] ✅ Task 11.4 — Join-by-code selalu jadi user baru `[Sedang]` (Selesai)
  - Form join + input nama; `joinByCode` selalu buat `RoomUser` baru (dedup ` (2)`) + hapus identitas session lama + system message joined; test ganti identitas (creator → Guest) passed
  - Terverifikasi: `composer test` 13 passed; pint passed
- [x] ✅ Task 11.5 — Label nama di semua bubble chat `[Mudah]` (Selesai)
  - Bubble sendiri (kanan, violet): label `{nama} (you)` di atas; bubble orang lain (kiri): label nama cyan di atas; rebuild + 13 passed
- [x] ✅ Task 11.6 — Identitas per-session + tombol Switch user `[Sedang]` (Selesai)
  - Akar masalah: buka link di browser yang sama diam-diam memakai identitas lama (tab berbagi session). Leave + `return_to=room` → kembali ke form join; tombol "Switch user" di header room; test dua-browser (pesan Budi tercatat atas nama Budi, flag mine benar) + test switch passed
  - Terverifikasi: `composer test` 15 passed; pint passed
- [x] ✅ Task 11.7 — Warna nama berbeda per user `[Mudah]` (Selesai)
  - Palet 8 warna; `colorFor(nama)` hash deterministik → stabil tiap reload; dipakai di label nama bubble + nama di typing indicator; bubble sendiri tetap violet
  - Terverifikasi: `npm run build` sukses; `composer test` 15 passed
- [x] ✅ Task 11.8 — Perbaiki timing typing indicator `[Sedang]` (Selesai)
  - Akar masalah: debounce trailing-only tidak mengirim apa-apa selama mengetik terus → indikator baru muncul saat pesan tiba. Ganti ke throttle leading-edge: POST langsung di keystroke pertama, lalu max 1x/2 dtk selama mengetik (jendela backend 3 dtk); kirim pesan langsung me-null-kan `typing_at` agar tak ada indikator hantu. Menyimpang dari debounce-300ms di PRD demi UX yang benar.
  - Terverifikasi: test baru (terlihat saat typing, hilang saat send) passed; `composer test` 16 passed; pint passed; `npm run build` sukses
- [x] ✅ Task 11.9 — Typing poll 1 detik (terpisah dari pesan 2 detik) `[Mudah]` (Selesai)
  - Polling dipecah: pesan tetap 2 dtk (PRD), `typing/status` tiap 1 dtk + stagger 500ms agar tak berbarengan; endpoint read-only murah (1 query indexed, tanpa write) sehingga interval rapat aman. Menyimpang dari polling-2-dtk PRD khusus typing demi kemunculan instan.
  - Terverifikasi: `npm run build` sukses; `composer test` 16 passed
