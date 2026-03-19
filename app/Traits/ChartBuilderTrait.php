<?php

namespace App\Traits;

trait ChartBuilderTrait
{
    protected function calculateDaysBetween(string $startDate, string $endDate): ?int
    {
        try {
            // Hitung selisih tanggal
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $interval = $start->diff($end);
            return (int) $interval->days;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function getCycleTimeCategory(int $days): string
    {
        // Kategorikan cycle time
        if ($days <= 7) {
            return '0-7 hari';
        } elseif ($days <= 14) {
            return '8-14 hari';
        } elseif ($days <= 30) {
            return '15-30 hari';
        } elseif ($days <= 60) {
            return '31-60 hari';
        } elseif ($days <= 90) {
            return '61-90 hari';
        }
        return '>90 hari';
    }

    protected function sortP2PCycleTime(array $p2pData): array
    {
        // urutan sort
        $order = [
            '0-7 hari' => 1,
            '8-14 hari' => 2,
            '15-30 hari' => 3,
            '31-60 hari' => 4,
            '61-90 hari' => 5,
            '>90 hari' => 6
        ];

        uksort($p2pData, function($a, $b) use ($order) {
            return ($order[$a] ?? 999) - ($order[$b] ?? 999);
        });

        return $p2pData;
    }

    protected function generateYearList(): array
    {
        // Generate 5 tahun terakhir
        $currentYear = (int) date('Y');
        $years = [];
        for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
            $years[] = (string) $y;
        }
        return $years;
    }

    protected function buildSummary(array $data): array
    {
        // statistik summary
        return [
            'total_po' => count(array_unique(array_column($data, 'nomor_po'))),
            'total_jumlah' => array_sum(array_column($data, 'jumlah')),
            'total_billed' => array_sum(array_column($data, 'jml_billed')),
            'total_received' => count(array_filter(array_column($data, 'receipt_num'), fn($v) => $v !== '')),
        ];
    }
}
