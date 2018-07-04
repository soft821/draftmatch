<?php

namespace App;

use App\Common\Consts\Contest\ContestStatusConsts;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    use Notifiable;
    use CanResetPassword;
    use HasPushSubscriptions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'username', 'role'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function contests()
    {
        return $this->hasMany(Contest::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function getHistoryEntryAmount()
    {
        return $this->contests()->sum('entryFee');
    }

    public function getLiveContestsInfo($userId)
    {
        return User::where('id', '=', $userId)->with('entries')->selectRaw('users.entries.*, sum(users.entries.entryFee) as total_amount')
            ->withCount('entries')->get();//entries()->whereHas('contest', function ($query){$query->where('status', '=', 'HISTORY');})
        //->withCount()->sum('entryFee')->get();
    }

    public function getFreePendingContestsCount()
    {
        return $this->contests()
            ->where('status', '=', ContestStatusConsts::$CONTEST_STATUS_PENDING)
            ->where('entryFee', '=', 0)
            ->count();
    }

    public function getContests()
    {
        return $this->contests()->with(array('entries'=>function($query){
            $query->select('contest_id', 'winning');}))->select('id', 'matchupType', 'entryFee')->get();
    }

    public static function getAllUsers(){
        return User::where('username', '!=', 'admin')->select('username', 'wins', 'loses', 'history_winning', 'history_count')->get();
    }

    /*public function getUserLiveContests()
    {
        return $this->contests()->with(['entries',
            'entries.fantasyPlayer' => function($query) {
            $query->select('id', 'name', 'position', 'tier', 'fps');},
            'entries.game' => function($query)
        {$query->select('id', 'homeTeam', 'awayTeam', 'homeScore', 'awayScore', 'time', 'date');}
        ])->select('id', 'entryFee', 'start')->where('status', '=', ContestStatusConsts::$CONTEST_STATUS_LIVE)->get();
    }

    public function getUserHistoryContests()
    {
        return $this->contests()->with(['entries',
            'entries.fantasyPlayer' => function($query) {
                $query->select('id', 'name', 'position', 'tier', 'fps');},
            'entries.game' => function($query)
            {$query->select('id', 'homeTeam', 'awayTeam', 'homeScore', 'awayScore', 'time', 'date');}
        ])->select('id', 'entryFee', 'start')->where('status', '=', ContestStatusConsts::$CONTEST_STATUS_FINISHED)->get();
    }
    */



    public static function getUsers($userName, $status)
    {
        $builder = User::query();
        if ($userName)
        {
            $builder->where('username', '=', $userName);
        }

        if ($status)
        {
            $builder->where('status', '=', $status);
        }

        $builder->orderBy('id', 'asc');

        return $builder->get();
    }
}
