# CTC General Ledger

Aplikasi web internal untuk membantu pencatatan transaksi keuangan, pengelolaan jurnal dan rekening, rekonsiliasi bank, penggajian, perpajakan, serta penyajian laporan keuangan. Aplikasi dibangun dengan Laravel dan menyediakan antarmuka web berbasis Blade. Repository juga berisi definisi route API v1, tetapi route tersebut belum diaktifkan pada bootstrap aplikasi.

> **Status proyek:** dokumentasi ini merangkum route, controller, model, migration, seeder, dan tampilan yang ada di repository. Keberadaan struktur data saja tidak berarti sebuah fitur sudah tersedia melalui antarmuka atau API. Bagian **Batasan dan hal yang perlu diverifikasi** mencatat area yang belum terhubung atau membutuhkan pengujian lebih lanjut.

> **Catatan API:** `routes/api.php` mendefinisikan endpoint dengan prefix `v1`, tetapi file tersebut tidak dimasukkan ke `withRouting()` di `bootstrap/app.php`. Saat ini `php artisan route:list --path=api` tidak menampilkan route API; endpoint di bawah ini merupakan definisi source, bukan endpoint aktif.

## Daftar Isi

- [Teknologi dan persyaratan](#teknologi-dan-persyaratan)
- [Instalasi lokal](#instalasi-lokal)
- [Konfigurasi environment](#konfigurasi-environment)
- [Database, migration, dan seeder](#database-migration-dan-seeder)
- [Modul dan fitur](#modul-dan-fitur)
- [Role dan hak akses](#role-dan-hak-akses)
- [Ringkasan route](#ringkasan-route)
- [Pengujian](#pengujian)
- [Struktur direktori](#struktur-direktori)
- [Batasan dan hal yang perlu diverifikasi](#batasan-dan-hal-yang-perlu-diverifikasi)
- [Troubleshooting](#troubleshooting)

## Teknologi dan persyaratan

| Area | Teknologi / persyaratan |
|---|---|
| Backend | PHP `^8.3`, Laravel `^13.17` |
| Database | Konfigurasi mendukung MySQL, MariaDB, PostgreSQL, SQLite, dan SQL Server. `.env.example` menggunakan MySQL. |
| Autentikasi API | Laravel Sanctum |
| Role dan permission | Spatie Laravel Permission |
| PDF | `barryvdh/laravel-dompdf` |
| Excel | `maatwebsite/excel` |
| Frontend | Blade, Tailwind CSS v4, Alpine.js v3 melalui CDN, Vite 8 |
| JavaScript tambahan | Laravel Echo dan Pusher JS |
| Runtime frontend | Node.js `^20.19.0` atau `>=22.12.0` sesuai batas Vite di lockfile; npm |
| Tool PHP | Composer |

PHP harus memiliki ekstensi yang dibutuhkan Laravel dan driver PDO untuk database yang dipilih, misalnya `pdo_mysql` untuk MySQL.

## Instalasi lokal

Pastikan layanan database sudah berjalan dan database kosong untuk instalasi baru telah dibuat terlebih dahulu. Dari direktori repository:

```bash
composer install
```

Salin `.env.example` ke `.env`. Di Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Di Linux, macOS, atau Git Bash:

```bash
cp .env.example .env
```

Atur koneksi database di `.env`, lalu buat application key dan jalankan migration:

```bash
php artisan key:generate
php artisan migrate
```

Instal dependency frontend dan buat asset:

```bash
npm ci
npm run build
```

Untuk menjalankan aplikasi dalam mode lokal, gunakan terminal terpisah:

```bash
php artisan serve
```

```bash
npm run dev
```

`npm run dev` menjalankan Vite untuk pengembangan; `npm run build` membuat asset produksi. Jangan jalankan keduanya sekaligus kecuali memang memerlukan server Vite.

## Konfigurasi environment

`.env.example` merupakan sumber nilai awal. Konfigurasi utamanya meliputi:

| Variabel | Keterangan |
|---|---|
| `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_URL` | Identitas, mode, debugging, dan URL aplikasi. Matikan debug pada deployment produksi. |
| `APP_KEY` | Kunci enkripsi aplikasi; buat dengan `php artisan key:generate`. |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Driver dan kredensial database. Nilai contoh di `.env.example` ditujukan untuk konfigurasi lokal, bukan kredensial produksi. |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | Contoh konfigurasi menggunakan penyimpanan database. Migration terkait membuat tabel pendukungnya. |
| `MAIL_MAILER` | Contoh menggunakan `log`; email seperti reset password dicatat ke log, bukan dikirim melalui SMTP. |
| `BROADCAST_CONNECTION` | Contoh menggunakan `log`. |
| `VITE_*` | Variabel frontend, termasuk nama aplikasi. Konfigurasi Echo/Reverb tambahan harus disediakan bila layanan tersebut akan digunakan. |

Untuk konfigurasi MySQL lokal, gunakan nama koneksi `mysql` (huruf kecil, sesuai key di `config/database.php`) dan sesuaikan dengan server Anda. Contoh berikut memakai nama database dari `.env.example`; username/password harus diisi sesuai lingkungan masing-masing:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_glctc
DB_USERNAME=<akun-database-lokal>
DB_PASSWORD=<password-database-lokal>
```

Jangan commit `.env` atau memasukkan kredensial produksi ke repository. Gunakan akun database khusus aplikasi dengan hak akses minimum yang diperlukan.

Untuk mengaktifkan pengiriman email sungguhan, atur mailer dan variabel SMTP/provider sesuai `config/mail.php`. Nilai default `log` tidak mengirim email ke penerima.

## Database, migration, dan seeder

### Database

Konfigurasi `.env.example` mengarah ke MySQL lokal dengan nama database contoh `db_glctc`. Buat database tersebut atau gunakan nama lain, lalu samakan `DB_DATABASE`, host, port, username, dan password dengan konfigurasi server Anda. Nilai contoh tidak menjamin database atau akun tersebut sudah tersedia.

Driver yang dikonfigurasi di `config/database.php` mencakup MySQL, MariaDB, PostgreSQL, SQLite, dan SQL Server. Konfigurasi serta migration tetap perlu disesuaikan dan diuji untuk driver selain MySQL yang dicontohkan.

### Migration

Jalankan migration pada database yang telah dipilih:

```bash
php artisan migrate
```

Migration domain membuat tabel untuk:

- Pengguna dan akun (`users`), akun perkiraan (`chart_of_accounts`), transaksi kas (`cash_transactions`), jurnal dan detail jurnal (`journals`, `journal_details`).
- Rekonsiliasi bank (`bank_reconciliations`), pajak (`taxes`), penggajian dan komponennya (`payrolls`, `payroll_items`).
- Pengeluaran berulang (`recurring_expenses`).
- Role, permission, dan tabel pivot dari Spatie Laravel Permission.
- Infrastruktur Laravel untuk session, cache, queue, password reset, serta token API.

### Seeder

Seeder utama dijalankan dengan:

```bash
php artisan db:seed
```

`DatabaseSeeder` menyiapkan role dan akun contoh melalui `RolePermissionSeeder`, lalu memanggil `ChartOfAccountSeeder`. Seeder tersebut juga membuat data role dan akun contoh; kredensial awal ditulis di source seeder. Periksa dan ganti kredensial tersebut sebelum aplikasi digunakan bersama atau dipasang di lingkungan publik. README ini tidak menyalin kredensial login.

> **Peringatan data:** `ChartOfAccountSeeder` menghapus data pada tabel `chart_of_accounts` sebelum membuat data awal. Karena itu, `php artisan db:seed` bukan operasi yang aman untuk dijalankan berulang kali pada database berisi data. Jalankan hanya pada database lokal/baru yang dapat dibuang, atau tinjau seeder dan siapkan backup sebelum tindakan lain.

`TaxSeeder` dan `UserSeeder` tersedia di repository, tetapi tidak dipanggil oleh `DatabaseSeeder`. `UserSeeder` menggunakan pembuatan record langsung, bukan pola yang idempotent. Jalankan seeder tambahan hanya setelah meninjau implementasi dan dampaknya terhadap data.

Hindari `php artisan migrate:fresh` pada database yang berisi data penting karena perintah tersebut menghapus tabel lalu membuat ulang schema.

## Modul dan fitur

Fitur berikut merujuk pada route web dan controller yang tersedia. Endpoint yang hanya didefinisikan di `routes/api.php` disebut sebagai definisi API dan belum aktif sampai file route tersebut didaftarkan pada bootstrap.

1. **Autentikasi dan akun**
   - Login, pendaftaran, logout, reset password, dan halaman verifikasi email tersedia pada route web.
   - `routes/api.php` mendefinisikan endpoint API v1 untuk pendaftaran, login, reset password, logout, dan profil pengguna; endpoint tersebut belum aktif.
2. **Dashboard**
   - Route dashboard web untuk pengguna terautentikasi dengan role yang diizinkan.
   - Endpoint dashboard tercantum pada definisi API v1, tetapi belum aktif.
3. **Manajemen pengguna**
   - Route resource web tersedia untuk admin; controller menyediakan daftar, pembuatan, pembaruan, dan penghapusan pengguna.
   - Resource web juga mendaftarkan action `create`, `edit`, dan `show` yang belum didefinisikan pada controller.
   - `apiResource` pengguna tercantum pada definisi API v1, tetapi route belum aktif. Controller pengguna mengembalikan view/redirect web, bukan response JSON, dan action `show` belum tersedia. API manajemen pengguna belum siap diperlakukan sebagai API JSON.
4. **Chart of Accounts (COA)**
   - Route resource web tersedia untuk admin; controller menyediakan daftar, pembuatan, pembaruan, dan penghapusan akun, termasuk response JSON bila diminta.
   - Definisi `apiResource` COA ada di `routes/api.php`, tetapi belum aktif. Action `show` belum tersedia; resource web juga menghasilkan action `create`, `edit`, dan `show` yang tidak didefinisikan. Antarmuka yang tersedia menggunakan halaman daftar/form.
   - Model mendukung relasi akun induk dan anak.
5. **Transaksi kas**
   - Daftar dan pencatatan transaksi kas tersedia pada web.
   - Definisi API v1 mencakup daftar dan pencatatan, tetapi belum aktif.
   - Route penghapusan web untuk admin dideklarasikan, tetapi action `destroy` belum tersedia pada controller.
6. **Jurnal dan jurnal pembalikan**
   - Daftar dan pencatatan jurnal, halaman daftar jurnal pembalikan, serta proses reversal sesuai permission.
   - Definisi API v1 mencakup daftar, pencatatan, dan endpoint reversal jurnal, tetapi belum aktif.
7. **Rekonsiliasi bank**
   - Daftar dan pencatatan rekonsiliasi tersedia pada web.
   - Definisi API v1 mencakup daftar dan pencatatan, tetapi belum aktif.
   - Route web untuk pembaruan dan penghapusan terdaftar, tetapi action `update` dan `destroy` belum tersedia pada controller.
8. **Jurnal penyesuaian**
   - Daftar, pencatatan, dan reversal melalui route web.
   - Definisi API v1 mencakup daftar dan pencatatan, tetapi belum aktif.
9. **Pajak**
   - Pengolahan transaksi pajak bagi pengguna dengan permission terkait.
   - Master skema pajak dapat dibuat, diperbarui, dan dihapus oleh admin.
   - Laporan pajak menyediakan tampilan web serta ekspor PDF dan Excel.
10. **Penggajian**
    - Daftar dan pencatatan payroll tersedia pada web.
    - Route penghapusan web untuk admin terdaftar, tetapi action `destroy` belum tersedia pada controller.
    - Definisi API v1 mencakup daftar dan pencatatan, tetapi belum aktif.
    - Laporan payroll menyediakan tampilan web serta ekspor PDF dan Excel.
11. **Laporan keuangan**
    - General Ledger, Profit & Loss, dan Balance Sheet tersedia melalui web beserta ekspor PDF dan Excel.
    - Definisi API v1 mencakup endpoint pembacaan untuk ketiga laporan tersebut, tetapi belum aktif.

Ekspor laporan PDF menggunakan DomPDF dan ekspor spreadsheet menggunakan Laravel Excel sesuai dependency proyek.

> **Perbedaan route dan implementasi:** beberapa route resource dapat menghasilkan endpoint yang action-nya belum dibuat. Periksa controller terkait sebelum mengandalkan action `show` pada API, pembaruan/penghapusan rekonsiliasi bank, atau penghapusan transaksi kas/payroll.

## Role dan hak akses

Role serta permission dikelola menggunakan Spatie Laravel Permission. Route web menerapkan middleware role atau permission. Definisi route API juga mencantumkan batasan middleware, tetapi route API belum didaftarkan.

| Role dari seeder | Hak akses yang ditetapkan |
|---|---|
| `admin` | Semua permission yang dibuat oleh `RolePermissionSeeder`; route admin tertentu juga membatasi operasi secara eksplisit ke role ini. |
| `akuntan` | COA, transaksi kas, jurnal dan reversal, rekonsiliasi bank, penyesuaian, proses pajak, payroll, dan seluruh permission laporan. |
| `staff` | Transaksi kas dan payroll sesuai permission yang ditetapkan seeder. |

Nama permission yang didefinisikan mencakup `manage-users`, `manage-chart-of-accounts`, `manage-cash-transactions`, `manage-journals`, `reverse-journals`, `manage-bank-reconciliations`, `manage-adjustments`, `process-taxes`, `manage-payrolls`, serta `view-reports-*` untuk masing-masing laporan.

Pendaftaran role/permission pada route dan permission yang diberikan oleh seeder adalah sumber perilaku akses aplikasi. Tinjau keduanya sebelum mengubah matriks hak akses.

## Ringkasan route

Prefix route web yang tersedia:

| Prefix | Kegunaan |
|---|---|
| `/login`, `/register`, `/forgot-password`, `/reset-password/...` | Autentikasi web dan pemulihan password. |
| `/dashboard` | Dashboard web terautentikasi. |
| `/email/verify...` | Pemberitahuan, verifikasi, dan pengiriman ulang email verifikasi. |
| `/admin/users`, `/admin/chart-of-accounts` | Manajemen pengguna dan COA. |
| `/admin/cash-transactions`, `/admin/bank-reconciliations` | Transaksi kas dan rekonsiliasi bank. |
| `/admin/journals`, `/admin/journals/reversals`, `/admin/adjustments` | Jurnal, reversal, dan penyesuaian. |
| `/admin/taxes`, `/admin/payrolls` | Pemrosesan pajak, master pajak, dan payroll. |
| `/admin/reports/{general-ledger,profit-loss,balance-sheet,tax,payroll}` | Tampilan dan ekspor laporan. |

`routes/api.php` mendefinisikan prefix `v1` yang secara konvensi Laravel akan berada di bawah `/api/v1` jika file tersebut didaftarkan. Dalam definisi itu, route autentikasi tertentu bersifat publik; endpoint profil, dashboard, dan operasi aplikasi menggunakan `auth:sanctum` serta pembatasan role/permission. Namun, bootstrap saat ini tidak memuat file API tersebut sehingga endpoint belum terdaftar.

Di dalam definisi API, `apiResource` pengguna dan COA mencantumkan route `show`, sedangkan controller terkait belum mendefinisikan action tersebut. Controller pengguna juga belum menyediakan response JSON. Route penghapusan transaksi kas/payroll dan pembaruan/penghapusan rekonsiliasi bank dideklarasikan pada web, tetapi action controller terkait belum tersedia. Setelah API didaftarkan, periksa kembali `php artisan route:list` dan implementasi setiap action.

Untuk melihat daftar route aktual beserta middleware dan nama route:

```bash
php artisan route:list
```

## Pengujian

Jalankan suite test Laravel:

```bash
php artisan test
```

Test yang tersedia saat ini masih berupa contoh dasar di `tests/Feature/ExampleTest.php` dan `tests/Unit/ExampleTest.php`; cakupan perilaku modul belum didokumentasikan sebagai suite lengkap.

Feature test contoh saat ini mengharapkan `GET /` berstatus `200`, sedangkan route `/` yang ada mengarahkan pengguna ke halaman login. Ekspektasi tersebut perlu diselaraskan sebelum suite digunakan sebagai gerbang penerimaan.

## Struktur direktori

```text
app/
├── Http/
│   ├── Controllers/       # Controller autentikasi, admin, dan laporan
│   ├── Middleware/        # Middleware role dan status akun
│   └── Requests/          # Validasi request form/API
├── Models/                # Model Eloquent dan relasi domain
database/
├── migrations/            # Schema aplikasi dan tabel pendukung
├── seeders/               # Role, permission, COA, pajak, dan user contoh
resources/
├── css/                   # CSS aplikasi, tema, layout, komponen, print
├── js/                    # Entry point JavaScript dan modul frontend
└── views/                 # Blade auth, layout, dashboard, modul admin, laporan
routes/
├── api.php                # Definisi API v1; belum dimuat oleh bootstrap saat ini
├── web.php                # Route antarmuka web
└── console.php            # Route/command console
tests/
├── Feature/
└── Unit/
```

## Batasan dan hal yang perlu diverifikasi

- **Pengeluaran berulang:** migration dan model `RecurringExpense` tersedia, tetapi belum ditemukan controller, route, atau tampilan untuk mengelola fitur tersebut. Fitur UI/API belum tersedia.
- **API v1:** definisi endpoint tersedia di `routes/api.php`, tetapi tidak didaftarkan pada `bootstrap/app.php`. Verifikasi dan aktifkan konfigurasi routing sebelum menganggap endpoint API tersedia.
- **Pemeriksaan akun aktif:** class `CheckActiveUser` tersedia, tetapi belum ditemukan pendaftaran/pemakaian middleware pada bootstrap atau route. Efek `is_active` pada akses perlu diverifikasi.
- **Verifikasi email:** route dan controller verifikasi ada, tetapi implementasi `MustVerifyEmail` pada model `User` dikomentari. Alur verifikasi aktual perlu diverifikasi sebelum diandalkan.
- **Seeder pajak:** `TaxSeeder` tersedia, tetapi tidak dipanggil oleh seeder utama. Data pajak tidak otomatis dibuat oleh `php artisan db:seed`.
- **Broadcasting/realtime:** dependency Reverb dan Echo tersedia dan Echo diinisialisasi pada frontend. `.env.example` menggunakan broadcast `log`, dan source aplikasi yang ditinjau tidak menunjukkan listener/event broadcast domain. Penggunaan realtime perlu konfigurasi dan verifikasi terpisah.
- **Cakupan test:** test saat ini berupa contoh dasar. Verifikasi perhitungan, izin, ekspor, dan alur transaksi perlu test khusus.
- **Data seed:** akun contoh yang dibuat seeder menggunakan nilai awal yang tersimpan di source. Jangan gunakan nilai tersebut di lingkungan produksi.

## Troubleshooting

### Koneksi database gagal

- Pastikan server database berjalan, nama database telah dibuat, dan host/port/user/password pada `.env` benar.
- Pastikan `DB_CONNECTION` cocok dengan koneksi yang tersedia di `config/database.php`; untuk MySQL gunakan driver `mysql`.
- Pastikan ekstensi PDO untuk driver tersebut tersedia, misalnya `pdo_mysql`.
- Setelah perubahan environment yang tidak terbaca, jalankan:

  ```bash
  php artisan config:clear
  ```

### Migration gagal

- Pastikan database yang dipilih benar dan pengguna database memiliki hak membuat tabel serta foreign key.
- Periksa migration yang gagal dan urutan relasi tabel sebelum mencoba ulang.
- Jangan gunakan `migrate:fresh` atau mengulang seeder utama pada database berisi data penting.

### Asset Vite tidak ditemukan

- Jalankan `npm ci`, kemudian `npm run build`; atau gunakan `npm run dev` ketika mengembangkan.
- Pastikan `public/build/manifest.json` tersedia setelah build produksi.
- Jika manifest atau cache lama masih digunakan, jalankan `php artisan optimize:clear`, lalu build ulang.

### Application key belum tersedia

Jalankan `php artisan key:generate` setelah membuat `.env`. Jangan membagikan atau mengganti key pada aplikasi yang telah memiliki data terenkripsi tanpa memahami dampaknya.

### Email reset/verifikasi tidak diterima

Mailer contoh adalah `log`, sehingga email tidak dikirim ke inbox. Atur SMTP/provider pada `.env` dan `config/mail.php`; periksa juga log Laravel saat memakai mailer `log`.

### Akses dashboard atau modul ditolak

Pastikan pengguna telah masuk, role dan permission sudah diseed, serta route yang diminta memang memberikan akses kepada role tersebut. Periksa middleware route melalui `php artisan route:list`.
