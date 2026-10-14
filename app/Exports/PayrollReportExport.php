<?php

namespace App\Exports;

use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    protected $period;
    protected $rowNumber = 0;

    public function __construct($period = null)
    {
        $this->period = $period;
    }

    /**
     * Mengambil koleksi data penggajian berdasarkan periode (format YYYY-MM)
     */
    public function collection()
    {
        $query = Payroll::with('user')->orderBy('id', 'desc');

        if ($this->period) {
            $query->where('period', $this->period);
        }

        return $query->get();
    }

    /**
     * Judul Sheet Excel
     */
    public function title(): string
    {
        return 'Laporan Penggajian';
    }

    /**
     * Header Kolom Excel
     */
    public function headings(): array
    {
        return [
            'No.',
            'Periode',
            'Nama Karyawan',
            'Email',
            'Gaji Pokok (Rp)',
            'Tunjangan (Rp)',
            'Potongan (Rp)',
            'Gaji Bersih / Net Salary (Rp)',
            'Status Pembayaran',
            'Tanggal Dibuat',
        ];
    }

    /**
     * Mapping Data per Baris
     */
    public function map($payroll): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $payroll->period ?? '-',
            $payroll->user->name ?? 'Karyawan',
            $payroll->user->email ?? '-',
            $payroll->base_salary ?? 0,
            $payroll->allowances ?? 0,
            $payroll->deductions ?? 0,
            $payroll->net_salary ?? 0,
            strtoupper($payroll->status ?? 'DRAFT'),
            $payroll->created_at ? $payroll->created_at->format('d/m/Y H:i') : '-',
        ];
    }

    /**
     * Styling Tampilan Header Excel (Bold)
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}