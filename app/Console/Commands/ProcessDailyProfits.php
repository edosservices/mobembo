<?php

namespace App\Console\Commands;

use App\Services\DailyProfitService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ProcessDailyProfits extends Command
{
    protected $signature = 'investments:process-daily-profits {--date= : Jour à traiter, format Y-m-d}';

    protected $description = 'Crédite les profits quotidiens dus et clôture les investissements arrivés à échéance';

    public function handle(DailyProfitService $profits): int
    {
        $date = $this->option('date');
        $day = $date ? Carbon::parse($date, config('app.timezone'))->startOfDay() : now();
        $result = $profits->process($day);

        $this->info(sprintf(
            'Profits crédités : %d. Investissements terminés : %d. Erreurs : %d.',
            $result['credited'],
            $result['matured'],
            $result['errors'],
        ));

        return $result['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
