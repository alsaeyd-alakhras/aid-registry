<?php

namespace App\Imports;

use App\Models\Allocation;
use App\Models\Currency;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AllocationsImport implements ToModel,WithHeadingRow
{
    public function formatDate($date){
        if(is_numeric($date)){
            return Carbon::createFromFormat('Y-m-d', Date::excelToDateTimeObject($date)->format('Y-m-d'));
        }else{
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
        $alaaml = $row['alaaml'];
        ($alaaml == 'دولار') ? $alaaml = 'USD' : $alaaml = 'USD';
        ($alaaml == 'دينار أردني') ? $alaaml = 'JOD' : $alaaml = 'USD';
        ($alaaml == 'دينار كويتي') ? $alaaml = 'KWD' : $alaaml = 'USD';
        ($alaaml == 'شيكل') ? $alaaml = 'ILS' : $alaaml = 'USD';
        ($alaaml == 'يورو') ? $alaaml = 'EUR' : $alaaml = 'USD';
        $currency_allocation = Currency::where('code', $alaaml)->first();
        return Allocation::updateOrCreate([
            'budget_number' => $row['rkm_almoazn'],
        ],[
            'quantity' => $row['alkmy'],
            'price' => $row['saar'],
            'total_dollar' => $row['agmaly_dolar'],
            'allocation' => $row['altkhsys'],
            'currency_allocation' => $currency_allocation != null ? $currency_allocation->code : null,
            'currency_allocation_value' => $currency_allocation != null ? $currency_allocation->value : null,
            'amount' => $row['almblgh_baldolar'],
            'amount_received' => $row['almblgh_dolar'],
        ]);
        // return new Allocation([
        //     'date_allocation' => $this->formatDate($row['tarykh_altkhsys']),
        //     'budget_number' => $row['rkm_almoazn'],
        //     'broker_name' => $row['alasm_almkhtsr'],
        //     'organization_name' => $row['almoss'],
        //     'project_name' => $row['mshroaa'],
        //     'item_name' => $row['alsnf'],
        //     'quantity' => $row['alkmy'],
        //     'price' => $row['saar'],
        //     'total_dollar' => $row['agmaly_dolar'],
        //     'allocation' => $row['altkhsys'],
        //     'currency_allocation' => $currency_allocation != null ? $currency_allocation->code : null,
        //     'currency_allocation_value' => $currency_allocation != null ? $currency_allocation->value : null,
        //     'amount' => $row['almblgh_baldolar'],
        //     'implementation_items' => $row['bnod_altnfyth'],
        //     'date_implementation' => $this->formatDate($row['tarykh_alastlam'] ?? null),
        //     'implementation_statement' => $row['byan'],
        //     'amount_received' => $row['almblgh_dolar'],
        //     // 'notes' => $row['mlahthat'],
        //     // 'number_beneficiaries' => $row['aadd_almstfydyn'],
        // ]);
    }
    public function chunkSize(): int
    {
        return 100; // حجم القطعة الواحدة
    }
}
