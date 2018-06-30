<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client as HttpClient;
use App\Common\Consts\Fakes\FakeConsts;
use App\BitCoinInfo;

class BitPaySetKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitpaykey:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run bitpay pri,pub key and store';

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
    public function handle()
    {
      \Log::info('Set BitPay keys');

     
      $privateKey = \Bitpay\PrivateKey::create('/tmp/bitpay.pri')->generate();
      $publicKey = \Bitpay\PublicKey::create('/tmp/bitpay.pub')->setPrivateKey($privateKey)->generate();
      $storageEngine = new \Bitpay\Storage\EncryptedFilesystemStorage(config(FakeConsts::$CONSOL_API_DOMAIN));
      $storageEngine->persist($privateKey);
      $storageEngine->persist($publicKey);

      $sin = \Bitpay\SinKey::create()->setPublicKey($publicKey)->generate();
/*
      $client = new HttpClient();
      $res = $client->request('POST', 
          "https://bitpay.com/tokens?".
          "label=outlookaccount&".
          "id=".(string)$sin.
          "&facade=merchant"
      )->getBody();
      $resBody = json_decode($res);
*/

      $client = new \Bitpay\Client\Client();
      $network = new \Bitpay\Network\Livenet();
      $adapter = new \Bitpay\Client\Adapter\CurlAdapter();
      $client->setPrivateKey($privateKey);
      $client->setPublicKey($publicKey);
      $client->setNetwork($network);
      $client->setAdapter($adapter);
      try {
            $token = $client->createToken(
                array(
                    'facade' => 'payroll',
                    'label'       => 'DraftMatch Payroll Token',
                    'id'          => (string) $sin,
                )
            );
        } catch (\Exception $e) {
           
            \Log::info("Pairing failed. Please check whether you're trying to pair a production pairing code on test.");
            $request  = $client->getRequest();
            $response = $client->getResponse();
            
            \Log::info((string) $request.PHP_EOL.PHP_EOL.PHP_EOL);
            \Log::info((string) $response.PHP_EOL.PHP_EOL);


            
        }

      $this->updateToken($token->getToken());
      $this->updatePairingKey($token->getPairingCode());


        // \Artisan::call('games:update');
        

    }

    function updateToken($token){
      
        try {
      
            BitCoinInfo::updateOrCreate(array('id' => '2'),
                ['key' => $token]);
      
        }
      
        catch (Exception $e){
      
            \Log::info('Error updating token');
      
        }
    }

    function updatePairingKey($key){
      
        try {
      
            BitCoinInfo::updateOrCreate(array('id' => '3'),
                ['key' => $key]);
      
        }
      
        catch (Exception $e){
      
            \Log::info('Error updating key');
      
        }
    }

    function getExchangeRate(){
        $client = new HttpClient();
        $exchange = $client->request('GET', "https://test.bitpay.com/rates/USD")->getBody();
        $obj = json_decode($exchange);

        foreach($obj as $ex) {
            return $ex->rate;
        }
    }
}
