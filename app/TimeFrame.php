<?php

namespace App;

class TimeFrame extends Model
{
    public static function getCurrentTimeFrame(){
        return TimeFrame::where('status', '=', 'current')->first();
    }

    public static function getPreviousTimeFrame(){
        return TimeFrame::where('status', '=', 'previous')->first();
    }

    public static function getTimeFrame($week){
        return TimeFrame::where('api_season', '=', '2017REG')->where('week', '=', $week)->first();
    }

    public function retrieveKey(){
        return $this->season.'_'.$this->week.'_'.$this->season_type;
    }
}
