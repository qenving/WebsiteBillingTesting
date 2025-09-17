<?php

namespace App\Support;

use App\Models\Service;
use Carbon\Carbon;

class ServiceBilling
{
    public static function addCycle(Carbon $from, string $cycle): Carbon
    {
        $next = $from->copy();
        switch ($cycle) {
            case 'annually':
                $next->addYear();
                break;
            case 'semiannually':
                $next->addMonths(6);
                break;
            case 'quarterly':
                $next->addMonths(3);
                break;
            case 'monthly':
            default:
                $next->addMonth();
                break;
        }
        return $next->startOfDay();
    }

    public static function initialDueDate(Service $service, ?Carbon $reference = null): Carbon
    {
        $base = $reference ? $reference->copy() : now();
        return static::addCycle($base, $service->billing_cycle ?: 'monthly');
    }

    public static function advanceNextDueDate(Service $service): void
    {
        $base = $service->next_due_date ? $service->next_due_date->copy() : now();
        $service->next_due_date = static::addCycle($base, $service->billing_cycle ?: 'monthly');
    }
}
