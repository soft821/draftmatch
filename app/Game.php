<?php

namespace App;

class Game extends Model
{
    protected $primaryKey = 'id';

    public $incrementing = false;

    //
    public function slates()
    {
        return $this->belongsToMany(Slate::class);
    }

    public function fantasyPlayers()
    {
        return $this->hasMany(FantasyPlayer::class);
    }

    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    public static function gamesFromWeek($id)
    {
        return Game::where('id', 'like', '%'.$id.'%')->get();
    }

    public static function nextGameStarting(){
        return Game::where('status', '=', 'PENDING')->orderBy('date', 'asc')->first();
    }
}
