<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## JHSOFTERP

JHSOFTERP adalah aplikasi ERP untuk proses pembelian, persediaan, produksi, dan penjualan. Modul data master yang tersedia:

- Barang, kategori barang, dan satuan
- Pelanggan dan supplier
- Gudang dan lokasi gudang
- Purchase Order dan penerimaan barang bertahap
- Saldo stok per gudang/lokasi, adjustment in, dan adjustment out
- Work Order make to order dari Sales Order dan make to stock untuk persediaan
- Bill of Materials (BOM) beserta daftar komponennya
- Sales Order draft, konfirmasi pesanan, dan detail baris barang
- Master Harga Jual per barang dan Master Harga Beli per supplier-item dengan riwayat periode berlaku

Jalankan migrasi database dan siapkan asset frontend sebelum membuka aplikasi:

```bash
php artisan migrate
php artisan db:seed
npm install
npm run build
php artisan serve
```

Halaman awal tersedia di `/` dan menjelaskan langkah persiapan sebelum registrasi. Setelah masuk, dashboard ERP tersedia di `/dashboard`. BOM dikelola melalui menu **Bill of Materials**, harga melalui **Master Harga**, pesanan pelanggan melalui **Sales Order**, pembelian melalui **Purchase Order**, dan pencatatan barang datang melalui menu **Receiving**. Item memiliki satu master terpusat tanpa harga atau biaya standar; item yang sama dapat didaftarkan ke banyak supplier dengan harga beli dan periode masing-masing. Master Harga menyimpan harga jual per item serta harga beli per kombinasi supplier-item. PO mengambil harga supplier yang dipilih, menampilkan harga supplier lain sebagai pembanding, dan menyimpan harga terpilih sebagai snapshot. Kuantitas pada antarmuka dan input transaksi menggunakan maksimal dua angka desimal. Pilih PO terbuka di menu Receiving untuk mencatat penerimaan parsial maupun penuh; nomor PO menjadi referensi receiving, yang dicatat ke transaksi persediaan dan memperbarui saldo stok. Work Order mendukung make to order dari Sales Order terkonfirmasi maupun make to stock untuk barang jadi dengan BOM aktif. Penyesuaian stok manual menggunakan **Adjustment In Stock** atau **Adjustment Out Stock**; adjustment keluar dibatasi oleh stok tersedia di gudang/lokasi yang dipilih. Nomor SO, PO, penerimaan, dan adjustment stok berurutan per bulan berdasarkan tanggal dokumen. Periode harga tidak boleh tumpang tindih untuk kombinasi item, jenis harga, dan supplier yang sama. Sales Order yang dikonfirmasi belum mengubah stok; pengurangan stok dilakukan saat delivery dan piutang dicatat saat invoice.

Autentikasi menggunakan scaffold Laravel Breeze (Blade): pengguna dapat mendaftar, masuk/keluar, meminta tautan reset kata sandi, dan mengubah kata sandi. Semua halaman dan aksi ERP memerlukan login. Buat akun awal melalui `/register`, lalu jadikan administrator pertama dari terminal dengan `php artisan user:make-admin email@perusahaan.id`; seeder tidak membuat akun dengan kata sandi bawaan. Registrasi terbuka dan pengguna baru mendapat peran **Penjualan**. Atur mailer aplikasi sebelum memakai reset kata sandi di luar lingkungan lokal.

Peran yang tersedia: **Administrator** (akses penuh dan pengelolaan peran), **Penjualan** (data master, Sales Order, dan Delivery), **Persediaan & Pembelian** (data master, BOM, PO, Receiving, dan stok), **Produksi** (BOM dan Work Order), serta **Keuangan** (melihat Delivery, menerbitkan Invoice, dan mencatat pembayaran). Administrator mengatur peran melalui menu **Pengguna & Peran**; administrator tidak dapat menurunkan perannya sendiri.

Alur produksi mengeluarkan material berdasarkan BOM, mencatat hasil produksi, pemeriksaan QC, lalu menerima barang jadi ke stok. Delivery mengurangi stok sesuai gudang/lokasi; invoice dibuat dari delivery dengan harga snapshot Sales Order, kemudian pembayaran dapat dicatat bertahap. Invoice saat ini tidak menghitung pajak dan satu delivery hanya dapat ditagihkan satu kali.

Menu master dan transaksi dapat menampilkan panduan PIC berupa divisi, jabatan penginput, dan pemeriksa. Sesuaikan nama peran atau nonaktifkan panduannya di menu **Pengaturan PIC**. Panduan ini hanya untuk membantu memahami alur; belum mencatat user pada transaksi, membatasi hak akses berdasarkan peran, atau menerapkan approval.

Master harga diakses melalui dua menu terpisah: **Master Harga Jual** hanya mengelola harga per item, sedangkan **Master Harga Beli** mengelola harga per item dan supplier. Pilih menu yang sesuai sebelum menambah atau mengubah harga; jenis harga tidak dipilih dari form.

Saldo stok disimpan dalam satuan dasar barang. Pada form barang, tambahkan konversi kemasan dengan jumlah satuan dasar per kemasan (misalnya Dus = 100 pcs dan Pack = 10 pcs); tampilan stok akan menguraikan jumlah sebagai `1 dus · 9 pack · 3 pcs`, sementara nilai database dan transaksi tetap `193 pcs`. Menu **Stock Take** menampilkan saldo sistem, menerima hitungan fisik, dan menghitung selisih; klik **OK** untuk menambahkan hitungan ke daftar lalu simpan dokumen untuk merekonsiliasi saldo. Jika saldo berubah sejak hitungan dimuat, simpanan ditolak agar pengguna menghitung ulang; rekonsiliasi tidak dapat menurunkan saldo di bawah stok yang dicadangkan.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
