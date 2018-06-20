<?php

namespace App\Console\Commands;

use App\Common\Consts\Contest\TierConsts;
use App\Helpers\DatesHelper;
use Illuminate\Console\Command;
use Weidner\Goutte\GoutteFacade;
use App\FantasyPlayer;
use App\Game;

class FantasyProsScraper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fantasyPros:scrape';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private $teamsMap;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->teamsMap = array("Seattle Seahawks" => "SEA",
            "Jacksonville Jaguars" => "JAC",
            "Washington Redskins" => "WAS",
            "Cincinnati Bengals"  => "CIN",
            "Tennessee Titans" => "TEN",
            "Arizona Cardinals" => "ARI",
            "Los Angeles Chargers" => "LAC",
            "Denver Broncos" => "DEN",
            "Kansas City Chiefs" => "KC",
            "Miami Dolphins" => "MIA",
            "Chicago Bears" => "CHI",
            "Dallas Cowboys" => "DAL",
            "Pittsburgh Steelers" => "PIT",
            "Houston Texans" => "HOU",
            "New York Giants" => "NYG",
            "New England Patriots" => "NE",
            "Philadelphia Eagles" => "PHI",
            "Baltimore Ravens" => "BAL",
            "Buffalo Bills" => "BUF",
            "New Orleans Saints" => "NO",
            "Atlanta Falcons" => "ATL",
            "Los Angeles Rams" => "LAR",
            "San Francisco 49ers" => "SF",
            "Detroit Lions" => "DET",
            "Indianapolis Colts" => "IND",
            "New York Jets" => "NYJ",
            "Oakland Raiders" => "OAK",
            "Cleveland Browns" => "CLE",
            "Carolina Panthers" => "CAR",
            "Green Bay Packers" => "GB",
            "Minnesota Vikings" => "MIN",
            "Tampa Bay Buccaneers" => "TB");
    }

    private function scrapeProjections($year, $week, $seasonType, $position)
    {
        $gamesInRange = Game::with('slates')->where('id', 'like', '%'.$year . '_' . (string)((float)$week). '_'.$seasonType.'%')->get();

        $gamesMap  = array();
        $slatesMap = array();
        foreach($gamesInRange as $game ) {
            $gamesMap[$game->homeTeam] = $game->id;
            $gamesMap[$game->awayTeam] = $game->id;
            $slatesMap[$game->id]      = $game->slates;
        }

        //$crawler = GoutteFacade::request('GET', 'https://www.fantasypros.com/nfl/projections/'.$position.'.php?week='.$week);
        $crawler = GoutteFacade::request('GET', 'https://www.fantasypros.com/nfl/projections/'.$position.'.php?week=0');

        $table   = $crawler->filter('#data');
        $tbody = $table->filter('tbody')->getNode(0);

        $trs = $tbody->getElementsByTagName('tr');
        $tested = false;

        foreach ($trs as $tr) {
            $tds = $tr->getElementsByTagName('td');
            $playerId = $tr->getAttribute("class");
            $playerId = str_replace("mpb-player-9001", "",$playerId);
            if (strlen($playerId) < 2){
                continue;
            }
            $playerId = $playerId.'_'.$year.'_'.$week.'_'.$seasonType;

            if (!$tested)
            {
                $playerTest = FantasyPlayer::find($playerId);
                if ($playerTest)return;
                $tested = true;
            }

            $name     = trim($tds[0]->getElementsByTagName('a')[0]->nodeValue);
            if (strcmp($position, 'dst') == 0) {
                $team = $this->teamsMap[$name];
            }
            else {
                $team = trim(str_replace($name, '', $tds[0]->nodeValue));
            }
            if (!array_key_exists($team, $gamesMap)){
                continue;
            }
            $pos      = strtoupper($position);
            $games    = 16;

            $passAtt = "0";$complPass = "0";$passYds = "0";$passTds = "0";$passInt = "0";$rushAtt = "0";$rushYds = "0";$rushTds = "0";
            $fumbles = "0";$fps = "0";$rec="0";$recYds = "0";$recTds = "0"; $fg = "0"; $fga = "0"; $xpts = "0";
            $defSacks = "0"; $defInt = "0";$fumblesRecovered = "0";$defTds = "0"; $safeties = "0";$pointsAllowed = "0";$ydsAgainst = "0";

            // @todo do not change tier if fps changed, probably better to use previous statistics not projection
            if (strcmp($position, 'qb') == 0) {
                $passAtt       = trim((string)round((float)$tds[1]->nodeValue/16.0), 2);
                $complPass     = trim((string)round((float)$tds[2]->nodeValue/16.0), 2);
                $passYds       = trim((string)round((float)$tds[3]->nodeValue/16.0), 2);
                $passTds       = trim((string)round((float)$tds[4]->nodeValue/16.0), 2);
                $passInt       = trim((string)round((float)$tds[5]->nodeValue/16.0), 2);
                $rushAtt       = trim((string)round((float)$tds[6]->nodeValue/16.0), 2);
                $rushYds       = trim((string)round((float)$tds[7]->nodeValue/16.0), 2);
                $rushTds       = trim((string)round((float)$tds[8]->nodeValue/16.0), 2);
                $fumbles       = trim((string)round((float)$tds[9]->nodeValue/16.0), 2);
                $fps           = trim((string)round((float)$tds[10]->nodeValue/16.0), 2);
            }
            else if (strcmp($position, 'rb') == 0 || strcmp($position, 'wr') == 0) {
                $rushAtt       = trim((string)round((float)$tds[1]->nodeValue/16.0), 2);
                $rushYds       = trim((string)round((float)$tds[2]->nodeValue/16.0), 2);
                $rushTds       = trim((string)round((float)$tds[3]->nodeValue/16.0), 2);
                $rec           = trim((string)round((float)$tds[4]->nodeValue/16.0), 2);
                $recYds        = trim((string)round((float)$tds[5]->nodeValue/16.0), 2);
                $recTds        = trim((string)round((float)$tds[6]->nodeValue/16.0), 2);
                $fumbles       = trim((string)round((float)$tds[7]->nodeValue/16.0), 2);
                $fps           = trim((string)round((float)$tds[8]->nodeValue/16.0), 2);
            }
            else if (strcmp($position, 'te') == 0) {
                $rec        = trim((string)round((float)$tds[1]->nodeValue/16.0), 2);
                $recYds     = trim((string)round((float)$tds[2]->nodeValue/16.0), 2);
                $recTds     = trim((string)round((float)$tds[3]->nodeValue/16.0), 2);
                $fumbles    = trim((string)round((float)$tds[4]->nodeValue/16.0), 2);
                $fps        = trim((string)round((float)$tds[5]->nodeValue/16.0), 2);
            }
            else if (strcmp($position, 'k') == 0) {
                $fg        = trim((string)round((float)$tds[1]->nodeValue/16.0), 2);
                $fga       = trim((string)round((float)$tds[2]->nodeValue/16.0), 2);
                $xpts      = trim((string)round((float)$tds[3]->nodeValue/16.0), 2);
                $fps       = trim((string)round((float)$tds[4]->nodeValue/16.0), 2);
            }
            else if (strcmp($position, 'dst') == 0) {
                $defSacks           = trim((string)round((float)$tds[1]->nodeValue/16.0), 2);
                $defInt             = trim((string)round((float)$tds[2]->nodeValue/16.0), 2);
                $fumblesRecovered   = trim((string)round((float)$tds[3]->nodeValue/16.0), 2);
                $defTds             = trim((string)round((float)$tds[5]->nodeValue/16.0), 2);
                $safeties           = trim((string)round((float)$tds[7]->nodeValue/16.0), 2);
                $pointsAllowed      = trim((string)round((float)$tds[8]->nodeValue/16.0), 2);
                $ydsAgainst         = trim((string)round((float)$tds[9]->nodeValue/16.0), 2);
                $fps                = trim((string)round((float)$tds[10]->nodeValue/16.0), 2);
            }


            $tier = TierConsts::$TIER_E;
            if ($fps > 16)
            {
                $tier = TierConsts::$TIER_A;
            }
            else if ($fps > 12)
            {
                $tier = TierConsts::$TIER_B;
            }
            else if ($fps > 8)
            {
                $tier = TierConsts::$TIER_C;
            }
            else if ($fps > 4)
            {
                $tier = TierConsts::$TIER_D;
            }

            $fantasyPlayer = FantasyPlayer::updateOrCreate(array('id'=>$playerId),[
                "name"             => $name,
                "position"         => $pos,
                "team"             => $team,
                "tier"             => $tier,
                "games"            => (string)$games,
                "passAtt"          => $passAtt,
                "complPass"        => $complPass,
                "paYd"             => $passYds,
                "paTd"             => $passTds,
                "int"              => $passInt,
                "rushAtt"          => $rushAtt,
                "ruYd"             => $rushYds,
                "ruTd"             => $rushTds,
                "fum"              => $fumbles,
                "rec"              => $rec,
                "reYd"             => $recYds,
                "reTd"             => $recTds,
                "fg"               => $fg,
                "fgA"              => $fga,
                "xp"               => $xpts,
                "defSacks"         => $defSacks,
                "defInt"           => $defInt,
                "fumblesRecovered" => $fumblesRecovered,
                "defTds"           => $defTds,
                "safeties"         => $safeties,
                "pointsAllowed"    => $pointsAllowed,
                "ydsAgainst"       => $ydsAgainst,
                "fps"              => $fps,
                "game_id"          => array_key_exists($team, $gamesMap)?$gamesMap[$team]:null]);

            if (array_key_exists ($team, $gamesMap)) {
  //              $fantasyPlayer->game()->associate($gamesMap[$team]);
                $fantasyPlayer->slates()->syncWithoutDetaching($slatesMap[$gamesMap[$team]]);
//                $fantasyPlayer->save();
            }
        }
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->scrapeProjections('2018', DatesHelper::getCurrentWeek(), DatesHelper::getCurrentRound(),'qb' );
        $this->scrapeProjections('2018', DatesHelper::getCurrentWeek(), DatesHelper::getCurrentRound(),'rb' );
        $this->scrapeProjections('2018', DatesHelper::getCurrentWeek(), DatesHelper::getCurrentRound(),'wr' );
        $this->scrapeProjections('2018', DatesHelper::getCurrentWeek(), DatesHelper::getCurrentRound(),'te' );
        $this->scrapeProjections('2018', DatesHelper::getCurrentWeek(), DatesHelper::getCurrentRound(),'k' );
        $this->scrapeProjections('2018', DatesHelper::getCurrentWeek(), DatesHelper::getCurrentRound(),'dst' );
    }
}
