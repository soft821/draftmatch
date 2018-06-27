<?php
namespace App\Helpers;

use App\Invoice;
use App\BitPayInfo;
use GuzzleHttp\Client as HttpClient;
//Include autoload, as it’s a necessary component
include('vendor/autoload.php');

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
$storageEngine = new \Bitpay\Storage\EncryptedFilesystemStorage('YourTopSecretPassword');
$storageEngine->persist($privateKey);
$storageEngine->persist($publicKey);

dd($storageEngine);

?>