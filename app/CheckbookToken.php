<?php

namespace App;


class CheckbookToken extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'time', 'token', 'refreshtoken'
    ];

    
    
    

}
