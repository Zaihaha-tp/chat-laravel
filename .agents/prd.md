Buatkan aplikasi Temporary Chat Room sederhana menggunakan Laravel 13 + PHP 8.4 + MySQL + TailwindCSS + Vanilla JavaScript.

Konsep aplikasi:

- User bisa membuat room chat sementara.
- Setelah room dibuat, sistem menghasilkan kode room unik.
- User lain bisa join menggunakan link room.
- Tidak perlu login/register.
- Chat bersifat realtime sederhana menggunakan AJAX polling tiap 2 detik.
- Room otomatis expired setelah 24 jam.
- Semua chat otomatis terhapus saat room expired.
- Ada fitur “user sedang mengetik”.

Fitur MVP:

1. Homepage
    - Tombol "Create Room"
    - Input "Nama Anda"
    - Desain minimalis modern dark mode

2. Create Room
    - Generate room code random 8 karakter (contoh: X7K9P2MQ), kombinasi huruf kapital + angka, collision resistant.
    - Simpan ke database
    - Set expired_at = now + 24 jam
    - Redirect ke halaman chat room

3. Join Room
    - URL format: /room/{code}
    - User diminta memasukkan nama jika belum ada session
    - Simpan nama user menggunakan session sederhana
    - Cegah nama duplikat di room yang sama (tambahkan suffix seperti "Rafif (2)" jika nama sudah dipakai)

4. Chat Room
    - Daftar pesan realtime
    - Input pesan + tombol kirim
    - Jumlah user online
    - Status “sedang mengetik”
    - Countdown room expired (realtime, berubah warna jika <1 jam tersisa)
    - Auto scroll ke bawah saat pesan baru masuk (smart scroll)
    - Pesan sendiri di kanan (violet), pesan orang lain di kiri
    - Tampilkan waktu setiap pesan (HH:mm)
    - Tombol "Copy Link" dengan toast notification
    - Tombol "Leave Room" dengan konfirmasi
    - Empty state yang bagus saat belum ada pesan

5. Realtime
    - AJAX polling setiap 2 detik
    - Pesan baru muncul tanpa reload halaman
    - Polling typing indicator menggunakan GET endpoint read-only (jangan pakai POST yang mengupdate typing_at)

6. Typing Indicator
    - True debounce 300ms: POST typing hanya dikirim setelah user berhenti mengetik 300ms (bukan setiap keystroke)
    - POST /room/{code}/typing — update typing_at = now() (hanya dipanggil saat user benar-benar mengetik)
    - GET /room/{code}/typing/status — query siapa yang sedang mengetik (dipanggil polling 2 detik, read-only, tidak memperbarui typing_at)
    - Backend: typing status = typing_at >= now() - 3 detik
    - Tampilkan "Rafif sedang mengetik..." (jangan tampilkan milik sendiri)
    - Animasi titik-titik
    - Typing indicator otomatis hilang max 5 detik setelah user berhenti mengetik

7. Temporary Room Behavior
    - Room expired setelah 24 jam
    - Semua data (room, users, messages) otomatis dihapus
    - Gunakan foreign key dengan cascade delete
    - Buat Laravel Scheduler/Command untuk cleanup

8. System Messages
    - Tampilkan "Rafif joined the room" saat user masuk
    - Tampilkan "Rafif left the room" saat user keluar (Leave Room)

9. Security & Input Handling
    - Sanitize semua inputan dari user (nama dan pesan) untuk mencegah XSS dan script injection.
    - Gunakan Laravel Purifier atau HTMLPurifier untuk membersihkan pesan.
    - Strip tag dan escape output dengan benar di Blade dan JSON response.
    - Batasi panjang nama maksimal 30 karakter dan pesan maksimal 500 karakter.

10. Additional Features
    - Fitur Share Room via WhatsApp dan Telegram (tombol share)
    - Notifikasi suara saat ada pesan baru (dengan toggle on/off)
    - Toast notification untuk success, error, dan info
    - Rate limiting sederhana (maks 1 pesan per detik per user)

Database Structure:

- rooms (id, code, expired_at, timestamps)
- users (id, room_id, name, last_seen, typing_at, timestamps)
- messages (id, room_id, user_id, message, timestamps)

Backend Logic:

- Update last_seen saat user aktif, kirim pesan, atau typing
- Online user = last_seen < 30 detik yang lalu
- Cek room expired di middleware
- CSRF protection untuk semua AJAX

Frontend:

- Blade + TailwindCSS (modern dark mode, responsive mobile friendly)
- Chat bubble modern
- Typing indicator dengan animasi
- Countdown realtime
- Sound notification toggle

JavaScript:

- AJAX polling + axios
- Debounce untuk typing
- Smart auto scroll
- Toast notification
- Sound notification

Code Style:

- Clean, readable, well commented
- Gunakan Eloquent relationship
- Validation yang baik
- Pisahkan logic dengan rapi
- Sertakan migration, model, route, controller, command scheduler, dan semua blade file yang diperlukan

Tujuan:
Aplikasi harus bisa langsung dijalankan dan didemokan sebagai realtime temporary anonymous chat room yang modern, lengkap dengan typing indicator, online users, system messages, sound notification, input sanitization, dan auto delete setelah expired.

Berikan juga instruksi cara menjalankan aplikasi setelah kode selesai (migrate, npm install, scheduler, dll).
