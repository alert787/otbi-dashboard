<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Traits\ChartBuilderTrait;

class PO extends BaseController
{
    use ChartBuilderTrait;

    private string $url;
    private string $username;
    private string $password;
    private string $reportPath;
    private ?string $lastParseError = null;
    private int $cacheDuration = 300;

    public function __construct()
    {
        $this->url        = env('OTBI_URL', '');
        $this->username   = env('OTBI_USERNAME', '');
        $this->password   = env('OTBI_PASSWORD', '');
        $this->reportPath = env('OTBI_REPORT_PATH', '/Custom/Custom Report/Procurement/Laporan PO/laporan_po.xdo');
    }

    public function index(): string
    {
        // Generate daftar tahun
        $tahunList   = $this->generateYearList();
        $defaultYear = date('Y');

        return view('po/index', [
            'title'       => 'Dashboard Laporan PO',
            'tahunList'   => $tahunList,
            'defaultYear' => $defaultYear,
        ]);
    }

    public function getData(): \CodeIgniter\HTTP\ResponseInterface
    {
        // Ambil tahun dan refresh flag
        $tahun = $this->request->getGet('tahun') ?: date('Y');
        $forceRefresh = $this->request->getGet('refresh') === '1';
        $cacheKey = "po_data_{$tahun}";

        // Cek cache dulu
        if (!$forceRefresh) {
            $cachedData = $this->getCache($cacheKey);
            if ($cachedData !== null) {
                return $this->response->setJSON($cachedData);
            }
        }

        // Panggil service SOAP
        $response = $this->callSoap($tahun);

        if ($response['error']) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $response['error'],
            ]);
        }

        // Parse response XML
        $data = $this->parseXmlResponse($response['data']);

        if ($data === null) {
            $detail = $this->lastParseError ? ' Detail: ' . $this->lastParseError : '';
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal memparse response XML dari OTBI.' . $detail,
            ]);
        }

        // Build data hasil
        $result = [
            'success'   => true,
            'summary'   => $this->buildSummary($data),
            'charts'    => $this->buildCharts($data),
            'rows'      => $data,
            'cached'    => false,
            'timestamp' => time()
        ];

        // Simpan ke cache
        $this->setCache($cacheKey, $result);

        return $this->response->setJSON($result);
    }

    private function callSoap(string $tahun = ''): array
    {
        $paramBlock = '';
        if ($tahun !== '') {
            $paramBlock = '
                <pub:parameterNameValues>
                    <pub:item>
                        <pub:name>p_thn_buat_po</pub:name>
                        <pub:values>
                            <pub:item>' . htmlspecialchars($tahun, ENT_XML1) . '</pub:item>
                        </pub:values>
                    </pub:item>
                </pub:parameterNameValues>';
        }

        $payload = '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"
                                   xmlns:pub="http://xmlns.oracle.com/oxp/service/PublicReportService">
            <soap:Header/>
            <soap:Body>
                <pub:runReport>
                    <pub:reportRequest>
                        <pub:attributeFormat>xml</pub:attributeFormat>
                        <pub:reportAbsolutePath>' . $this->reportPath . '</pub:reportAbsolutePath>
                        <pub:sizeOfDataChunkDownload>-1</pub:sizeOfDataChunkDownload>'
                        . $paramBlock . '
                    </pub:reportRequest>
                </pub:runReport>
            </soap:Body>
        </soap:Envelope>';

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->url,
            CURLOPT_POST           => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/soap+xml',
                'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password),
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
        ]);

        $response = curl_exec($curl);
        $err      = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return ['data' => null, 'error' => 'cURL Error: ' . $err];
        }

        return ['data' => $response, 'error' => null];
    }

    private function parseXmlResponse(string $soapResponse): ?array
    {
        // error handling
        libxml_use_internal_errors(true);
        $this->lastParseError = null;
        $response = str_replace("text/xml", "", $soapResponse);

        try {
            $doc = new \DOMDocument();
            $doc->loadXML($response);
            
            $xpath = new \DOMXPath($doc);
            $xpath->registerNamespace('ns2', 'http://xmlns.oracle.com/oxp/service/PublicReportService');
            
            $reportBytesNode = $xpath->query('//ns2:reportBytes')->item(0);
            
            if ($reportBytesNode && trim($reportBytesNode->nodeValue) !== '') {
                // Decode data
            $reportBytes = $reportBytesNode->nodeValue;
                $xmlData = base64_decode($reportBytes);
                
                if ($xmlData === false || $xmlData === '') {
                    $this->lastParseError = 'Failed to base64 decode reportBytes from SOAP.';
                    return null;
                }
                
                return $this->parseDataSetXml($xmlData);
            } else {
                $this->lastParseError = 'No reportBytes found in SOAP response.';
                return null;
            }
        } catch (\Exception $e) {
            $this->lastParseError = 'SOAP parsing error: ' . $e->getMessage();
            return null;
        }
    }

    private function buildCharts(array $data): array
    {
        // Inisialisasi
        $byStatus      = [];
        $byBU          = [];
        $byMonth       = [];
        $byJenisPO     = [];
        $byJenisPengadaan = [];
        $byJenisItem   = [];
        $p2pCycleTimes = [];

        foreach ($data as $row) {
            $status = $row['status_po'] ?: 'UNKNOWN';
            $bu = $row['bu'] ?: 'UNKNOWN';
            $jenisPO = $row['jenis_po'] ?? 'UNKNOWN';
            $jenisPengadaan = $row['jenis_pengadaan'] ?? 'UNKNOWN';
            $jenisItem = $row['jenis_item'] ?? 'UNKNOWN';
            $tglBuat = $row['tgl_buat_po'] ?? '';
            $jumlah = (float) ($row['jumlah'] ?? 0);

            $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;
            $byBU[$bu] = ($byBU[$bu] ?? 0) + $jumlah;
            $byJenisPO[$jenisPO] = ($byJenisPO[$jenisPO] ?? 0) + 1;
            $byJenisPengadaan[$jenisPengadaan] = ($byJenisPengadaan[$jenisPengadaan] ?? 0) + 1;
            $byJenisItem[$jenisItem] = ($byJenisItem[$jenisItem] ?? 0) + 1;

            // Hitung cycle time P2P
            $tglReceipt = $row['receipt_date'] ?? '';
            if ($tglBuat !== '' && $tglReceipt !== '') {
                $cycleDays = $this->calculateDaysBetween($tglBuat, $tglReceipt);
                if ($cycleDays !== null && $cycleDays >= 0) {
                    $category = $this->getCycleTimeCategory($cycleDays);
                    $p2pCycleTimes[$category] = ($p2pCycleTimes[$category] ?? 0) + 1;
                }
            }

            if ($tglBuat !== '') {
                $parts = explode('-', $tglBuat);
                if (count($parts) === 3) {
                    $bulan = $parts[1] . '-' . $parts[2];
                    $byMonth[$bulan] = ($byMonth[$bulan] ?? 0) + $jumlah;
                }
            }
        }

        // Sort data
        ksort($byMonth);
        $p2pCycleTimes = $this->sortP2PCycleTime($p2pCycleTimes);

        return [
            'by_status'        => $byStatus,
            'by_bu'            => $byBU,
            'by_month'         => $byMonth,
            'by_jenis_po'      => $byJenisPO,
            'by_jenis_pengadaan' => $byJenisPengadaan,
            'by_jenis_item'    => $byJenisItem,
            'p2p_cycle_time'   => $p2pCycleTimes,
        ];
    }

    private function decodeReportXml(string $binary): ?string
    {
        // Daftar metode decode
        $attempts = [
            'plain'        => fn($data) => $data,
            'gzdecode'     => fn($data) => @gzdecode($data),
            'gzinflate'    => fn($data) => @gzinflate($data),
            'gzuncompress' => fn($data) => @gzuncompress($data),
        ];

        foreach ($attempts as $decoder) {
            $result = $decoder($binary);
            if ($result !== false && $result !== '' && $this->looksLikeXml($result)) {
                return $result;
            }
        }

        return null;
    }

    private function extractRowsFromXml(\SimpleXMLElement $dataXml): array
    {
        // Ekstrak baris dari XML
        $rows = [];
        $rowNodes = $dataXml->xpath('/DATA_DS/*[starts-with(local-name(), "G_")]');

        if ($rowNodes === false || empty($rowNodes)) {
            $rowNodes = $dataXml->xpath('//*[local-name()="row"]');
        }

        if ($rowNodes === false || empty($rowNodes)) {
            $rowNodes = $dataXml->xpath('//*[starts-with(local-name(), "G_")]');
        }

        if ($rowNodes === false || empty($rowNodes)) {
            $rowNodes = $dataXml->children();
        }

        foreach ($rowNodes as $row) {
            if (!($row instanceof \SimpleXMLElement)) {
                continue;
            }

            $item = $this->nodeToAssoc($row);
            if (!empty($item)) {
                $rows[] = $item;
            }
        }

        if (empty($rows)) {
            $fallback = $this->nodeToAssoc($dataXml);
            if (!empty($fallback)) {
                $rows[] = $fallback;
            }
        }

        return $rows;
    }

    private function logLibxmlErrors(string $context): void
    {
        // Log error libxml
        $errors = array_map(static fn($err) => trim($err->message ?? ''), libxml_get_errors());
        if (!empty($errors)) {
            log_message('error', sprintf('OTBI %s parse error: %s', $context, implode(' | ', $errors)));
        }
        libxml_clear_errors();
        if (!empty($errors)) {
            $this->lastParseError = implode(' | ', $errors);
        }
    }

    private function looksLikeXml(string $value): bool
    {
        // Cek apakah XML
        $trimmed = ltrim($this->stripBom($value));
        return $trimmed !== '' && $trimmed[0] === '<';
    }

    private function looksLikeDataSet(string $value): bool
    {
        // Cek format dataset
        $lower = strtolower($value);
        return str_starts_with($lower, '<data_ds');
    }

    private function parseDataSetXml(string $rawXml): ?array
    {
        // Parse dataset XML
        $decodedXml = $this->decodeReportXml($rawXml);
        if ($decodedXml === null) {
            $decodedXml = $rawXml;
        }

        $dataXml = simplexml_load_string($decodedXml, null, LIBXML_NOCDATA);
        if ($dataXml === false) {
            $error = $this->logLibxmlErrors('Report payload');
            $this->lastParseError = $error ?: 'Report payload tidak valid.';
            return null;
        }

        libxml_clear_errors();

        return $this->extractRowsFromXml($dataXml);
    }

    private function stripBom(string $value): string
    {
        // Hapus BOM dari string
        if (str_starts_with($value, "\xEF\xBB\xBF")) {
            return substr($value, 3);
        }
        return $value;
    }

    private function nodeToAssoc(\SimpleXMLElement $node): array
    {
        // Ubah node ke array
        $item = [];
        foreach ($node->children() as $col) {
            if ($col->count() > 0) {
                continue;
            }
            $item[strtolower($col->getName())] = (string) $col;
        }
        return $item;
    }

    private function getCache(string $key): ?array
    {
        // Ambil path cache
        $cacheFile = WRITEPATH . 'cache/' . $key . '.json';

        if (!file_exists($cacheFile)) {
            return null;
        }

        $cacheData = json_decode(file_get_contents($cacheFile), true);

        if ($cacheData === null || !isset($cacheData['timestamp'], $cacheData['data'])) {
            @unlink($cacheFile);
            return null;
        }

        // Cek cache kadaluarsa
        if (time() - $cacheData['timestamp'] > $this->cacheDuration) {
            @unlink($cacheFile);
            return null;
        }

        $data = $cacheData['data'];
        $data['cached'] = true;

        return $data;
    }

    private function setCache(string $key, array $data): void
    {
        // Buat folder cache
        $cacheDir = WRITEPATH . 'cache';

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $cacheFile = $cacheDir . '/' . $key . '.json';
        $cacheData = [
            'timestamp' => time(),
            'data' => $data
        ];

        file_put_contents($cacheFile, json_encode($cacheData), LOCK_EX);
        $this->cleanupOldCache($cacheDir);
    }

    private function cleanupOldCache(string $cacheDir): void
    {
        $files = glob($cacheDir . '/po_data_*.json');
        if ($files === false) {
            return;
        }

        usort($files, fn($a, $b) => filemtime($a) - filemtime($b));

        foreach (array_slice($files, 0, -10) as $file) {
            @unlink($file);
        }
    }
}
