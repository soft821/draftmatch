<?php
/**
 * Created by PhpStorm.
 * User: hariso
 * Date: 18/08/2017
 * Time: 16:29
 */

namespace App\Helpers;

use App\Invoice;
use App\BitPayInfo;
use GuzzleHttp\Client as HttpClient;

class BitPayHelper{
    public static function createKeys(){
        $privateKey = new \Bitpay\PrivateKey('/tmp/bitpay.pri');

        // Generate a random number
        $privateKey->generate();

        // You can generate a private key with only one line of code like so
        $privateKey = \Bitpay\PrivateKey::create('/tmp/bitpay.pri')->generate();

        // NOTE: This has overridden the previous $privateKey variable, although its
        //       not an issue in this case since we have not used this key for
        //       anything yet.

        /**
         * Once we have a private key, a public key is created from it.
         */
        $publicKey = new \Bitpay\PublicKey('/tmp/bitpay.pub');

        // Inject the private key into the public key
        $publicKey->setPrivateKey($privateKey);

        // Generate the public key
        $publicKey->generate();

        // NOTE: You can again do all of this with one line of code like so:
        //       `$publicKey = \Bitpay\PublicKey::create('/tmp/bitpay.pub')->setPrivateKey($privateKey)->generate();`

        /**
         * Now that you have a private and public key generated, you will need to store
         * them somewhere. This optioin is up to you and how you store them is up to
         * you. Please be aware that you MUST store the private key with some type
         * of security. If the private key is comprimised you will need to repeat this
         * process.
         */

        /**
         * It's recommended that you use the EncryptedFilesystemStorage engine to persist your
         * keys. You can, of course, create your own as long as it implements the StorageInterface
         */



        $storageEngine = new \Bitpay\Storage\EncryptedFilesystemStorage(config('app.bitpay_password'));
        $storageEngine->persist($privateKey);
        $storageEngine->persist($publicKey);

        $sin = \Bitpay\SinKey::create()->setPublicKey($publicKey)->generate();
        \Log::info("SIN " . $sin);
        /**
         * This is all for the first tutorial, you can run this script from the command
         * line `php examples/tutorial/001.php` This will generate and create two files
         * located at `/tmp/bitpay.pri` and `/tmp/bitpay.pub`
         */

    }

    public static function pairIt(){
        /**
         * To load up keys that you have previously saved, you need to use the same
         * storage engine. You also need to tell it the location of the key you want
         * to load.
         */
        $storageEngine = new \Bitpay\Storage\EncryptedFilesystemStorage(config('app.bitpay_password'));
        $privateKey    = $storageEngine->load('/tmp/bitpay.pri');
        $publicKey     = $storageEngine->load('/tmp/bitpay.pub');
        /**
         * Create the client, there's a lot to it and there are some easier ways, I am
         * showing the long form here to show how various things are injected into the
         * client.
         */
        $client = new \Bitpay\Client\Client();
        /**
         * The network is either livenet or testnet. You can also create your
         * own as long as it implements the NetworkInterface. In this example
         * we will use testnet
         */
        $network = new \Bitpay\Network\Testnet();
        /**
         * The adapter is what will make the calls to BitPay and return the response
         * from BitPay. This can be updated or changed as long as it implements the
         * AdapterInterface
         */
        $adapter = new \Bitpay\Client\Adapter\CurlAdapter();
        /**
         * Now all the objects are created and we can inject them into the client
         */
        $client->setPrivateKey($privateKey);
        $client->setPublicKey($publicKey);
        $client->setNetwork($network);
        $client->setAdapter($adapter);
        /**
         * Visit https://test.bitpay.com/api-tokens and create a new pairing code. Pairing
         * codes can only be used once and the generated code is valid for only 24 hours.
         */
        $pairingCode = config('app.bitpay_pair_code');
        /**
         * Currently this part is required, however future versions of the PHP SDK will
         * be refactor and this part may become obsolete.
         */
        $sin = \Bitpay\SinKey::create()->setPublicKey($publicKey)->generate();
        \Log::info("SIN " . $sin);
        /**** end ****/
        try {
            $token = $client->createToken(
                array(
                    'pairingCode' => $pairingCode,
                    'label'       => 'payroll',
                    'id'          => (string) $sin,
                )
            );
        } catch (\Exception $e) {
            /**
             * The code will throw an exception if anything goes wrong, if you did not
             * change the $pairingCode value or if you are trying to use a pairing
             * code that has already been used, you will get an exception. It was
             * decided that it makes more sense to allow your application to handle
             * this exception since each app is different and has different requirements.
             */
            \Log::info("Pairing failed. Please check whether you're trying to pair a production pairing code on test.");
            $request  = $client->getRequest();
            $response = $client->getResponse();
            /**
             * You can use the entire request/response to help figure out what went
             * wrong, but for right now, we will just var_dump them.
             */
            \Log::info((string) $request.PHP_EOL.PHP_EOL.PHP_EOL);
            \Log::info((string) $response.PHP_EOL.PHP_EOL);
            /**
             * NOTE: The `(string)` is include so that the objects are converted to a
             *       user friendly string.
             */
            //exit(1); // We do not want to continue if something went wrong
        }
        /**
         * You will need to persist the token somewhere, by the time you get to this
         * point your application has implemented an ORM such as Doctrine or you have
         * your own way to persist data. Such as using a framework or some other code
         * base such as Drupal.
         */
        $persistThisValue = $token->getToken();
        BitPayHelper::updateToken($persistThisValue);
        BitPayHelper::updateExchangeRate();
        \Log::info( 'Token obtained: '.$persistThisValue.PHP_EOL);
        /**
         * Make sure you persist the token, you will need it for the next tutorial
         */
    }

    public static function addInvoice($user, $amount){

        // See 002.php for explanation
        $storageEngine = new \Bitpay\Storage\EncryptedFilesystemStorage(config('app.bitpay_password')); // Password may need to be updated if you changed it
        $privateKey    = $storageEngine->load('/tmp/bitpay.pri');
        $publicKey     = $storageEngine->load('/tmp/bitpay.pub');
        $client        = new \Bitpay\Client\Client();
        $network       = new \Bitpay\Network\Testnet();
        $adapter       = new \Bitpay\Client\Adapter\CurlAdapter();
        $client->setPrivateKey($privateKey);
        $client->setPublicKey($publicKey);
        $client->setNetwork($network);
        $client->setAdapter($adapter);
        // ---------------------------
        /**
         * The last object that must be injected is the token object.
         */
        $token = new \Bitpay\Token();
        $bitpayInfo = null;//BitCoinInfos::getInfo();
        $token->setToken($bitpayInfo->token); // UPDATE THIS VALUE
        /**
         * Token object is injected into the client
         */
        $client->setToken($token);
        /**
         * This is where we will start to create an Invoice object, make sure to check
         * the InvoiceInterface for methods that you can use.
         */

        $invoice = new \Bitpay\Invoice();
        $buyer = new \Bitpay\Buyer();
        $buyer
            ->setEmail($user->email);

        // Add the buyers info to invoice
        $invoice->setBuyer($buyer);
        /**
         * Item is used to keep track of a few things
         */

        $timestamp = time();
        $item = new \Bitpay\Item();
        $item
            ->setCode($timestamp.'_'.$user->id.'_dfm')
            ->setDescription('Adding funds to draftmatch')
            ->setPrice(0.001);

        $invoice->setItem($item);
        /**
         * BitPay supports multiple different currencies. Most shopping cart applications
         * and applications in general have defined set of currencies that can be used.
         * Setting this to one of the supported currencies will create an invoice using
         * the exchange rate for that currency.
         *
         * @see https://test.bitpay.com/bitcoin-exchange-rates for supported currencies
         */
        $invoice->setCurrency(new \Bitpay\Currency('BTC'));
        // Configure the rest of the invoice
        $invoice->setFullNotifications(true)->setTransactionSpeed('low')->setNotificationEmail("glupkotocak@gmail.com")
            ->setOrderId($timestamp.'_'.$user->id)
            // You will receive IPN's at this URL, should be HTTPS for security purposes!
            ->setNotificationUrl('http://162.243.21.233/bitpay/callback');

        /**
         * Updates invoice with new information such as the invoice id and the URL where
         * a customer can view the invoice.
         */
        try {
            $invoice = $client->createInvoice($invoice);

            // @todo Do not change slate if game time changed
            \Log::info('Adding invoice ');
            $inv = new Invoice();
            $inv->invoiceId = $invoice->getId();
            $inv->email = $user->email;
            $inv->orderId = $invoice->getOrderId();
            $inv->amount = floatval($amount);
            $inv->currency = $invoice->getCurrency()->getCode();
            $inv->description = $invoice->getItemDesc();
            $inv->status = $invoice->getStatus();
            $inv->notificationUrl = $invoice->getNotificationUrl();
            $inv->notificationEmail = $invoice->getNotificationEmail();
            $inv->code = $invoice->getItemCode();
            $inv->user = $user;
            $inv->save();
        } catch (\Exception $e) {
            \Log::info("Exception while trying to create invoice ".$e->getMessage());
            $request  = $client->getRequest();
            $response = $client->getResponse();
            \Log::info('Request =====> '.(string) $request.PHP_EOL.PHP_EOL.PHP_EOL);
            \Log::info('Response =====> '.(string) $response.PHP_EOL.PHP_EOL);
        }

        \Log::info('Successfully created invoice for user '.$user->username);
        return $invoice;
    }

    public static function getInvoice($invoiceId){

        //\Log::info("GOT ID ".$invoiceId);
        // See 002.php for explanation
        $storageEngine = new \Bitpay\Storage\EncryptedFilesystemStorage(config('app.bitpay_password')); // Password may need to be updated if you changed it
        $privateKey    = $storageEngine->load('/tmp/bitpay.pri');
        $publicKey     = $storageEngine->load('/tmp/bitpay.pub');
        $client        = new \Bitpay\Client\Client();
        $network       = new \Bitpay\Network\Testnet();
        $adapter       = new \Bitpay\Client\Adapter\CurlAdapter();
        $client->setPrivateKey($privateKey);
        $client->setPublicKey($publicKey);
        $client->setNetwork($network);
        $client->setAdapter($adapter);
        // ---------------------------
        /**
         * The last object that must be injected is the token object.
         */
        //\Log::info("CHECKPOINT1 ");
        $token = new \Bitpay\Token();
        $bitpayInfo = null;;//;BitCoinInfos::getInfo();
        $token->setToken($bitpayInfo->token); // UPDATE THIS VALUE
        /**
         * Token object is injected into the client
         */
        $client->setToken($token);
        /**
         * This is where we will start to create an Invoice object, make sure to check
         * the InvoiceInterface for methods that you can use.
         */

        try {
            $invoice = $client->getInvoice($invoiceId);

            //\Log::info('Response2'.$client->getResponse());
            //var_dump(
            //    (string) $client->getRequest(),
            //    (string) $client->getResponse(),
            //    $invoice

        } catch (\Exception $e) {
            \Log::info("Got exception ".$e->getMessage());
            return null;
        }

        return $invoice;

    }

//JYhRYFUtK3La6nxZSEXBUy

    public static function payToUser(){
        // See 002.php for explanation
        $storageEngine = new \Bitpay\Storage\EncryptedFilesystemStorage(config('app.bitpay_password')); // Password may need to be updated if you changed it
        $privateKey    = $storageEngine->load('/tmp/bitpay.pri');
        $publicKey     = $storageEngine->load('/tmp/bitpay.pub');
        $client        = new \Bitpay\Client\Client();
        $network       = new \Bitpay\Network\Testnet();
        $adapter       = new \Bitpay\Client\Adapter\CurlAdapter();
        $client->setPrivateKey($privateKey);
        $client->setPublicKey($publicKey);
        $client->setNetwork($network);
        $client->setAdapter($adapter);

        \Log::info($client->getTokens());


        // ---------------------------
        /**
         * The last object that must be injected is the token object.
         */

        $time = gmdate('Y-m-d\TH:i:s\.', 1414691179)."000Z";
        $token = new \Bitpay\Token();

        //$bitpayInfo = BitCoinInfos::getInfo();

        $token->setFacade('payroll')->setToken(config('app.bitpay_token')); // UPDATE THIS VALUE

       // $inv = $client->getPayouts();

       // print_r($inv);
        $instruction1 = new \Bitpay\PayoutInstruction();
        $instruction1
            ->setAmount(0.00001)
            ->setAddress('mxLxx5wKseBd5of2AMC3Eh7ipVzap8KHyj')
            ->setLabel('Paying Chris');

        $payout = new \Bitpay\Payout();
        $payout
            ->setEffectiveDate($time)
            ->setAmount(0.00001)
            ->setCurrency(new \Bitpay\Currency('BTC'))
            ->setPricingMethod('bitcoinbestbuy')
            ->setReference('{ref}')
            ->setNotificationEmail('haris.omerovic87@gmail.com')
            ->setNotificationUrl('https://example.com/ipn.php')
            ->setToken($token)
            ->addInstruction($instruction1);


        //$client->setToken($token);
        //$client->createPayout($payout);
        print_r($client->createPayout($payout));
        //print_r($client->getPayouts());
    }

   /* public static function updateExchangeRate(){
        try {
            $rate = BitPayHelper::getExchangeRate();

            BitCoinInfos::updateOrCreate(array('id' => '1'),
                ['rate' => $rate]);
        }
        catch (Exception $e){
            \Log::info('Error updating exchange rate');
        }
    }
*/
    /*public static function updateToken($token){
        try {
            BitCoinInfos::updateOrCreate(array('id' => '1'),
                ['token' => $token]);
        }
        catch (Exception $e){
            \Log::info('Error updating token');
        }
    }
*/
    public static function getExchangeRate(){
        $client = new HttpClient();
        $exchange = $client->request('GET', "https://test.bitpay.com/rates/USD")->getBody();
        $obj = json_decode($exchange);

        foreach($obj as $ex) {
            return $ex->rate;
        }
    }
}