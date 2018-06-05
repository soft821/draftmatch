<?php

namespace App\Console\Commands;

use App\Helpers\BitPayHelper;
use App\Helpers\CoinbaseHelper;
use App\Helpers\LocationHelper;
use Illuminate\Console\Command;
use Mockery\Exception;
use Weidner\Goutte\GoutteFacade;
use Carbon\Carbon;
use App\Game;
use App\Slate;
use App\User;
use App\Invoice;
use Coinbase\Wallet\Client;
use Coinbase\Wallet\Configuration;
use Coinbase\Wallet\Resource\Account;
use Coinbase\Wallet\Resource\Address;
use Coinbase\Wallet\Enum\CurrencyCode;
use Coinbase\Wallet\Resource\Transaction;
use Coinbase\Wallet\Value\Money;
use GuzzleHttp\Client as HttpClient;


class FoxSportsScraper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'foxSports:scrape';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private $daysMap;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->daysMap = array("Monday"    => "Mon",
                               "Tuesday"   => "Tue",
                               "Wednesday" => "Wed",
                               "Thursday"  => "Thu",
                               "Friday"    => "Fri",
                               "Saturday"  => "Sat",
                               "Sunday"    => "Sun");
    }

    private function getSchedule($year, $week, $seasonType, $active)
    {
        $testSlate = Slate::find('Thu-Mon_'.$year.'_'.$week.'_'.$seasonType);
        if ($testSlate)
        {
            return;
        }

        $slate1 = Slate::updateOrCreate(array('id' => 'Thu-Mon_'.$year.'_'.$week.'_'.$seasonType), ["name" => "Thursday-Monday (All Games)",
                                                                                          "firstDay" => "Thu", "lastDay" => "Mon",
                                                                                          "active"   => $active]);
        $slate2 = Slate::updateOrCreate(array('id' => 'Thu-Sun_'.$year.'_'.$week.'_'.$seasonType), ["name" => "Thursday-Sunday",
                                                                                          "firstDay" => "Thu", "lastDay" => "Sun",
                                                                                          "active"   => $active]);
        $slate3 = Slate::updateOrCreate(array('id' => 'Sun_'    .$year.'_'.$week.'_'.$seasonType),     ["name" => "Sunday Only",
                                                                                          "firstDay" => "Sun", "lastDay" => "Sun",
                                                                                          "active"   => $active]);
        $slate4 = Slate::updateOrCreate(array('id' => 'Sun-Mon_'.$year.'_'.$week.'_'.$seasonType), ["name" => "Sunday-Monday",
                                                                                          "firstDay" => "Sun", "lastDay" => "Mon",
                                                                                          "active"   => $active]);
        $slate5 = Slate::updateOrCreate(array('id' => 'Mon_'    .$year.'_'.$week.'_'.$seasonType),     ["name" => "Monday Only",
                                                                                          "firstDay" => "Mon", "lastDay" => "Mon",
                                                                                          "active"   => $active]);

        $crawler = GoutteFacade::request('GET', 'http://www.foxsports.com/nfl/schedule?season='.$year.'&seasonType=1&week='.($week + 1));

        \Log::info('http://www.foxsports.com/nfl/schedule?season='.$year.'&seasonType=1'.'&week='.($week + 1));
        $table  = $crawler->filter('#wisfoxbox table[class="wisbb_scheduleTable"]');
        $theads = $table->filter('thead');
        $tbodys = $table->filter('tbody');

        $i = 0;
        foreach ($tbodys as $tbody) {
            $dateStr   = trim($theads->getNode($i)->nodeValue);
            $dayMonth  = preg_split('/\s+/', trim(explode(',',$dateStr)[1]));
            $dayAbbr   = $this->daysMap[explode(',', $dateStr)[0]];
            $day       = trim($dayMonth[1]);
            $month     = date_parse(trim($dayMonth[0]));
            $month     = $month['month'];
            foreach ($tbody->getElementsByTagName('tr') as $tr) {
                if ($tr->getAttribute("class") || strlen($tr->getAttribute("class")) > 0) {
                    continue;
                }

                $tds      = $tr->getElementsByTagName('td');
                $awayTeam = trim($tds[0]->getElementsByTagName('label')[0]->nodeValue);
                $awayTeam = (strcmp($awayTeam, "JAX") === 0) ? "JAC" : $awayTeam;
                $time     = explode(" ", trim($tds[1]->getElementsByTagName('span')[1]->nodeValue))[0];
                $AM_PM    = "pm";

                if (strpos($time, 'a') !== false) {
                    $AM_PM = "am";
                }
                $time     = str_replace('p', '', $time);
                $time     = str_replace('a', '', $time);
                $time     = $time.':00';
                $homeTeam = trim($tds[2]->getElementsByTagName('label')[0]->nodeValue);
                $homeTeam = (strcmp($homeTeam, "JAX") === 0) ? "JAC" : $homeTeam;

                $gameId   = $awayTeam . '_' . $homeTeam . '_' . $year . '_' . $week . '_' . $seasonType;
                $UTC      = new \DateTimeZone("US/Eastern");
                \Log::info("OOOOOOO ".$year.'-'.$month.'-'.$day.' '.$time.$AM_PM);
                $date     = new Carbon($year.'-'.$month.'-'.$day.' '.$time.$AM_PM, $UTC);

                $time = $date->hour < 10?'0'.$date->hour:$date->hour;
                $time = $date->minute < 10? $time.':0'.$date->minute:$time.':'.$date->minute;

                // @todo Do not change slate if game time changed
                $game = Game::updateOrCreate(array('id' => $gameId), [
                    "year"       => $year,
                    "seasonType" => $seasonType,
                    "week"       => $week,
                    "date"       => $date,
                    "day"        => $dayAbbr,
                    "time"       => $time,
                    "homeScore"  => 0,
                    "awayScore"  => 0,
                    "homeTeam"   => $homeTeam,
                    "awayTeam"   => $awayTeam]);

                if (strpos($dateStr, 'Thu') !== false) {
                    $game->slates()->syncWithoutDetaching(['Thu-Mon_' . $year . '_' . $week . '_' . $seasonType,
                                                           'Thu-Sun_' . $year . '_' . $week . '_' . $seasonType]);
                }
                else if (strpos($dateStr, 'Sun') !== false) {
                    $game->slates()->syncWithoutDetaching(['Thu-Mon_' . $year . '_' . $week . '_' . $seasonType,
                                                           'Thu-Sun_' . $year . '_' . $week . '_' . $seasonType,
                                                           'Sun_'     . $year . '_' . $week . '_' . $seasonType,
                                                           'Sun-Mon_' . $year . '_' . $week . '_' . $seasonType]);
                }
                else if (strpos($dateStr, 'Mon') !== false) {
                    $game->slates()->syncWithoutDetaching(['Thu-Mon_' . $year . '_' . $week . '_' . $seasonType,
                                                           'Sun-Mon_' . $year . '_' . $week . '_' . $seasonType,
                                                           'Mon_'     . $year . '_' . $week . '_' . $seasonType,]);
                }
                else
                {
                    $game->slates()->syncWithoutDetaching(['Thu-Mon_' . $year . '_' . $week . '_' . $seasonType]);
                }
            }
            $i++;
        }

        Slate::updateOrCreate(array('id' => 'Thu-Mon_'.$year.'_'.$week.'_'.$seasonType), ["firstGame" => $slate1->firstGameDate(),
            "lastGame" => $slate1->lastGameDate()]);
        Slate::updateOrCreate(array('id' => 'Thu-Sun_'.$year.'_'.$week.'_'.$seasonType), ["firstGame" => $slate2->firstGameDate(),
            "lastGame" => $slate2->lastGameDate()]);
        Slate::updateOrCreate(array('id' => 'Sun_'.$year.'_'.$week.'_'.$seasonType),     ["firstGame" => $slate3->firstGameDate(),
            "lastGame" => $slate3->lastGameDate()]);
        Slate::updateOrCreate(array('id' => 'Sun-Mon_'.$year.'_'.$week.'_'.$seasonType), ["firstGame" => $slate4->firstGameDate(),
            "lastGame" => $slate4->lastGameDate()]);
        Slate::updateOrCreate(array('id' => 'Mon_'.$year.'_'.$week.'_'.$seasonType),     ["firstGame" => $slate5->firstGameDate(),
            "lastGame" => $slate5->lastGameDate()]);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //$this->getSchedule("2017", DatesHelper::getCurrentWeek(), DatesHelper::getCurrentRound(), true);
        //$this->getSchedule("2017", "2", "3", true);
        //$this->getSchedule("2017", "3", "3", false);
        //$this->getSchedule("2017", "4", "3", false);

        /*$data = [
            'title' => 'Hi student I hope you like the course',
            'content' => 'This laravel course was created with a lot of love and dedication for you'
        ];

       // \Mail::send(new Welcome());

        \Mail::raw('Welcome', function ($message) {
            $message->subject('Test2')->to('haris.omerovic87@gmail.com');
        });

        \Password::sendResetLink(['email' => 'haris.omerovic87@gmail.com']);*/

        //$var = new ForgotPasswordController();
        //BitPayHelper::createKeys();
        //BitPayHelper::pairIt();
        //BitPayHelper::updateExchangeRate();
        //BitPayHelper::payToUser();

        $configuration = Configuration::apiKey('i5NR996mKZnGRg2O', 'rnKoy7kbN6VI4pThlvinke9MkSHLXMJm');
        $client = Client::create($configuration);

        //$account = new Account();
        //$account->setName('Draftmatch test wallet');
        //$client->createAccount($account);

        $primaryAccount = $client->getPrimaryAccount();

        //$address = new Address();
        //$client->createAccountAddress($account, $address);

        //$transaction = Transaction::send();
        //$transaction->setToEmail('admin@draftmatch.com');
        //$transaction->setAmount(new Money(0.2, CurrencyCode::USD));
        //$transaction->setDescription('For being awesome!');

       // $something = $client->createAccountTransaction($primaryAccount, $transaction);

        //print_r($transaction);

        $transaction = Transaction::request();

        //$transaction->setBitcoinAmount(0.0001);
        //$transaction->setAmount(new Money(1, CurrencyCode::USD));
        //$transaction->setToEmail('haris.omerovic87@gmail.com');
        //$transaction->setDescription('Request money from Haris cancel22');
        //$something = $client->createAccountTransaction($primaryAccount, $transaction);
     //   print_r($transaction);
       // print_r('\n\n--------------');
        //print_r($transaction);


        $title = 'Test title';
        $content = 'Test content';

       /* \Mail::send('emails.pending_invoice', ['amount' => 1, 'username' => 'test', 'link' => 'https://www.coinbase.com/join/598b537efeff9f031ba3d0aa'], function ($message)
        {
            $message->subject('Test subject');
            $message->from('admin@draftmatch.com', 'DraftMatch');

            $message->to('haris.omerovic87@gmail.com');

        });*/


       // $client = new HttpClient(['headers' => ['Authorization' => "Basic ZHJhZnRtYXRjaDpxMTNZIE5uQ3EgSGtETSB4VVRDIDVjbHIgNFBHaw=="]]);

        //$url = 'https://api.fantasydata.net/v3/nfl/stats/JSON/DailyFantasyPlayers/'.$newDate;
        //\Log::info('Retrieving data from url '.$url);

      //  try {
      //      $players = json_decode($client->request('POST', 'https://draftmatch.com/wp-json/wp/v2/users',
       //         ['json' => ['username' => 'mehice2', 'password' => 'Change_01', 'email' => 'eneas.kotromanic2@yahoo.com']])->getBody()->getContents()
       //         , true);
       //     print_r($players);
        //}
        //catch (\GuzzleHttp\Exception\ServerException $exception){
        //    print_r('Exception '.$exception->getMessage());
       // }


        //return response()->json(['message' => 'Request completed']);

        //print_r(LocationHelper::get_location( 43.8474918, 18.3718006));

        print_r(LocationHelper::isUserInAllowedLocation( 40.7471786, -73.9873628));
       // print_r(CoinbaseHelper::getExchangeRate());
        //$timestamp = strtotime('02-11-2017');
        //\Log::info($timestamp);
        //$client->enableActiveRecord();
        //$transactions = $primaryAccount->getTransactions(['ending_before' => '57122994-5cbd-5670-b1e4-4d00ed0ef0fb']);
        //$transactions = $primaryAccount->getTransactions();


        //foreach($transactions as $trans){

        //    $result = $trans->getCreatedAt()->format('Y-m-d-H-i-s');
        //    \Log::info($trans->getStatus().'    '.$trans->getDescription().'    '.$trans->getId().'        '.$trans->getType() .'     '.$result);
        //    \Log::info($trans->getNativeAmount()->getCurrency().'   '.$trans->getNativeAmount()->getAmount().'   '.$trans->getAmount()->getAmount().'    '.$trans->getAmount()->getCurrency());


            /*if ($trans->getStatus() === 'pending') {
                \Log::info('Canceling');
                $client->cancelTransaction($trans);
             //   break;
            }*/
        //}

        //$user = User::find('3');
        //  \Log::info(Invoice::getLastSuccessfull());

         //CoinbaseHelper::sendMoneyToUser($user, 0.55);
       // CoinbaseHelper::updateExchangeRate();
        //CoinbaseHelper::sendRequestToUser()
        //CoinbaseHelper::getExchangeRate();
       // $test = $primaryAccount->getTransaction('77bf96df-ddd1-5fbf-b9d1-8639785737e7');
       // print_r($test);

//a5b0f4af-27ce-566f-9c4c-31fda4b6e8bb
//79cd6470-165b-5335-ab8c-1543a55b371d
//        $test = $client->getAccountTransaction($primaryAccount, '77bf96df-ddd1-5fbf-b9d1-8639785737e7');

  //      print_r($test);

    }
}
