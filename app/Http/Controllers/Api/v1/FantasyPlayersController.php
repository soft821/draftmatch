<?php

namespace App\Http\Controllers\Api\v1;

use App\FantasyPlayer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\HttpResponse;
use App\Http\HttpStatus;
use App\Http\HttpMessage;
use App\Http\Requests;
use Mockery\Exception;

use Illuminate\Validation\Rule;
use App\Common\Consts\Contest\ContestConsts;

class FantasyPlayersController extends Controller
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
     * @param  \App\FantasyPlayer  $fantasyPlayer
     * @return \Illuminate\Http\Response
     */
    public function show(FantasyPlayer $fantasyPlayer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\FantasyPlayer  $fantasyPlayer
     * @return \Illuminate\Http\Response
     */
    public function edit(FantasyPlayer $fantasyPlayer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\FantasyPlayer  $fantasyPlayer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, FantasyPlayer $fantasyPlayer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\FantasyPlayer  $fantasyPlayer
     * @return \Illuminate\Http\Response
     */
    public function destroy(FantasyPlayer $fantasyPlayer)
    {
        //
    }

    public function getRoster()
    {

        return FantasyPlayer::getPlayersBySlate();
    }

    public function getFantasyPlayers(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'slate_id'    => 'required',
            'position'    => ['required', Rule::in(ContestConsts::getPositions())],
            'tier'        => ['nullable', Rule::in(ContestConsts::getTiers())],
        ]);

        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$FANTASY_PLAYERS_ERROR_RETRIEVING,
                $validator->errors()->all());
        }

        try{
            $result = FantasyPlayer::getPlayersBySlate($request->get('slate_id'), $request->get('position'), $request->get('tier'));
        }
        catch (QueryException $e){
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$FANTASY_PLAYERS_ERROR_RETRIEVING, $e->getMessage());
        }
        catch (Exception $exception){
            return HttpResponse::serverError(HttpStatus::$ERR_FANTASY_PLAYERS_RETRIEVE, HttpMessage::$FANTASY_PLAYERS_ERROR_RETRIEVING,
                $exception->getMessage());
        }

        return HttpResponse::ok(HttpMessage::$FANTASY_PLAYERS_SUCCESSFULLY_RETRIEVED, $result);
    }
}
