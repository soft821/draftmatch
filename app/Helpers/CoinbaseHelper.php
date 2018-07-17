<?php
/**
 * Created by PhpStorm.
 * User: hariso
 * Date: 03/11/2017
 * Time: 17:47
 */

namespace App\Helpers;

use App\BitCoinInfo;
use Coinbase\Wallet\Client;
use Coinbase\Wallet\Configuration;
use Coinbase\Wallet\Enum\CurrencyCode;
use Coinbase\Wallet\Resource\Transaction;
use Coinbase\Wallet\Value\Money;
use App\Invoice;
use Mockery\Exception;
use GuzzleHttp\Exception\ClientException;
use Coinbase\Wallet\Exception\ValidationException;


class CoinbaseHelper{
    public static function sendMoneyToUser($user, $amount){
        set_error_handler(function($errno, $errstr, $errfile, $errline ){
            throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
        });
        CoinbaseHelper::updateExchangeRate();
        $retMessage = "";
        $adminEmail = 'admin@draftmatch.com';
        $configuration = Configuration::apiKey('i5NR996mKZnGRg2O', 'rnKoy7kbN6VI4pThlvinke9MkSHLXMJm');
        $client = Client::create($configuration);

        $primaryAccount = $client->getPrimaryAccount();

        $transaction = Transaction::send();
        $transaction->setToEmail($user->email);
        $transaction->setAmount(new Money($amount, CurrencyCode::USD));
        $transaction->setDescription('Transaction ID: '.time().'_'.($user->id).'_'.($amount).'
        Sending '.$amount.'$ to user '.$user->email);
        $amonunt = $transaction->getAmount()->getAmount();
            dd($amount);
        if ($amount >= 0) {
            $takenAmount = $amount * CoinbaseHelper::getExchangeRate();

            if ($takenAmount > $user->balance) {
                throw new Exception("You are trying to withdraw more money then you have on your account.");
            }
        }
        else{
            \Log::info('User balance '.$user->balance);
            $amount = $user->balance * 1.0/CoinbaseHelper::getExchangeRate();
            \Log::info('Taking amount '.round($amount, 2));
            $transaction->setAmount(new Money(round($amount, 2), CurrencyCode::USD));
            \Log::info('Taking amount '.round($amount, 2));
        }

        $takenAmount = $amount * CoinbaseHelper::getExchangeRate();

        try {
            \Log::info('Taking '.$takenAmount.'BTC from user('.$user->id.') account');
            $user->balance = $user->balance - $takenAmount;
            $user->save();
        }
        catch (Exception $exception){
            \Log::info('Error occurred while trying to take money from users account');
            throw new Exception('Error occurred while trying to withdraw money from your account');
        }

        try {
            $inv = new Invoice();
            $inv->invoiceId = $transaction->getId();
            $inv->email = $user->email;
            $inv->amount = floatval($amount);
            $inv->currency = 'USD';
            $inv->description = $transaction->getDescription();
            $inv->status = 'pending';
            $inv->type = 'send';
            $inv->user_id = $user->id;
            $inv->createdAt = date('Y/m/d H:i:s');
            $inv->save();
        }
        catch (Exception $e){
            \Log::info('Error saving transaction id in database ...');
            try {
                $user->balance = $user->balance + $takenAmount;
                $user->save();
            }
            catch (Exception $exception){
                \Log::info('Error occurred while returning money to user '.$user->id.' amount '.$takenAmount);
            }

            throw new Exception('Error occurred while trying to withdraw money from your account');
        }

        try {
            $client->createAccountTransaction($primaryAccount, $transaction);
            try {
                $inv->status = $transaction->getStatus();
                $inv->invoiceId = $transaction->getId();
                $inv->save();
                try {
                    if ($transaction->getStatus() === 'complete') {

                        \Log::info('Transaction for sending ' . ($amount) . '$ to user ' . $user->email . ' is completed ...');
                        try {
                            \Mail::raw('Successfully sent ' . ($amount) . '$ to user with email ' . $user->email . '.', function ($message) use ($user, $adminEmail) {
                                $message->subject('Successful transaction notification ' . $user->email)->to($adminEmail);
                            });
                            \Mail::raw('DraftMatch has successfully added  ' . ($amount) . '$ to your coinbase account.', function ($message) use ($user) {
                                $message->subject('DraftMatch Withdraw Successful')->to($user->email);
                            });
                        }
                        catch (Exception $exception){
                            \Log::info('Error occurred while trying to send mail for successful withdrawing money transaction.'.$inv->id);
                        }
                        $retMessage = 'You successfully withdraw money to your coinbase account';
                    } else if ($transaction->getStatus() === 'pending') {
                        try {
                            // use '+' because $transaction->getAmount()->getAmount() is negative
                            $user->balance = $user->balance + $takenAmount + $transaction->getAmount()->getAmount();
                            $user->save();
                        }
                        catch (Exception $exception) {
                            \Log::info('Error occurred while trying to update balance for user ' . $user->id);
                        }

                        try {
                            \Log::info('Transaction for sending ' . ($amount) . '$ to user ' . $user->email . ' is pending ...');
                            \Mail::raw('Successfully sent ' . ($amount) . '$ to user with email ' . $user->email . '.', function ($message) use ($user, $adminEmail) {
                                $message->subject('Pending transaction notification ' . $user->email)->to($adminEmail);
                            });

                            \Mail::raw('Your request for withdrawing ' . ($amount) . '$ will be processed soon.', function ($message) use ($user) {
                                $message->subject('DraftMatch Withdraw Pending')->to($user->email);
                            });
                        }
                        catch (Exception $exception){
                            \Log::info('Error occurred while trying to send mail for pending withdrawing money transaction.'.$inv->id);
                        }
                        $retMessage = 'Your withdrawal request will be processed soon';
                    } else if ($transaction->getStatus() === 'failed') {
                        try {
                            \Log::info('Transaction for sending ' . ($amount) . '$ to user ' . $user->email . ' failed');
                            \Mail::raw('Transaction failed for user with email ' . $user->email . '.', function ($message) use ($user, $adminEmail) {
                                $message->subject('Failed transaction notification ' . $user->email)->to($adminEmail);
                            });

                            \Mail::raw('Your request for withdrawing ' . ($amount) . '$ failed', function ($message) use ($user) {
                                $message->subject('DraftMatch Withdraw Failed')->to($user->email);
                            });
                        }
                        catch (Exception $exception){
                            \Log::info('Error occurred while trying to send email for failed transaction ');
                        }
                        $retMessage = "Error occurred while processing withdrawal request";
                    }
                    else{
                        try {
                            \Log::info('Unknown status for transaction for user ' . $user->email . '. Got status ' . $transaction->getStatus());
                            \Mail::raw('Unknown status for transaction to user  ' . $user->email . '. Got status ' . $transaction->getStatus(),
                                function ($message) use ($user, $adminEmail) {
                                    $message->subject('Failed transaction notification ' . $user->email)->to($adminEmail);
                                });

                            \Mail::raw('Your request for withdrawing ' . ($amount) . '$ failed', function ($message) use ($user) {
                                $message->subject('DraftMatch Withdraw Failed')->to($user->email);
                            });
                        }
                        catch (Exception $exception){
                            \Log::info('Error sending status for unknown transaction '.$inv->id);
                        }
                        $retMessage = "Error occurred while processing withdrawal request";
                    }
                }
                finally {
                    $inv->status = $transaction->getStatus();
                    $inv->invoiceId = $transaction->getId();
                    $inv->save();
                }
            }
            catch (Exception $e){
                var_dump($e);
                \Log::info('Error saving transaction ===> '.$e->getMessage());
                $inv->invoiceId = $transaction->getId();
                $inv->save();
                $retMessage = 'Your withdrawal request will be processed soon';
            }
        }
        catch (Exception | ClientException | ValidationException $e){
            var_dump($e);
            \Log::info('Submitting transaction failed ====> '.$e->getMessage());

            try {
                $user->balance = $user->balance + $takenAmount;
                $user->save();
            }
            catch (Exception $exception){
                \Log::info('Error occurred while returning money to user '.$user->id.' amount '.$takenAmount);
            }

            $inv->status = 'failed';
            $inv->invoiceId = $transaction->getId();
            $inv->save();

            $retMessage = "Error occurred while processing withdrawal request";
        }

        return $retMessage;
    }

    public static function sendRequestToUser($user, $amount){
        set_error_handler(function($errno, $errstr, $errfile, $errline ){
            throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
        });
        $retMessage = 'Payment request has been sent to your coinbase account.';
        $adminEmail = 'admin@draftmatch.com';
        $configuration = Configuration::apiKey('i5NR996mKZnGRg2O', 'rnKoy7kbN6VI4pThlvinke9MkSHLXMJm');
        $client = Client::create($configuration);

        $primaryAccount = $client->getPrimaryAccount();

        $transaction = Transaction::request();
        $transaction->setToEmail($user->email);
        $transaction->setAmount(new Money($amount, CurrencyCode::USD));
        $transaction->setDescription('Transaction ID: '. time().'_'.($user->id).'_'.($amount).'
        Please complete this payment request to deposit funds into DraftMatch. Your funds will appear within 2-3 minutes.');

        try {
            $inv = new Invoice();
            $inv->invoiceId = $transaction->getId();
            $inv->email = $user->email;
            $inv->amount = floatval($amount);
            $inv->currency = 'USD';
            $inv->description = $transaction->getDescription();
            $inv->status = 'pending';
            $inv->type = 'request';
            $inv->user_id = $user->id;
            $inv->save();
        }
        catch (Exception $e){
            \Log::info('Error saving transaction id in database ...');
            throw $e;
        }

        try {
            $client->createAccountTransaction($primaryAccount, $transaction);
            try {
                $inv->status = $transaction->getStatus();
                $inv->invoiceId = $transaction->getId();
                $inv->createdAt = $transaction->getCreatedAt();
                $inv->save();

                try {
                    // this will never happen, but just in case it somehow happens
                    if ($transaction->getStatus() === 'complete') {
                        try {
                            \Log::info('Transaction for sending ' . ($amount) . '$ to user ' . $user->email . ' is completed ...');
                            \Mail::raw('Successfully sent request for ' . ($amount) . '$ to user with email ' . $user->email . '.', function ($message) use ($user, $adminEmail) {
                                $message->subject('Successful transaction notification ' . $user->email)->to($adminEmail);
                            });
                            /*\Mail::raw('You successfully pulled ' . ($amount) . '$ from your draftmatch account.', function ($message) use ($user) {
                                $message->subject('DraftMatch Deposit Successful')->to($user->email);
                            });*/
                        }
                        catch (Exception $exception){
                            \Log::info('Sending email notification for completed request transaction '.$transaction->getId().' has failed. Reason : '.$exception->getMessage());
                        }
                    } else if ($transaction->getStatus() === 'pending') {
                        $user->balance =  $user->balance + $transaction->getAmount()->getAmount();
                        $user->save();
                        try {
                            \Log::info('Transaction for sending ' . ($amount) . '$ to user ' . $user->email . ' is pending ...');

                            \Mail::send('emails.admin_invoices', ['text' => 'Pending request for ' . ($amount) . '$ for DraftMatch sent to user .'.$user->email,
                                'header' => 'DraftMatch Deposit Pending'], function ($message) use ($adminEmail)
                            {
                                $message->subject('DraftMatch Deposit Pending');

                                $message->to($adminEmail);
                            });

                            \Mail::send('emails.pending_invoice', ['amount' => $amount], function ($message) use ($user)
                            {
                                $message->subject('DraftMatch Deposit Pending');

                                $message->to($user->email);
                            });
                        }
                        catch (Exception $exception){
                            \Log::info('Sending email notification for pending request transaction '.$transaction->getId().' has failed. Reason : '.$exception->getMessage());
                        }
                    } else if ($transaction->getStatus() === 'failed' or $transaction->getStatus() === 'expired') {
                        try {
                            \Log::info('Transaction for sending ' . ($amount) . '$ to user ' . $user->email . ' ' . $transaction->getStatus());
                            \Mail::raw('Transaction ' . $transaction->getStatus() . ' for user with email ' . $user->email . '.', function ($message) use ($user, $adminEmail) {
                                $message->subject('Failed transaction notification ' . $user->email)->to($adminEmail);
                            });

                            \Mail::raw('Your request for adding ' . ($amount) . '$ to draftmatch is in status' . $transaction->getStatus(), function ($message) use ($user, $transaction) {
                                $message->subject('DraftMatch Deposit Failed')->to($user->email);
                            });
                        }
                        catch (Exception $exception){
                            \Log::info('Sending email notification for failed request transaction '.$transaction->getId().' has failed. Reason : '.$exception->getMessage());
                        }
                        $retMessage = 'Error occurred while processing your request';
                    }
                    else{
                        try {
                            \Log::info('Unknown status for transaction for user ' . $user->email . '. Got status ' . $transaction->getStatus());
                            \Mail::raw('Unknown status for transaction to user  ' . $user->email . '. Got status ' . $transaction->getStatus(),
                                function ($message) use ($user, $adminEmail) {
                                    $message->subject('Failed transaction notification ' . $user->email)->to($adminEmail);
                                });

                            \Mail::raw('Your request for withdrawing ' . ($amount) . '$ failed', function ($message) use ($user) {
                                $message->subject('Draftmatch invoice failed')->to($user->email);
                            });
                        }
                        catch (Exception $exception){
                            \Log::info('Sending email notification for unknown status request transaction '.$transaction->getId().' has failed. Reason : '.$exception->getMessage());
                        }
                        $retMessage = 'Error occurred while processing your request';
                    }
                }
                catch (Exception $e){
                    \Log::info('Error sending email notification ====> '.$e->getMessage());
                    $retMessage = "Payment request has been sent to your coinbase account.";
                }
            }
            catch (Exception $e){
                \Log::info('Error updating transaction ===> '.$e->getMessage());
                $inv->invoiceId = $transaction->getId();
                $inv->save();
                $retMessage = 'Payment request has been sent to your coinbase account.';
            }
        }
        catch (Exception | ClientException | ValidationException $e){
            \Log::info('Submitting transaction failed ====> '.$e->getMessage());
            $inv->status = 'failed';
            $inv->save();

            $retMessage = 'Error occurred while processing your request';
        }
        return $retMessage;
    }

    public static function updateExchangeRate(){
        try {
            $configuration = Configuration::apiKey('i5NR996mKZnGRg2O', 'rnKoy7kbN6VI4pThlvinke9MkSHLXMJm');
            $client = Client::create($configuration);

            $exchangeRate = $client->getExchangeRates();

            $rate = $exchangeRate['rates']['BTC'];

            BitCoinInfo::updateOrCreate(array('id' => '1'),
                ['rate' => $rate]);
        }
        catch (Exception $e){
            \Log::info('Error updating exchange rate');
        }

        return $rate;
    }

    public static function getExchangeRate(){
        $rate = BitCoinInfo::first();
        \Log::info('Rate '.$rate);

        return $rate->rate;
    }
}