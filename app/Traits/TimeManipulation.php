<?php

namespace App\Traits;

use Carbon\Carbon;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

trait TimeManipulation
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function convertDateToUtc($date , $format = 'Y-m-d H:i'): string
    {
        $timezone = session()->get('timezone')?? config('app.timezone_display');
        $date = Carbon::createFromFormat($format, $date,$timezone );
        return  $date->setTimezone('UTC')->format($format);
    }
}
