<?php

namespace App\Helpers;

use Exception;

class Utils
{
    public static function exceptToStr(Exception $except, $traceLength = 10)
    {
        $trace = explode(PHP_EOL, $except->getTraceAsString());
        if ($traceLength > 0 && count($trace) > $traceLength) {
            $trace = array_slice($trace, 0, $traceLength);
            $trace[] = '...';
        }
        return sprintf(
            "%s\nOccured in %s(%d)\n\t- %s",
            $except->getMessage(),
            $except->getFile(),
            $except->getLine(),
            implode("\n\t- ", $trace)
        );
    }
}
