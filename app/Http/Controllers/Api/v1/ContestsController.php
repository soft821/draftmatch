<?php

namespace App\Http\Controllers\Api\v1;

use App\Common\Consts\Contest\ContestConsts;
use App\Common\Consts\Contest\ContestStatusConsts;
use App\Common\Consts\Contest\MatchTypeConsts;
use App\Contest;
use App\Entry;
use App\FantasyPlayer;
use App\Helpers\CoinbaseHelper;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Slate;
use App\TimeFrame;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use JWTAuth;

use App\Http\HttpResponse;
use App\Http\HttpStatus;
use App\Http\HttpMessage;
use Mockery\Exception;


class ContestsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    // @todo_haris Do not forget to validate if all params are fine, someone could try to register over post method using rest client
    // @todo_haris Admin can create contest with 2 fantasy players without owner setted up, but no one else
    public function create(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            ContestConsts::$CONTEST_SLATE_ID        => 'required',
            ContestConsts::$CONTEST_ENTRY_FEE       => ['required', Rule::in(ContestConsts::getEntryFees())],
            ContestConsts::$CONTEST_MATCH_TYPE      => ['required', Rule::in(ContestConsts::getMatchTypes())],
            ContestConsts::$CONTEST_TIER            => ['required_if:match_type,==,'.MatchTypeConsts::$MATCH_TYPE_TIER_RANKING,Rule::in(ContestConsts::getTiers())],
            ContestConsts::$CONTEST_USER_PLAYER_ID  => ['required', 'min:6'],
            ContestConsts::$CONTEST_OPP_PLAYER_ID   => ['required_if:match_type,==,'.MatchTypeConsts::$MATCH_SET_OPPONENT, 'min:6'],
            ContestConsts::$CONTEST_NUM_OF_ENTRIES  => 'numeric|min:1',
            ContestConsts::$CONTEST_PRIVATE         => 'boolean'
        ]);

        // if any of validation rules failed, we will fail to create contest
        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$CONTEST_ERROR_CREATING,
                $validator->errors()->all());
        }

        try {
            $user = JWTAuth::toUser($request->token);
        }
        catch (Exception $exception)
        {
            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,
                HttpMessage::$AUTH_INVALID_TOKEN_PROVIDED, $exception->getMessage());
        }

        // admin is only allowed to create set opponent type of contest, because other things does not make sense
        if ($user->isAdmin() && $request->get(ContestConsts::$CONTEST_MATCH_TYPE) !== MatchTypeConsts::$MATCH_SET_OPPONENT) {
            return HttpResponse::serverError(HttpStatus::$ERR_CREATE_CONTEST, HttpMessage::$CONTEST_ADMIN_WRONG_CONTEST_TYPE,
                HttpMessage::$CONTEST_ADMIN_WRONG_CONTEST_TYPE);
        }

        $numOfEntries = 1;
        if ($request->get(ContestConsts::$CONTEST_NUM_OF_ENTRIES)) {
            $numOfEntries = $request->get(ContestConsts::$CONTEST_NUM_OF_ENTRIES);

            if (!$user->isAdmin() && $user->balance * (1.0/CoinbaseHelper::getExchangeRate()) < $numOfEntries * $request->get(ContestConsts::$CONTEST_ENTRY_FEE)) {
                return HttpResponse::serverError(HttpStatus::$ERR_NOT_ENOUGH_FUNDS, HttpMessage::$CONTEST_NOT_ENOUGH_FUNDS,
                    HttpMessage::$CONTEST_NOT_ENOUGH_FUNDS);
            }
        }

        $private = false;
        if ($request->get(ContestConsts::$CONTEST_PRIVATE)){
            $private = true;
        }

        // do not allow more than 10 free contests in pending state per user
        if ($request->get(ContestConsts::$CONTEST_ENTRY_FEE) === 0 && !$user->isAdmin()) {
            $freeContestsCount = $user->getFreePendingContestsCount();
            if ($freeContestsCount > 10) {
                return HttpResponse::serverError(HttpStatus::$ERR_CREATE_CONTEST, HttpMessage::$CONTEST_FREE_CONTESTS_LIMIT,
                    HttpMessage::$CONTEST_FREE_CONTESTS_LIMIT);
            }
        }

        $errors = array();
        $slate = Slate::find($request->get(ContestConsts::$CONTEST_SLATE_ID));
        // slate not found
        if (!$slate) {
            array_push($errors, HttpMessage::$CONTEST_SLATE_NOT_FOUND);
        }
        else {
            // slate not active, it could be that someone through rest tried to call this end point directly, it will never happen through app
            if (!$slate->active) {
                array_push($errors, HttpMessage::$CONTEST_GAME_TIME_NOT_ACTIVE);
            }
        }

        $userPlayer = FantasyPlayer::find($request->get(ContestConsts::$CONTEST_USER_PLAYER_ID));
        // check if specified player exists

        if (!$userPlayer) {
            array_push($errors, HttpMessage::$CONTEST_USER_PLAYER_NOT_FOUND);
        }
        else
        {
            // check if player is in correct slate, again because of possible call directly through rest
            if (!$userPlayer->playerInSlate($slate->id)) {
                array_push($errors, HttpMessage::$CONTEST_USER_PLAYER_NOT_IN_SLATE);
            }
        }

        if ($request->get(ContestConsts::$CONTEST_MATCH_TYPE) === MatchTypeConsts::$MATCH_TYPE_TIER_RANKING) {
            // if contest is tier_ranking and someone tries to specify player from different tier
            if ($request->get(ContestConsts::$CONTEST_TIER) !== $userPlayer->tier) {
                array_push($errors, HttpMessage::$CONTEST_TIER_DO_NOT_MATCH);
            }
        }

        $oppPlayer = null;
        if ($request->get(ContestConsts::$CONTEST_MATCH_TYPE) === MatchTypeConsts::$MATCH_SET_OPPONENT)
        {
            $oppPlayer = FantasyPlayer::find($request->get(ContestConsts::$CONTEST_OPP_PLAYER_ID));
            // if it is set_opponent type, check if opp player exists
            if (!$oppPlayer)
            {
                array_push($errors, HttpMessage::$CONTEST_OPP_PLAYER_NOT_FOUND);
            }
            else
            {
                // check if opponent player is in correct slate
                if (!$oppPlayer->playerInSlate($slate->id))
                {
                    array_push($errors, HttpMessage::$CONTEST_OPP_PLAYER_NOT_IN_SLATE);
                }
            }

            if ($oppPlayer && $userPlayer)
            {
                // check if positions match for both players
                if ($oppPlayer->position !== $userPlayer->position)
                {
                    array_push($errors, HttpMessage::$CONTEST_PLAYER_POSITIONS_DO_NOT_MATCH);
                }

                // same players can't be selected
                if ($oppPlayer->id === $userPlayer->id)
                {
                    array_pull($errors, HttpMessage::$CONTEST_PLAYER_ID_MATCH);
                }
            }
        }

        if (!empty($errors))
        {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$CONTEST_ERROR_CREATING, $errors);
        }

        $tier = null;
        if ($request->get(ContestConsts::$CONTEST_MATCH_TYPE) === MatchTypeConsts::$MATCH_TYPE_TIER_RANKING)
        {
            $tier = $request->get(ContestConsts::$CONTEST_TIER);
        }

        $groupId = $user->id.'_'.$request->get(ContestConsts::$CONTEST_ENTRY_FEE).'_'.$request->get(ContestConsts::$CONTEST_MATCH_TYPE).
            '_'.$request->get(ContestConsts::$CONTEST_SLATE_ID).'_'.$private;

        if ($request->get(ContestConsts::$CONTEST_MATCH_TYPE) === MatchTypeConsts::$MATCH_SET_OPPONENT){
            if ($userPlayer->id > $oppPlayer->id) {
                $groupId = $groupId.'_'.$userPlayer->id.'_'.$oppPlayer->id;
            }
            else{
                $groupId = $groupId.'_'.$oppPlayer->id.'_'.$userPlayer->id;
            }
        }
        else
        {
            $groupId = $groupId.'_'.$userPlayer->id;
        }

        try {
            $user->balance = $user->balance - $numOfEntries * $request->get(ContestConsts::$CONTEST_ENTRY_FEE) * CoinbaseHelper::getExchangeRate();
            $user->save();
        }
        catch (Exception $e)
        {
            return HttpResponse::serverError(HttpStatus::$ERR_CREATE_CONTEST, HttpMessage::$CONTEST_ERROR_CREATING, $e->getMessage());
        }

        try {
            $contestsData = [];
            $entriesData = [];

            $timestamp = microtime(true);

            for ($i = 0; $i < $numOfEntries; $i++) {
                array_push($contestsData, [
                    'id'          => $timestamp.'_'.$user->id.'_'.$i,
                    'matchupType' => $request->get(ContestConsts::$CONTEST_MATCH_TYPE),
                    'slate_id'    => $request->get(ContestConsts::$CONTEST_SLATE_ID),
                    'type'        => 'H2H',
                    'tier'        => $tier,
                    'size'        => 2,
                    'entryFee'    => $request->get(ContestConsts::$CONTEST_ENTRY_FEE),
                    'position'    => $userPlayer->position,
                    'status'      => ContestStatusConsts::$CONTEST_STATUS_PENDING,
                    'start'       => $slate->firstGame,
                    'filled'      => false,
                    'user_id'     => $user->id,
                    'group_id'    => $groupId,
                    'private'     => $private,
                    'admin_contest' => $user->isAdmin() ? true:false
                ]);

                //if (!$user->isAdmin()) {
                    array_push($entriesData, [
                        'contest_id'        => $timestamp . '_' . $user->id . '_' . $i,
                        'slate_id'          => $slate->id,
                        'user_id'           => $user->isAdmin() ? null : $user->id,
                        'username'          => $user->isAdmin() ? null : $user->username,
                        'fantasy_player_id' => $userPlayer->id,
                        'game_id'           => $userPlayer->game_id,
                        'owner'             => $user->isAdmin() ? false : true
                    ]);

                    if ($oppPlayer) {
                        array_push($entriesData, [
                            'contest_id'        => $timestamp . '_' . $user->id . '_' . $i,
                            'slate_id'          => $slate->id,
                            'fantasy_player_id' => $oppPlayer->id,
                            'user_id'           => null,
                            'username'          => null,
                            'game_id'           => $oppPlayer->game_id,
                            'owner'             => false
                        ]);
                    }
               // }
            }

            $contestInserted = Contest::insert($contestsData);
            \Log::info('Inserted contests '.$contestInserted);
            if (!$contestInserted)
            {
                if (!$user->isAdmin()) {
                    $user->balance = $user->balance + $numOfEntries * $request->get(ContestConsts::$CONTEST_ENTRY_FEE) * CoinbaseHelper::getExchangeRate();
                    $user->save();
                }
                return HttpResponse::serverError(HttpStatus::$ERR_CREATE_CONTEST, HttpMessage::$CONTEST_ERROR_CREATING,
                    HttpMessage::$CONTEST_ERROR_CREATING);
            }

            //if (!$user->isAdmin()) {
                $entriesUpdates = Entry::insert($entriesData);
                if (!$entriesUpdates) {
                    Contest::where('id', 'like', '%' . $timestamp . '_' . $user->id . '%')->update(['status' => ContestStatusConsts::$CONTEST_STATUS_ERROR]);
                    $user->balance = $user->balance + $numOfEntries * $request->get(ContestConsts::$CONTEST_ENTRY_FEE) * CoinbaseHelper::getExchangeRate();
                    $user->save();
                    //Contest::updateOrCreate(array('id' => $contest->id), ["status" => ContestStatusConsts::$CONTEST_STATUS_ERROR]);
                    return HttpResponse::serverError(HttpStatus::$ERR_CREATE_USER_ENTRY, HttpMessage::$CONTEST_USER_ENTRY_ERROR_CREATING,
                        HttpMessage::$CONTEST_USER_ENTRY_ERROR_CREATING);
                }
           // }
            /*$contest = Contest::create([
                'matchupType' => $request->get(ContestConsts::$CONTEST_MATCH_TYPE),
                'slate_id'    => $request->get(ContestConsts::$CONTEST_GAME_TIME_ID),
                'type'        => 'H2H',
                'tier'        => $tier,
                'size'        => 2,
                'entryFee'    => $request->get(ContestConsts::$CONTEST_ENTRY_FEE),
                'position'    => $userPlayer->position,
                'status'      => ContestStatusConsts::$CONTEST_STATUS_PENDING,
                'filled'      => false,
                'user_id'     => $user->id,
                'group_id'    => $groupId
            ]);*/


        }
        catch (Exception $exception)
        {
            return HttpResponse::serverError(HttpStatus::$ERR_CREATE_CONTEST, HttpMessage::$CONTEST_ERROR_CREATING,
                $exception->getMessage());
        }

        /*try {
            Entry::create([
                'contest_id' => $contest->id,
                'user_id' => $user->isAdmin()? null:$user->id,
                'fantasy_player_id' => $userPlayer->id,
                'owner' => $user->isAdmin()? false:true
            ]);
        }
        catch (Exception $exception)
        {
            Contest::updateOrCreate(array('id' => $contest->id), ["status" => ContestStatusConsts::$CONTEST_STATUS_ERROR]);
            return HttpResponse::serverError(HttpStatus::$ERR_CREATE_USER_ENTRY, HttpMessage::$CONTEST_USER_ENTRY_ERROR_CREATING);
        }

        try
        {
            if ($oppPlayer) {
                Entry::create([
                    'contest_id' => $contest->id,
                    'fantasy_player_id' => $oppPlayer->id,
                    'owner' => false
                ]);
            }
        }
        catch (Exception $exception)
        {
            Contest::updateOrCreate(array('id' => $contest->id), ["status" => ContestStatusConsts::$CONTEST_STATUS_ERROR]);
            return HttpResponse::serverError(HttpStatus::$ERR_CREATE_OPP_ENTRY, HttpMessage::$CONTEST_OPP_ENTRY_ERROR_CREATING);
        }
*/
        return HttpResponse::ok(HttpMessage::$CONTEST_CREATED_SUCCESSFULLY, array('group_id' => $groupId));
    }

    public function enter(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            ContestConsts::$CONTEST_ID           => 'required_without_all:group_id',
            ContestConsts::$GROUP_ID             => 'required_without_all:contest_id',
            ContestConsts::$CONTEST_PLAYER_ID    => 'required'  // todo _Haris for set_opponent we do not need player_id
        ]);

        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$CONTEST_ERROR_REGISTERING,
                $validator->errors()->all());
        }

        try {
            $user = JWTAuth::toUser($request->token);
        }
        catch (Exception $exception)
        {
            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,
                HttpMessage::$AUTH_INVALID_TOKEN_PROVIDED, $exception->getMessage());
        }

        if ($user->isAdmin())
        {
            return HttpResponse::serverError(HttpStatus::$ERR_REGISTER_CONTEST, HttpMessage::$CONTEST_ADMIN_REGISTER_ERROR,
                HttpMessage::$CONTEST_ADMIN_REGISTER_ERROR);
        }

        if ($request->get(ContestConsts::$CONTEST_ID)) {
            $contest = Contest::with('entries')->find($request->get(ContestConsts::$CONTEST_ID));
        }
        else
        {
            $contest = Contest::getContestsByGroupId($request->get(ContestConsts::$GROUP_ID));
        }

        $player = FantasyPlayer::find($request->get(ContestConsts::$CONTEST_PLAYER_ID));

        $errors = $this->validateEntry($user, $contest, $player, null, false);

        if (!empty($errors))
        {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$CONTEST_ERROR_REGISTERING, $errors);
        }

        if ($user->balance * (1.0/CoinbaseHelper::getExchangeRate()) < $contest->entryFee)
        {
            return HttpResponse::serverError(HttpStatus::$ERR_NOT_ENOUGH_FUNDS, HttpMessage::$CONTEST_ENTRY_NOT_ENOUGH_FUNDS,
                HttpMessage::$CONTEST_ENTRY_NOT_ENOUGH_FUNDS);
        }

        $contest = $contest->fresh();
        // check if contest is filled
        if ($contest->filled)
        {
            return HttpResponse::serverError(HttpStatus::$ERR_REGISTER_CONTEST, HttpMessage::$CONTEST_FULL_ERROR,
                HttpMessage::$CONTEST_FULL_ERROR);
        }

        if ($contest->matchupType === MatchTypeConsts::$MATCH_TYPE_TIER_RANKING ||
            $contest->matchupType === MatchTypeConsts::$MATCH_TYPE_ANY_CHALLENGER)
        {
            // @todo _haris what if create entry was successfull and update contest failed???
            try {
                Entry::create([
                    'contest_id'        => $contest->id,
                    'slate_id'          => $contest->slate_id,
                    'user_id'           => $user->id,
                    'fantasy_player_id' => $player->id,
                    'game_id'           => $player->game_id,
                    'username'          => $user->username,
                    'owner'             => false
                ]);
                $user->balance = $user->balance - $contest->entryFee * CoinbaseHelper::getExchangeRate();
                $user->save();
                $contest->filled = true;
                $contest->save();
            }
            catch (Exception $exception)
            {
                return HttpResponse::serverError(HttpStatus::$ERR_REGISTER_CONTEST,
                    HttpMessage::$CONTEST_ERROR_REGISTERING, $exception->getMessage());
            }
        }
        else
        {
            try {
                $index = 0;

                if ($contest->admin_contest === true){
                    if ($contest->entries[0]->user_id === null && $contest->entries[1]->user_id === null){
                        $exploded = explode("_", $contest->group_id);
                        $exploded[0] = $user->id;
                        $contest->group_id = implode("_", $exploded);
                        $contest->user_id = $user->id;
                        
                    }
                }

                if ($contest->entries[1]->fantasyPlayer->id === $player->id)
                {
                    $index = 1;
                }
                $contest->entries[$index]->user_id = $user->id;
                $contest->entries[$index]->username = $user->username;
                $contest->entries[$index]->save();

                if ($contest->entries[0]->user_id !== null && $contest->entries[1]->user_id !== null) {
                    $contest->filled = true;
                }
                $contest->save();
                $user->balance = $user->balance - $contest->entryFee * CoinbaseHelper::getExchangeRate();
                $user->save();
            }
            catch (Exception $exception)
            {
                return HttpResponse::serverError(HttpStatus::$ERR_REGISTER_CONTEST,
                    HttpMessage::$CONTEST_ERROR_REGISTERING, $exception->getMessage());
            }
        }

        return HttpResponse::ok(HttpMessage::$CONTEST_ENTRY_SUCCESSFULLY, $contest);
    }

    public function editEntry(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            ContestConsts::$ENTRY_ID          => 'required',
            ContestConsts::$CONTEST_PLAYER_ID => 'required']);

        // if any of validation rules failed, we will fail to create contest
        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$CONTEST_ERROR_EDIT,
                $validator->errors()->all());
        }

        try {
            $user = JWTAuth::toUser($request->token);
        } catch (Exception $exception) {
            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,
                HttpMessage::$AUTH_INVALID_TOKEN_PROVIDED, HttpMessage::$AUTH_INVALID_TOKEN_PROVIDED);
        }

        $errors = array();

        try {
            $entry  = Entry::find($request->get(ContestConsts::$ENTRY_ID));
            $player = FantasyPlayer::find($request->get(ContestConsts::$CONTEST_PLAYER_ID));

            if (!$entry)
            {
                array_push($errors, HttpMessage::$CONTEST_ERROR_EDIT_NO_ENTRY);
            }
            if ($entry->owner)
            {
                array_push($errors, HttpMessage::$CONTEST_ERROR_EDIT_OWNER_ENTRY);
            }
            if ($entry->contest->matchupType === MatchTypeConsts::$MATCH_SET_OPPONENT)
            {
                array_push($errors, HttpMessage::$CONTEST_ERROR_EDIT_SET_OPPONENT);
            }
            if ($entry->user->id !== $user->id)
            {
                array_push($errors, HttpMessage::$CONTEST_ERROR_EDIT_NOT_OWNER);
            }

            if (!empty($errors))
            {
                return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$CONTEST_ERROR_EDIT, $errors);
            }

            $errors = $this->validateEntry($user, $entry->contest, $player,true);
            if (!empty($errors))
            {
                return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$CONTEST_ERROR_EDIT, $errors);
            }

            $entry->fantasy_player_id = $request->get(ContestConsts::$CONTEST_PLAYER_ID);
            $entry->save();
        }
        catch (QueryException $e) {
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$CONTEST_ERROR_EDIT, $e->getMessage());
        }
        catch (Exception $e) {
            return HttpResponse::serverError(HttpStatus::$ERR_EDIT_ENTRY, HttpMessage::$CONTEST_ERROR_EDIT, $e->getMessage());
        }

        return HttpResponse::ok(HttpMessage::$CONTEST_ENTRY_EDITED, null);
    }

    private function validateEntry($user, $contest, $player, $edit)
    {
        $errors = array();
        // check if specified conetst exists
        if (!$contest)
        {
            array_push($errors, HttpMessage::$CONTEST_NOT_FOUND_ERROR);
        }
        else
        {
            // check if contest is filled
            if ($contest->filled && !$edit)
            {
                array_push($errors, HttpMessage::$CONTEST_FULL_ERROR);
            }
            // check is status is correct
            if ($contest->status !== ContestStatusConsts::$CONTEST_STATUS_PENDING)
            {
                array_push($errors, HttpMessage::$CONTEST_WRONG_STATUS);
            }
        }

        // check if player specified exists
        if (!$player)
        {
            array_push($errors, HttpMessage::$CONTEST_PLAYER_NOT_FOUND_ERROR);
        }

        if ($player && $contest)
        {
            // check if player is in correct slate
            if (!$player->playerInSlate(($contest->slate_id)))
            {
                array_push($errors, HttpMessage::$CONTEST_PLAYER_NOT_IN_SLATE);
            }

            // check if player is in correct tier
            if ($contest->matchupType === MatchTypeConsts::$MATCH_TYPE_TIER_RANKING && $player->tier !== $contest->tier)
            {
                array_push($errors, HttpMessage::$CONTEST_PLAYER_WRONG_TIER_ERROR);
            }

            // check if player is in correct position
            if ($player->position !== $contest->position)
            {
                array_push($errors, HttpMessage::$CONTEST_PLAYER_POSITIONS_DO_NOT_MATCH);
            }

            $entries = $contest->entries()->getResults();
            // check if player specified is different then player which is already used when contest is created
            if ($contest->matchupType === MatchTypeConsts::$MATCH_TYPE_TIER_RANKING ||
                $contest->matchupType === MatchTypeConsts::$MATCH_TYPE_ANY_CHALLENGER) {
                $use_index = 0;
                if ($entries->get(0)->user_id === $user->id) {
                    if (!$edit){
                        array_push($errors, HttpMessage::$CONTEST_USER_ALREADY_REGISTERED);
                    }
                    $use_index = 1;
                }
                if ($entries->get($use_index)->fantasy_player_id === $player->id) {
                    array_push($errors, HttpMessage::$CONTEST_PLAYER_ID_MATCH);
                }
                if ($entries->get($use_index)->user_id === $user->id) {
                    array_push($errors, HttpMessage::$CONTEST_USER_ALREADY_REGISTERED);
                }
            }
            else
            {
                // check if in set_opponent mode passed player id exists
                if ($entries->get(0)->fantasy_player_id !== $player->id &&
                    $entries->get(1)->fantasy_player_id !== $player->id)
                {
                    array_push($errors, HttpMessage::$CONTEST_SET_OPPONENT_WRONG_PLAYER);
                }
                if (($entries->get(0)->fantasy_player_id === $player->id && $entries->get(0)->user_id !== null) ||
                    ($entries->get(1)->fantasy_player_id === $player->id && $entries->get(1)->user_id !== null))
                {
                    array_push($errors, HttpMessage::$CONTEST_SELECTED_PLAYER_ALREADY_TAKEN);
                }

                if ($entries->get(0)->user_id !== null && $entries->get(0)->user_id === $user->id)
                {
                    array_push($errors, HttpMessage::$CONTEST_USER_ALREADY_REGISTERED);
                }
                else if ($entries->get(1)->user_id !== null && $entries->get(1)->user_id === $user->id)
                {
                    array_push($errors, HttpMessage::$CONTEST_USER_ALREADY_REGISTERED);
                }
            }
        }

        return $errors;
    }

    public function getContests(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            ContestConsts::$CONTEST_STATUS   => ['required', Rule::in(array('LIVE', 'HISTORY', 'ENTER_MATCHUP', 'MATCHUPS', 'LOBBY'))],
            ContestConsts::$GROUP_ID]);

        // if any of validation rules failed, we will fail to create contest
        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$SLATES_ERROR_RETRIEVING, $validator->errors()->all());
        }

        try {
            $user = JWTAuth::toUser($request->token);
        }
        catch (Exception $exception) {
            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,
                HttpMessage::$AUTH_INVALID_TOKEN_PROVIDED, HttpMessage::$AUTH_INVALID_TOKEN_PROVIDED);
        }

        $userInfo = null;
        try {
            if ($request->get(ContestConsts::$CONTEST_STATUS) === 'LIVE') {
                $contests = Slate::getUserLiveContests($user->id);
                $entries = 0; $winning = 0; $totalEntry = 0;
                foreach ($contests as $slate) {
                    foreach ($slate->contests as $contest) {
                        $entries++;
                        $totalEntry += $contest->entryFee;
                        $winning += $contest->entries[0]->winning;
                    }
                }
                $userInfo = ["balance" => round($user->balance * CoinbaseHelper::getExchangeRate(), 2), "wins" => $user->wins, "loses" => $user->loses,
                             "entries" => $entries, "totalEntry" => $totalEntry, "winning" => $winning];
            }
            else if ($request->get(ContestConsts::$CONTEST_STATUS) === 'HISTORY') {
                $contests = Contest::getUserHistoryContests($user->id);
                $userInfo = ["balance" => $user->balance, "wins" => $user->wins, "loses" => $user->loses,
                             "entries" => $user->history_count, "totalEntry" => $user->history_entry, "winning" => $user->history_winning];
            }
            else if ($request->get(ContestConsts::$CONTEST_STATUS) === 'MATCHUPS') {
                $entries = 0; $totalEntry = 0;
                $contests = Slate::getUserMatchupContests($user->id);
                foreach ($contests as $slate) {
                    foreach ($slate->contests as $contest) {
                        $entries++;
                        $totalEntry += $contest->entryFee;
                    }
                }
                $userInfo = ["balance" => round($user->balance * CoinbaseHelper::getExchangeRate(), 2), "wins" => $user->wins, "loses" => $user->loses,
                    "entries" => $entries, "totalEntry" => $totalEntry];
            }
            else if($request->get(ContestConsts::$CONTEST_STATUS) === 'LOBBY') {
                $contests = Contest::getAdminContests();
                $timeFrame = TimeFrame::getCurrentTimeFrame();
                $userInfo = ['week' => $timeFrame->week];
            }
            else {
                $contests = Contest::getEnterMatchupContests($user->id, $request->get(ContestConsts::$GROUP_ID));

            }
        }
        catch (Exception $e) {
            return HttpResponse::serverError(HttpStatus::$ERR_RETRIEVE_CONTEST, HttpMessage::$SLATES_ERROR_RETRIEVING,
                $e->getMessage());
        }


        return HttpResponse::ok(HttpMessage::$CONTEST_SUCCESSFULLY_RETRIEVED, $contests, $userInfo);
    }

    public function cancelContest(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            ContestConsts::$CONTEST_ID => 'required']);

        // if any of validation rules failed, we will fail to create contest
        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$CONTEST_ERROR_CANCELING,
                $validator->errors()->all());
        }

        try {
            $user = JWTAuth::toUser($request->token);
        } catch (Exception $exception) {
            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,
                HttpMessage::$AUTH_INVALID_TOKEN_PROVIDED, HttpMessage::$AUTH_INVALID_TOKEN_PROVIDED);
        }

        $errors = array();

        try {
            $contest = Contest::find($request->get(ContestConsts::$CONTEST_ID));
            if ($contest->filled) {
                array_push($errors, HttpMessage::$CONTEST_ERROR_CANCELING_FILLED);
            }
            if ($contest->user_id !== $user->id)
            {
                array_push($errors, HttpMessage::$CONTEST_ERROR_CANCELING_OWNER);
            }

            if (!empty($errors))
            {
                return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$CONTEST_ERROR_CANCELING, $errors);
            }

            $contest->status = ContestStatusConsts::$CONTEST_STATUS_CANCELLED;
            $user->balance = $user->balance + $contest->entryFee * CoinbaseHelper::getExchangeRate();
            $user->save();
            $contest->save();
        }
        catch (QueryException $e) {
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$CONTEST_ERROR_CANCELING, $e->getMessage());
        }
        catch (Exception $e) {
            return HttpResponse::serverError(HttpStatus::$ERR_CANCELING_CONTEST, HttpMessage::$CONTEST_ERROR_CANCELING,
                $e->getMessage());
        }

        return HttpResponse::ok(HttpMessage::$CONTEST_CANCELED_SUCCESSFULLY, null);
    }

    public function getContestsForWeb()
    {
        $timeFrame = TimeFrame::getCurrentTimeFrame();
        $userInfo = ['week' => $timeFrame->week];
        $contests = Contest::getContestsForWeb();
        return HttpResponse::ok(HttpMessage::$CONTEST_SUCCESSFULLY_RETRIEVED, $contests, $userInfo);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Contest  $contest
     * @return \Illuminate\Http\Response
     */
    public function show(Contest $contest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Contest  $contest
     * @return \Illuminate\Http\Response
     */
    public function edit(Contest $contest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Contest  $contest
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Contest $contest)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Contest  $contest
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contest $contest)
    {
        //
    }


}
