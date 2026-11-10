<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Panduan memulai penggunaan JHSOFTERP untuk pembelian, persediaan, produksi, dan penjualan.">
    <title>Mulai menggunakan JHSOFTERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="onboarding-page">
    <header class="onboarding-nav">
        <a href="{{ route('home') }}" class="brand">
            <span class="brand-mark">J</span>
            <span>JHSOFT<span class="brand-light">ERP</span></span>
        </a>
        <nav aria-label="Akun pengguna">
            <a class="onboarding-login" href="{{ route('login') }}">Masuk</a>
        </nav>
    </header>

    <main class="onboarding-main">
        <section class="onboarding-hero">
            <div class="onboarding-hero-copy">
                <span class="onboarding-eyebrow">PANDUAN MEMULAI</span>
                <h1>Kelola operasional bisnis dalam satu alur.</h1>
                <p>JHSOFTERP membantu tim Anda menghubungkan pembelian, persediaan, produksi, pengiriman, hingga pembayaran.</p>
                <div class="onboarding-actions">
                    <a class="button primary" href="{{ route('login') }}">Masuk ke sistem <span aria-hidden="true">→</span></a>
                    <a class="onboarding-text-link" href="#langkah">Lihat langkah penggunaan</a>
                </div>
            </div>
            <div class="onboarding-hero-art" aria-hidden="true">
                <div class="onboarding-art-card">
                    <span class="onboarding-art-label">ALUR OPERASIONAL</span>
                    <div class="onboarding-flow"><span>Data</span><i>→</i><span>Stok</span><i>→</i><span>Produksi</span></div>
                    <div class="onboarding-flow onboarding-flow-last"><span>Pesanan</span><i>→</i><span>Pengiriman</span><i>→</i><span>Pembayaran</span></div>
                    <div class="onboarding-art-line"></div>
                </div>
            </div>
        </section>

        <section class="onboarding-guide" id="langkah">
            <div class="onboarding-section-heading">
                <span class="onboarding-eyebrow">MULAI DARI SINI</span>
                <h2>Empat langkah menyiapkan aplikasi</h2>
                <p>Ikuti urutan ini agar pengguna dan data operasional siap sebelum transaksi pertama.</p>
            </div>

            <div class="onboarding-steps">
                <article class="onboarding-step">
                    <span class="onboarding-step-number">01</span>
                    <div>
                        <h3>Masuk dengan akun perusahaan</h3>
                        <p>Akun pengguna dibuat dan dikelola oleh administrator perusahaan. Hubungi administrator jika Anda memerlukan akun.</p>
                        <a href="{{ route('login') }}">Masuk sekarang <span aria-hidden="true">→</span></a>
                    </div>
                </article>
                <article class="onboarding-step">
                    <span class="onboarding-step-number">02</span>
                    <div>
                        <h3>Administrator mengelola akun tim</h3>
                        <p>Administrator dapat mendaftarkan pengguna, menetapkan peran termasuk Administrator, dan mengatur akses menu melalui Pengaturan Pengguna &amp; Hak Akses.</p>
                    </div>
                </article>
                <article class="onboarding-step">
                    <span class="onboarding-step-number">03</span>
                    <div>
                        <h3>Siapkan data dan akses tim</h3>
                        <p>Masuk sebagai administrator, atur peran pengguna, lalu lengkapi satuan, kategori, gudang, lokasi, barang, pelanggan, supplier, dan harga.</p>
                    </div>
                </article>
                <article class="onboarding-step">
                    <span class="onboarding-step-number">04</span>
                    <div>
                        <h3>Jalankan transaksi sesuai alur</h3>
                        <p>Catat pembelian dan penerimaan untuk menambah stok; siapkan BOM dan Work Order untuk produksi; lalu buat Sales Order, Delivery, Invoice, dan pembayaran.</p>
                    </div>
                </article>
            </div>
        </section>

        <section class="onboarding-roles">
            <div>
                <span class="onboarding-eyebrow">AKSES BERDASARKAN PERAN</span>
                <h2>Setiap tim mendapat menu sesuai tugasnya.</h2>
                <p>Administrator mengatur seluruh akses. Peran operasional tersedia untuk Penjualan, Persediaan &amp; Pembelian, Produksi, dan Keuangan.</p>
            </div>
            <a class="button secondary" href="{{ route('login') }}">Masuk ke sistem</a>
        </section>
    </main>

    <footer class="onboarding-footer">JHSOFTERP <span>·</span> Sistem manajemen operasional</footer>
</body>
</html>
