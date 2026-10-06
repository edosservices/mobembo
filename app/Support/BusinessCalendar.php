<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Calendrier d'affichage. Il ne crédite aucun revenu et ne déplace aucun solde.
 */
class BusinessCalendar
{
    public static function growsOn(CarbonInterface $moment): bool
    {
        return $moment->dayOfWeekIso >= 1 && $moment->dayOfWeekIso <= 5;
    }

    public static function isMaintenance(?CarbonInterface $moment = null): bool
    {
        return ($moment ?? now())->dayOfWeekIso === 6;
    }

    public static function withdrawalsOpen(?CarbonInterface $moment = null): bool
    {
        $day = ($moment ?? now())->dayOfWeekIso;

        return $day >= 1 && $day <= 6;
    }

    /**
     * Jours du lundi au vendredi entre le début inclus et la fin exclue,
     * plus la journée en cours lorsqu'elle est ouvrée et encore dans la durée.
     */
    public static function accrualDays(CarbonInterface $start, CarbonInterface $end, ?CarbonInterface $today = null): int
    {
        $start = $start->copy()->timezone(config('app.timezone'))->startOfDay();
        $end = $end->copy()->timezone(config('app.timezone'))->startOfDay();
        $today = Carbon::parse($today ?? now())->timezone(config('app.timezone'))->startOfDay();

        if ($today->lt($start) || $end->lte($start)) {
            return 0;
        }

        $limit = $today->lt($end) ? $today->copy() : $end->copy();
        $count = 0;
        $cursor = $start->copy();

        while ($cursor->lt($limit)) {
            if (self::growsOn($cursor)) {
                $count++;
            }
            $cursor->addDay();
        }

        if ($today->lt($end) && $today->gte($start) && self::growsOn($today)) {
            $count++;
        }

        return $count;
    }
}
