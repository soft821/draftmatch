<?php

namespace App;

class Entry extends Model
{
    //
    public function contest()
    {
        return $this->belongsTo(Contest::class);
    }

    public function slate()
    {
        return $this->belongsTo(Slate::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function fantasyPlayer()
    {
        return $this->belongsTo(FantasyPlayer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function status()
    {
        return $this->contest->status;
    }
}
