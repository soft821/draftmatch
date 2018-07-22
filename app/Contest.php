<?php

namespace App;

use App\Common\Consts\Contest\ContestConsts;
use App\Common\Consts\Contest\ContestStatusConsts;
use App\Common\Consts\Contest\MatchTypeConsts;
use App\Helpers\DatesHelper;

class Contest extends Model
{
    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $fillable = ['credit_applied'];

    //
    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function slate()
    {
        return $this->belongsTo(Slate::class);
    }

    public static function getContestsByGroupId($groupId)
    {
        return Contest::with('entries')
            ->inRandomOrder()
            ->where('group_id', '=', $groupId)
            ->where('status', '=', ContestStatusConsts::$CONTEST_STATUS_PENDING)
            ->where('filled', '=', false)->first();
    }

    /*public static function getUserContestsByStatus($userId, $status)
    {
        if ($status) {
            return Contest::whereHas('entries', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->with('entries')->where('status', '=', $status)->get();
        }
        else{
            return Contest::where('status', '=', ContestStatusConsts::$CONTEST_STATUS_PENDING)->where('filled', '=', false)->get();
        }
    }*/

    public static function getUserContestsByStatus($userId, $status)
    {
        if ($status) {
            return Contest::whereHas('entries', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->with('entries')->where('status', '=', $status)->get();
        } else {
            return Contest::where('status', '=', ContestStatusConsts::$CONTEST_STATUS_PENDING)->where('filled', '=', false)->get();
        }
    }

    // NOT USED
    public static function getUserLiveContests($userId)
    {
        return Contest::whereHas('entries', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->with(['entries',
            'entries.fantasyPlayer' => function ($query) {
                $query->select('id', 'name', 'position', 'tier', 'fps');
            },
            'entries.game' => function ($query) {
                $query->select('id', 'homeTeam', 'awayTeam', 'homeScore', 'awayScore', 'time', 'date', 'status');
            }
        ])->select('id', 'entryFee', 'start', 'matchupType', 'status')->where('status', '=', ContestStatusConsts::$CONTEST_STATUS_LIVE)->get();
    }

    public static function getUserHistoryContests($userId)
    {
        return Contest::whereHas('entries', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->with(['slate' => function ($query) {
            $query->select('id', 'name');
        },
            'entries' => function ($query) use ($userId) {
                $query->select(['*', \DB::raw('user_id = ' . $userId . ' AS useridnul')])->orderBy('useridnul', 'desc');
            },
            'entries.fantasyPlayer' => function ($query) {
                $query->select('id', 'name', 'team', 'position', 'tier', 'fps',
                    'fps_live', 'paYd', 'paTd', 'ruYd', 'ruTd', 'rec', 'reYd', 'reTd', 'fum', 'int', 'krTd', 'prTd', 'frTd',
                    'convRec', 'convPass', 'convRuns', 'fg0_39', 'fg40_49', 'fg50', 'xp', 'sacks', 'defInt', 'fumRec',
                    'safeties', 'defTds', 'ptsA', 'convRet', 'fps_live');
            },
            'entries.game' => function ($query) {
                $query->select('id', 'homeTeam', 'awayTeam', 'homeScore', 'awayScore', 'time', 'date');
            }
        ])->where('status', '=', ContestStatusConsts::$CONTEST_STATUS_HISTORY)
            ->orderBy('start', 'DESC')
            ->select('id', 'entryFee', 'start', 'matchupType', 'status', 'slate_id')->get();
    }

    /* public static function getAdminContests()
     {
         return Contest::where('status', '=', ContestStatusConsts::$CONTEST_STATUS_PENDING)
             ->where('filled', '=' , false)->whereDoesntHave('entries', function ($query){
                 $query->where('user_id', '!=', null);})->with(['entries',
             'entries.fantasyPlayer' => function($query) {
                 $query->select('id', 'name', 'position', 'tier', 'fps');},
             'entries.game' => function($query)
             {$query->select('id', 'homeTeam', 'awayTeam', 'homeScore', 'awayScore', 'time', 'date');}
         ])->select('id', 'entryFee', 'start', 'matchupType', 'status')->inRandomOrder()->get();
     }*/


    public static function getAdminContests()
    {
        $countGroups = [];
        $countGroups = Contest::select('group_id', \DB::raw('count(*) as total'))
            ->where('status', '=', ContestStatusConsts::$CONTEST_STATUS_PENDING)
            ->where('admin_contest', '=', true)
            ->whereHas('slate', function ($query) {
                $query->where('firstGame', '>', DatesHelper::getCurrentDate());
            })
            ->where('filled', '=', false)
            ->whereDoesntHave('entries', function ($query) {
                $query->where('user_id', '!=', null);
            })
            ->groupBy('group_id')
            ->get();

        foreach ($countGroups as $group) {
            $groupCounts[$group['group_id']] = $group['total'];
        }

        $groupedContests = array_values(Contest::with(['slate' => function ($query) {
            $query->select('id', 'name', 'firstGame');
        },
            'entries', 'entries.fantasyPlayer' => function ($query) {
                $query->select('id', 'name', 'team', 'position', 'tier', 'fps');
            },
            'entries.game' => function ($query) {
                $query->select('id', 'homeTeam', 'awayTeam', 'homeScore', 'awayScore', 'time', 'date');
            }])
            ->select('id', 'entryFee', 'group_id', 'matchupType', 'status', 'slate_id')
            ->whereDoesntHave('entries', function ($query) {
                $query->where('user_id', '!=', null);
            })
            ->where('admin_contest', '=', true)
            ->whereHas('slate', function ($query) {
                $query->where('firstGame', '>', DatesHelper::getCurrentDate());
            })
            ->where('status', '=', ContestStatusConsts::$CONTEST_STATUS_PENDING)
            ->where('filled', '=', false)->inRandomOrder()->get()
            ->unique('group_id')->groupBy('group_id')->toArray());

        $response = [];

        foreach ($groupedContests as $contestGroup) {
            $contestGroup = $contestGroup[0];
            $contestGroup['count'] = $groupCounts[$contestGroup['group_id']];
            array_push($response, $contestGroup);
        }

        return $response;
        /*

         $groupedContests =  array_values(Contest::with(['slate' =>function($query){$query->select('id', 'name', 'firstGame');},
             'entries'  => function($query) use ($userId){
                 $query->select(['*', \DB::raw('user_id IS NULL AS useridnul')])->orderBy('useridnul', 'asc');}, 'entries.fantasyPlayer' => function($query) {
                 $query->select('id', 'name', 'team', 'position', 'tier', 'fps');},
             'entries.game' => function($query)
             {$query->select('id', 'homeTeam', 'awayTeam', 'homeScore', 'awayScore', 'time', 'date');}])
             ->select('id', 'entryFee', 'group_id', 'matchupType', 'status', 'slate_id')
             ->whereDoesntHave('entries', function ($query) use ($userId){
                 $query->where('user_id', $userId);})
             ->whereHas('slate', function ($query){$query->where('firstGame', '>', DatesHelper::getCurrentDate());})
             ->where('status', '=', ContestStatusConsts::$CONTEST_STATUS_PENDING)
             ->where('filled', '=' , false)->get()
             ->unique('group_id')->groupBy('group_id')->toArray());

         return Contest::where('status', '=', ContestStatusConsts::$CONTEST_STATUS_PENDING)
             ->where('filled', '=' , false)->whereDoesntHave('entries', function ($query){
                 $query->where('user_id', '!=', null);})->with(['entries',
                 'entries.fantasyPlayer' => function($query) {
                     $query->select('id', 'name', 'position', 'tier', 'fps');},
                 'entries.game' => function($query)
                 {$query->select('id', 'homeTeam', 'awayTeam', 'homeScore', 'awayScore', 'time', 'date');}
             ])->select('id', 'entryFee', 'start', 'matchupType', 'status')->inRandomOrder()->get();*/

        return $retval;
    }


    // NOT USED
    public static function getUserMatchupContests($userId)
    {
        \Log::info('Trying to get user matchups for user ' . $userId);
        $contests = Contest::whereHas('entries', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->with(['entries',
            'entries.fantasyPlayer' => function ($query) {
                $query->select('id', 'name', 'position', 'tier', 'fps');
            },
            'entries.game' => function ($query) {
                $query->select('id', 'homeTeam', 'awayTeam', 'homeScore', 'awayScore', 'time', 'date');
            }
        ])->select('id', 'entryFee', 'start', 'matchupType', 'user_id', 'filled', 'status')->where('status', '=', ContestStatusConsts::$CONTEST_STATUS_PENDING)->get();

        foreach ($contests as $contest) {
            $contest["edit"] = false;
            $contest["cancel"] = false;
            if ($contest->user_id && $contest->user_id !== $userId && $contest->matchupType !== MatchTypeConsts::$MATCH_SET_OPPONENT) {
                $contest["edit"] = true;
            } else if ($contest->user_id && $contest->user_id === $userId && !$contest->filled) {
                $contest["cancel"] = true;
            }else if ($contest->admin_contest && !$contest->filled){
                \Log::info('It is admin and it is not filled');
                $contest["cancel"] = true;
            }
        }

        \Log::info('Successfully retrieved user matchups for user' . $userId);
        return $contests;
    }

    public static function getEnterMatchupContests($userId, $groupId)
    {
        /* $groupCounts = [];
        $countGroups = Contest::select('group_id', \DB::raw('count(*) as total'))
            ->where('status', '=', ContestStatusConsts::$CONTEST_STATUS_PENDING)
            ->where('filled', '=' , false)
            ->groupBy('group_id')
            ->get();

        foreach ($countGroups as $group)
        {
            $groupCounts[$group['group_id']] = $group['total'];
        }

        $groupedContests =  array_values(Contest::with('entries')->select('id', 'entryFee', 'group_id')
            ->where('status', '=', ContestStatusConsts::$CONTEST_STATUS_PENDING)
            ->where('filled', '=' , false)->get()
            ->unique('group_id')->groupBy('group_id')->toArray());

        $response = [];

        foreach($groupedContests as $contestGroup)
        {
            $contestGroup = $contestGroup[0];
            $contestGroup['count'] = $groupCounts[$contestGroup['group_id']];
            array_push($response, $contestGroup);
        }
        return $response;*/


        $groupCounts = [];
        $countGroups = Contest::select('group_id', \DB::raw('count(*) as total'))
            ->where('status', '=', ContestStatusConsts::$CONTEST_STATUS_PENDING)
            ->where('filled', '=', false)
            ->groupBy('group_id')
            ->get();

        foreach ($countGroups as $group) {
            $groupCounts[$group['group_id']] = $group['total'];
        }

        if (!$groupId) {
            $groupedContests = array_values(Contest::with(['slate' => function ($query) {
                $query->select('id', 'name', 'firstGame');
            },
                'entries' => function ($query) use ($userId) {
                    $query->select(['*', \DB::raw('user_id IS NULL AS useridnul')])->orderBy('useridnul', 'asc');
                }, 'entries.fantasyPlayer' => function ($query) {
                    $query->select('id', 'name', 'team', 'position', 'tier', 'fps');
                },
                'entries.game' => function ($query) {
                    $query->select('id', 'homeTeam', 'awayTeam', 'homeScore', 'awayScore', 'time', 'date');
                }])
                ->select('id', 'entryFee', 'group_id', 'matchupType', 'status', 'slate_id')
                ->whereDoesntHave('entries', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->whereHas('entries', function($query){
                    $query->whereNotNull('user_id');
                })
                ->whereHas('slate', function ($query) {
                    $query->where('firstGame', '>', DatesHelper::getCurrentDate());
                })
               // ->where('admin_contest', '=', false)
                ->where('status', '=', ContestStatusConsts::$CONTEST_STATUS_PENDING)
                ->where('private', '=', false)
                ->where('filled', '=', false)
                ->orderBy('start', 'ASC')->get()
                ->unique('group_id')->groupBy('group_id')->toArray());
        } else {
            $groupedContests = array_values(Contest::where('group_id', '=', $groupId)->with(['slate' => function ($query) {
                $query->select('id', 'name', 'firstGame');
            },
                'entries' => function ($query) use ($userId) {
                    $query->select(['*', \DB::raw('user_id IS NULL AS useridnul')])->orderBy('useridnul', 'asc');
                }, 'entries.fantasyPlayer' => function ($query) {
                    $query->select('id', 'name', 'team', 'position', 'tier', 'fps');
                },
                'entries.game' => function ($query) {
                    $query->select('id', 'homeTeam', 'awayTeam', 'homeScore', 'awayScore', 'time', 'date');
                }])
                ->select('id', 'entryFee', 'group_id', 'matchupType', 'status', 'slate_id')
                ->whereDoesntHave('entries', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->whereHas('slate', function ($query) {
                    $query->where('firstGame', '>', DatesHelper::getCurrentDate());
                })
                ->where('admin_contest', '=', false)
                ->where('status', '=', ContestStatusConsts::$CONTEST_STATUS_PENDING)
                ->where('filled', '=', false)
                ->orderBy('start', 'ASC')->get()
                ->unique('group_id')->groupBy('group_id')->toArray());
        }

        $response = [];

        
        foreach ($groupedContests as $contestGroup) {
            $contestGroup = $contestGroup[0];
            $contestGroup['count'] = $groupCounts[$contestGroup['group_id']];
            array_push($response, $contestGroup);
        }
        return $response;

    }

    public static function getContestsForWeb(){
       /* return Contest::where('filled', '=', false)
            ->whereIn('status', ['PENDING'])->where('admin_contest', '=', true)->
            with(['entries',
             'entries.fantasyPlayer' => function ($query) {
                $query->select('id', 'name', 'team', 'position', 'tier', 'fps');
            },
                'entries.game' => function ($query) {
                    $query->select('id', 'homeTeam', 'awayTeam', 'homeScore', 'awayScore', 'time', 'date');
                }])->get();*/

        /*$groupedContests = array_values(Contest::with(['slate' => function ($query) {
            $query->select('id', 'name', 'firstGame');
        },
            'entries', 'entries.fantasyPlayer' => function ($query) {
                $query->select('id', 'name', 'team', 'position', 'tier', 'fps');
            },
            'entries.game' => function ($query) {
                $query->select('id', 'homeTeam', 'awayTeam', 'homeScore', 'awayScore', 'time', 'date');
            }])
            ->select('id', 'entryFee', 'group_id', 'matchupType', 'status', 'slate_id')
            ->where('admin_contest', '=', true)
            ->whereHas('slate', function ($query) {
                $query->where('firstGame', '>', DatesHelper::getCurrentDate());
            })
            ->where('status', '=', ContestStatusConsts::$CONTEST_STATUS_PENDING)
            ->where('filled', '=', false)->get()
            ->unique('group_id')->groupBy('group_id')->toArray());

        return $groupedContests;*/

        /*$groupedContests = array_values(Contest::with(['slate' => function ($query) {
            $query->select('id', 'name', 'firstGame');
        },
            'entries', 'entries.fantasyPlayer' => function ($query) {
                $query->select('id', 'name', 'team', 'position', 'tier', 'fps');
            },
            'entries.game' => function ($query) {
                $query->select('id', 'homeTeam', 'awayTeam', 'homeScore', 'awayScore', 'time', 'date');
            }])
            ->select('id', 'entryFee', 'group_id', 'matchupType', 'status', 'slate_id')
            //->where('admin_contest', '=', true)
            //->whereHas('slate', function ($query) {
            //    $query->where('firstGame', '>', DatesHelper::getCurrentDate());
            //})
            //->where('status', '=', ContestStatusConsts::$CONTEST_STATUS_PENDING)
            ->where('filled', '=', true)->get()
            ->unique('group_id')->groupBy('group_id')->toArray());
*/

        \Log::info('Retrieving matchups for web ...');
        $groupedAdminContests = array_values(Contest::with(['slate' => function ($query) {
            $query->select('id', 'name', 'firstGame');
        },
            'entries', 'entries.fantasyPlayer' => function ($query) {
                $query->select('id', 'name', 'team', 'position', 'tier', 'fps');
            },
            'entries.game' => function ($query) {
                $query->select('id', 'homeTeam', 'awayTeam', 'homeScore', 'awayScore', 'time', 'date');
            }])
            ->select('id', 'entryFee', 'group_id', 'matchupType', 'status', 'slate_id', 'admin_contest')
            ->whereDoesntHave('entries', function ($query) {
                $query->where('user_id', '!=', null);
            })
            ->where('admin_contest', '=', true)
            ->whereHas('slate', function ($query) {
                $query->where('firstGame', '>', DatesHelper::getCurrentDate());
            })
            ->where('status', '=', ContestStatusConsts::$CONTEST_STATUS_PENDING)
            ->where('filled', '=', false)
            ->orderBy('start', 'ASC')
            ->inRandomOrder()->get()
            ->unique('group_id')->groupBy('group_id')->toArray());


        $groupedFilledContests = array_values(Contest::with(['slate' => function ($query) {
            $query->select('id', 'name', 'firstGame');
        },
            'entries', 'entries.fantasyPlayer' => function ($query) {
                $query->select('id', 'name', 'team', 'position', 'tier', 'fps');
            },
            'entries.game' => function ($query) {
                $query->select('id', 'homeTeam', 'awayTeam', 'homeScore', 'awayScore', 'time', 'date');
            }])
            ->select('id', 'entryFee', 'group_id', 'matchupType', 'status', 'slate_id', 'admin_contest')
            ->where('admin_contest', '=', false)
            ->where('filled', '=', true)
            ->whereIn('status', array('PENDING', 'LIVE'))
            ->orderBy('start', 'ASC')
            ->inRandomOrder()->get()
            ->unique('group_id')->groupBy('group_id')->toArray());

        \Log::info('Successfully retrieved web matchups ...');

        return array_merge($groupedAdminContests, $groupedFilledContests);

    }
    /*public static function getLobbyContests()
    {
            return Contest::whereHas('entries', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->with('entries')->where('status', '=', $status)->get();

    }*/

}
