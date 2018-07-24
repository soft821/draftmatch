<?php

namespace App\Http\Controllers\Admin\fake;

use App\Game;
use App\Slate;
use App\TimeFrame;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\DatesHelper;

use App\Http\HttpResponse;
use App\Http\HttpStatus;
use App\Http\Requests;
use App\Http\HttpMessage;
use Mockery\Exception;
use Illuminate\Validation\Rule;


class FakeDataController extends Controller
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
    

    public function getCurrentTimeFrame()
    {
        
        $currentTimeFrame =  array (
            'SeasonType'=> 1,
            'Season'=> 2018,
            'Week'=> 10,
            'Name'=> 'Week 10',
            'ShortName'=> 'Week 10',
            'StartDate'=> '2018-07-25T00:00:00',
            'EndDate'=> '2018-07-31T23:59:59',
            'FirstGameStart'=> '2018-07-25T20:20:00',
            'FirstGameEnd'=> '2018-07-26T10:20:00',
            'LastGameEnd'=> '2018-07-31T00:15:00',
            'HasGames'=> true,
            'HasStarted'=> false,
            'HasEnded'=> false,
            'HasFirstGameStarted'=> false,
            'HasFirstGameEnded'=> false,
            'HasLastGameEnded'=> false,
            'ApiSeason'=> '2018REG',
            'ApiWeek'=> '10'
        );

        return response()->json(array($currentTimeFrame));

       
    }

    public function getWeekGames()
    {

        $currentTime = DatesHelper::getCurrentDate()->format('Y-m-d H:i:s');

        $currentTime = str_replace(' ', 'T', $currentTime);                 
        
        $currentGames = 
            array (
                array (
        "GameKey" => "201811006",
        "SeasonType" => 1,
        "Season" => 2018,
        "Week" => 10,
        "Date" => "2018-07-30T13:00:00",
        "AwayTeam" => "DET",
        "HomeTeam" => "CHI",
        "AwayScore" => null,
        "HomeScore" => null,
        "Channel" => null,
        "PointSpread" => null,
        "OverUnder" => null,
        "Quarter" => null,
        "TimeRemaining" => null,
        "Possession" => null,
        "Down" => null,
        "Distance" => null,
        "YardLine" => null,
        "YardLineTerritory" => null,
        "RedZone" => null,
        "AwayScoreQuarter1" => null,
        "AwayScoreQuarter2" => null,
        "AwayScoreQuarter3" => null,
        "AwayScoreQuarter4" => null,
        "AwayScoreOvertime" => null,
        "HomeScoreQuarter1" => null,
        "HomeScoreQuarter2" => null,
        "HomeScoreQuarter3" => null,
        "HomeScoreQuarter4" => null,
        "HomeScoreOvertime" => null,
        "HasStarted" => false,
        "IsInProgress" => false,
        "IsOver" => false,
        "Has1stQuarterStarted" => false,
        "Has2ndQuarterStarted" => false,
        "Has3rdQuarterStarted" => false,
        "Has4thQuarterStarted" => false,
        "IsOvertime" => false,
        "DownAndDistance" => null,
        "QuarterDescription" => "",
        "StadiumID" => 20,
        "LastUpdated" => "2018-04-20T13:14:11",
        "GeoLat" => null,
        "GeoLong" => null,
        "ForecastTempLow" => null,
        "ForecastTempHigh" => null,
        "ForecastDescription" => null,
        "ForecastWindChill" => null,
        "ForecastWindSpeed" => null,
        "AwayTeamMoneyLine" => null,
        "HomeTeamMoneyLine" => null,
        "Canceled" => false,
        "Closed" => false,
        "LastPlay" => null,
        "Day" => "2018-07-30T00:00:00",
        "DateTime" => "2018-07-30T13:00:00",
        "AwayTeamID" => 11,
        "HomeTeamID" => 6,
        "GlobalGameID" => 16789,
        "GlobalAwayTeamID" => 11,
        "GlobalHomeTeamID" => 6,
        "PointSpreadAwayTeamMoneyLine" => null,
        "PointSpreadHomeTeamMoneyLine" => null,
        "ScoreID" => 16789,
        "StadiumDetails" => array (
            "StadiumID" => 20,
            "Name" => "Soldier Field",
            "City" => "Chicago",
            "State" => "IL",
            "Country" => "USA",
            "Capacity" => 61500,
            "PlayingSurface" => "Grass",
            "GeoLat" => 41.86232,
            "GeoLong" => -87.616699
        )
                ),
                array (
        "GameKey" => "201811007",
        "SeasonType" => 1,
        "Season" => 2018,
        "Week" => 10,
        "Date" => "2018-07-30T13:00:00",
        "AwayTeam" => "NO",
        "HomeTeam" => "CIN",
        "AwayScore" => null,
        "HomeScore" => null,
        "Channel" => null,
        "PointSpread" => null,
        "OverUnder" => null,
        "Quarter" => null,
        "TimeRemaining" => null,
        "Possession" => null,
        "Down" => null,
        "Distance" => null,
        "YardLine" => null,
        "YardLineTerritory" => null,
        "RedZone" => null,
        "AwayScoreQuarter1" => null,
        "AwayScoreQuarter2" => null,
        "AwayScoreQuarter3" => null,
        "AwayScoreQuarter4" => null,
        "AwayScoreOvertime" => null,
        "HomeScoreQuarter1" => null,
        "HomeScoreQuarter2" => null,
        "HomeScoreQuarter3" => null,
        "HomeScoreQuarter4" => null,
        "HomeScoreOvertime" => null,
        "HasStarted" => false,
        "IsInProgress" => false,
        "IsOver" => false,
        "Has1stQuarterStarted" => false,
        "Has2ndQuarterStarted" => false,
        "Has3rdQuarterStarted" => false,
        "Has4thQuarterStarted" => false,
        "IsOvertime" => false,
        "DownAndDistance" => null,
        "QuarterDescription" => "",
        "StadiumID" => 5,
        "LastUpdated" => "2018-04-20T13:14:11",
        "GeoLat" => null,
        "GeoLong" => null,
        "ForecastTempLow" => null,
        "ForecastTempHigh" => null,
        "ForecastDescription" => null,
        "ForecastWindChill" => null,
        "ForecastWindSpeed" => null,
        "AwayTeamMoneyLine" => null,
        "HomeTeamMoneyLine" => null,
        "Canceled" => false,
        "Closed" => false,
        "LastPlay" => null,
        "Day" => "2018-07-30T00:00:00",
        "DateTime" => "2018-07-30T13:00:00",
        "AwayTeamID" => 22,
        "HomeTeamID" => 7,
        "GlobalGameID" => 16790,
        "GlobalAwayTeamID" => 22,
        "GlobalHomeTeamID" => 7,
        "PointSpreadAwayTeamMoneyLine" => null,
        "PointSpreadHomeTeamMoneyLine" => null,
        "ScoreID" => 16790,
        "StadiumDetails" => array (
            "StadiumID" => 5,
            "Name" => "Paul Brown Stadium",
            "City" => "Cincinnati",
            "State" => "OH",
            "Country" => "USA",
            "Capacity" => 65535,
            "PlayingSurface" => "Artificial",
            "GeoLat" => 39.095309,
            "GeoLong" => -84.516003
        )
                ),
                array (
        "GameKey" => "201811008",
        "SeasonType" => 1,
        "Season" => 2018,
        "Week" => 10,
        "Date" => "2018-07-29T13:00:00",
        "AwayTeam" => "ATL",
        "HomeTeam" => "CLE",
        "AwayScore" => null,
        "HomeScore" => null,
        "Channel" => null,
        "PointSpread" => null,
        "OverUnder" => null,
        "Quarter" => null,
        "TimeRemaining" => null,
        "Possession" => null,
        "Down" => null,
        "Distance" => null,
        "YardLine" => null,
        "YardLineTerritory" => null,
        "RedZone" => null,
        "AwayScoreQuarter1" => null,
        "AwayScoreQuarter2" => null,
        "AwayScoreQuarter3" => null,
        "AwayScoreQuarter4" => null,
        "AwayScoreOvertime" => null,
        "HomeScoreQuarter1" => null,
        "HomeScoreQuarter2" => null,
        "HomeScoreQuarter3" => null,
        "HomeScoreQuarter4" => null,
        "HomeScoreOvertime" => null,
        "HasStarted" => false,
        "IsInProgress" => false,
        "IsOver" => false,
        "Has1stQuarterStarted" => false,
        "Has2ndQuarterStarted" => false,
        "Has3rdQuarterStarted" => false,
        "Has4thQuarterStarted" => false,
        "IsOvertime" => false,
        "DownAndDistance" => null,
        "QuarterDescription" => "",
        "StadiumID" => 6,
        "LastUpdated" => "2018-04-20T13:14:11",
        "GeoLat" => null,
        "GeoLong" => null,
        "ForecastTempLow" => null,
        "ForecastTempHigh" => null,
        "ForecastDescription" => null,
        "ForecastWindChill" => null,
        "ForecastWindSpeed" => null,
        "AwayTeamMoneyLine" => null,
        "HomeTeamMoneyLine" => null,
        "Canceled" => false,
        "Closed" => false,
        "LastPlay" => null,
        "Day" => "2018-07-29T00:00:00",
        "DateTime" => "2018-07-29T13:00:00",
        "AwayTeamID" => 2,
        "HomeTeamID" => 8,
        "GlobalGameID" => 16791,
        "GlobalAwayTeamID" => 2,
        "GlobalHomeTeamID" => 8,
        "PointSpreadAwayTeamMoneyLine" => null,
        "PointSpreadHomeTeamMoneyLine" => null,
        "ScoreID" => 16791,
        "StadiumDetails" => array (
            "StadiumID" => 6,
            "Name" => "FirstEnergy Stadium",
            "City" => "Cleveland",
            "State" => "OH",
            "Country" => "USA",
            "Capacity" => 71516,
            "PlayingSurface" => "Artificial",
            "GeoLat" => 41.505885,
            "GeoLong" => -81.699458
        )
                ),
                array (
        "GameKey" => "201811012",
        "SeasonType" => 1,
        "Season" => 2018,
        "Week" => 10,
        "Date" => "2018-07-29T13:00:00",
        "AwayTeam" => "MIA",
        "HomeTeam" => "GB",
        "AwayScore" => null,
        "HomeScore" => null,
        "Channel" => null,
        "PointSpread" => null,
        "OverUnder" => null,
        "Quarter" => null,
        "TimeRemaining" => null,
        "Possession" => null,
        "Down" => null,
        "Distance" => null,
        "YardLine" => null,
        "YardLineTerritory" => null,
        "RedZone" => null,
        "AwayScoreQuarter1" => null,
        "AwayScoreQuarter2" => null,
        "AwayScoreQuarter3" => null,
        "AwayScoreQuarter4" => null,
        "AwayScoreOvertime" => null,
        "HomeScoreQuarter1" => null,
        "HomeScoreQuarter2" => null,
        "HomeScoreQuarter3" => null,
        "HomeScoreQuarter4" => null,
        "HomeScoreOvertime" => null,
        "HasStarted" => false,
        "IsInProgress" => false,
        "IsOver" => false,
        "Has1stQuarterStarted" => false,
        "Has2ndQuarterStarted" => false,
        "Has3rdQuarterStarted" => false,
        "Has4thQuarterStarted" => false,
        "IsOvertime" => false,
        "DownAndDistance" => null,
        "QuarterDescription" => "",
        "StadiumID" => 22,
        "LastUpdated" => "2018-04-20T13:14:11",
        "GeoLat" => null,
        "GeoLong" => null,
        "ForecastTempLow" => null,
        "ForecastTempHigh" => null,
        "ForecastDescription" => null,
        "ForecastWindChill" => null,
        "ForecastWindSpeed" => null,
        "AwayTeamMoneyLine" => null,
        "HomeTeamMoneyLine" => null,
        "Canceled" => false,
        "Closed" => false,
        "LastPlay" => null,
        "Day" => "2018-07-29T00:00:00",
        "DateTime" => "2018-07-29T13:00:00",
        "AwayTeamID" => 19,
        "HomeTeamID" => 12,
        "GlobalGameID" => 16792,
        "GlobalAwayTeamID" => 19,
        "GlobalHomeTeamID" => 12,
        "PointSpreadAwayTeamMoneyLine" => null,
        "PointSpreadHomeTeamMoneyLine" => null,
        "ScoreID" => 16792,
        "StadiumDetails" => array (
            "StadiumID" => 22,
            "Name" => "Lambeau Field",
            "City" => "Green Bay",
            "State" => "WI",
            "Country" => "USA",
            "Capacity" => 80750,
            "PlayingSurface" => "Grass",
            "GeoLat" => 44.501389,
            "GeoLong" => -88.061944
        )
                ),
                array (
        "GameKey" => "201811014",
        "SeasonType" => 1,
        "Season" => 2018,
        "Week" => 10,
        "Date" => "2018-07-29T13:00:00",
        "AwayTeam" => "JAX",
        "HomeTeam" => "IND",
        "AwayScore" => null,
        "HomeScore" => null,
        "Channel" => null,
        "PointSpread" => null,
        "OverUnder" => null,
        "Quarter" => null,
        "TimeRemaining" => null,
        "Possession" => null,
        "Down" => null,
        "Distance" => null,
        "YardLine" => null,
        "YardLineTerritory" => null,
        "RedZone" => null,
        "AwayScoreQuarter1" => null,
        "AwayScoreQuarter2" => null,
        "AwayScoreQuarter3" => null,
        "AwayScoreQuarter4" => null,
        "AwayScoreOvertime" => null,
        "HomeScoreQuarter1" => null,
        "HomeScoreQuarter2" => null,
        "HomeScoreQuarter3" => null,
        "HomeScoreQuarter4" => null,
        "HomeScoreOvertime" => null,
        "HasStarted" => false,
        "IsInProgress" => false,
        "IsOver" => false,
        "Has1stQuarterStarted" => false,
        "Has2ndQuarterStarted" => false,
        "Has3rdQuarterStarted" => false,
        "Has4thQuarterStarted" => false,
        "IsOvertime" => false,
        "DownAndDistance" => null,
        "QuarterDescription" => "",
        "StadiumID" => 9,
        "LastUpdated" => "2018-04-20T13:14:12",
        "GeoLat" => null,
        "GeoLong" => null,
        "ForecastTempLow" => null,
        "ForecastTempHigh" => null,
        "ForecastDescription" => null,
        "ForecastWindChill" => null,
        "ForecastWindSpeed" => null,
        "AwayTeamMoneyLine" => null,
        "HomeTeamMoneyLine" => null,
        "Canceled" => false,
        "Closed" => false,
        "LastPlay" => null,
        "Day" => "2018-07-29T00:00:00",
        "DateTime" => "2018-07-29T13:00:00",
        "AwayTeamID" => 15,
        "HomeTeamID" => 14,
        "GlobalGameID" => 16794,
        "GlobalAwayTeamID" => 15,
        "GlobalHomeTeamID" => 14,
        "PointSpreadAwayTeamMoneyLine" => null,
        "PointSpreadHomeTeamMoneyLine" => null,
        "ScoreID" => 16794,
        "StadiumDetails" => array (
            "StadiumID" => 9,
            "Name" => "Lucas Oil Stadium",
            "City" => "Indianapolis",
            "State" => "IN",
            "Country" => "USA",
            "Capacity" => 67000,
            "PlayingSurface" => "Dome",
            "GeoLat" => 39.760056,
            "GeoLong" => -86.163806
        )
                ),
                array (
        "GameKey" => "201811016",
        "SeasonType" => 1,
        "Season" => 2018,
        "Week" => 10,
        "Date" => "2018-07-29T13:00:00",
        "AwayTeam" => "ARI",
        "HomeTeam" => "KC",
        "AwayScore" => null,
        "HomeScore" => null,
        "Channel" => null,
        "PointSpread" => null,
        "OverUnder" => null,
        "Quarter" => null,
        "TimeRemaining" => null,
        "Possession" => null,
        "Down" => null,
        "Distance" => null,
        "YardLine" => null,
        "YardLineTerritory" => null,
        "RedZone" => null,
        "AwayScoreQuarter1" => null,
        "AwayScoreQuarter2" => null,
        "AwayScoreQuarter3" => null,
        "AwayScoreQuarter4" => null,
        "AwayScoreOvertime" => null,
        "HomeScoreQuarter1" => null,
        "HomeScoreQuarter2" => null,
        "HomeScoreQuarter3" => null,
        "HomeScoreQuarter4" => null,
        "HomeScoreOvertime" => null,
        "HasStarted" => false,
        "IsInProgress" => false,
        "IsOver" => false,
        "Has1stQuarterStarted" => false,
        "Has2ndQuarterStarted" => false,
        "Has3rdQuarterStarted" => false,
        "Has4thQuarterStarted" => false,
        "IsOvertime" => false,
        "DownAndDistance" => null,
        "QuarterDescription" => "",
        "StadiumID" => 15,
        "LastUpdated" => "2018-04-20T13:14:12",
        "GeoLat" => null,
        "GeoLong" => null,
        "ForecastTempLow" => null,
        "ForecastTempHigh" => null,
        "ForecastDescription" => null,
        "ForecastWindChill" => null,
        "ForecastWindSpeed" => null,
        "AwayTeamMoneyLine" => null,
        "HomeTeamMoneyLine" => null,
        "Canceled" => false,
        "Closed" => false,
        "LastPlay" => null,
        "Day" => "2018-07-29T00:00:00",
        "DateTime" => "2018-07-29T13:00:00",
        "AwayTeamID" => 1,
        "HomeTeamID" => 16,
        "GlobalGameID" => 16795,
        "GlobalAwayTeamID" => 1,
        "GlobalHomeTeamID" => 16,
        "PointSpreadAwayTeamMoneyLine" => null,
        "PointSpreadHomeTeamMoneyLine" => null,
        "ScoreID" => 16795,
        "StadiumDetails" => array (
            "StadiumID" => 15,
            "Name" => "Arrowhead Stadium",
            "City" => "Kansas City",
            "State" => "MO",
            "Country" => "USA",
            "Capacity" => 76416,
            "PlayingSurface" => "Grass",
            "GeoLat" => 39.049002,
            "GeoLong" => -94.483864
        )
                ),
                array (
        "GameKey" => "201811032",
        "SeasonType" => 1,
        "Season" => 2018,
        "Week" => 10,
        "Date" => "2018-07-29T16:25:00",
        "AwayTeam" => "SEA",
        "HomeTeam" => "LAR",
        "AwayScore" => null,
        "HomeScore" => null,
        "Channel" => null,
        "PointSpread" => null,
        "OverUnder" => null,
        "Quarter" => null,
        "TimeRemaining" => null,
        "Possession" => null,
        "Down" => null,
        "Distance" => null,
        "YardLine" => null,
        "YardLineTerritory" => null,
        "RedZone" => null,
        "AwayScoreQuarter1" => null,
        "AwayScoreQuarter2" => null,
        "AwayScoreQuarter3" => null,
        "AwayScoreQuarter4" => null,
        "AwayScoreOvertime" => null,
        "HomeScoreQuarter1" => null,
        "HomeScoreQuarter2" => null,
        "HomeScoreQuarter3" => null,
        "HomeScoreQuarter4" => null,
        "HomeScoreOvertime" => null,
        "HasStarted" => false,
        "IsInProgress" => false,
        "IsOver" => false,
        "Has1stQuarterStarted" => false,
        "Has2ndQuarterStarted" => false,
        "Has3rdQuarterStarted" => false,
        "Has4thQuarterStarted" => false,
        "IsOvertime" => false,
        "DownAndDistance" => null,
        "QuarterDescription" => "",
        "StadiumID" => 38,
        "LastUpdated" => "2018-04-20T13:14:14",
        "GeoLat" => null,
        "GeoLong" => null,
        "ForecastTempLow" => null,
        "ForecastTempHigh" => null,
        "ForecastDescription" => null,
        "ForecastWindChill" => null,
        "ForecastWindSpeed" => null,
        "AwayTeamMoneyLine" => null,
        "HomeTeamMoneyLine" => null,
        "Canceled" => false,
        "Closed" => false,
        "LastPlay" => null,
        "Day" => "2018-07-29T00:00:00",
        "DateTime" => "2018-07-29T16:25:00",
        "AwayTeamID" => 30,
        "HomeTeamID" => 32,
        "GlobalGameID" => 16799,
        "GlobalAwayTeamID" => 30,
        "GlobalHomeTeamID" => 32,
        "PointSpreadAwayTeamMoneyLine" => null,
        "PointSpreadHomeTeamMoneyLine" => null,
        "ScoreID" => 16799,
        "StadiumDetails" => array (
            "StadiumID" => 38,
            "Name" => "Los Angeles Memorial Coliseum",
            "City" => "Los Angeles",
            "State" => "CA",
            "Country" => "USA",
            "Capacity" => 93607,
            "PlayingSurface" => "Grass",
            "GeoLat" => 34.014167,
            "GeoLong" => -118.287778
        )
                ),
                array (
        "GameKey" => "201811024",
        "SeasonType" => 1,
        "Season" => 2018,
        "Week" => 10,
        "Date" => "2018-07-29T13:00:00",
        "AwayTeam" => "BUF",
        "HomeTeam" => "NYJ",
        "AwayScore" => null,
        "HomeScore" => null,
        "Channel" => null,
        "PointSpread" => null,
        "OverUnder" => null,
        "Quarter" => null,
        "TimeRemaining" => null,
        "Possession" => null,
        "Down" => null,
        "Distance" => null,
        "YardLine" => null,
        "YardLineTerritory" => null,
        "RedZone" => null,
        "AwayScoreQuarter1" => null,
        "AwayScoreQuarter2" => null,
        "AwayScoreQuarter3" => null,
        "AwayScoreQuarter4" => null,
        "AwayScoreOvertime" => null,
        "HomeScoreQuarter1" => null,
        "HomeScoreQuarter2" => null,
        "HomeScoreQuarter3" => null,
        "HomeScoreQuarter4" => null,
        "HomeScoreOvertime" => null,
        "HasStarted" => false,
        "IsInProgress" => false,
        "IsOver" => false,
        "Has1stQuarterStarted" => false,
        "Has2ndQuarterStarted" => false,
        "Has3rdQuarterStarted" => false,
        "Has4thQuarterStarted" => false,
        "IsOvertime" => false,
        "DownAndDistance" => null,
        "QuarterDescription" => "",
        "StadiumID" => 3,
        "LastUpdated" => "2018-04-20T13:14:13",
        "GeoLat" => null,
        "GeoLong" => null,
        "ForecastTempLow" => null,
        "ForecastTempHigh" => null,
        "ForecastDescription" => null,
        "ForecastWindChill" => null,
        "ForecastWindSpeed" => null,
        "AwayTeamMoneyLine" => null,
        "HomeTeamMoneyLine" => null,
        "Canceled" => false,
        "Closed" => false,
        "LastPlay" => null,
        "Day" => "2018-07-29T00:00:00",
        "DateTime" => "2018-07-29T13:00:00",
        "AwayTeamID" => 4,
        "HomeTeamID" => 24,
        "GlobalGameID" => 16796,
        "GlobalAwayTeamID" => 4,
        "GlobalHomeTeamID" => 24,
        "PointSpreadAwayTeamMoneyLine" => null,
        "PointSpreadHomeTeamMoneyLine" => null,
        "ScoreID" => 16796,
        "StadiumDetails" => array (
            "StadiumID" => 3,
            "Name" => "MetLife Stadium",
            "City" => "East Rutherford",
            "State" => "NJ",
            "Country" => "USA",
            "Capacity" => 82500,
            "PlayingSurface" => "Artificial",
            "GeoLat" => 40.813611,
            "GeoLong" => -74.074444
        )
                ),
                array (
        "GameKey" => "201811025",
        "SeasonType" => 1,
        "Season" => 2018,
        "Week" => 10,
        "Date" => "2018-07-29T16:05:00",
        "AwayTeam" => "LAC",
        "HomeTeam" => "OAK",
        "AwayScore" => null,
        "HomeScore" => null,
        "Channel" => null,
        "PointSpread" => null,
        "OverUnder" => null,
        "Quarter" => null,
        "TimeRemaining" => null,
        "Possession" => null,
        "Down" => null,
        "Distance" => null,
        "YardLine" => null,
        "YardLineTerritory" => null,
        "RedZone" => null,
        "AwayScoreQuarter1" => null,
        "AwayScoreQuarter2" => null,
        "AwayScoreQuarter3" => null,
        "AwayScoreQuarter4" => null,
        "AwayScoreOvertime" => null,
        "HomeScoreQuarter1" => null,
        "HomeScoreQuarter2" => null,
        "HomeScoreQuarter3" => null,
        "HomeScoreQuarter4" => null,
        "HomeScoreOvertime" => null,
        "HasStarted" => false,
        "IsInProgress" => false,
        "IsOver" => false,
        "Has1stQuarterStarted" => false,
        "Has2ndQuarterStarted" => false,
        "Has3rdQuarterStarted" => false,
        "Has4thQuarterStarted" => false,
        "IsOvertime" => false,
        "DownAndDistance" => null,
        "QuarterDescription" => "",
        "StadiumID" => 16,
        "LastUpdated" => "2018-04-20T13:14:13",
        "GeoLat" => null,
        "GeoLong" => null,
        "ForecastTempLow" => null,
        "ForecastTempHigh" => null,
        "ForecastDescription" => null,
        "ForecastWindChill" => null,
        "ForecastWindSpeed" => null,
        "AwayTeamMoneyLine" => null,
        "HomeTeamMoneyLine" => null,
        "Canceled" => false,
        "Closed" => false,
        "LastPlay" => null,
        "Day" => "2018-07-29T00:00:00",
        "DateTime" => "2018-07-29T16:05:00",
        "AwayTeamID" => 29,
        "HomeTeamID" => 25,
        "GlobalGameID" => 16798,
        "GlobalAwayTeamID" => 29,
        "GlobalHomeTeamID" => 25,
        "PointSpreadAwayTeamMoneyLine" => null,
        "PointSpreadHomeTeamMoneyLine" => null,
        "ScoreID" => 16798,
        "StadiumDetails" => array (
            "StadiumID" => 16,
            "Name" => "O.co Coliseum",
            "City" => "Oakland",
            "State" => "CA",
            "Country" => "USA",
            "Capacity" => 53200,
            "PlayingSurface" => "Grass",
            "GeoLat" => 37.751613,
            "GeoLong" => -122.200509
        )
                ),
                array (
        "GameKey" => "201811026",
        "SeasonType" => 1,
        "Season" => 2018,
        "Week" => 10,
        "Date" => "2018-07-26T20:20:00",
        "AwayTeam" => "DAL",
        "HomeTeam" => "PHI",
        "AwayScore" => null,
        "HomeScore" => null,
        "Channel" => null,
        "PointSpread" => null,
        "OverUnder" => null,
        "Quarter" => null,
        "TimeRemaining" => null,
        "Possession" => null,
        "Down" => null,
        "Distance" => null,
        "YardLine" => null,
        "YardLineTerritory" => null,
        "RedZone" => null,
        "AwayScoreQuarter1" => null,
        "AwayScoreQuarter2" => null,
        "AwayScoreQuarter3" => null,
        "AwayScoreQuarter4" => null,
        "AwayScoreOvertime" => null,
        "HomeScoreQuarter1" => null,
        "HomeScoreQuarter2" => null,
        "HomeScoreQuarter3" => null,
        "HomeScoreQuarter4" => null,
        "HomeScoreOvertime" => null,
        "HasStarted" => false,
        "IsInProgress" => false,
        "IsOver" => false,
        "Has1stQuarterStarted" => false,
        "Has2ndQuarterStarted" => false,
        "Has3rdQuarterStarted" => false,
        "Has4thQuarterStarted" => false,
        "IsOvertime" => false,
        "DownAndDistance" => null,
        "QuarterDescription" => "",
        "StadiumID" => 18,
        "LastUpdated" => "2018-04-20T13:14:14",
        "GeoLat" => null,
        "GeoLong" => null,
        "ForecastTempLow" => null,
        "ForecastTempHigh" => null,
        "ForecastDescription" => null,
        "ForecastWindChill" => null,
        "ForecastWindSpeed" => null,
        "AwayTeamMoneyLine" => null,
        "HomeTeamMoneyLine" => null,
        "Canceled" => false,
        "Closed" => false,
        "LastPlay" => null,
        "Day" => "2018-07-26T00:00:00",
        "DateTime" => "2018-07-26T20:20:00",
        "AwayTeamID" => 9,
        "HomeTeamID" => 26,
        "GlobalGameID" => 16800,
        "GlobalAwayTeamID" => 9,
        "GlobalHomeTeamID" => 26,
        "PointSpreadAwayTeamMoneyLine" => null,
        "PointSpreadHomeTeamMoneyLine" => null,
        "ScoreID" => 16800,
        "StadiumDetails" => array (
            "StadiumID" => 18,
            "Name" => "Lincoln Financial Field",
            "City" => "Philadelphia",
            "State" => "PA",
            "Country" => "USA",
            "Capacity" => 68532,
            "PlayingSurface" => "Grass",
            "GeoLat" => 39.900771,
            "GeoLong" => -75.167469
        )
                ),
                array (
        "GameKey" => "201811028",
        "SeasonType" => 1,
        "Season" => 2018,
        "Week" => 10,
        "Date" => "2018-07-26T20:20:00",
        "AwayTeam" => "CAR",
        "HomeTeam" => "PIT",
        "AwayScore" => null,
        "HomeScore" => null,
        "Channel" => null,
        "PointSpread" => null,
        "OverUnder" => null,
        "Quarter" => null,
        "TimeRemaining" => null,
        "Possession" => null,
        "Down" => null,
        "Distance" => null,
        "YardLine" => null,
        "YardLineTerritory" => null,
        "RedZone" => null,
        "AwayScoreQuarter1" => null,
        "AwayScoreQuarter2" => null,
        "AwayScoreQuarter3" => null,
        "AwayScoreQuarter4" => null,
        "AwayScoreOvertime" => null,
        "HomeScoreQuarter1" => null,
        "HomeScoreQuarter2" => null,
        "HomeScoreQuarter3" => null,
        "HomeScoreQuarter4" => null,
        "HomeScoreOvertime" => null,
        "HasStarted" => false,
        "IsInProgress" => false,
        "IsOver" => false,
        "Has1stQuarterStarted" => false,
        "Has2ndQuarterStarted" => false,
        "Has3rdQuarterStarted" => false,
        "Has4thQuarterStarted" => false,
        "IsOvertime" => false,
        "DownAndDistance" => null,
        "QuarterDescription" => "",
        "StadiumID" => 8,
        "LastUpdated" => "2018-04-20T13:14:10",
        "GeoLat" => null,
        "GeoLong" => null,
        "ForecastTempLow" => null,
        "ForecastTempHigh" => null,
        "ForecastDescription" => null,
        "ForecastWindChill" => null,
        "ForecastWindSpeed" => null,
        "AwayTeamMoneyLine" => null,
        "HomeTeamMoneyLine" => null,
        "Canceled" => false,
        "Closed" => false,
        "LastPlay" => null,
        "Day" => "2018-07-26T00:00:00",
        "DateTime" => "2018-07-26T20:20:00",
        "AwayTeamID" => 5,
        "HomeTeamID" => 28,
        "GlobalGameID" => 16788,
        "GlobalAwayTeamID" => 5,
        "GlobalHomeTeamID" => 28,
        "PointSpreadAwayTeamMoneyLine" => null,
        "PointSpreadHomeTeamMoneyLine" => null,
        "ScoreID" => 16788,
        "StadiumDetails" => array (
            "StadiumID" => 8,
            "Name" => "Heinz Field",
            "City" => "Pittsburgh",
            "State" => "PA",
            "Country" => "USA",
            "Capacity" => 65050,
            "PlayingSurface" => "Grass",
            "GeoLat" => 40.446751,
            "GeoLong" => -80.015707
        )
                ),
                array (
        "GameKey" => "201811031",
        "SeasonType" => 1,
        "Season" => 2018,
        "Week" => 10,
        "Date" => "2018-07-26T20:15:00",
        "AwayTeam" => "NYG",
        "HomeTeam" => "SF",
        "AwayScore" => null,
        "HomeScore" => null,
        "Channel" => null,
        "PointSpread" => null,
        "OverUnder" => null,
        "Quarter" => null,
        "TimeRemaining" => null,
        "Possession" => null,
        "Down" => null,
        "Distance" => null,
        "YardLine" => null,
        "YardLineTerritory" => null,
        "RedZone" => null,
        "AwayScoreQuarter1" => null,
        "AwayScoreQuarter2" => null,
        "AwayScoreQuarter3" => null,
        "AwayScoreQuarter4" => null,
        "AwayScoreOvertime" => null,
        "HomeScoreQuarter1" => null,
        "HomeScoreQuarter2" => null,
        "HomeScoreQuarter3" => null,
        "HomeScoreQuarter4" => null,
        "HomeScoreOvertime" => null,
        "HasStarted" => false,
        "IsInProgress" => false,
        "IsOver" => false,
        "Has1stQuarterStarted" => false,
        "Has2ndQuarterStarted" => false,
        "Has3rdQuarterStarted" => false,
        "Has4thQuarterStarted" => false,
        "IsOvertime" => false,
        "DownAndDistance" => null,
        "QuarterDescription" => "",
        "StadiumID" => 37,
        "LastUpdated" => "2018-04-20T13:14:14",
        "GeoLat" => null,
        "GeoLong" => null,
        "ForecastTempLow" => null,
        "ForecastTempHigh" => null,
        "ForecastDescription" => null,
        "ForecastWindChill" => null,
        "ForecastWindSpeed" => null,
        "AwayTeamMoneyLine" => null,
        "HomeTeamMoneyLine" => null,
        "Canceled" => false,
        "Closed" => false,
        "LastPlay" => null,
        "Day" => "2018-07-26T00:00:00",
        "DateTime" => "2018-07-26T20:15:00",
        "AwayTeamID" => 23,
        "HomeTeamID" => 31,
        "GlobalGameID" => 16801,
        "GlobalAwayTeamID" => 23,
        "GlobalHomeTeamID" => 31,
        "PointSpreadAwayTeamMoneyLine" => null,
        "PointSpreadHomeTeamMoneyLine" => null,
        "ScoreID" => 16801,
        "StadiumDetails" => array (
            "StadiumID" => 37,
            "Name" => "Levi's Stadium",
            "City" => "Santa Clara",
            "State" => "CA",
            "Country" => "USA",
            "Capacity" => 68500,
            "PlayingSurface" => "Grass",
            "GeoLat" => 37.404108,
            "GeoLong" => -121.970274
        )
                ),
                array (
        "GameKey" => "201811033",
        "SeasonType" => 1,
        "Season" => 2018,
        "Week" => 10,
        "Date" => "2018-07-29T13:00:00",
        "AwayTeam" => "WAS",
        "HomeTeam" => "TB",
        "AwayScore" => null,
        "HomeScore" => null,
        "Channel" => null,
        "PointSpread" => null,
        "OverUnder" => null,
        "Quarter" => null,
        "TimeRemaining" => null,
        "Possession" => null,
        "Down" => null,
        "Distance" => null,
        "YardLine" => null,
        "YardLineTerritory" => null,
        "RedZone" => null,
        "AwayScoreQuarter1" => null,
        "AwayScoreQuarter2" => null,
        "AwayScoreQuarter3" => null,
        "AwayScoreQuarter4" => null,
        "AwayScoreOvertime" => null,
        "HomeScoreQuarter1" => null,
        "HomeScoreQuarter2" => null,
        "HomeScoreQuarter3" => null,
        "HomeScoreQuarter4" => null,
        "HomeScoreOvertime" => null,
        "HasStarted" => false,
        "IsInProgress" => false,
        "IsOver" => false,
        "Has1stQuarterStarted" => false,
        "Has2ndQuarterStarted" => false,
        "Has3rdQuarterStarted" => false,
        "Has4thQuarterStarted" => false,
        "IsOvertime" => false,
        "DownAndDistance" => null,
        "QuarterDescription" => "",
        "StadiumID" => 24,
        "LastUpdated" => "2018-04-20T13:14:13",
        "GeoLat" => null,
        "GeoLong" => null,
        "ForecastTempLow" => null,
        "ForecastTempHigh" => null,
        "ForecastDescription" => null,
        "ForecastWindChill" => null,
        "ForecastWindSpeed" => null,
        "AwayTeamMoneyLine" => null,
        "HomeTeamMoneyLine" => null,
        "Canceled" => false,
        "Closed" => false,
        "LastPlay" => null,
        "Day" => "2018-07-29T00:00:00",
        "DateTime" => "2018-07-29T13:00:00",
        "AwayTeamID" => 35,
        "HomeTeamID" => 33,
        "GlobalGameID" => 16797,
        "GlobalAwayTeamID" => 35,
        "GlobalHomeTeamID" => 33,
        "PointSpreadAwayTeamMoneyLine" => null,
        "PointSpreadHomeTeamMoneyLine" => null,
        "ScoreID" => 16797,
        "StadiumDetails" => array (
            "StadiumID" => 24,
            "Name" => "Raymond James Stadium",
            "City" => "Tampa Bay",
            "State" => "FL",
            "Country" => "USA",
            "Capacity" => 65890,
            "PlayingSurface" => "Grass",
            "GeoLat" => 27.975967,
            "GeoLong" => -82.50335
        )
                ),
                array (
        "GameKey" => "201811034",
        "SeasonType" => 1,
        "Season" => 2018,
        "Week" => 10,
        "Date" => "2018-07-29T13:00:00",
        "AwayTeam" => "NE",
        "HomeTeam" => "TEN",
        "AwayScore" => null,
        "HomeScore" => null,
        "Channel" => null,
        "PointSpread" => null,
        "OverUnder" => null,
        "Quarter" => null,
        "TimeRemaining" => null,
        "Possession" => null,
        "Down" => null,
        "Distance" => null,
        "YardLine" => null,
        "YardLineTerritory" => null,
        "RedZone" => null,
        "AwayScoreQuarter1" => null,
        "AwayScoreQuarter2" => null,
        "AwayScoreQuarter3" => null,
        "AwayScoreQuarter4" => null,
        "AwayScoreOvertime" => null,
        "HomeScoreQuarter1" => null,
        "HomeScoreQuarter2" => null,
        "HomeScoreQuarter3" => null,
        "HomeScoreQuarter4" => null,
        "HomeScoreOvertime" => null,
        "HasStarted" => false,
        "IsInProgress" => false,
        "IsOver" => false,
        "Has1stQuarterStarted" => false,
        "Has2ndQuarterStarted" => false,
        "Has3rdQuarterStarted" => false,
        "Has4thQuarterStarted" => false,
        "IsOvertime" => false,
        "DownAndDistance" => null,
        "QuarterDescription" => "",
        "StadiumID" => 12,
        "LastUpdated" => "2018-04-20T13:14:12",
        "GeoLat" => null,
        "GeoLong" => null,
        "ForecastTempLow" => null,
        "ForecastTempHigh" => null,
        "ForecastDescription" => null,
        "ForecastWindChill" => null,
        "ForecastWindSpeed" => null,
        "AwayTeamMoneyLine" => null,
        "HomeTeamMoneyLine" => null,
        "Canceled" => false,
        "Closed" => false,
        "LastPlay" => null,
        "Day" => "2018-07-29T00:00:00",
        "DateTime" => "2018-07-29T13:00:00",
        "AwayTeamID" => 21,
        "HomeTeamID" => 34,
        "GlobalGameID" => 16793,
        "GlobalAwayTeamID" => 21,
        "GlobalHomeTeamID" => 34,
        "PointSpreadAwayTeamMoneyLine" => null,
        "PointSpreadHomeTeamMoneyLine" => null,
        "ScoreID" => 16793,
        "StadiumDetails" => array (
            "StadiumID" => 12,
            "Name" => "LP Field",
            "City" => "Nashville",
            "State" => "TN",
            "Country" => "USA",
            "Capacity" => 6914,
            "PlayingSurface" => "Grass",
            "GeoLat" => 36.166441,
            "GeoLong" => -86.771253
        )
                )
            );


        for ($i = 0; $i < count($currentGames); $i++) {
            
            $deltaTimeStamp = strtotime(date($currentGames[$i]['DateTime'])) - strtotime(date($currentTime)) + 3*3600;

            if ($deltaTimeStamp < 0) {
                
                $currentGames[$i]['IsOver'] = true;

            } elseif ($deltaTimeStamp < 3*3600) {
                
                $currentGames[$i]['HasStarted'] = true;
            } 
        }

        return response()->json($currentGames);
    }

    
}
