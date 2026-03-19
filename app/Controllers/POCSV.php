<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Traits\ChartBuilderTrait;

class POCSV extends BaseController
{
    use ChartBuilderTrait;

    public function index()
    {
        // Generate daftar tahun
        $tahunList = $this->generateYearList();
        $defaultYear = date('Y');
        
        return view('po_csv/index', [
            'tahunList' => $tahunList,
            'defaultYear' => $defaultYear
        ]);
    }
    
    public function getData()
    {
        // Ambil parameter tahun
        $tahun = $this->request->getGet('tahun');
        
        if ($tahun === null || $tahun === '') {
            $tahun = date('Y');
        }
        
        try {
            // path file CSV
            $csvPath = ROOTPATH . 'public/assets/laporan_po_' . $tahun . '.csv';
            
            if (!file_exists($csvPath)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'File CSV untuk tahun ' . $tahun . ' tidak ditemukan.'
                ]);
            }
            
            $csvData = $this->parseCSV($csvPath);
            
            if (empty($csvData)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Tidak ada data dalam file CSV.'
                ]);
            }
            
            $summary = $this->buildSummary($csvData);
            $charts = $this->buildCharts($csvData);
            
            return $this->response->setJSON([
                'success' => true,
                'summary' => $summary,
                'charts' => $charts,
                'rows' => $csvData,
                'cached' => false,
                'timestamp' => time()
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function parseCSV($filePath): array
    {
        $data = [];
        $header = null;
        
        if (($handle = fopen($filePath, 'r')) !== false) {
            // Skip BOM jika ada
            if (fgets($handle, 4) !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                if ($header === null) {
                    $header = $this->normalizeHeaders($row);
                } else {
                    $rowData = [];
                    foreach ($header as $index => $field) {
                        $value = $row[$index] ?? '';
                        $rowData[$field] = $value;
                    }
                    $data[] = $rowData;
                }
            }
            fclose($handle);
        }
        
        return $data;
    }
    
    private function normalizeHeaders(array $headers): array
    {
        // Array mapping header
        $mapping = [
            'Nomor PO' => 'nomor_po',
            'No. Requisisi' => 'no_requisition',
            'Tgl Buat PO' => 'tgl_buat_po',
            'Triwulan' => 'triwulan',
            'Status' => 'status_po',
            'Tipe PO' => 'tipe_po',
            'Jenis PO' => 'jenis_po',
            'BU' => 'bu',
            'Unit Kerja' => 'unit_kerja',
            'Rekanan' => 'nama_rekanan',
            'Kode Item' => 'kode_item',
            'Nama Item' => 'nama_item',
            'Kategori' => 'kategori',
            'Jenis Item' => 'jenis_item',
            'Mata Uang' => 'mata_uang',
            'Harga Satuan' => 'harga_satuan',
            'Qty Ordered' => 'qty_ordered',
            'Jumlah' => 'jumlah',
            'Qty Received' => 'qty_received',
            'Qty Delivered' => 'qty_delivered',
            'Qty Billed' => 'qty_billed',
            'Jml Billed' => 'jml_billed',
            'No. Receipt' => 'receipt_num',
            'Tgl Receipt' => 'receipt_date',
            'No. Invoice' => 'invoice_num',
            'Tgl Invoice' => 'invoice_date',
            'Agreement' => 'agreement',
            'Nomor Kontrak' => 'nomor_kontrak',
            'Tgl Kontrak' => 'tanggal_kontrak',
            'Tgl Selesai SPMK' => 'tanggal_selesai_spmk',
            'Project' => 'project_number',
            'Task' => 'task_number',
            'Aktivitas' => 'aktifitas',
            'Sumber Dana' => 'desc_sumber_dana',
            'PO Charge Account' => 'po_charge_account'
        ];
        
        $normalized = [];
        foreach ($headers as $header) {
            $normalized[] = $mapping[$header] ?? strtolower(str_replace([' ', '.'], '_', $header));
        }
        
        return $normalized;
    }
    
    private function buildCharts(array $data): array
    {
        // Inisialisasi
        $byStatus = [];
        $byBU = [];
        $byMonth = [];
        $byJenisPO = [];
        $byJenisPengadaan = [];
        $byJenisItem = [];
        $p2pCycleTimes = [];

        foreach ($data as $row) {
            $status = $row['status_po'] ?: 'UNKNOWN';
            $bu = $row['bu'] ?: 'UNKNOWN';
            $jenisPO = $row['jenis_po'] ?? 'UNKNOWN';
            $jenisItem = $row['jenis_item'] ?? 'UNKNOWN';
            $tglBuat = $row['tgl_buat_po'] ?? '';
            $jumlah = (float) ($row['jumlah'] ?? 0);
            
            $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;
            $byBU[$bu] = ($byBU[$bu] ?? 0) + $jumlah;
            $byJenisPO[$jenisPO] = ($byJenisPO[$jenisPO] ?? 0) + 1;
            $byJenisItem[$jenisItem] = ($byJenisItem[$jenisItem] ?? 0) + 1;
            
            // Hitung cycle time
            $tglReceipt = $row['receipt_date'] ?? '';
            if ($tglBuat !== '' && $tglReceipt !== '') {
                $cycleDays = $this->calculateDaysBetween($tglBuat, $tglReceipt);
                if ($cycleDays !== null && $cycleDays >= 0) {
                    $category = $this->getCycleTimeCategory($cycleDays);
                    $p2pCycleTimes[$category] = ($p2pCycleTimes[$category] ?? 0) + 1;
                }
            }
            
            if ($tglBuat !== '') {
                $parts = explode('/', $tglBuat);
                if (count($parts) === 3) {
                    $bulan = $parts[1] . '-' . $parts[2];
                    $byMonth[$bulan] = ($byMonth[$bulan] ?? 0) + $jumlah;
                }
            }
        }
        
        ksort($byMonth);
        $p2pCycleTimes = $this->sortP2PCycleTime($p2pCycleTimes);
        
        return [
            'by_status' => $byStatus,
            'by_bu' => $byBU,
            'by_month' => $byMonth,
            'by_jenis_po' => $byJenisPO,
            'by_jenis_pengadaan' => $byJenisPengadaan,
            'by_jenis_item' => $byJenisItem,
            'p2p_cycle_time' => $p2pCycleTimes,
        ];
    }

    private function parseDateCSV(string $date): ?\DateTime
    {
        // Format tanggal
        $formats = ['m/d/Y', 'd/m/Y', 'Y-m-d', 'Y/m/d'];

        foreach ($formats as $format) {
            $dateObj = \DateTime::createFromFormat($format, $date);
            if ($dateObj !== false) {
                return $dateObj;
            }
        }

        return null;
    }

    protected function calculateDaysBetween(string $startDate, string $endDate): ?int
    {
        try {
            // Parse kedua tanggal
            $start = $this->parseDateCSV($startDate);
            $end = $this->parseDateCSV($endDate);

            if (!$start || !$end) {
                return null;
            }

            return (int) $start->diff($end)->days;
        } catch (\Exception $e) {
            return null;
        }
    }
}
