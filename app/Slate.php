<?php

namespace App;

use App\Common\Consts\Contest\ContestStatusConsts;
use App\Common\Consts\Contest\MatchTypeConsts;

use App\Helpers\DatesHelper;

class Slate extends Model
{
    protected $primaryKey = 'id';

    public $incrementing = false;

    public function games()
    {
        return $this->belongsToMany(Game::class);
    }

    public function contests()
    {
        return $this->hasMany(Contest::class);
    }

    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    public function fantasyPlayers()
    {
        return $this->belongsToMany(FantasyPlayer::class);
    }

    public function firstGame()
    {
        $games = $this->games()->orderBy('date', 'ASC')->get();
        return count($games) > 0? $games[0]:null;
    }

    public function lastGame()
    {
        $games = $this->games()->orderBy('date', 'DESC')->get();
        return count($games) > 0? $games[0]:null;
    }

    public function firstGameDate()
    {
        $game = $this->firstGame();
        return $game? $game->date:null;
    }

    public function lastGameDate()
    {
        $game = $this->lastGame();

        return $game? $game->date:null;
    }

    public static function getUserLiveContests($userId)
    {
        /*return Slate::whereHas('contests', function ($query) use ($userId) {
            $query->whereHas('entries', function ($query) use ($userId) {
            $query->where('user_id', $userId);}
        )->with(['entries',
            'entries.fantasyPlayer' => function($query) {
                $query->select('id', 'name', 'position', 'tier', 'fps');},
            'entries.game' => function($query)
            {$query->select('id', 'homeTeam', 'awayTeam', 'homeScore', 'awayScore', 'time', 'date');}
        ])->select('id', 'entryFee', 'start', 'matchupType')->where('status', '=', ContestStatusConsts::$CONTEST_STATUS_LIVE);})->get();*/

        \Log::info('Retrieving live contests for user '.$userId);

        $ret_val = Slate::where('active', '=', true)->where('status', '=', 'LIVE')
            ->with(['contests' => function($query) use ($userId){$query->where('status', '=', ContestStatusConsts::$CONTEST_STATUS_LIVE)
                ->whereHas('entries', function ($query) use ($userId) {
                    $query->where('user_id', '=',  $userId);})
            ->with(['entries' => function($query) use ($userId){
            $query->select(['*', \DB::raw('(user_id != '.$userId.' OR user_id IS NULL) AS useridnul')])->orderBy('useridnul', 'asc');},
                'entries.fantasyPlayer' => function($query){
                $query->select('id', 'name', 'team', 'position', 'tier', 'fps',
                    'fps_live', 'paYd', 'paTd', 'ruYd', 'ruTd', 'rec', 'reYd', 'reTd', 'fum', 'int', 'krTd', 'prTd', 'frTd',
                    'convRec', 'convPass', 'convRuns', 'fg0_39', 'fg40_49', 'fg50', 'xp', 'sacks', 'defInt', 'fumRec',
                    'safeties', 'defTds', 'ptsA', 'convRet', 'fps_live');},
            'entries.game' => function($query)
            {$query->select('id', 'homeTeam', 'awayTeam', 'homeScore', 'awayScore', 'time', 'date', 'quarter', 'time_remaining', 'overtime');}]);} ])
            ->orderBy('firstGame', 'ASC')->get();

        \Log::info('Successfully retrieved live matchups for user'.$userId);

        return $ret_val;
    }



    public static function getUserMatchupContests($userId)
    {
        /*return Slate::whereHas('contests', function ($query) use ($userId) {
            $query->whereHas('entries', function ($query) use ($userId) {
            $query->where('user_id', $userId);}
        )->with(['entries',
            'entries.fantasyPlayer' => function($query) {
                $query->select('id', 'name', 'position', 'tier', 'fps');},
            'entries.game' => function($query)
            {$query->select('id', 'homeTeam', 'awayTeam', 'homeScore', 'awayScore', 'time', 'date');}
        ])->select('id', 'entryFee', 'start', 'matchupType')->where('status', '=', ContestStatusConsts::$CONTEST_STATUS_LIVE);})->get();*/

        \Log::info('Trying to get user matchups for user '.$userId);

        $slates = Slate::where('active', '=', true)->where('status', '=', 'PENDING')
            ->with(['contests' => function ($query) use ($userId) {
                $query->where('status', '=', ContestStatusConsts::$CONTEST_STATUS_PENDING)
                    ->whereHas('entries', function ($query) use ($userId) {
                        $query->where('user_id', '=', $userId);
                    })
                    ->with(['entries' => function ($query) use ($userId) {
                        $query->select(['*', \DB::raw('(user_id != ' . $userId . ' OR user_id IS NULL) AS useridnul')])->orderBy('useridnul', 'asc');
                    }
                        , 'entries.fantasyPlayer' => function ($query) {
                            $query->select('id', 'name', 'team', 'position', 'tier', 'fps');
                        }
                        , 'entries.game' => function ($query) {
                            $query->select('id', 'homeTeam', 'awayTeam', 'homeScore', 'awayScore', 'time', 'date');
                        }]);
            }])->orderBy('firstGame', 'ASC')->get();

        foreach ($slates as $slate)
        {
            $slateFirstDate = $slate->firstGameDate();
            foreach ($slate->contests as $contest) {
                $contest["editStatus"] = "locked";
                if ($slateFirstDate < DatesHelper::getCurrentDate())continue;
                if ($contest->user_id && $contest->user_id !== $userId && $contest->matchupType !== MatchTypeConsts::$MATCH_SET_OPPONENT) {
                    $contest["editStatus"] = "edit";
                } else if ($contest->user_id && $contest->user_id === $userId && !$contest->filled) {
                    $contest["editStatus"] = "cancel";
                }
                else if ($contest->admin_contest && !$contest->filled){
                    $contest["editStatus"] = "cancel";
                }
            }
        }

        \Log::info('Successfully retrieved user matchups for user'.$userId);
        return $slates;
        //])->get();
    }

    public static function getNonEmptySlates()
    {
        \Log::info('Retrieving available slates ...');

//        $ret_val =  array_values(Slate::with(array('games'=>function($query){
//            $query->select('id','time', 'homeTeam', 'awayTeam');}))
//                  ->where('active', '=', true)
//                  ->where('firstGame', '>', DatesHelper::getCurrentDate())
//                  ->where('status', 'HISTORY')
//                  ->orderBy('firstGame', 'ASC')
//                  ->withCount('games')->get()
//                  ->makeHidden(['lastGame', 'lastDay'])
//                  ->where('games_count', '>', 0)->toArray());
        $ret_val = Slate::with(array('games'=>function($query){
            $query->select('id','time', 'homeTeam', 'awayTeam');
        }))->
            where('active', '=', true)->where('status', '=', 'HISTORY')->orderBy('firstGame', 'ASC')->get();
        \Log::info('Successfully retrieved available slates ...');        

        foreach ($ret_val as $ret){
            foreach ($ret['games'] as $game){
                $game['fantasy_players'] = $game->fantasyPlayers()->get();
                
            }
        }
    
        
        return $ret_val;
    }

    public static function getNonEmptyAdminSlates()
    {
        \Log::info('Retrieving available slates ...');

        $ret_val =  array_values(Slate::with(array('games'=>function($query){
            $query->select('id','time', 'homeTeam', 'awayTeam');}))
            ->where('firstGame', '>', DatesHelper::getCurrentDate())
            ->where('status', 'PENDING')
            ->orderBy('firstGame', 'ASC')
            ->withCount('games')->get()
            ->makeHidden(['lastGame', 'lastDay'])
            ->where('games_count', '>', 0)->toArray());

        \Log::info('Successfully retrieved available slates ...');

        return $ret_val;
    }

   // public static function getSlatePlayers($id, $position, $tier)
    public static function getSlatePlayers($id)
    {
//        return Slate::with(['games'=>function($query) {
//                                              $query->select('id','time', 'homeTeam', 'awayTeam');},
//                            'games.fantasyPlayers' => function($query) use ($position, $tier){
//                                                    $query->select('id','name', 'position', 'team','tier', 'fps')->
//                                                    where('position', 'like', '%'.$position.'%')->
//                                                    where('tier', 'like', '%'.$tier.'%');}])->
//        where('id', $id)->select('id')->get();

            return Slate::with(['fantasyPlayers' => function($query){
                $query->select('id', 'name', 'team', 'position', 'tier');},
                'fantasyPlayers.game' => function($query)
                {
                    $query->select('id');
                }
                ])->where('id', $id)->get();
    }
}
