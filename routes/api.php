<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('pusher', function()
{
    return View::make('pusher');
});

Route::post ('user/payToUser',      'Api\v1\UsersController@payToUser');

Route::group(['middleware' =>  'cors', 'prefix' => 'v1'], function() {
    Route::post('auth/register', 'Api\v1\UsersController@register');
    Route::post('auth/login',    'Api\v1\UsersController@login');
    Route::post('auth/admin-login',    'Api\v1\UsersController@adminLogin');
    Route::post('auth/resetPassword',    'Api\v1\UsersController@resetPassword');
    Route::get ('info/games',    'Api\v1\GamesController@getGames');
    Route::get ('info/ranking',  'Api\v1\UsersController@getRanking');
    Route::get ('info/matchups', 'Api\v1\ContestsController@getContestsForWeb');
    Route::post('help/contactSupport', 'Api\v1\UsersController@contactSupport');

    Route::get('statusCodes',    'Api\v1\ApiDescriptionController@getStatusCodes');
    Route::get('statusMessages', 'Api\v1\ApiDescriptionController@getStatusMessages');
    Route::get('routes',         'Api\v1\ApiDescriptionController@getRoutes');
    Route::get('responseFormat', 'Api\v1\ApiDescriptionController@responseMessageFormat');
    Route::get('help',           'Api\v1\ApiDescriptionController@help');
    
});

Route::group(['middleware' => ['cors', 'jwt.auth'], 'prefix' => 'v1'], function () {
    Route::get  ('user',                'Api\v1\UsersController@getUser');
    Route::post ('user/addFunds',       'Api\v1\UsersController@addFunds');
    Route::post ('user/addFunds/checkbook',       'Api\v1\UsersController@addFundsByCheckbook');
    Route::post ('user/withdrawFunds',  'Api\v1\UsersController@withdrawFunds');
    Route::post ('user/withdrawFunds/checkbook',  'Api\v1\UsersController@withdrawFundsByCheckbook');
    Route::get  ('user/history',        'Api\v1\UsersController@getHistoryEntries');
    Route::post ('user/addBitcoins',    'Api\v1\UsersController@addBitcoins');

    Route::get  ('user/transactions',   'Api\v1\UsersController@transactions');

    Route::post ('contests',            'Api\v1\ContestsController@create');
    Route::post ('contests/enter',      'Api\v1\ContestsController@enter');
    Route::get  ('contests',            'Api\v1\ContestsController@getContests');
    Route::post ('contests/enter',      'Api\v1\ContestsController@enter');
    Route::post ('contests/cancel',     'Api\v1\ContestsController@cancelContest');
    Route::patch('contests/entry',      'Api\v1\ContestsController@editEntry');
    Route::post('auth/checkbook',    'Api\v1\UsersController@hasCheckbook');
    Route::get  ('slates'   ,           'Api\v1\SlatesController@getSlates');
    Route::get  ('fantasyPlayers',      'Api\v1\FantasyPlayersController@getFantasyPlayers');

});

Route::group(['middleware' => ['cors', 'jwt.auth', 'is-allowed-location'], 'prefix' => 'v1'], function () {
    Route::post ('user/addFunds',       'Api\v1\UsersController@addFunds');
});

Route::group(['middleware' => ['cors', 'admin.auth'], 'prefix' => 'v1'], function () {
    Route::get ('admin/users',             'Api\v1\UsersController@getUsers');
    Route::post ('admin/users/block',       'Api\v1\UsersController@blockUser');
    Route::post ('admin/users/activate',    'Api\v1\UsersController@activateUser');

    Route::post ('admin/slates/activate',     'Api\v1\SlatesController@activateSlate');
    Route::post ('admin/slates/deactivate',   'Api\v1\SlatesController@deactivateSlate');
    Route::post('admin/slates',            'Api\v1\SlatesController@createSlate');
    Route::get ('admin/slates',            'Api\v1\SlatesController@getAdminSlates');
    Route::get ('admin/pendingGames',      'Api\v1\GamesController@getPendingGames');
    Route::post('admin/sendpromo',         'Admin\v1\MailController@sendPromoCode');
    Route::post('admin/setbitpaytoken',         'Admin\fake\BitPaySetController@getTokensForMerchant');
    Route::post ('admin/user/delete',    'Api\v1\UsersController@deleteUser');
    Route::post ('admin/user/access-blog',    'Api\v1\UsersController@changeAccessPermission');
    Route::get  ('admin/fantasyPlayers',      'Api\v1\FantasyPlayersController@getFPlayers');
    Route::post  ('admin/update/fp_tier',      'Api\v1\FantasyPlayersController@updateFPTier');

});


Route::group(['prefix' => 'fake'], function(){

    Route::get('timeframes/current', 'Admin\fake\FakeDataController@getCurrentTimeFrame');
    Route::get('timeframes/upcoming', 'Admin\fake\FakeDataController@getCurrentTimeFrame');
    Route::get('get_games', 'Admin\fake\FakeDataController@getWeekGames');
});

Route::post('coinbase/notification', 'Admin\v1\WebHookController@getInvoiceCoinbase');
Route::group(['prefix' => 'v1'], function(){
    Route::get('checkbook/callback', ['as' => 'checkbookcallback', 'uses' => 'Api\v1\UsersController@checkbookCallback']);
});