<?php

namespace App\Support;

class Money
{
    /**
     * Format integer pence as pounds: 1000 is "£10", 750 is "£7.50".
     */
    public static function format(?int $pence): string
    {
        if ($pence === null) {
            return 'n/a';
        }

        $sign = $pence < 0 ? '-' : '';
        $pence = abs($pence);

        return $sign.'£'.($pence % 100 === 0
            ? number_format(intdiv($pence, 100))
            : number_format($pence / 100, 2));
    }
}
