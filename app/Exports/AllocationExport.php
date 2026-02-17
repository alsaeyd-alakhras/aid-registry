<?php

namespace App\Exports;

use App\Models\Allocation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AllocationExport implements FromCollection, WithHeadings, WithMapping
{
    protected $allocations;
    private $rowNumber = 1; // متغير لبدء الترقيم من 1


    public function __construct($allocations)
    {
        $this->allocations = $allocations;
    }

    // استرجاع البيانات
    public function collection()
    {
        return $this->allocations;
    }

    // تخصيص البيانات لكل صف
    public function map($allocation): array
    {
        return [
            $this->rowNumber++, // رقم تسلسلي يبدأ من 1 ويزيد تلقائيًا
            $allocation->date_allocation,
            $allocation->budget_number,
            $allocation->broker_name,
            $allocation->organization_name,
            $allocation->project_name,
            $allocation->item_name,
            $allocation->quantity,
            $allocation->price,
            $allocation->total_dollar,
            $allocation->allocation,
            $allocation->currency_allocation,
            $allocation->amount,
            $allocation->number_beneficiaries,
            $allocation->implementation_items,
            $allocation->date_implementation,
            $allocation->implementation_statement,
            $allocation->amount_received,
        ];
    }

    // إضافة رؤوس الأعمدة
    public function headings(): array
    {
        return [
            '#',
            'تاريخ التخصيص',
            'رقم الموازنة',
            'الاسم المختصر',
            'المؤسسة',
            'المشروع',
            'الصنف',
            'الكمية',
            'السعر',
            'إجمالي $',
            'التخصيص',
            'العملة',
            'المبلغ $',
            'عدد المستفيدين',
            'بنود التنفيد',
            'تاريخ القبض',
            'بيان',
            'المبلغ المقبوض $',
        ];
    }
}
