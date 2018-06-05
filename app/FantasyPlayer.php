<?php

namespace App;

class FantasyPlayer extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = false;

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function slates()
    {
        return $this->belongsToMany(Slate::class);
    }

    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    public static function getPlayersBySlate($slateId, $position, $tier)
    {
        \Log::info('Trying to get fantasy players for slateId: '.$slateId.', position: '.$position.'tier:'.($tier?$tier:''));

        $ret_val = FantasyPlayer::whereHas('slates', function ($query) use ($slateId){
            $query->where('id', $slateId);})->select('id', 'name', 'team', 'tier', 'position', 'fps', 'game_id')->
            with(["game" => function ($query){
                $query->select('id', 'homeTeam', 'awayTeam');
        }])->where('position', 'like', '%'.$position.'%')->where('tier', 'like', '%'.$tier.'%')->orderBy('tier', 'ASC')->get();

        \Log::info('Successfully retrieved fantasy players ...');

        return $ret_val;
        /*
        return FantasyPlayer::with(['game' => function($query){
            $query->select('id', 'homeTeam', 'awayTeam', 'day');}
        ])->whereHas('games', function($query){
                $query->select('id', 'homeTeam', 'awayTeam');
                $query->whereHas('slates', function($query){
                    $query->where('id',  'Sun_2017_2_3');});})->select('id', 'name', 'team', 'tier', 'fps')->
        orderBy('tier', 'ASC')->get();*/

       // return FantasyPlayer::with('slates')->where('id', 'Sun_2017_2_3')->get();

    }

    public function playerInSlate($slateId)
    {
        return $this->slates()->find($slateId)? true:false;
    }
}
