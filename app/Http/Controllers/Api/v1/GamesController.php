<?php

namespace App\Http\Controllers\Api\v1;

use App\Game;
use App\TimeFrame;
use App\Helpers\DatesHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\HttpResponse;
use App\Http\HttpStatus;
use App\Http\HttpMessage;
use Mockery\Exception;

class GamesController extends Controller
{

    public function getGames(Request $request)
    {
        try{
            $timeframe = TimeFrame::getCurrentTimeFrame();
            $key = $timeframe->retrieveKey();
            $result = Game::whereIn('status', array('PENDING', 'LIVE'))->orWhere('id', 'like', '%'.$key.'%')->get();
            $userInfo = ['timestamp' => time()];
        }
        catch (QueryException $e){
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$GAMES_ERROR_RETRIEVING, $e->getMessage());
        }
        catch (Exception $exception){
            return HttpResponse::serverError(HttpStatus::$ERR_GAMES_RETRIEVE, HttpMessage::$GAMES_ERROR_RETRIEVING, $exception->getMessage());
        }

        return HttpResponse::ok(HttpMessage::$GAMES_SUCCESSFULLY_RETRIEVED, $result, $userInfo);
    }

    public function getPendingGames(Request $request)
    {
        try{
            $result = Game::whereIn('status', array('PENDING'))->orderBy("date", "ASC")->get();
        }
        catch (QueryException $e){
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$GAMES_ERROR_RETRIEVING, $e->getMessage());
        }
        catch (Exception $exception){
            return HttpResponse::serverError(HttpStatus::$ERR_GAMES_RETRIEVE, HttpMessage::$GAMES_ERROR_RETRIEVING, $exception->getMessage());
        }

        return HttpResponse::ok(HttpMessage::$GAMES_SUCCESSFULLY_RETRIEVED, $result);
    }

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
     * @param  \App\Game  $game
     * @return \Illuminate\Http\Response
     */
    public function show(Game $game)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Game  $game
     * @return \Illuminate\Http\Response
     */
    public function edit(Game $game)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Game  $game
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Game $game)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Game  $game
     * @return \Illuminate\Http\Response
     */
    public function destroy(Game $game)
    {
        //
    }

}
