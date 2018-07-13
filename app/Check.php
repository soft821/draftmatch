<?php

namespace App;

class Check extends Model
{
    //

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'email', 'amount', 'description', 'status', 'image_uri', 'gen_time', 'checkId', 'type', 'checked'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    
}
