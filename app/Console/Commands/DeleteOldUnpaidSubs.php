<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sub;
class DeleteOldUnpaidSubs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-old-unpaid-subs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();
        // Get last week's Sunday 00:00
        $upcomingSunday = $now->copy()->next('sunday')->startOfDay();
        $week= $now->weekOfYear;
        echo $upcomingSunday->toDateTimeString() . " " . $week . "\n";
        $unpaidSubs = Sub::where('status', 'unpaid')->get();
        if ($unpaidSubs->isEmpty()) {
            $this->info('No unpaid subscriptions found.');
            return;
        }
        foreach ($unpaidSubs as $sub) {
            $sub->delete();
            $this->info("Deleted subscription ID: {$sub->id}");
        }
        $this->info('Old unpaid subscriptions deleted.');
    }
}
