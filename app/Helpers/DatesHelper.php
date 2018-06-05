<?php

namespace App\Helpers;
use Carbon\Carbon;

class DatesHelper{

    public static $startWeek = 36;

    public static function getSeasonType(){
        //if ()
    }


    public static function getCurrentDateOld(){
        $minDiffBetweenStart = round((strtotime(date('2018-01-01 00:00:00')) - strtotime(date ('2017-09-06 00:00:00')))/60, 0);
        $myDate = date("2017-08-22 00:00:00");
        $startDate = date("2017-09-06 00:00:00");
        //$endingDate = date("2017-09-04 11:20:51");
        $currentDate = date('Y-m-d H:i:s');

        $diff = round((strtotime($currentDate) - strtotime($myDate))/60,0);
        $newDate = date('Y-m-d H:i:s',strtotime($startDate."+".(($diff * 6) % $minDiffBetweenStart)." minutes"));

        return $newDate;
    }

    public static function getCurrentDate(){
        $estTime = (new \DateTime('America/New_York'))->format('Y-m-d H:i:s');
        return (Carbon::parse($estTime));
    }

    public static function getDateForRoundDiff(){
        $minDiffBetweenStart = round((strtotime(date('2018-01-01 00:00:00')) - strtotime(date ('2017-09-06 00:00:00')))/60, 0);
        $myDate = date("2017-08-22 00:00:00");
        $startDate = date("2017-09-06 00:00:00");
        //$endingDate = date("2017-09-04 11:20:51");
        $currentDate = date('Y-m-d H:i:s');

        $diff = round((strtotime($currentDate) - strtotime($myDate))/60,0);
        $newDate = date('Y-m-d H:i:s',strtotime($startDate."+".(($diff * 6))." minutes"));

        return $newDate;
    }

    public static function convertDate($date)
    {
        $dateConverted = DatesHelper::getCurrentDate();
        $currentDate = date('Y-m-d H:i:s');

        $diff = date(strtotime($dateConverted) - strtotime($currentDate));
        $newDate = date('Y-m-d H:i:s',strtotime($date) + $diff);

        return $newDate;
    }

    public static function getCurrentWeek(){
        return date("W", strtotime(DatesHelper::getCurrentDate()));
    }

    public static function getCurrentRound(){
        return  1203 + abs(round(floor((date("W", strtotime(DatesHelper::getDateForRoundDiff())) + 1  - DatesHelper::$startWeek)/17) , 0));
    }

    public static function getCurrentDay(){
        return date('l', strtotime(DatesHelper::getCurrentDate()));
    }
}
