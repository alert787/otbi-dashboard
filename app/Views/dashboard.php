<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="hero min-h-[60vh] bg-base-100 rounded-2xl shadow-lg">
    <div class="hero-content text-center">
        <div class="max-w-md">
            <h1 class="text-5xl font-bold">Selamat Datang</h1>
            <p class="py-6">Dashboard OTBI untuk monitoring dan analisis data procurement dari Oracle Fusion.</p>
            <div class="flex gap-4 justify-center">
            <a href="<?= base_url('po') ?>" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                </svg>
                Laporan PO (Live)
            </a>
            <a href="<?= base_url('pocsv') ?>" class="btn btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                </svg>
                Laporan PO (CSV)
            </a>
        </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
    <div class="card bg-base-100 shadow-md">
        <div class="card-body">
            <h2 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                </svg>
                Laporan PO
            </h2>
            <p>Dashboard lengkap untuk monitoring Purchase Order dengan visualisasi chart dan tabel detail.</p>
            <div class="card-actions justify-end">
                <a href="<?= base_url('po') ?>" class="btn btn-sm btn-primary">Buka</a>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-md opacity-50">
        <div class="card-body">
            <h2 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Laporan Lainnya
            </h2>
            <p>Fitur laporan tambahan akan segera hadir.</p>
            <div class="card-actions justify-end">
                <button class="btn btn-sm btn-disabled">Segera Hadir</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
