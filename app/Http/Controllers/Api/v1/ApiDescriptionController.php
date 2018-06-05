<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\HttpMessage;
use App\Http\HttpStatus;
use App\Http\Controllers\Controller;

class ApiDescriptionController extends Controller
{

    public function getStatusCodes()
    {
        $class = new \ReflectionClass(HttpStatus::class);
        $staticProperties = $class->getStaticProperties();

        return $staticProperties;
    }

    public function getStatusMessages()
    {
        $class = new \ReflectionClass(HttpMessage::class);
        $staticProperties = $class->getStaticProperties();

        return $staticProperties;
    }

    public function getRoutes()
    {
        \Artisan::call('route:list');
        return \Artisan::output();

    }

    public function responseMessageFormat()
    {
        $responseMessageFormat = ['status' => 'StatusCode. List of all statuses can be retrieved using /api/v1/statusCodes route. This is internal api status',
                                  'messages' => 'List of messages. Message of success if everything was fine (if status = 0) or error messages if something went wrong (status != 0). List of all status messages can be retriver using /api/v1/statusMessages route.',
                                  'entities' => 'List of entities returned from api. Example, list of users, list of contests, token'];

        return $responseMessageFormat;
    }

    public function help()
    {
        $help = ['help' => "First step is to register user over /api/v1/auth/register route. After successful register you need to retrieve a token using /api/v1/auth/login route.Token will be in a entities of the response. Token will be used for authentication for all future requests which requires authentication.Token should be past in Authorization header as Bearer TOKEN. Token expires in 30 mins, and then you have to call login route again."];

        return $help;
    }
}
