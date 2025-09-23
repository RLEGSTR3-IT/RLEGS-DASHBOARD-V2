<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Unified Admin Dashboard Export
 * Menggabungkan semua export data dashboard admin dalam 1 file dengan multiple sheets
 */
class AdminDashboardExport implements WithMultipleSheets
{
    protected $data;
    protected $dateRange;
    protected $filters;

    public function __construct($data, $dateRange, $filters)
    {
        $this->data = $data;
        $this->dateRange = $dateRange;
        $this->filters = $filters;
    }

    /**
     * Create all sheets untuk comprehensive export
     */
    public function sheets(): array
    {
        $sheets = [];

        // Sheet 1: Summary Overview
        $sheets[] = new SummaryOverviewSheet($this->data['summary'], $this->dateRange, $this->filters);

        // Sheet 2: Revenue Table
        $sheets[] = new RevenueTableSheet($this->data['revenue_table'], $this->dateRange, $this->filters);

        // Sheet 3-6: Performance Data (sesuai 4 tabs dashboard)
        $sheets[] = new AccountManagerPerformanceSheet($this->data['performance']['account_managers'], $this->dateRange, $this->filters);
        $sheets[] = new WitelPerformanceSheet($this->data['performance']['witels'], $this->dateRange, $this->filters);
        $sheets[] = new SegmentPerformanceSheet($this->data['performance']['segments'], $this->dateRange, $this->filters);
        $sheets[] = new CorporateCustomerPerformanceSheet($this->data['performance']['corporate_customers'], $this->dateRange, $this->filters);

        return $sheets;
    }
}

/**
 * Sheet 1: Summary Overview
 */
class SummaryOverviewSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    protected $data;
    protected $dateRange;
    protected $filters;

    public function __construct($data, $dateRange, $filters)
    {
        $this->data = $data;
        $this->dateRange = $dateRange;
        $this->filters = $filters;
    }

    public function collection()
    {
        // Create summary rows
        return collect([
            (object) [
                'metric' => 'Total Revenue',
                'value' => $this->data['total_revenue'] ?? 0,
                'format' => 'currency'
            ],
            (object) [
                'metric' => 'Total Target',
                'value' => $this->data['total_target'] ?? 0,
                'format' => 'currency'
            ],
            (object) [
                'metric' => 'Achievement Rate',
                'value' => $this->data['achievement_rate'] ?? 0,
                'format' => 'percentage'
            ],
            (object) [
                'metric' => 'Period Type',
                'value' => $this->filters['period_type'],
                'format' => 'text'
            ],
            (object) [
                'metric' => 'Period Range',
                'value' => $this->dateRange['start']->format('d M Y') . ' - ' . $this->dateRange['end']->format('d M Y'),
                'format' => 'text'
            ],
            (object) [
                'metric' => 'Export Date',
                'value' => Carbon::now()->format('d M Y H:i:s'),
                'format' => 'text'
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'Metric',
            'Value',
            'Status/Category'
        ];
    }

    public function map($row): array
    {
        $status = '';

        switch ($row->metric) {
            case 'Achievement Rate':
                $achievement = floatval($row->value);
                if ($achievement >= 100) {
                    $status = 'Excellent (≥100%)';
                } elseif ($achievement >= 80) {
                    $status = 'Good (80-99%)';
                } else {
                    $status = 'Needs Improvement (<80%)';
                }
                $value = $achievement . '%';
                break;

            case 'Total Revenue':
            case 'Total Target':
                $value = 'Rp ' . number_format($row->value, 0, ',', '.');
                $status = $row->value >= 1000000000 ? 'High Value' : ($row->value >= 100000000 ? 'Medium Value' : 'Low Value');
                break;

            default:
                $value = $row->value;
                $status = '-';
                break;
        }

        return [
            $row->metric,
            $value,
            $status
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1976D2']
                ]
            ],
            'A:C' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 30,
            'C' => 25
        ];
    }

    public function title(): string
    {
        return 'Summary Overview';
    }
}

/**
 * Sheet 2: Revenue Table
 */
class RevenueTableSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    protected $data;
    protected $dateRange;
    protected $filters;

    public function __construct($data, $dateRange, $filters)
    {
        $this->data = collect($data);
        $this->dateRange = $dateRange;
        $this->filters = $filters;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Bulan',
            'Target Revenue (Rp)',
            'Realisasi Revenue (Rp)',
            'Achievement (%)',
            'Status Achievement',
            'Gap (Rp)',
            'Month Name'
        ];
    }

    public function map($row): array
    {
        $achievement = $row['achievement'] ?? 0;
        $target = $row['target'] ?? 0;
        $realisasi = $row['realisasi'] ?? 0;
        $gap = $realisasi - $target;

        $status = '';
        if ($achievement >= 100) {
            $status = 'Excellent (≥100%)';
        } elseif ($achievement >= 80) {
            $status = 'Good (80-99%)';
        } else {
            $status = 'Needs Improvement (<80%)';
        }

        return [
            $row['bulan'] ?? '',
            $target,
            $realisasi,
            $achievement,
            $status,
            $gap,
            date('F Y', mktime(0, 0, 0, $row['bulan'] ?? 1, 1, date('Y')))
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2E7D32']
                ]
            ],
            'B:C' => [
                'numberFormat' => ['formatCode' => '#,##0']
            ],
            'D' => [
                'numberFormat' => ['formatCode' => '0.00"%"']
            ],
            'F' => [
                'numberFormat' => ['formatCode' => '#,##0']
            ],
            'A:G' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 20,
            'C' => 20,
            'D' => 15,
            'E' => 25,
            'F' => 20,
            'G' => 15
        ];
    }

    public function title(): string
    {
        return 'Revenue Table';
    }
}

/**
 * Sheet 3: Account Manager Performance
 */
class AccountManagerPerformanceSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    protected $data;
    protected $dateRange;
    protected $filters;

    public function __construct($data, $dateRange, $filters)
    {
        $this->data = collect($data);
        $this->dateRange = $dateRange;
        $this->filters = $filters;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Ranking',
            'Nama Account Manager',
            'NIK',
            'Witel',
            'Divisi',
            'Total Revenue (Rp)',
            'Total Target (Rp)',
            'Achievement (%)',
            'Status',
            'Category',
            'Gap (Rp)'
        ];
    }

    public function map($row): array
    {
        static $ranking = 0;
        $ranking++;

        $achievementRate = $row->achievement_rate ?? 0;
        $totalRevenue = $row->total_revenue ?? 0;
        $totalTarget = $row->total_target ?? 0;
        $gap = $totalRevenue - $totalTarget;

        $status = $this->getAchievementStatus($achievementRate);
        $category = $this->getRevenueCategory($totalRevenue);

        // Get divisi names
        $divisiNames = 'Unknown';
        if (isset($row->divisis) && $row->divisis->count() > 0) {
            $divisiNames = $row->divisis->pluck('nama')->implode(', ');
        } elseif (isset($row->divisi_list)) {
            $divisiNames = $row->divisi_list;
        }

        return [
            $ranking,
            $row->nama ?? '',
            $row->nik ?? '',
            isset($row->witel) ? $row->witel->nama : 'Unknown',
            $divisiNames,
            $totalRevenue,
            $totalTarget,
            round($achievementRate, 2),
            $status,
            $category,
            $gap
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1976D2']
                ]
            ],
            'F:G' => [
                'numberFormat' => ['formatCode' => '#,##0']
            ],
            'H' => [
                'numberFormat' => ['formatCode' => '0.00"%"']
            ],
            'K' => [
                'numberFormat' => ['formatCode' => '#,##0']
            ],
            'A:K' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10, 'B' => 25, 'C' => 15, 'D' => 20, 'E' => 30,
            'F' => 20, 'G' => 20, 'H' => 15, 'I' => 20, 'J' => 15, 'K' => 20
        ];
    }

    public function title(): string
    {
        return 'Account Managers';
    }

    private function getAchievementStatus($achievement)
    {
        if ($achievement >= 100) return 'Excellent';
        if ($achievement >= 80) return 'Good';
        return 'Needs Improvement';
    }

    private function getRevenueCategory($revenue)
    {
        if ($revenue >= 1000000000) return 'High Value (≥1B)';
        if ($revenue >= 500000000) return 'Medium High (500M-1B)';
        if ($revenue >= 100000000) return 'Medium (100M-500M)';
        if ($revenue > 0) return 'Low (<100M)';
        return 'No Revenue';
    }
}

/**
 * Sheet 4: Witel Performance
 */
class WitelPerformanceSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    protected $data;
    protected $dateRange;
    protected $filters;

    public function __construct($data, $dateRange, $filters)
    {
        $this->data = collect($data);
        $this->dateRange = $dateRange;
        $this->filters = $filters;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Ranking',
            'Nama Witel',
            'Kode Witel',
            'Total Customers',
            'Total Account Managers',
            'Total Revenue (Rp)',
            'Total Target (Rp)',
            'Achievement (%)',
            'Status',
            'Gap (Rp)'
        ];
    }

    public function map($row): array
    {
        static $ranking = 0;
        $ranking++;

        $achievementRate = $row->achievement_rate ?? 0;
        $totalRevenue = $row->total_revenue ?? 0;
        $totalTarget = $row->total_target ?? 0;
        $gap = $totalRevenue - $totalTarget;

        $status = $this->getAchievementStatus($achievementRate);

        return [
            $ranking,
            $row->nama ?? '',
            $row->kode ?? '',
            $row->total_customers ?? 0,
            $row->total_account_managers ?? 0,
            $totalRevenue,
            $totalTarget,
            round($achievementRate, 2),
            $status,
            $gap
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '388E3C']
                ]
            ],
            'F:G' => [
                'numberFormat' => ['formatCode' => '#,##0']
            ],
            'H' => [
                'numberFormat' => ['formatCode' => '0.00"%"']
            ],
            'J' => [
                'numberFormat' => ['formatCode' => '#,##0']
            ],
            'A:J' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10, 'B' => 25, 'C' => 15, 'D' => 15, 'E' => 20,
            'F' => 20, 'G' => 20, 'H' => 15, 'I' => 20, 'J' => 20
        ];
    }

    public function title(): string
    {
        return 'Witels';
    }

    private function getAchievementStatus($achievement)
    {
        if ($achievement >= 100) return 'Excellent';
        if ($achievement >= 80) return 'Good';
        return 'Needs Improvement';
    }
}

/**
 * Sheet 5: Segment Performance
 */
class SegmentPerformanceSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    protected $data;
    protected $dateRange;
    protected $filters;

    public function __construct($data, $dateRange, $filters)
    {
        $this->data = collect($data);
        $this->dateRange = $dateRange;
        $this->filters = $filters;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Ranking',
            'Nama Segment',
            'Kode Segment',
            'Divisi',
            'Total Customers',
            'Total Revenue (Rp)',
            'Total Target (Rp)',
            'Achievement (%)',
            'Status',
            'Gap (Rp)'
        ];
    }

    public function map($row): array
    {
        static $ranking = 0;
        $ranking++;

        $achievementRate = $row->achievement_rate ?? 0;
        $totalRevenue = $row->total_revenue ?? 0;
        $totalTarget = $row->total_target ?? 0;
        $gap = $totalRevenue - $totalTarget;

        $status = $this->getAchievementStatus($achievementRate);

        return [
            $ranking,
            $row->lsegment_ho ?? '',
            $row->ssegment_ho ?? '',
            isset($row->divisi) ? $row->divisi->nama : 'Unknown',
            $row->total_customers ?? 0,
            $totalRevenue,
            $totalTarget,
            round($achievementRate, 2),
            $status,
            $gap
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F57C00']
                ]
            ],
            'F:G' => [
                'numberFormat' => ['formatCode' => '#,##0']
            ],
            'H' => [
                'numberFormat' => ['formatCode' => '0.00"%"']
            ],
            'J' => [
                'numberFormat' => ['formatCode' => '#,##0']
            ],
            'A:J' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10, 'B' => 30, 'C' => 15, 'D' => 20, 'E' => 15,
            'F' => 20, 'G' => 20, 'H' => 15, 'I' => 20, 'J' => 20
        ];
    }

    public function title(): string
    {
        return 'Segments';
    }

    private function getAchievementStatus($achievement)
    {
        if ($achievement >= 100) return 'Excellent';
        if ($achievement >= 80) return 'Good';
        return 'Needs Improvement';
    }
}

/**
 * Sheet 6: Corporate Customer Performance
 */
class CorporateCustomerPerformanceSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    protected $data;
    protected $dateRange;
    protected $filters;

    public function __construct($data, $dateRange, $filters)
    {
        $this->data = collect($data);
        $this->dateRange = $dateRange;
        $this->filters = $filters;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Ranking',
            'Nama Corporate Customer',
            'NIPNAS',
            'Divisi',
            'Segment',
            'Account Manager',
            'Total Revenue (Rp)',
            'Total Target (Rp)',
            'Achievement (%)',
            'Status',
            'Category',
            'Gap (Rp)'
        ];
    }

    public function map($row): array
    {
        static $ranking = 0;
        $ranking++;

        $achievementRate = $row->achievement_rate ?? 0;
        $totalRevenue = $row->total_revenue ?? 0;
        $totalTarget = $row->total_target ?? 0;
        $gap = $totalRevenue - $totalTarget;

        $status = $this->getAchievementStatus($achievementRate);
        $category = $this->getRevenueCategory($totalRevenue);

        return [
            $ranking,
            $row->nama ?? '',
            $row->nipnas ?? '',
            $row->divisi_nama ?? 'Unknown',
            $row->segment_nama ?? 'Unknown',
            $row->account_manager_nama ?? 'Unknown',
            $totalRevenue,
            $totalTarget,
            round($achievementRate, 2),
            $status,
            $category,
            $gap
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '7B1FA2']
                ]
            ],
            'G:H' => [
                'numberFormat' => ['formatCode' => '#,##0']
            ],
            'I' => [
                'numberFormat' => ['formatCode' => '0.00"%"']
            ],
            'L' => [
                'numberFormat' => ['formatCode' => '#,##0']
            ],
            'A:L' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10, 'B' => 30, 'C' => 15, 'D' => 20, 'E' => 25, 'F' => 25,
            'G' => 20, 'H' => 20, 'I' => 15, 'J' => 20, 'K' => 20, 'L' => 20
        ];
    }

    public function title(): string
    {
        return 'Corporate Customers';
    }

    private function getAchievementStatus($achievement)
    {
        if ($achievement >= 100) return 'Excellent';
        if ($achievement >= 80) return 'Good';
        return 'Needs Improvement';
    }

    private function getRevenueCategory($revenue)
    {
        if ($revenue >= 1000000000) return 'High Value (≥1B)';
        if ($revenue >= 500000000) return 'Medium High (500M-1B)';
        if ($revenue >= 100000000) return 'Medium (100M-500M)';
        if ($revenue > 0) return 'Low (<100M)';
        return 'No Revenue';
    }
}