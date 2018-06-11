<?php

namespace App\Http\Controllers\Api\v1;

use App\Game;
use App\Slate;
use App\TimeFrame;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\HttpResponse;
use App\Http\HttpStatus;
use App\Http\Requests;
use App\Http\HttpMessage;
use Mockery\Exception;
use Illuminate\Validation\Rule;
use App\Common\Consts\Contest\ContestConsts;
use App\Common\Consts\Slate\SlateConsts;

class SlatesController extends Controller
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
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Slate  $slate
     * @return \Illuminate\Http\Response
     */
    public function show(Slate $slate)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Slate  $slate
     * @return \Illuminate\Http\Response
     */
    public function edit(Slate $slate)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Slate  $slate
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Slate $slate)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Slate  $slate
     * @return \Illuminate\Http\Response
     */
    public function destroy(Slate $slate)
    {
        //
    }

    public function getSlates()
    {
        \Log::info("Getting info about slates ...");
        try{
            $slates = Slate::getNonEmptySlates();
        }
        catch (QueryException $e){
            \Log::info("Errrr ".$e->getMessage());
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$SLATES_ERROR_RETRIEVING, $e->getMessage());
        }
        catch (Exception $exception){
            \Log::info("Errrr ".$exception->getMessage());
            return HttpResponse::serverError(HttpStatus::$ERR_SLATES_RETRIEVE, HttpMessage::$SLATES_ERROR_RETRIEVING,
                $exception->getMessage());
        }

        return HttpResponse::ok(HttpMessage::$SLATES_SUCCESSFULLY_RETRIEVED, $slates);
    }

    public function getAdminSlates()
    {
        \Log::info("Getting info about admin slates ...");
        try{
            $slates = Slate::getNonEmptyAdminSlates();
        }
        catch (QueryException $e){
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$SLATES_ERROR_RETRIEVING, $e->getMessage());
        }
        catch (Exception $exception){
            return HttpResponse::serverError(HttpStatus::$ERR_SLATES_RETRIEVE, HttpMessage::$SLATES_ERROR_RETRIEVING,
                $exception->getMessage());
        }

        return HttpResponse::ok(HttpMessage::$SLATES_SUCCESSFULLY_RETRIEVED, $slates);
    }

    public function getSlatePlayers(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'slate_id'    => 'required',
            'position'    => ['required', Rule::in(ContestConsts::getPositions())],
            'tier'        => ['required', Rule::in(ContestConsts::getTiers())],
        ]);

        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$SLATES_ERROR_RETRIEVING,
                $validator->errors()->all());
        }

        try{
            $result = Slate::getSlatePlayers($request->get('slate_id'), $request->get('position'), $request->get('tier'));
        }
        catch (QueryException $e){
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$SLATES_ERROR_RETRIEVING, $e->getMessage());
        }
        catch (Exception $exception){
            return HttpResponse::serverError(HttpStatus::$ERR_FANTASY_PLAYERS_RETRIEVE, HttpMessage::$SLATES_ERROR_RETRIEVING,
                $exception->getMessage());
        }

        return HttpResponse::ok(HttpMessage::$SLATES_SUCCESSFULLY_RETRIEVED, $result);
    }

    public function createSlate(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            SlateConsts::$SLATE_NAME => 'required',
            SlateConsts::$GAMES      => 'required|array'
        ]);

        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$SLATE_ERROR_CREATING,
                $validator->errors()->all());
        }

        $timeframe = TimeFrame::getCurrentTimeFrame();
        $key = $timeframe->retrieveKey();

        $name_for_key = str_replace(' ', '_', $request->get(SlateConsts::$SLATE_NAME));
        $name = $request->get(SlateConsts::$SLATE_NAME).'_'.$key;
        $id = $name_for_key.'_'.$key;
        $slate_exists = Slate::find($id);

        if ($slate_exists)
        {
            return HttpResponse::serverError(HttpStatus::$ERR_SLATE_CREATE, HttpMessage::$SLATE_ERROR_CREATING_EXISTS,
                HttpMessage::$SLATE_ERROR_CREATING_EXISTS);
        }

        $games = [];$game_ids = [];
        foreach ($request->get(SlateConsts::$GAMES) as $game_id)
        {
            $game = Game::find($game_id);
            if ($game && !in_array($game_id, $game_ids) && $game->status === 'PENDING')
            {
                array_push($games, $game);
                array_push($game_ids, $game_id);
            }
        }

        if (!$games || count($games) === 0)
        {
            return HttpResponse::serverError(HttpStatus::$ERR_SLATE_CREATE, HttpMessage::$SLATE_ERROR_NO_GAMES,
                HttpMessage::$SLATE_ERROR_NO_GAMES);
        }

        try{
            $slate = Slate::create(["id" => $id, "name" => $request->get(SlateConsts::$SLATE_NAME),
                "firstDay" => "Thu", "lastDay" => "Mon",
                "active"   => false]);

            $slate->games()->syncWithoutDetaching($game_ids);

            foreach ($games as $game){
                $gameNew = Game::find($game->id);
                $ids = [];

                foreach($gameNew->fantasyPlayers()->get() as $player){
                    array_push($ids, $player->id);
                }
                $slate->fantasyPlayers()->syncWithoutDetaching($ids);

            }

            $slate->firstGame = $slate->firstGameDate();
            $slate->lastGame = $slate->lastGameDate();

            $slate->firstDay = $slate->firstGame()->day;
            $slate->lastDay = $slate->lastGame()->day;
            //$slate->active = true;
            $slate->save();
        }
        catch (QueryException $e){
            if ($slate){
                $slate->active = false;
                $slate->status = 'HISTORY';
                $slate->save();
            }
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$SLATE_ERROR_CREATING, $e->getMessage());
        }
        catch (Exception $exception){
            if ($slate){
                $slate->active = false;
                $slate->status = 'HISTORY';
                $slate->save();
            }
            return HttpResponse::serverError(HttpStatus::$ERR_UNKNOWN, HttpMessage::$SLATE_ERROR_CREATING, $exception->getMessage());
        }

        return HttpResponse::ok(HttpMessage::$SLATE_SUCCESSFULLY_CREATED, $slate);
    }

    public function activateSlate(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            SlateConsts::$SLATE_ID   => 'required'
        ]);

        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$SLATE_ERROR_ACTIVATING,
                $validator->errors()->all());
        }

        $slate = Slate::find($request->get(SlateConsts::$SLATE_ID));
        if (!$slate){
            return HttpResponse::serverError(HttpStatus::$ENTITY_NOT_FOUND, HttpMessage::$CONTEST_SLATE_NOT_FOUND, HttpMessage::$CONTEST_SLATE_NOT_FOUND);
        }

        try{
            $slate->active = true;
            $slate->save();
        }
        catch (QueryException $e){
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$SLATE_ERROR_ACTIVATING, $e->getMessage());
        }
        catch (Exception $exception){
            return HttpResponse::serverError(HttpStatus::$ERR_UNKNOWN, HttpMessage::$SLATE_ERROR_ACTIVATING,
                $exception->getMessage());
        }

        return HttpResponse::ok(HttpMessage::$SLATE_SUCCESSFULLY_ACTIVATED, null);
    }

    public function deactivateSlate(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            SlateConsts::$SLATE_ID   => 'required'
        ]);

        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$SLATE_ERROR_DEACTIVATING,
                $validator->errors()->all());
        }

        $slate = Slate::find($request->get(SlateConsts::$SLATE_ID));
        if (!$slate){
            return HttpResponse::serverError(HttpStatus::$ENTITY_NOT_FOUND, HttpMessage::$CONTEST_SLATE_NOT_FOUND, HttpMessage::$CONTEST_SLATE_NOT_FOUND);
        }

        try{
            $slate->active = false;
            $slate->save();
        }
        catch (QueryException $e){
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$SLATE_ERROR_DEACTIVATING, $e->getMessage());
        }
        catch (Exception $exception){
            return HttpResponse::serverError(HttpStatus::$ERR_UNKNOWN, HttpMessage::$SLATE_ERROR_DEACTIVATING,
                $exception->getMessage());
        }

        return HttpResponse::ok(HttpMessage::$SLATE_SUCCESSFULLY_DEACTIVATED, null);
    }
}
