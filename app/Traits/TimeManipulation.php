<?php

namespace App\Traits;

use Carbon\Carbon;
use Exception;
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
        try{
            if ((Carbon::hasFormat($date,$timezone) && $format=='Y-m-d')){
                $date = Carbon::parse($date)->format($format);
            }
            $date = Carbon::createFromFormat($format, $date,$timezone );
        }catch (Exception $e) {
            $date = Carbon::now();
        } finally {
            return  $date->setTimezone('UTC')->format($format);
        }
    }
}
