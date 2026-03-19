const PODashboard = {
    // Variabel global
    BASE_URL: '',
    DEFAULT_YEAR: '',
    allData: [],
    chartsData: null,
    dataTable: null,
    charts: {},

    fmt: new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 }),

    init(baseUrl, defaultYear) {
        // Inisialisasi
        this.BASE_URL = baseUrl;
        this.DEFAULT_YEAR = defaultYear;
        window.charts = this.charts;

        const tahunSelect = document.getElementById('filterTahun');
        if (tahunSelect && this.DEFAULT_YEAR) {
            tahunSelect.value = this.DEFAULT_YEAR;
        }

        document.getElementById('btnLoad').addEventListener('click', () => this.loadData(false));
        document.getElementById('btnRefresh').addEventListener('click', () => this.loadData(true));
        document.getElementById('btnExport').addEventListener('click', () => this.exportCSV());

        this.loadData();
    },

    fmtCurrency(v) {
        return 'Rp ' + this.fmt.format(v);
    },

    showLoading(show) {
        document.getElementById('loadingOverlay').classList.toggle('active', show);
    },

    showAlert(msg) {
        const box = document.getElementById('alertBox');
        document.getElementById('alertMsg').textContent = msg;
        box.classList.remove('hidden');
    },

    hideAlert() {
        document.getElementById('alertBox').classList.add('hidden');
    },

    updateSummary(s) {
        document.getElementById('s-total-po').textContent = this.fmt.format(s.total_po);
        document.getElementById('s-total-jumlah').textContent = this.fmtCurrency(s.total_jumlah);
        document.getElementById('s-total-billed').textContent = this.fmtCurrency(s.total_billed);
        document.getElementById('s-total-received').textContent = this.fmt.format(s.total_received);
    },

    renderChart(id, type, labels, values, formatter) {
        // Render chart
        const container = document.getElementById(id);
        if (!container) return;

        container.innerHTML = '';

        if (!labels || !values || labels.length === 0 || values.length === 0) {
            container.innerHTML = '<div class="text-center text-gray-400 py-8">No data available</div>';
            return;
        }

        if (labels.length !== values.length) {
            container.innerHTML = '<div class="text-center text-red-400 py-8">Data mismatch error</div>';
            return;
        }

        const cleanLabels = labels.map(label => String(label || 'Unknown'));
        const cleanValues = values.map(value => {
            const num = Number(value) || 0;
            return isNaN(num) ? 0 : num;
        });

        let options = {
            chart: {
                type,
                height: 280,
                toolbar: { show: false },
                fontFamily: 'inherit',
                animations: { enabled: false }
            },
            colors: ['#570DF8', '#F000B8', '#37CDBE', '#3D4451', '#EF9FBC', '#65C3C8', '#FBBD23', '#E11D48'],
            legend: { position: 'bottom', fontSize: '11px' },
            tooltip: {
                y: {
                    formatter: (v) => {
                        const num = Number(v) || 0;
                        return formatter ? formatter(num) : this.fmt.format(num);
                    }
                }
            },
        };

        if (type === 'donut') {
            options.series = cleanValues;
            options.labels = cleanLabels;
            options.dataLabels = { enabled: true };
            options.plotOptions = {
                pie: { donut: { size: '70%' } }
            };
        } else if (type === 'bar') {
            options.series = [{ name: 'Nilai', data: cleanValues }];
            options.xaxis = {
                categories: cleanLabels,
                labels: { rotate: -35, style: { fontSize: '10px' } }
            };
            options.yaxis = {
                labels: {
                    formatter: (v) => {
                        const num = Number(v) || 0;
                        return formatter ? formatter(num) : num;
                    }
                }
            };
            options.dataLabels = { enabled: false };
            options.plotOptions = {
                bar: { horizontal: false, borderRadius: 4, columnWidth: '70%' }
            };
        }

        try {
            if (this.charts[id]) {
                this.charts[id].destroy();
            }
            this.charts[id] = new ApexCharts(container, options);
            this.charts[id].render();
        } catch (err) {
            container.innerHTML = '<div class="text-center text-red-400 py-8">Chart rendering failed</div>';
        }
    },

    updateCharts(chartsData) {
        if (!chartsData || typeof chartsData !== 'object') return;

        try {
            const statusData = chartsData.by_status || {};
            if (Object.keys(statusData).length > 0) {
                this.renderChart('chart-status', 'donut', Object.keys(statusData), Object.values(statusData), null);
            }

            const jenisData = chartsData.by_jenis_po || chartsData.by_jenis_pengadaan || {};
            if (Object.keys(jenisData).length > 0) {
                this.renderChart('chart-jenis', 'donut', Object.keys(jenisData), Object.values(jenisData), null);
            }

            const p2pData = chartsData.p2p_cycle_time || {};
            if (Object.keys(p2pData).length > 0) {
                this.renderChart('chart-p2p-cycle', 'bar', Object.keys(p2pData), Object.values(p2pData), null);
            }

            const buData = chartsData.by_bu || {};
            if (Object.keys(buData).length > 0) {
                const buEntries = Object.entries(buData).sort(([, a], [, b]) => b - a).slice(0, 10);
                this.renderChart('chart-bu', 'bar', buEntries.map(([k]) => k), buEntries.map(([, v]) => v), (v) => this.fmtCurrency(v));
            }

            const byMonth = chartsData.by_month || {};
            const monthLabels = Object.keys(byMonth);
            const monthValues = monthLabels.map(k => Math.round(byMonth[k] || 0));
            if (monthLabels.length > 0 && monthValues.length > 0) {
                this.renderChart('chart-month', 'bar', monthLabels, monthValues, (v) => this.fmtCurrency(v));
            }
        } catch (err) {}
    },

    initializeDataTable() {
        // Inisialisasi
        if (this.dataTable) {
            this.dataTable.destroy();
        }

        const columns = [
            { data: 'nomor_po', render: (data) => data ? `<span class="font-mono font-semibold text-primary whitespace-nowrap">${data}</span>` : '-' },
            { data: 'no_requisition', render: (data) => data || '-' },
            { data: 'tgl_buat_po', render: (data) => data || '-' },
            { data: 'triwulan', render: (data) => data || '-' },
            { data: 'status_po', render: (data) => data ? `<span class="badge badge-sm">${data}</span>` : '-' },
            { data: 'tipe_po', render: (data) => data || '-' },
            { data: 'jenis_po', render: (data) => data || '-' },
            { data: 'bu', render: (data) => data || '-' },
            { data: 'unit_kerja', render: (data) => data || '-' },
            { data: 'nama_rekanan', render: (data) => data || '-' },
            { data: 'kode_item', render: (data) => data || '-' },
            { data: 'nama_item', render: (data) => data ? `<span class="max-w-xs truncate" title="${data}">${data}</span>` : '-' },
            { data: 'kategori', render: (data) => data || '-' },
            { data: 'jenis_item', render: (data) => data || '-' },
            { data: 'mata_uang', render: (data) => data || '-' },
            { data: 'harga_satuan', render: (data, type) => type === 'display' && data ? this.fmt.format(data) : (data || 0), className: 'text-right' },
            { data: 'qty_ordered', render: (data, type) => type === 'display' && data ? this.fmt.format(data) : (data || 0), className: 'text-right' },
            { data: 'jumlah', render: (data, type) => type === 'display' && data ? this.fmt.format(data) : (data || 0), className: 'text-right font-semibold' },
            { data: 'qty_received', render: (data, type) => type === 'display' && data ? this.fmt.format(data) : (data || 0), className: 'text-right' },
            { data: 'qty_delivered', render: (data, type) => type === 'display' && data ? this.fmt.format(data) : (data || 0), className: 'text-right' },
            { data: 'qty_billed', render: (data, type) => type === 'display' && data ? this.fmt.format(data) : (data || 0), className: 'text-right' },
            { data: 'jml_billed', render: (data, type) => type === 'display' && data ? this.fmt.format(data) : (data || 0), className: 'text-right font-semibold' },
            { data: 'receipt_num', render: (data) => data || '-' },
            { data: 'receipt_date', render: (data) => data || '-' },
            { data: 'invoice_num', render: (data) => data || '-' },
            { data: 'invoice_date', render: (data) => data || '-' },
            { data: 'agreement', render: (data) => data || '-' },
            { data: 'nomor_kontrak', render: (data) => data || '-' },
            { data: 'tanggal_kontrak', render: (data) => data || '-' },
            { data: 'tanggal_selesai_spmk', render: (data) => data || '-' },
            { data: 'project_number', render: (data) => data || '-' },
            { data: 'task_number', render: (data) => data || '-' },
            { data: 'aktifitas', render: (data) => data || '-' },
            { data: 'desc_sumber_dana', render: (data) => data || '-' },
            { data: 'po_charge_account', render: (data) => data || '-' }
        ];

        this.dataTable = $('#poTable').DataTable({
            data: this.allData,
            columns: columns,
            pageLength: 25,
            responsive: true,
            ordering: true,
            orderMulti: true,
            searching: true,
            lengthChange: true,
            info: true,
            paging: true,
            scrollX: true,
            language: {
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data per halaman",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                paginate: { first: "Pertama", last: "Terakhir", next: "Selanjutnya", previous: "Sebelumnya" }
            },
            dom: 'Blfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: 'Export Excel',
                    className: 'btn btn-success btn-sm',
                    title: 'Laporan PO',
                    filename: () => `laporan_po_${document.getElementById('filterTahun').value || 'all'}`
                },
                {
                    extend: 'csvHtml5',
                    text: 'Export CSV',
                    className: 'btn btn-success btn-sm',
                    title: 'Laporan PO',
                    filename: () => `laporan_po_${document.getElementById('filterTahun').value || 'all'}`
                },
                {
                    extend: 'pdfHtml5',
                    text: 'Export PDF',
                    className: 'btn btn-success btn-sm',
                    title: 'Laporan PO',
                    filename: () => `laporan_po_${document.getElementById('filterTahun').value || 'all'}`,
                    orientation: 'landscape',
                    pageSize: 'A4'
                }
            ]
        });
    },

    exportCSV() {
        if (!this.dataTable) {
            this.showAlert('Tidak ada data untuk diekspor');
            return;
        }
        this.dataTable.button(1).trigger();
    },

    updateCacheStatus(cached, timestamp) {
        const statusEl = document.getElementById('cacheStatus');
        if (cached) {
            const cacheTime = new Date(timestamp * 1000).toLocaleString('id-ID');
            statusEl.textContent = `📦 Cached: ${cacheTime}`;
            statusEl.className = 'badge badge-info badge-sm';
            statusEl.classList.remove('hidden');
        } else {
            statusEl.classList.add('hidden');
        }
    },

    async loadData(forceRefresh = false) {
        // Load data
        this.hideAlert();
        this.showLoading(true);
        const tahun = document.getElementById('filterTahun').value;

        try {
            const url = forceRefresh
                ? this.BASE_URL + 'po/data?tahun=' + encodeURIComponent(tahun) + '&refresh=1'
                : this.BASE_URL + 'po/data';

            const res = await axios.get(url, { params: { tahun } });
            const d = res.data;

            if (!d.success) {
                this.showAlert(d.message || 'Terjadi kesalahan saat mengambil data.');
                this.showLoading(false);
                return;
            }

            this.allData = d.rows || [];
            this.chartsData = d.charts;
            this.updateSummary(d.summary);
            this.updateCharts(d.charts);
            this.initializeDataTable();
            document.getElementById('btnExport').disabled = (this.allData.length === 0);
            document.getElementById('lastUpdate').textContent = 'Update: ' + new Date().toLocaleString('id-ID');
            // Update status cache
            this.updateCacheStatus(d.cached || false, d.timestamp || Date.now() / 1000);

            // Buat data chart global
            window.chartsData = this.chartsData;
            window.updateCharts = (data) => this.updateCharts(data);
        } catch (err) {
            this.showAlert('Gagal menghubungi server: ' + (err.message || err));
        } finally {
            this.showLoading(false);
        }
    }
};
