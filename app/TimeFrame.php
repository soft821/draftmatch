<?php

namespace App;

class TimeFrame extends Model
{
    public static function getCurrentTimeFrame(){
        // return TimeFrame::where('status', '=', 'current')->first();
        $current = TimeFrame::where('id', 1)->first();
        return $current;
    }

    public static function getPreviousTimeFrame(){
        return TimeFrame::where('status', '=', 'previous')->first();
    }

    public function retrieveKey(){
        return $this->season.'_'.$this->week.'_'.$this->season_type;
    }
}
