<?php

namespace App\Observers;

use App\Models\Currency;
use App\Services\ActivityLogService;

class CurrencyObserver
{
    /**
     * Handle the Currency "created" event.
     */
    public function created(Currency $currency): void
    {
        ActivityLogService::log(
            'Created',
            'Currency',
            "تم إضافة عملة : {$currency->name}.",
            null,
            $currency->toArray()
        );
    }

    /**
     * Handle the Currency "updated" event.
     */
    public function updated(Currency $currency): void
    {
        //
    }

    /**
     * Handle the Currency "deleted" event.
     */
    public function deleted(Currency $currency): void
    {
        ActivityLogService::log(
            'Deleted',
            'Currency',
            "تم حذف عملة : {$currency->name}.",
            $currency->toArray(),
            null
        );
    }

    /**
     * Handle the Currency "restored" event.
     */
    public function restored(Currency $currency): void
    {
        //
    }

    /**
     * Handle the Currency "force deleted" event.
     */
    public function forceDeleted(Currency $currency): void
    {
        //
    }
}
