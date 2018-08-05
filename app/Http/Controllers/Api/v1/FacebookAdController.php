<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;



use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\AbstractObject;
use FacebookAds\Object\AbstractCrudObject;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Campaign;
use FacebookAds\Object\Fields\AdAccountFields;
use FacebookAds\Object\Fields\CampaignFields;
use FacebookAds\ApiRequest;
use FacebookAds\Http\RequestInterface;
use FacebookAds\Object\Targeting;
use FacebookAds\Object\Fields\TargetingFields;
use FacebookAds\Object\AdSet;
use FacebookAds\Object\Fields\AdSetFields;
use FacebookAds\Object\Values\AdSetBillingEventValues;
use FacebookAds\Object\Values\AdSetOptimizationGoalValues;
use FacebookAds\Object\Ad;
use FacebookAds\Object\Fields\AdFields;
use FacebookAds\Object\AdImage;
use FacebookAds\Object\Fields\AdImageFields;
use FacebookAds\Object\AdCreative;
use FacebookAds\Object\Values\AdCreativeCallToActionTypeValues;
use FacebookAds\Object\AdCreativeLinkData;
use FacebookAds\Object\Fields\AdCreativeLinkDataCallToActionValueFields;
use FacebookAds\Object\Fields\AdCreativeLinkDataFields;
use FacebookAds\Object\AdCreativeObjectStorySpec;
use FacebookAds\Object\Fields\AdCreativeObjectStorySpecFields;
use FacebookAds\Object\Fields\AdCreativeFields;
use FacebookAds\Object\Values\PageCallToActionWebDestinationTypeValues;

use FacebookAds\Object\Business;
use FacebookAds\Object\Fields\BusinessFields;
use FacebookAds\Object\ExtendedCredit;
use FacebookAds\Object\AdRule;
use FacebookAds\Object\AdPreview;

// define('SDK_DIR', __DIR__); // Path to the SDK directory
// include SDK_DIR.'/vendor/autoload.php';
class FacebookAdController extends Controller
{ 
    function getEdges($api, $params, $fields, $node_id, $edge) {
      if ($fields) {
        $fields = implode(',', $fields);
        $params['fields'] = $fields;
      }
      $response =
        $api->call('/'.$node_id.'/'.$edge, RequestInterface::METHOD_GET, $params);
      return $response->getContent();
    }

    function setAccessToken($access_token) {
      global $app_secret, $app_id;
      $app_id = '684973041845313';
      $app_secret = '47a83806ccf028d6fe2ec4240761e476';
      Api::init($app_id, $app_secret, $access_token);
      $api = Api::instance();
      $logger = new CurlLogger();
      $api->setLogger($logger);
      return $api;
    }

     //sandbox ad account: 431506280694939 
     //page_id : 2143591632551643
     // https://www.facebook.com/Draftmatch-daily-fantasy-sports-nfl-game-2143591632551643/
    function createClientBusiness1() {
      global
        $app_id,
        $app_secret,
        $aggregator_bm_id,
        $page_id,
        $bm_loc_id,
        $aggregator_access_token;
      $aggregator_access_token = 'EAAaeEsFZAIEsBAAfpkZCbASUtQZBo9cyZB0PDRfpEygTolZC00flsdScdU6HYBqmwfYIPUDzsjaJ5ZBwxgSlvGuV9CbXYulJdD3V2ph7RZC9zmDjPP9e6pqRn9ZAZAlsUwycIWRNZAsmZAEtUr2uNfQ6dJpP3UhvpVBQZBaFGskRXUztafphvs0iWkkC0FC4PxOZB9OqJK72GuwNUvosHAx1PQOBXAZCqyFLJn9uUZD';
      $account_id = '431506280694939'; 
      // $aggregator_bm_id = 0;
      // $api = $this->setAccessToken($access_token);
      // $me = $this->getEdges($api, array(), array(), 'me', '');
      // $app_scoped_user_id = $me['id'];


      $api = $this->setAccessToken($aggregator_access_token);
      $account = new AdAccount($account_id);
      // dd($account);
      // $campaign = $account->createCampaign(
      //   array(),
      //   array(
      //     CampaignFields::NAME => 'First test Campaign',
      //     CampaignFields::OBJECTIVE => 'MESSAGES'
      //   )
      // );
      // dd($campaign);
      // dd($account);
      /**
       * Step 2 Search Targeting
       */
      $targeting = new Targeting();
      $targeting->setData(array(
        TargetingFields::GEO_LOCATIONS => array(
          'countries' => array('JP'),
          'regions' => array(array('key' => '3886')),
          'cities' => array(
            array(
              'key' => '2420605',
              'radius' => 10,
              'distance_unit' => 'mile',
            ),
          ),
        ),
      ));
      // dd($targeting);
      /**
       * Step 3 Create the AdSet
       */
      // $start_time = (new \DateTime("+1 week"))->format(DateTime::ISO8601);
      // $end_time = (new \DateTime("+2 week"))->format(DateTime::ISO8601);
      $fields = array();
      $params = array(
        AdSetFields::NAME => 'First Ad Set',
        AdSetFields::OPTIMIZATION_GOAL => AdSetOptimizationGoalValues::REPLIES,
        AdSetFields::BILLING_EVENT => AdSetBillingEventValues::IMPRESSIONS,
        AdSetFields::BID_AMOUNT => 2,
        AdSetFields::DAILY_BUDGET => 1000,
        // AdSetFields::CAMPAIGN_ID => $campaign->id,
        AdSetFields::TARGETING => $targeting,
        // AdSetFields::START_TIME => $start_time,
        // AdSetFields::END_TIME => $end_time,
      );
      $ad_set = $account->createAdSet($fields, $params);
      dd(json_decode($ad_set->data));
      $ad_set_id = $ad_set->id;
      dd($ad_set_id);

    }

    function createClientBusiness() {

          $access_token = 'EAAaeEsFZAIEsBAAfpkZCbASUtQZBo9cyZB0PDRfpEygTolZC00flsdScdU6HYBqmwfYIPUDzsjaJ5ZBwxgSlvGuV9CbXYulJdD3V2ph7RZC9zmDjPP9e6pqRn9ZAZAlsUwycIWRNZAsmZAEtUr2uNfQ6dJpP3UhvpVBQZBaFGskRXUztafphvs0iWkkC0FC4PxOZB9OqJK72GuwNUvosHAx1PQOBXAZCqyFLJn9uUZD';
          $ad_account_id = 'act_431506280694939';
          $app_secret = 'e9b42eebc27bb2b866e552c7e2810be5';
          $page_id = '2143591632551643';
          $app_id = '1862653250707531';

          $api = Api::init($app_id, $app_secret, $access_token);
          $api->setLogger(new CurlLogger());

          $fields = array(
          );
          $params = array(
            'objective' => 'PAGE_LIKES',
            'status' => 'PAUSED',
            'buying_type' => 'AUCTION',
            'name' => 'My Campaign',
          );
          $campaign = (new AdAccount($ad_account_id))->createCampaign(
            $fields,
            $params
          );
          $campaign_id = $campaign->id;
          echo 'campaign_id: ' . $campaign_id . "\n\n";

          $fields = array(
          );
          $params = array(
            'status' => 'PAUSED',
            'targeting' => array('geo_locations' => array('countries' => array('CH'))),
            'daily_budget' => '1000',
            'billing_event' => 'IMPRESSIONS',
            'bid_amount' => '20',
            'campaign_id' => $campaign_id,
            'optimization_goal' => 'PAGE_LIKES',
            'promoted_object' => array('page_id' =>  $page_id),
            'name' => 'My AdSet',
          );
          $ad_set = (new AdAccount($ad_account_id))->createAdSet(
            $fields,
            $params
          );
          $ad_set_id = $ad_set->id;
          // echo 'ad_set_id: ' . $ad_set_id . "\n\n";
          $fields = array(
          );
          $params = array(
            'body' => 'Like My Page Headline matchups will continue here in DraftMatch, as we’re putting together two great matchups to see. Next Sunday, we’ll be witnessing two of the best quarterbacks in the league going head to head for this matchup, trying to succeed this postseason and win another PLAY10GAME',
            'image_url' => 'https://draftmatch.com/wp-content/uploads/2018/01/usa_today_10385104.0-1024x683.jpg',
            'name' => 'My Creative',
            'object_id' => $page_id,
            'title' => 'My Page Like Ad',
          );
          $creative = (new AdAccount($ad_account_id))->createAdCreative(
            $fields,
            $params
          );
          $creative_id = $creative->id;
          // echo 'creative_id: ' . $creative_id . "\n\n";
          $fields = array(
          );
          $params = array(
            'status' => 'PAUSED',
            'adset_id' => $ad_set_id,
            'name' => 'My Ad',
            'creative' => array('creative_id' => $creative_id),
          );
          $ad = (new AdAccount($ad_account_id))->createAd(
            $fields,
            $params
          );
          $ad_id = $ad->id;
          // echo 'ad_id: ' . $ad_id . "\n\n";
          // dd($creative);

          $fields = array(
          );
          $params = array(
            'ad_format' => 'DESKTOP_FEED_STANDARD',
          );
          echo json_encode((new Ad($ad_id))->getPreviews(
            $fields,
            $params
          )->getResponse()->getContent(), JSON_PRETTY_PRINT);

    }

    function createClientBusines2() {

            $access_token = 'EAAaeEsFZAIEsBAF5bLJgvpfjNuA2jIsZBciKRZBdtgoJqZCdoFnTP2HvWYnZBMXnVdL9OnFIqwGrQHp1WlOCXAqcMd6UrugqZAn7aMcDGOnZAJleJZAgZCiNYGlDIOTyrVGR4umZBXlnz2VHEmJLZAZAitJwRYXdIuI2Oruo23a6G4FZCy2C1o0HC7A0TPavnLCL1ZAIjUmH2Qy7x3ZCBZBewNuExRlasopwIWGlsIsZD';
            $app_secret = 'e9b42eebc27bb2b866e552c7e2810be5';
            $ad_account_id = 'act_431506280694939';
            $schedule_interval = 'HOURLY';
            $entity_type = 'CAMPAIGN';
            $notification_user_id = '148092439417152';
            $filter_field = 'impressions';
            $filter_value = '1';
            $filter_operator = 'GREATER_THAN';
            $app_id = '1862653250707531';

            $api = Api::init($app_id, $app_secret, $access_token);
            $api->setLogger(new CurlLogger());

            $fields = array(
            );
            $params = array(
              'execution_spec' => array( 'execution_type' =>  'NOTIFICATION', 'execution_options' =>  array( array( 'field' =>  'user_ids', 'value' =>  array($notification_user_id), 'operator' =>  'EQUAL' ) ) ),
              'name' => 'Sample SDK Rule',
              'schedule_spec' => array( 'schedule_type' =>  $schedule_interval ),
              'evaluation_spec' => array( 'evaluation_type' =>  'SCHEDULE', 'filters' =>  array( array( 'field' =>  $filter_field, 'value' =>  $filter_value, 'operator' =>  $filter_operator ), array( 'field' =>  'entity_type', 'value' =>  $entity_type, 'operator' =>  'EQUAL' ), array( 'field' =>  'time_preset', 'value' =>  'LIFETIME', 'operator' =>  'EQUAL' ) ) ),
            );
            echo json_encode((new AdAccount($ad_account_id))->createAdRulesLibrary(
              $fields,
              $params
            )->getResponse()->getContent(), JSON_PRETTY_PRINT);


    }
}
