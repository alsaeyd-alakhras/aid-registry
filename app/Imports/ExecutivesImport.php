<?php

namespace App\Imports;

use App\Models\Allocation;
use App\Models\Executive;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ExecutivesImport implements ToModel, WithHeadingRow
{
    public function formatDate($date)
    {
        if (is_numeric($date)) {
            return Carbon::createFromFormat('Y-m-d', Date::excelToDateTimeObject($date)->format('Y-m-d'));
        } else {
            return $date;
        }
    }
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $implementation_date = Carbon::parse($this->formatDate($row['altarykh']))->format('Y-m-d');
        $month = Carbon::parse($implementation_date)->format('Y-m');
        $allocation = null;
        if($row['rkm_almoazn'] != null && $row['almoss'] != null && $row['mshroaa'] != null){
            $allocation = Allocation::where('budget_number', $row['rkm_almoazn'])
            ->where('broker_name', $row['almoss'])
            ->where(function($query) use ($row) {
                $query->where('project_name', $row['mshroaa'])
                    ->orWhere('project_name', $row['almgal']);
            })
            ->first();
        }
        $executive_status = 'implementation';
        if($row['byan'] == 'صرف'){
            $executive_status = 'exchange';
        }elseif($row['byan'] == 'تنفيذ'){
            $executive_status = 'implementation';
        }else{
            $executive_status = 'receipt';
        }
        return Executive::updateOrCreate([
            'implementation_date' => $implementation_date,
            'budget_number' => $row['rkm_almoazn'],
            'broker_name' => $row['almoss'],
            'account' => $row['alhsab'],
            'item_name' => $row['alsnf'],
            'quantity' => $row['alkmy'],
            'price' => $row['alsaar'],
            'total_ils' => $row['alagmaly_sh'],
        ],[
            'month' => $month,
            'affiliate_name' => $row['alasm'],
            'field' => $row['almgal'],
            'project_name' => $row['mshroaa'],
            // 'detail' => $row['altfsyl'],
            'received' => $row['almstlm'],
            'notes' => $row['mlahthat'],
            'amount_payments' => $row['aldfaaat'],
            'payment_mechanism' => $row['aly_aldfaa'],
            'payment_status' => null,
            'executive_status' => $executive_status,
            'allocation_id' => $allocation ? $allocation->id : null,
        ]);
    }

    public function chunkSize(): int
    {
        return 100; // حجم القطعة الواحدة
    }
}
