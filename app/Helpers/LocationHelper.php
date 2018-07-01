<?php
/**
 * Created by PhpStorm.
 * User: hariso
 * Date: 19/11/2017
 * Time: 07:09
 */

namespace App\Helpers;

use App\Common\Consts\Locations\AllowedLocations;
use GuzzleHttp\Client as HttpClient;
use Mockery\Exception;
use Psy\Exception\ErrorException;

set_error_handler(function($errno, $errstr, $errfile, $errline ){
    throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
});

class LocationHelper {
    public static function isUserInAllowedLocation($lang, $lat)
    {
        try {
            \Log::info('Getting location from coordinates lang=' . $lang . ', lat=' . $lat);

            $location = self::get_location($lang, $lat);

            $country = $location["country"];
            $state = $location["state"];

            \Log::info ('Got country '.$country);
            \Log::info ('Got state '.$state);
            if ($country === AllowedLocations::$CANADA or $country === AllowedLocations::$BOSNIA){
                return true;
            }

            if (!in_array($country, AllowedLocations::$ALLOWED_COUNTRIES)){
                return false;
            }

            if (!in_array($state, AllowedLocations::$ALLOWED_STATES)){
                return false;
            }

            return true;
        } catch (ErrorException $exception) {
            \Log::info('Error occurred while trying to retrieve location info ...');
        }

        return false;
    }

    public static function get_location($latitude='', $longitude='')
    {
        $geolocation = $latitude.','.$longitude;
        $request = 'http://maps.googleapis.com/maps/api/geocode/json?latlng='.$geolocation.'&sensor=false';
        $file_contents = file_get_contents($request);
        $json_decode = json_decode($file_contents);
        $loc = array();
        if(isset($json_decode->results[0])) {
            \Log::info('reach in address decode');
            $response = array();
            foreach($json_decode->results[0]->address_components as $addressComponet) {
                if(in_array('political', $addressComponet->types)) {
                    $response[] = $addressComponet->long_name;
                }
            }

            if(isset($response[0])){ $first  =  trim($response[0]);  } else { $first  = 'null'; }
            if(isset($response[1])){ $second =  trim($response[1]);  } else { $second = 'null'; }
            if(isset($response[2])){ $third  =  trim($response[2]);  } else { $third  = 'null'; }
            if(isset($response[3])){ $fourth =  trim($response[3]);  } else { $fourth = 'null'; }
            if(isset($response[4])){ $fifth  =  trim($response[4]);  } else { $fifth  = 'null'; }
            if(isset($response[5])){ $sixth  =  trim($response[5]);  } else { $sixth  = 'null'; }

            $loc['address']=''; $loc['city']=''; $loc['state']=''; $loc['country']='';
            if ($sixth != 'null'){
                $fourth = $fifth;
                $fifth = $sixth;
            }
            if( $first != 'null' && $second != 'null' && $third != 'null' && $fourth != 'null' && $fifth != 'null' ) {
                $loc['address'] = $first;
                $loc['city'] = $second;
                $loc['state'] = $fourth;
                $loc['country'] = $fifth;
            }
            else if ( $first != 'null' && $second != 'null' && $third != 'null' && $fourth != 'null' && $fifth == 'null'  ) {
                $loc['address'] = $first;
                $loc['city'] = $second;
                $loc['state'] = $third;
                $loc['country'] = $fourth;
            }
            else if ( $first != 'null' && $second != 'null' && $third != 'null' && $fourth == 'null' && $fifth == 'null' ) {
                $loc['city'] = $first;
                $loc['state'] = $second;
                $loc['country'] = $third;
            }
            else if ( $first != 'null' && $second != 'null' && $third == 'null' && $fourth == 'null' && $fifth == 'null'  ) {
                $loc['state'] = $first;
                $loc['country'] = $second;
            }
            else if ( $first != 'null' && $second == 'null' && $third == 'null' && $fourth == 'null' && $fifth == 'null'  ) {
                $loc['country'] = $first;
            }
        }
        else{
            \Log::info("Could not get location");
        }
        return $loc;
    }
}