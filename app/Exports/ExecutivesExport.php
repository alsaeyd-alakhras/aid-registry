<?php

namespace App\Exports;

use App\Models\Executive;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExecutivesExport implements FromCollection, WithHeadings, WithMapping
{
    protected $executives;
    private $rowNumber = 1; // متغير لبدء الترقيم من 1

    public function __construct($executives)
    {
        $this->executives = $executives;
    }

    public function collection()
    {
        return $this->executives;
    }

    public function map($executive): array
    {
        return [
            $this->rowNumber++, // رقم تسلسلي يبدأ من 1 ويزيد تلقائيًا
            $executive->implementation_date,
            $executive->broker_name,
            $executive->account,
            $executive->affiliate_name,
            $executive->project_name,
            $executive->detail,
            $executive->item_name,
            $executive->quantity,
            $executive->price,
            $executive->total_ils,
            $executive->received,
            $executive->notes,
            $executive->amount_payments,
            $executive->payment_mechanism,
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'التاريخ',
            'المؤسسة',
            'الحساب',
            'الاسم',
            'المشروع',
            'التفصيل',
            'الصنف',
            'الكمية',
            'السعر ₪',
            'إجمالي ₪',
            'المستلم',
            'ملاحظات',
            'الدفعات',
            'آلية الدفع'
        ];
    }
}
