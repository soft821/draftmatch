<?php

namespace App;


class BitCoinInfo extends Model
{
    //
    protected $primaryKey = 'id';

    public $incrementing = false;

    public static function getInfo(){
        return BitCoinInfo::first();
    }

    

}
