<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('bible', function () {
    $this->comment(
        "The path of the righteous man is beset on all sides\n" .
        "by the inequities of the selfish and the tyranny of evil men.\n" .
        "Blessed is he who shepherds the weak through the valley of darkness.\n" .
        "He is truly his brother's keeper and the finder of lost children.\n" .
        "And I will strike down upon thee with great vengeance and furious anger\n" .
        "those who attempt to poison and destroy my brothers."
    );
})->purpose('Display a quote from Pulp Fiction');

Artisan::command('krubi', function () {
    $this->comment("Életet és pénzt is, a kezeket fel\n".
        "Villognak a fények, ropog a fegyver\n".
        "Jól látod megérkezett a PHP artyzán így többé félnetek soha nem kell: ");
    $this->info("http://localhost:8000\n");
    // Run the php artisan serve command
    shell_exec('php artisan serve');
    
})->purpose("xd");

Artisan::command('results', function () {
    $this->comment("testing results");
    shell_exec(".\coverage\index.html");
})->purpose('Display results of a command');

Schedule::command('app:delete-old-unpaid-subs')->weeklyOn(0, '0:00');

Schedule::command('app:delete-old-unpaid-subs')->everyMinute();