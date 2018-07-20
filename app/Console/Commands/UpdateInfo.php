<?php

namespace App\Console\Commands;

use App\Helpers\CoinbaseHelper;
use App\Helpers\DatesHelper;
use App\Contest;
use App\Invoice;
use App\Check;
use App\Game;
use App\Slate;
use App\FantasyPlayer;
use Coinbase\Wallet\Client;
use Coinbase\Wallet\Configuration;
use GuzzleHttp\Client as HttpClient;

use Illuminate\Console\Command;
use Mockery\Exception;

class UpdateInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function updateSlateStatus()
    {
        \Log::info('Updating slates status => '.DatesHelper::getCurrentDate());

        $updated = Slate::where('active', '=', true)
                    ->where('status', '=', 'PENDING')
                    ->where('firstGame', '<', DatesHelper::getCurrentDate())->update(['status' => 'LIVE']);

        \Log::info('Updated active slates to LIVE status of  '.$updated.' slates successfully.');

        $updated = Slate::where('active', '=', true)
            ->whereDoesntHave('games', function ($query) {
            $query->where('status', '!=', 'HISTORY');})->update(['active' => false, 'status' => 'HISTORY']);

        \Log::info('Updated slate to HISTORY status  '.$updated.' slates successfully.');

        // update slates which does not have games at all to history
        $updated = Slate::where('status', '!=', 'HISTORY')->whereDoesntHave('games', function ($query) {
                $query->where('status', '!=', 'HISTORY');})->update(['active' => false, 'status' => 'HISTORY']);

        \Log::info('Successfully updated '.$updated.' slates');
    }

    public function updateLiveGamesInfo()
    {
        \Log::info('Updating live games results => '.DatesHelper::getCurrentDate());
        //$games = Game::where('status', '=', 'LIVE');

        //\Log::info('Updated '.count($games).' LIVE games scores');

        \Log::info('DATE : '.DatesHelper::getCurrentDate());
        $updated = Game::where('status', '=', 'PENDING')->where('date', '<=', DatesHelper::getCurrentDate())
            ->update(['status' => 'LIVE']);

        \Log::info('Updated '.$updated.' games to LIVE status');
    }

    public function updateLivePleyersInfo()
    {
        \Log::info('Updating live players results => '.DatesHelper::getCurrentDate());
        $players = FantasyPlayer::whereHas('game', function ($query){$query->whereIn('status', array('HISTORY', 'LIVE'));})
            ->whereDoesntHave('entries')->where('updated', '=', false)
            ->update(['updated' => true]);


        \Log::info('Updated '.' live players');
    }

    public function updateFinishedPlayersInfo()
    {
        \Log::info('Update finished players '.DatesHelper::getCurrentDate());

        $updated = FantasyPlayer::where('active', '=', true)
            ->whereHas('game', function($query){$query->where('status', '=', 'HISTORY');})
            ->update(['active' => false]);

        \Log::info('Updated '.$updated.' finished players');
    }

    public function updateContests()
    {
        \Log::info('Updating contests  '.DatesHelper::getCurrentDate());

        // set cancelled contests status
        $updated = Contest::where('status', '=', 'LIVE')
            ->where('filled', '=', false)
            ->update(['status' => 'HANDLE']);
        \Log::info('Updated '.$updated.' to CANCELLED status');

        $updated = Contest::whereIn('status', array('PENDING', 'LIVE'))
                            ->whereHas('slate', function ($query){$query->where('status', 'HISTORY');})
                            ->update(['status' => 'HANDLE']);

        \Log::info('Updated '.$updated.' contests to HISTORY status');

        $updated = Contest::where('status', '=', 'PENDING')
            ->whereHas('slate', function ($query){$query->where('status', 'LIVE');})
            ->update(['status' => 'LIVE']);

        \Log::info('Updated '.$updated.' contests to LIVE status');

       // Entry::where('status', '!=', 'CANCELLED')->with('slate')->update(['entry.status' => 'slate.status']);
            //->whereHas('contest', function ($query){$query->where('filled', '=', false);})
            //->update(['status' => 'cancelled', 'contest.status' => 'cancelled']);

        //Slate::where('active', '=', true)->with('contests')


        /*   $updated = Contest::where('status', '!=', 'history')
               ->where('slate.status', '=', 'live')
               ->update(['active' => true, 'status' => 'live']);*/

    }

    public function updateFinishedEntryScore()
    {
        \Log::info('Updating ENDED contests  '.DatesHelper::getCurrentDate());

        $contests = Contest::where('status', '=', 'HANDLE')->with(['entries'])->get();

        foreach ($contests as $contest) {
            \Log::info('Updating contest with id '.$contest->id);
            if (!$contest->filled) {
                if (!$contest->admin_contest) {
                    $contest->user->balance = $contest->user->balance + $contest->entryFee * CoinbaseHelper::getExchangeRate();
                    $contest->user->save();
                }
                else {
                    if ($contest->entries[0]->user !== null){
                        $contest->entries[0]->user->balance = $contest->entries[0]->user->balance + $contest->entryFee * CoinbaseHelper::getExchangeRate();
                        $contest->entries[0]->user->save();
                    }
                    if ($contest->entries[1]->user !== null){
                        $contest->entries[1]->user->balance = $contest->entries[1]->user->balance + $contest->entryFee * CoinbaseHelper::getExchangeRate();
                        $contest->entries[1]->user->save();
                    }
                }
                $contest->status = 'CANCELLED';
                $contest->save();
                continue;
            }
            else {
                if ($contest->entries()->count() > 1){
                    if ($contest->entries[0]->user === null || $contest->entries[1]->user === null){
                        \Log::info('Contest is filled but it had null user ');
                        if ($contest->entries[0]->user !== null){
                            \Log::info('Added '.$contest->entryFee.' balance to the user '.$contest->entries[0]->user->id);
                            $contest->entries[0]->user->balance = $contest->entries[0]->user->balance + $contest->entryFee * CoinbaseHelper::getExchangeRate();
                            $contest->entries[0]->user->save();
                        }
                        if ($contest->entries[1]->user !== null){
                            \Log::info('Added '.$contest->entryFee.' balance to the user '.$contest->entries[1]->user->id);
                            $contest->entries[1]->user->balance = $contest->entries[1]->user->balance + $contest->entryFee * CoinbaseHelper::getExchangeRate();
                            $contest->entries[1]->user->save();
                        }
                        $contest->status = 'CANCELLED';
                        $contest->save();
                        continue;
                    }
                }
                if (!$contest->entries[0]->fantasyPlayer->updated || !$contest->entries[1]->fantasyPlayer->updated ){
                    continue;
                }

                if ($contest->entries()->count() > 2){
                    \Log::info("ERROR : More then 2 entries ...");
                    $contest->status = "CANCELLED";
                    $contest->save();
                    foreach ($contest->entries() as $entry){
                        if ($entry->user != null){
                            $entry->user = $entry->user + $contest->entryFee;
                            $entry->user->save();
                            $entry->save();
                            $contest->status = 'HISTORY';
                            $contest->save();
                            \Log::info('Added '.$contest->entryFee.' balance to the user '.$entry->user->id);
                        }
                    }
                    continue;
                }
                \Log::info('FP1 '.$contest->entries[0]->fantasyPlayer->fps_live);
                \Log::info('FP2 '.$contest->entries[1]->fantasyPlayer->fps_live);

                if (!$contest->entries[0]->fantasyPlayer->played || !$contest->entries[1]->fantasyPlayer->played){
                    $contest->entries[0]->user->balance +=  ($contest->entryFee * CoinbaseHelper::getExchangeRate());
                    $contest->entries[1]->user->balance += ($contest->entryFee * CoinbaseHelper::getExchangeRate());

                    $contest->entries[0]->user->save();
                    $contest->entries[1]->user->save();

                    if (!$contest->entries[0]->fantasyPlayer->played){
                        \Log::info('Player with id '.$contest->entries[0]->fantasyPlayer->id.' did not played, money will be returned to players');
                    }
                    if (!$contest->entries[1]->fantasyPlayer->played){
                        \Log::info('Player with id '.$contest->entries[1]->fantasyPlayer->id.' did not played, money will be returned to players');
                    }

                    \Log::info('Contest is cancenled. Adding '.$contest->entryFee.' to user '.$contest->entries[0]->user->id.
                        ' and user '.$contest->entries[1]->user->id.' accounts ...');

                    $contest->status = 'HISTORY';
                    $contest->save();
                    continue;
                }
                if ($contest->entries[0]->fantasyPlayer->fps_live < $contest->entries[1]->fantasyPlayer->fps_live) {
                    $contest->entries[0]->winner = false;
                    $contest->entries[0]->winning = 0;
                    $contest->entries[1]->winner = true;
                    $contest->entries[1]->winning = 1.8 * $contest->entryFee;
                    $contest->entries[1]->user->balance += 1.8 * ($contest->entryFee * CoinbaseHelper::getExchangeRate());

                    $contest->entries[0]->user->loses = $contest->entries[0]->user->loses + 1;
                    $contest->entries[0]->user->history_count = $contest->entries[0]->user->history_count + 1;
                    $contest->entries[0]->user->history_entry = $contest->entries[0]->user->history_entry + $contest->entryFee;

                    $contest->entries[1]->user->wins = $contest->entries[1]->user->wins + 1;
                    $contest->entries[1]->user->history_count = $contest->entries[1]->user->history_count + 1;
                    $contest->entries[1]->user->history_entry = $contest->entries[1]->user->history_entry + $contest->entryFee;
                    $contest->entries[1]->user->history_winning = $contest->entries[1]->user->history_winning + 1.8 * $contest->entryFee;

                    $contest->entries[0]->user->save();
                    $contest->entries[1]->user->save();
                    \Log::info('User  '.$contest->entries[1]->user->username.' is winner. Adding '.(2 * $contest->entryFee).' to his balance');
                }
                if ($contest->entries[0]->fantasyPlayer->fps_live > $contest->entries[1]->fantasyPlayer->fps_live) {
                    $contest->entries[0]->winner = true;
                    $contest->entries[0]->winning = 1.8 * $contest->entryFee;
                    $contest->entries[1]->winner = false;
                    $contest->entries[1]->winning = 0;
                    $contest->entries[0]->user->balance += 1.8 * ($contest->entryFee * CoinbaseHelper::getExchangeRate());

                    $contest->entries[0]->user->wins = $contest->entries[0]->user->wins + 1;
                    $contest->entries[0]->user->history_count = $contest->entries[0]->user->history_count + 1;
                    $contest->entries[0]->user->history_entry = $contest->entries[0]->user->history_entry + $contest->entryFee;
                    $contest->entries[0]->user->history_winning = $contest->entries[0]->user->history_winning + 1.8 * $contest->entryFee;

                    $contest->entries[1]->user->loses = $contest->entries[1]->user->loses + 1;
                    $contest->entries[1]->user->history_count = $contest->entries[1]->user->history_count + 1;
                    $contest->entries[1]->user->history_entry = $contest->entries[1]->user->history_entry + $contest->entryFee;
                    //$contest->entries[1]->user->history_winning = $contest->entries[1]->user->history_winning - $contest->entryFee;

                    $contest->entries[0]->user->save();
                    $contest->entries[1]->user->save();
                    \Log::info('PASSED');
                    \Log::info('User  '.$contest->entries[0]->user->username.' is winner. Adding '.(2 * $contest->entryFee).' to his balance');
                }
                if ($contest->entries[0]->fantasyPlayer->fps_live === $contest->entries[1]->fantasyPlayer->fps_live) {
                    $contest->entries[0]->winner = true;
                    $contest->entries[0]->winning = $contest->entryFee;
                    $contest->entries[1]->winner = true;
                    $contest->entries[1]->winning = $contest->entryFee;
                    $contest->entries[0]->user->balance +=  $contest->entryFee * CoinbaseHelper::getExchangeRate();
                    $contest->entries[1]->user->balance += $contest->entryFee * CoinbaseHelper::getExchangeRate();

                    $contest->entries[0]->user->save();
                    $contest->entries[1]->user->save();
                    \Log::info('Result is tie. Adding '.$contest->entryFee.' to user '.$contest->entries[0]->user->id.
                        ' and user '.$contest->entries[1]->user->id.' accounts ...');
                }

                \Log::info('Updating entries');
                $contest->entries[0]->save();
                $contest->entries[1]->save();
                \Log::info('Updating contest status to HISTORY');

                $contest->status = 'HISTORY';
                $contest->save();
            }
        }

        \Log::info('Updated '.count($contests).' contests results');
    }

    public function updateLiveEntryScore()
    {
        \Log::info('Updating LIVE contests  '.DatesHelper::getCurrentDate());

        $contests = Contest::where('status', '=', 'LIVE')->get();

        foreach ($contests as $contest) {
            \Log::info('Updating contest with status LIVE and id '.$contest->id);

            if (!$contest->filled) {
                if (!$contest->admin_contest) {
                    $contest->user->balance = $contest->user->balance + $contest->entryFee * CoinbaseHelper::getExchangeRate();
                    $contest->user->save();
                }
                $contest->status = 'CANCELLED';
                $contest->save();
                continue;
            }

            if (!$contest->entries[0]->user || !$contest->entries[0]->user->id ||
                !$contest->entries[1]->user || !$contest->entries[1]->user->id){
                continue;
            }
            \Log::info('FP1 '.$contest->entries[0]->fantasyPlayer->fps_live);
            \Log::info('FP2 '.$contest->entries[1]->fantasyPlayer->fps_live);

            // if previously it was tie
            if ($contest->entries[0]->fantasyPlayer->fps_live < $contest->entries[1]->fantasyPlayer->fps_live) {
                $contest->entries[0]->winner = false;
                $contest->entries[0]->winning = 0;
                $contest->entries[1]->winner = true;
                $contest->entries[1]->winning = 1.8 * $contest->entryFee;
                \Log::info('User  '.$contest->entries[1]->user->username.' is winning currently');
            }
            if ($contest->entries[0]->fantasyPlayer->fps_live > $contest->entries[1]->fantasyPlayer->fps_live) {
                $contest->entries[0]->winner = true;
                $contest->entries[0]->winning = 1.8 * $contest->entryFee;
                $contest->entries[1]->winner = false;
                $contest->entries[1]->winning = 0;
                \Log::info('User  '.$contest->entries[0]->user->username.' is winning currently.');
            }
            if ($contest->entries[0]->fantasyPlayer->fps_live === $contest->entries[1]->fantasyPlayer->fps_live) {
                $contest->entries[0]->winner = true;
                $contest->entries[0]->winning = 0.9 * $contest->entryFee;
                $contest->entries[1]->winner = true;
                $contest->entries[1]->winning = 0.9 * $contest->entryFee;
                \Log::info('Result is tie currently');
            }

            $contest->entries[0]->save();
            $contest->entries[1]->save();
        }

        \Log::info('Updated '.count($contests).' contests results');
    }

    public static function checkInvoices(){
        $invoices = Invoice::getPendingInvoices();
        if (count($invoices) === 0){
            \Log::info('No pending invoices');
        }

        \Log::info('Checking invoices. Found '.count($invoices));

        $configuration = Configuration::apiKey('i5NR996mKZnGRg2O', 'rnKoy7kbN6VI4pThlvinke9MkSHLXMJm');
        $client = Client::create($configuration);
        $primaryAccount = $client->getPrimaryAccount();
        $client->enableActiveRecord();

        $allTransactions = [];
        if ($invoices->count() > 0){
            $lastSuccessfullInvoice = Invoice::getLastSuccessfull();

            if ($lastSuccessfullInvoice != null){
                \Log::info('Got some successfull invo');
                $allInvoices = $primaryAccount->getTransactions(['ending_before' => $lastSuccessfullInvoice->invoiceId]);
            }
            else{
                \Log::info('Got some successfull invo');
                $allInvoices = $primaryAccount->getTransactions();
            }

            foreach($allInvoices as $tmpInvoice){
                $desc = $tmpInvoice->getDescription();
                /*if (strpos($tmpInvoice->getDescription(), 'Transaction ID: ') !== false) {
                    \Log::info('Got desc '.$tmpInvoice->getDescription());
                    $removeTrID = explode('Transaction ID: ', $tmpInvoice->getDescription());
                    \Log::info('Exploded 1'.$removeTrID[1]);
                    $removeNewLine = explode('Please', $removeTrID[1]);
                    \Log::info('Created desc '.$removeNewLine[0]);
                    $desc = $removeNewLine[0];
                }*/
                $allTransactions[$desc] = $tmpInvoice;
            }
        }

        $adminEmail = 'admin@draftmatch.com';

        foreach ($invoices as $invoice) {
            \Log::info("INVOICE_ID " . $invoice->invoiceId);
            if ($invoice->invoiceId === null) {
                $invoice->status = 'failed';
                $invoice->save();
                continue;
            }
            $userEmail = $invoice->email;
            try {
                if (array_key_exists($invoice->description, $allTransactions)) {
                    \Log::info('Invoice found ...');
                    $inv = $allTransactions[$invoice->description];
                } else {
                    \Log::info('Invoice with id ' . $invoice->invoiceId . ' not found. Retries  '.$invoice->retries);
                    if ($invoice->retries > 15) {
                        $invoice->status = 'failed';
                        $invoice->save();
                    } else {
                        $invoice->retries = $invoice->retries + 1;
                        $invoice->save();
                    }
                    continue;
                }
                if ($invoice->invoiceId === null){
                    $invoice->invoiceId = $inv->getId();
                    $invoice->save();
                }

                if ($inv->getStatus() === 'pending') {
                    $now = new \DateTime();
                    $diff = $now->diff($inv->getCreatedAt());

                    $hours = $diff->h;
                    $hours = $hours + ($diff->days * 24);

                    \Log::info('Difference in hours ' . $hours);

                    if ($hours > 23) {
                        try {
                            \Log::info('Invoice with id ' . $invoice->id . ' expired ...');
                            $client->cancelTransaction($inv);
                            try {
                                $invoice->status = 'failed';
                                $invoice->save();
                            } catch (Exception $e) {
                                \Log::info('Error saving canceled transaction ' . $invoice->id . ' in database');
                            }

                            try {
                                \Mail::send('emails.admin_invoices', ['text' => 'Transaction with id ' . $invoice->id . ' expired for user with email ' . $invoice->email . '.',
                                    'header' => 'DraftMatch invoice expired'],
                                    function ($message) use ($userEmail, $adminEmail)
                                    {
                                        $message->subject('Expired transaction notification ' . $userEmail);

                                        $message->to($adminEmail);
                                    });

                                \Mail::send('emails.admin_invoices', ['text' => 'Your request for depositing ' . $invoice->amount . '$ to DraftMatch expired',
                                    'header' => 'DraftMatch invoice expired'],
                                    function ($message) use ($userEmail, $adminEmail)
                                    {
                                        $message->subject('DraftMatch invoice expired');

                                        $message->to($userEmail);
                                    });
                            }
                            catch (Exception $exception){
                                \Log::info('Error sending email notifications for canceling transaction ...');
                            }
                        } catch (Exception $e) {
                            \Log::info('Error canceling transaction with id ' . $invoice->id);
                            continue;
                        }
                    }
                    continue;
                } else if ($inv->getStatus() === 'completed') {
                    if ($invoice->type === 'request') {
                        try {
                            \Log::info('Transaction request with id ' . $invoice->id . ' completed. Adding money to user ' . $invoice->user_id . ' account');
                            \Log::info('Adding ' . $inv->getAmount()->getAmount() . $inv->getAmount()->getCurrency());
                            \Log::info('Old id ' . $invoice->invoiceId . ' new id ' . $inv->getId());
                            $invoice->status = 'completed';
                            $invoice->invoiceId = $inv->getId();
                            $invoice->save();

                            $invoice->user->balance = $invoice->user->balance + $inv->getAmount()->getAmount();
                            $invoice->user->deposit = $invoice->user->deposit + $inv->getAmount()->getAmount();
                            $invoice->user->save();

                            try {
                                \Mail::send('emails.admin_invoices', ['text' => 'Transaction with id ' . $invoice->id . ' completed for user ' . $invoice->email . '.',
                                    'header' => 'Completed DraftMatch invoice'],
                                    function ($message) use ($userEmail, $adminEmail)
                                {
                                    $message->subject('Completed Draftmatch invoice ' . $userEmail);

                                    $message->to($adminEmail);
                                });

                                /*\Mail::raw('Request for adding ' . $invoice->amount . '$ (' . $inv->getAmount()->getAmount() . 'BTC) to draftmatch account successfully completed.',
                                    function ($message) use ($userEmail) {
                                        $message->subject('Draftmatch invoice completed')->to($userEmail);
                                    });*/
                            }
                            catch (Exception $exception){
                                \Log::info('Sending email notification for completed request transaction '.$invoice->id.' has failed. Resaon : '.$exception->getMessage());
                            }
                        } catch (Exception $e) {
                            \Log::info('Error occurred while trying to save transaction in database ');
                            $invoice->status = 'pending';
                            $invoice->save();
                        }
                    } else if ($invoice->type === 'send') {
                        try {
                            \Log::info('Transaction send with id ' . $invoice->id . ' completed. Adding money to user ' . $invoice->user_id . ' account');
                            \Log::info('Adding ' . $inv->getAmount()->getAmount() . $inv->getAmount()->getCurrency());

                            $invoice->status = 'completed';
                            $invoice->save();

                            $invoice->user->balance = $invoice->user->balance + $inv->getAmount()->getAmount();
                            $invoice->user->save();

                            try {

                                \Mail::send('emails.admin_invoices', ['text' => 'Transaction with id ' . $invoice->id . ' completed for user ' . $invoice->email . '.',
                                    'header' => 'Completed Draftmatch invoice'],
                                    function ($message) use ($userEmail, $adminEmail)
                                    {
                                        $message->subject('Completed Draftmatch invoice ' .$userEmail);

                                        $message->to($adminEmail);
                                    });

                                /*\Mail::raw('Request for withdrawing ' . $invoice->amount . '$ from draftmatch account successfully completed.',
                                    function ($message) use ($userEmail) {
                                        $message->subject('Draftmatch invoice completed')->to($userEmail);
                                    });*/
                            }
                            catch (Exception $exception){
                                \Log::info('Sending email notification for completed send transaction '.$invoice->id.' has failed. Resaon : '.$exception->getMessage());
                            }
                        } catch (Exception $e) {
                            \Log::info('Error occurred while trying to save transaction in database ');
                            $invoice->status = 'pending';
                            $invoice->save();
                        }
                    }
                } else if ($inv->getStatus() === 'failed') {
                    \Log::info('Transaction with id ' . $invoice->id . ' failed ');
                    $invoice->status = 'failed';
                    $invoice->save();
                    try {
                        \Mail::raw('Transaction with id ' . $invoice->id . ' processing failed.',
                            function ($message) use ($invoice, $adminEmail) {
                                $message->subject('Failed Draftmatch invoice ' . $invoice->email)->to($adminEmail);
                            });

                        /*if ($invoice->type === 'request') {
                            \Mail::raw('Request for adding ' . $invoice->amount . '$ to draftmatch account have failed.',
                                function ($message) use ($userEmail) {
                                    $message->subject('Draftmatch invoice failed')->to($userEmail);
                                });
                        } else {
                            \Mail::raw('Request for withdrawing ' . $invoice->amount . '$ from draftmatch account have failed.',
                                function ($message) use ($userEmail) {
                                    $message->subject('Draftmatch invoice failed')->to($userEmail);
                                });
                        }*/
                    }
                    catch (Exception $exception){
                        \Log::info('Sending email notification for failed transaction '.$invoice->id.' has failed. Resaon : '.$exception->getMessage());
                    }
                }
            } catch (Exception $exception) {
                if ($invoice->retries > 5) {
                    $invoice->status = 'failed';
                    $invoice->save();
                } else {
                    $invoice->retries = $invoice->retries + 1;
                    $invoice->save();
                }
                continue;
            }
        }

        \Log::info('Finished with updating invoices');
    }

    public static function checkCheckbook(){

        $dbChecks = Check::getPendingChecks();
        if (count($dbChecks) === 0){
            \Log::info('No pending checks');
        }

        \Log::info('Checking checks. Found '.count($dbChecks));

        try {

            $client = new HttpClient(['headers' => ['Authorization' => "0a7990396d731af2d7802805b1c573ed:bdb71b58f24f853c6f60f7a03951e9b5",
                'Accept' => 'application/json'
                ]
            ]);
            $url = env('CHECKBOOK_URL', 'https://checkbook.io').'/v3/check';


            $checksFromSite = json_decode($client->request('GET', $url)->getBody()->getContents(), true);
            
            $siteChecks = $checksFromSite['checks'];
            $allTransactions = [];
            foreach ($siteChecks as $check) {
                $id = $check['id'];
                $allTransactions[$id] = $check;
            }

            foreach ($dbChecks as $check) {
                if (array_key_exists($check->checkid, $allTransactions)) {
                    
                    $sCheck = $allTransactions[$check->checkid];

                } else {
                    continue;
                }

                if ($sCheck['status'] === 'IN_PROCESS') {
                    continue;
                } else if ($sCheck['status'] === 'PAID') {
                    if ($check->type === 'INCOMING') {
                        
                        try {
                            $check->status = 'PAID';
                            $check->checked = true;
                            $check-save();

                            $check->user->balance = $check->user->balance + $check->amount /1.0 * CoinbaseHelper::getExchangeRate();
                            $check->user->deposit = $check->user->deposit + $check->amount /1.0 * CoinbaseHelper::getExchangeRate();
                            $check->user->save(); 
                        } catch (Exception $e) {
                            $check->status = 'IN_PROCESS';
                            $check->checked = false;
                            $check-save();
                            \Log::info('Error occurred while trying to save check in database ');
                            continue;
                        }
                    } else if ($check->type === 'OUTCOMING') {
                        try {
                            $check->status = 'PAID';
                            $check->checked = true;
                            $check-save();

                            $check->user->balance = $check->user->balance - $check->amount /1.0 * CoinbaseHelper::getExchangeRate();
                            $check->user->save(); 
                        } catch (Exception $e) {
                            $check->status = 'IN_PROCESS';
                            $check->checked = false;
                            $check-save();
                            \Log::info('Error occurred while trying to save check in database ');
                            continue;
                        }
                    }
                } else if ($sCheck['status'] === 'VOID') {
                    $check->status = 'VOID';
                    $check->checked = true;
                    $check->save();
                    if ($check->type === 'INCOMING') {
                        
                        try {
                            $check->user->balance = $check->user->balance - $check->amount /1.0 * CoinbaseHelper::getExchangeRate();
                            $check->user->deposit = $check->user->deposit - $check->amount /1.0 * CoinbaseHelper::getExchangeRate();
                            $check->user->save(); 
                        } catch (Exception $e) {
                            $check->status = 'IN_PROCESS';
                            $check->checked = false;
                            $check-save();
                            \Log::info('Error occurred while trying to save check in database ');
                            continue;
                        }
                    } else if ($check->type === 'OUTCOMING') {
                        try {
                            $check->user->balance = $check->user->balance + $check->amount /1.0 * CoinbaseHelper::getExchangeRate();
                            $check->user->save(); 
                        } catch (Exception $e) {
                            $check->status = 'IN_PROCESS';
                            $check->checked = false;
                            $check-save();
                            \Log::info('Error occurred while trying to save check in database ');
                            continue;
                        }
                    }
                } else if ($sCheck['status'] === 'EXPIRED') {
                    $check->status = 'EXPIRED';
                    $check->checked = true;
                    $check->save();
                    if ($check->type === 'INCOMING') {
                        
                        try {
                            $check->user->balance = $check->user->balance - $check->amount /1.0 * CoinbaseHelper::getExchangeRate();
                            $check->user->deposit = $check->user->deposit - $check->amount /1.0 * CoinbaseHelper::getExchangeRate();
                            $check->user->save(); 
                        } catch (Exception $e) {
                            $check->status = 'IN_PROCESS';
                            $check->checked = false;
                            $check-save();
                            \Log::info('Error occurred while trying to save check in database ');
                            continue;
                        }
                    } else if ($check->type === 'OUTCOMING') {
                        try {
                            $check->user->balance = $check->user->balance + $check->amount /1.0 * CoinbaseHelper::getExchangeRate();
                            $check->user->save(); 
                        } catch (Exception $e) {
                            $check->status = 'IN_PROCESS';
                            $check->checked = false;
                            $check-save();
                            \Log::info('Error occurred while trying to save check in database ');
                            continue;
                        }
                    }
                } else if ($sCheck['status'] === 'FAILED') {
                    $check->status = 'FAILED';
                    $check->checked = true;
                    $check->save();
                    if ($check->type === 'INCOMING') {
                        
                        try {
                            $check->user->balance = $check->user->balance - $check->amount /1.0 * CoinbaseHelper::getExchangeRate();
                            $check->user->deposit = $check->user->deposit - $check->amount /1.0 * CoinbaseHelper::getExchangeRate();
                            $check->user->save(); 
                        } catch (Exception $e) {
                            $check->status = 'IN_PROCESS';
                            $check->checked = false;
                            $check-save();
                            \Log::info('Error occurred while trying to save check in database ');
                            continue;
                        }
                    } else if ($check->type === 'OUTCOMING') {
                        try {
                            $check->user->balance = $check->user->balance + $check->amount /1.0 * CoinbaseHelper::getExchangeRate();
                            $check->user->save(); 
                        } catch (Exception $e) {
                            $check->status = 'IN_PROCESS';
                            $check->checked = false;
                            $check-save();
                            \Log::info('Error occurred while trying to save check in database ');
                            continue;
                        }
                    }
                }
            }

        }catch (Exception $e) {
            \Log::info('Error was encountered when getting digital checks.');
        }
    }

    public function handle()
    {
        //$updated = Contest::where('status', '!=', 'history')
        //    ->update(['active' => \DB::raw('slate.status')]);

        \Log::info('Updating data ...');

        //$slate = Slate::where('id', 'like', '%2017_'.DatesHelper::getCurrentWeek().'_'.DatesHelper::getCurrentRound().'%')->get()->first();

      //  if (!$slate)
       // {
       //     $this->collectGames();
       //     $this->collectPlayers();
       //     $this->createAdminContests();
       // }


        $this->updateLiveGamesInfo();
        $this->updateLivePleyersInfo();
       // $this->updateFinishedGamesInfo();
        $this->updateFinishedPlayersInfo();

        $this->updateSlateStatus();
        $this->updateContests();
        $this->updateFinishedEntryScore();
        $this->updateLiveEntryScore();
        $this->updateFinishedEntryScore();
        
    }
}
