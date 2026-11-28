<?php

namespace App\Console\Commands;

use App\Services\SpecialBillingGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateSpecialBillsCommand extends Command
{
    protected $signature = 'billing:generate-special {--month= : Target month in YYYY-MM format (defaults to current month)}';
    protected $description = 'Queue recurring retainer, secondment, and director fee invoices due for the target period';

    public function handle(SpecialBillingGenerator $generator): int
    {
        $target = $this->option('month')
            ? Carbon::createFromFormat('Y-m', $this->option('month'))->startOfMonth()
            : Carbon::today()->startOfMonth();

        $result = $generator->generateForDate($target);

        $this->info("Special billing queue complete for {$target->format('F Y')}: {$result['created_invoices']} invoice(s), {$result['created_items']} line item(s) queued.");

        return self::SUCCESS;
    }
}