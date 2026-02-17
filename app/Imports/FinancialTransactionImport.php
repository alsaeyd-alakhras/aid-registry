<?php

namespace App\Imports;

use App\Models\Allocation;
use App\Models\Executive;
use App\Models\FinancialTransaction;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class FinancialTransactionImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    public function formatDate($date){
        if(is_numeric($date)){
            return Carbon::createFromFormat('Y-m-d', Date::excelToDateTimeObject($date)->format('Y-m-d'));
        }else{
            return $date;
        }
    }
    /**
     * تحويل كل صف إلى موديل
     */
    public function model(array $row)
    {
        $transaction_date = Carbon::parse($this->formatDate($row['altarykh']))->format('Y-m-d');

        return new FinancialTransaction([
            'transaction_date' => $transaction_date,
            'project' => $row['almshroaa'],
            'field' => $row['almgal'],
            'funder' => $row['almmol'],
            'budget_number' => $row['rkm_almoazn'],
            'account_name' => $row['alhsab'],
            'name' => $row['alasm'],
            'description' => $row['albyan'],
            'association' => $row['gmaay'],
            'account_type' => $row['noaa_alhsab'],
            'amount' => $row['almblgh'],
            'currency' => $row['alaaml'],
            'fund' => $row['sndok'],
            'exchange_rate' => $row['saar_thoyl'],
            'debit_shekel' => $row['mdyn_sh'] ?? 0,
            'credit_shekel' => $row['dayn_sh'] ?? 0,
            'debit_dollar' => $row['mdyn_dolar'] ?? 0,
            'credit_dollar' => $row['dayn_dolar'] ?? 0,
            'debit_dinar' => $row['mdyn_dynar'] ?? 0,
            'credit_dinar' => $row['dayn_dynar'] ?? 0,
            'is_active' => true,
        ]);
    }

    /**
     * حجم الدفعة للإدراج
     */
    public function batchSize(): int
    {
        return 500;
    }

    /**
     * حجم كل جزء للقراءة
     */
    public function chunkSize(): int
    {
        return 500;
    }
}
