<?php

use Illuminate\Database\Seeder;
use App\Entry;
use App\Contest;
use App\FantasyPlayer;
use App\Slate;
use App\User;
use App\Common\Consts\Contest\ContestStatusConsts;
use App\Common\Consts\Contest\MatchTypeConsts;

class ContestsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       // for ($i = 0; $i < 20; $i++)
      //  {
            $slate = Slate::inRandomOrder()->get()->first();
        //    $players = $slate->fantasyPlayers->where('position', '=', 'QB')->paginate(2);

        $contest = Contest::updateOrCreate([
            'id'          => '1',
            'matchupType' => MatchTypeConsts::$MATCH_TYPE_ANY_CHALLENGER,
            'slate_id'    => $slate->id,
            'type'        => 'H2H',
            'tier'        => null,
            'size'        => 2,
            'entryFee'    => 2,
            'position'    => 'WR',
            'start'       => $slate->firstGame,
            'status'      => $slate->status,
            'filled'      => true,
            'user_id'     => '2',
            'group_id'    => '2_2_any_challenger_Sun_2017_2_3_mpb-player-9683_2017_0_3'
        ]);

        $player = FantasyPlayer::find('mpb-player-9683_2017_0_3');
        $user = User::find(2);
        Entry::create([
            'contest_id'        => $contest->id,
            'slate_id'          => $slate->id,
            'user_id'           => '2',
            'username'          => $user->username,
            'fantasy_player_id' => 'mpb-player-9683_2017_0_3',
            'game_id'           => $player->game_id,
            'owner'             => true,
        ]);

        $user = User::find(3);
        $player = FantasyPlayer::find('mpb-player-11616_2017_0_3');
        Entry::create([
            'contest_id'        => $contest->id,
            'slate_id'          => $slate->id,
            'user_id'           => '3',
            'username'          => $user->username,
            'fantasy_player_id' => 'mpb-player-11616_2017_0_3',
            'game_id'           => $player->game_id,
            'owner'             => false,
        ]);


        $contest = Contest::updateOrCreate([
            'id'          => '2',
            'matchupType' => MatchTypeConsts::$MATCH_TYPE_ANY_CHALLENGER,
            'slate_id'    => 'Sun_2017_2_3',
            'type'        => 'H2H',
            'tier'        => null,
            'size'        => 2,
            'entryFee'    => 2,
            'position'    => 'WR',
            'start'       => $slate->firstGame,
            'status'      => ContestStatusConsts::$CONTEST_STATUS_FINISHED,
            'filled'      => true,
            'user_id'     => '3',
            'group_id'    => '3_2_any_challenger_Sun_2017_2_3_mpb-player-9683_2017_0_3'
        ]);

        $player = FantasyPlayer::find('mpb-player-9683_2017_0_3');
        $user = User::find(2);
        Entry::create([
            'contest_id'        => $contest->id,
            'slate_id'          => $slate->id,
            'user_id'           => '3',
            'username'          => $user->username,
            'fantasy_player_id' => 'mpb-player-9683_2017_0_3',
            'game_id'           => $player->game_id,
            'owner'             => true,
        ]);

        $user = User::find(3);
        $player = FantasyPlayer::find('mpb-player-11616_2017_0_3');
        Entry::create([
            'contest_id'        => $contest->id,
            'slate_id'          => $slate->id,
            'user_id'           => '2',
            'username'          => $user->username,
            'fantasy_player_id' => 'mpb-player-11616_2017_0_3',
            'game_id'           => $player->game_id,
            'owner'             => false,
        ]);

        $slate = Slate::find('Sun_2017_2_3');
        $contest = Contest::updateOrCreate([
            'id'            => '3',
            'matchupType'   => MatchTypeConsts::$MATCH_SET_OPPONENT,
            'slate_id'      => 'Sun_2017_2_3',
            'type'          => 'H2H',
            'tier'          => null,
            'size'          => 2,
            'entryFee'      => 2,
            'position'      => 'QB',
            'start'         => $slate->firstGame,
            'status'        => ContestStatusConsts::$CONTEST_STATUS_PENDING,
            'filled'        => false,
            'user_id'       => '1',
            'admin_contest' => true,
            'group_id'      => '1_2_set_opponent_Sun_2017_2_3_mpb-player-11172_2017_0_3_mpb-player-11180_2017_0_3'
        ]);

        $player = FantasyPlayer::find('mpb-player-11172_2017_0_3');
        $user = User::find(2);
        Entry::create([
            'contest_id'        => $contest->id,
            'slate_id'          => $slate->id,
            'user_id'           => null,
            'username'          => null,
            'fantasy_player_id' => 'mpb-player-11172_2017_0_3',
            'game_id'           => $player->game_id,
            'owner'             => false,
        ]);

        $user = User::find(3);
        $player = FantasyPlayer::find('mpb-player-11180_2017_0_3');
        Entry::create([
            'contest_id'        => $contest->id,
            'slate_id'          => $slate->id,
            'user_id'           => null,
            'username'          => null,
            'fantasy_player_id' => 'mpb-player-11180_2017_0_3',
            'game_id'           => $player->game_id,
            'owner'             => false,
        ]);

        $slate = Slate::find('Sun_2017_2_3');
        $contest = Contest::updateOrCreate([
            'id'            => '4',
            'matchupType'   => MatchTypeConsts::$MATCH_SET_OPPONENT,
            'slate_id'      => 'Sun_2017_2_3',
            'type'          => 'H2H',
            'tier'          => null,
            'size'          => 2,
            'entryFee'      => 2,
            'position'      => 'QB',
            'start'         => $slate->firstGame,
            'status'        => ContestStatusConsts::$CONTEST_STATUS_LIVE,
            'filled'        => false,
            'user_id'       => 3,
            'group_id'      => '3_2_set_opponent_Sun_2017_2_3_mpb-player-11172_2017_0_3_mpb-player-11172_2017_0_3'

        ]);

        $player = FantasyPlayer::find('mpb-player-11172_2017_0_3');

        $user = User::find(3);
        $player = FantasyPlayer::find('mpb-player-11180_2017_0_3');
        Entry::create([
            'contest_id'        => $contest->id,
            'slate_id'          => $slate->id,
            'user_id'           => $user->id,
            'username'          => $user->username,
            'fantasy_player_id' => 'mpb-player-11180_2017_0_3',
            'game_id'           => $player->game_id,
            'owner'             => true,
        ]);

        $user = User::find(2);
        Entry::create([
            'contest_id'        => $contest->id,
            'slate_id'          => $slate->id,
            'user_id'           => $user->id,
            'username'          => $user->username,
            'fantasy_player_id' => 'mpb-player-11172_2017_0_3',
            'game_id'           => $player->game_id,
            'owner'             => false,
        ]);



        /*$user = new User();
        $user->create([
            'name' => 'admin',
            'email' => 'admin@draftmatch.com',
            'password' => bcrypt('admin123'),
            'username' => 'admin',
            'role' => UserRoleConsts::$ADMIN
        ]);

        $user = new User();
        $user->create([
            'name' => 'user1',
            'email' => 'user1@draftmatch.com',
            'password' => bcrypt('user123'),
            'username' => 'user1',
            'role' => UserRoleConsts::$USER
        ]);

        $user->create([
            'name' => 'user2',
            'email' => 'user2@draftmatch.com',
            'password' => bcrypt('user123'),
            'username' => 'user2',
            'role' => UserRoleConsts::$USER
        ]);*/
    }
}
