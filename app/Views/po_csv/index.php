<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="loading-overlay hidden" id="loadingOverlay">
    <div class="bg-white rounded-2xl p-8 flex flex-col items-center gap-4 shadow-xl">
        <span class="loading loading-spinner loading-lg text-primary"></span>
        <p class="text-gray-600 font-medium">Mengambil data dari CSV...</p>
    </div>
</div>

<div class="card bg-base-100 shadow-md mb-6">
    <div class="card-body py-4">
        <div class="flex flex-wrap items-end gap-4">
            <div class="form-control">
                <label class="label pb-1"><span class="label-text font-semibold">Tahun Buat PO</span></label>
                <select id="filterTahun" class="select select-bordered select-sm w-36">
                    <option value="">-- Semua Tahun --</option>
                    <?php foreach ($tahunList as $thn): ?>
                        <option value="<?= esc($thn) ?>" <?= ($defaultYear ?? '') === $thn ? 'selected' : '' ?>>
                            <?= esc($thn) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button id="btnLoad" class="btn btn-primary btn-sm gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Muat Data
            </button>
            <button id="btnExport" class="btn btn-success btn-sm gap-2" disabled>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export CSV
            </button>
        </div>
        <div class="mt-3">
            <span id="dataSource" class="badge badge-info badge-sm">📁 Data Source: CSV File</span>
        </div>
    </div>
</div>

<div id="alertBox" class="hidden fixed top-4 right-4 z-50 max-w-sm">
    <div class="alert alert-error shadow-lg">
        <div>
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span id="alertMsg"></span>
        </div>
    </div>
</div>

<style>
.stat-card-small .stat-title {
    font-size: 0.75rem !important;
}
.stat-card-small .stat-value {
    font-size: 1.25rem !important;
}
.stat-card-small .stat-figure svg {
    width: 1.25rem !important;
    height: 1.25rem !important;
}
</style>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="stat bg-base-100 shadow-md rounded-lg stat-card-small">
        <div class="stat-figure text-primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-5 h-5 stroke-current">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
        </div>
        <div class="stat-title">Total PO</div>
        <div class="stat-value text-primary" id="s-total-po">0</div>
    </div>

    <div class="stat bg-base-100 shadow-md rounded-lg stat-card-small">
        <div class="stat-figure text-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-5 h-5 stroke-current">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="stat-title">Total Nilai PO</div>
        <div class="stat-value text-secondary" id="s-total-jumlah">Rp 0</div>
    </div>

    <div class="stat bg-base-100 shadow-md rounded-lg stat-card-small">
        <div class="stat-figure text-accent">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-5 h-5 stroke-current">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
        </div>
        <div class="stat-title">Total Dibayar</div>
        <div class="stat-value text-accent" id="s-total-billed">Rp 0</div>
    </div>

    <div class="stat bg-base-100 shadow-md rounded-lg stat-card-small">
        <div class="stat-figure text-info">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-5 h-5 stroke-current">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
        </div>
        <div class="stat-title">Sudah Diterima</div>
        <div class="stat-value text-info" id="s-total-received">0</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
    <div class="card bg-base-100 shadow-md">
        <div class="card-body">
            <h2 class="card-title text-base">Status PO</h2>
            <div id="chart-status"></div>
        </div>
    </div>
    <div class="card bg-base-100 shadow-md">
        <div class="card-body">
            <h2 class="card-title text-base">Jenis PO</h2>
            <div id="chart-jenis"></div>
        </div>
    </div>
    <div class="card bg-base-100 shadow-md">
        <div class="card-body">
            <h2 class="card-title text-base">P2P Cycle Time</h2>
            <div id="chart-p2p-cycle"></div>
        </div>
    </div>
    <div class="card bg-base-100 shadow-md">
        <div class="card-body">
            <h2 class="card-title text-base">Nilai PO per BU (IDR)</h2>
            <div id="chart-bu"></div>
        </div>
    </div>
    <div class="card bg-base-100 shadow-md lg:col-span-2 xl:col-span-2">
        <div class="card-body">
            <h2 class="card-title text-base">Nilai PO per Bulan (IDR)</h2>
            <div id="chart-month"></div>
        </div>
    </div>
</div>

<div class="card bg-base-100 shadow-md">
    <div class="card-body">
        <h2 class="card-title text-lg mb-4">Detail Data PO</h2>
        <div class="overflow-x-auto">
            <table id="poTable" class="table table-zebra table-compact w-full">
                <thead>
                    <tr>
                        <th>Nomor PO</th>
                        <th>No. Requisisi</th>
                        <th>Tgl Buat PO</th>
                        <th>Triwulan</th>
                        <th>Status</th>
                        <th>Tipe PO</th>
                        <th>Jenis PO</th>
                        <th>BU</th>
                        <th>Unit Kerja</th>
                        <th>Rekanan</th>
                        <th>Kode Item</th>
                        <th>Nama Item</th>
                        <th>Kategori</th>
                        <th>Jenis Item</th>
                        <th>Mata Uang</th>
                        <th class="text-right">Harga Satuan</th>
                        <th class="text-right">Qty Ordered</th>
                        <th class="text-right">Jumlah</th>
                        <th class="text-right">Qty Received</th>
                        <th class="text-right">Qty Delivered</th>
                        <th class="text-right">Qty Billed</th>
                        <th class="text-right">Jml Billed</th>
                        <th>No. Receipt</th>
                        <th>Tgl Receipt</th>
                        <th>No. Invoice</th>
                        <th>Tgl Invoice</th>
                        <th>Agreement</th>
                        <th>Nomor Kontrak</th>
                        <th>Tgl Kontrak</th>
                        <th>Tgl Selesai SPMK</th>
                        <th>Project</th>
                        <th>Task</th>
                        <th>Aktivitas</th>
                        <th>Sumber Dana</th>
                        <th>PO Charge Account</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="<?= base_url('js/pocsv-dashboard.js') ?>"></script>
<script>
    POCSVDashboard.init('<?= base_url() ?>', '<?= esc($defaultYear ?? date('Y')) ?>');
</script>
<?= $this->endSection() ?>
