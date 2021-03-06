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

// Threads



// subscribe and unsubscribe
Route::post('/threads/{channel}/{thread}/subscriptions', 'Api\v1\ThreadSubscriptionsController@store')->middleware('auth');
Route::delete('/threads/{channel}/{thread}/subscriptions', 'Api\v1\ThreadSubscriptionsController@destroy')->middleware('auth');

// Replies
Route::post('/replies/{reply}/favorites', 'Api\v1\FavoritesController@store');
Route::delete('/replies/{reply}/favorites', 'Api\v1\FavoritesController@destroy');


Route::post ('user/payToUser',      'Api\v1\UsersController@payToUser');
Route::post ('update',      'Api\v1\UsersController@updateCredit');
Route::get ('test',      'HomeController@test');
Route::get ('facebookClient',      'Api\v1\FacebookAdController@createClientBusiness');

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
    Route::get('test',           'Api\v1\ApiDescriptionController@test');
    //Forum
    Route::get('threads/index', 'Api\v1\ThreadController@index');
    // Route::get('threads/{channel}', 'Api\v1\ThreadController@index');
    Route::get('threads/{threadId}', 'Api\v1\ThreadController@show');
    Route::get('/threads/{id}/replies', 'Api\v1\RepliesController@index');
    
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
    Route::post ('auth/checkbook',      'Api\v1\UsersController@hasCheckbook');
    Route::get  ('slates'   ,           'Api\v1\SlatesController@getSlates');
    Route::get  ('fantasyPlayers',      'Api\v1\FantasyPlayersController@getFantasyPlayers');
    //Blog
    Route::get  ('posts/list',               'Api\v1\PostsController@list');
    Route::get  ('posts/details/{id}',       'Api\v1\PostsController@getDetails');
    Route::get ('posts/create',         'Api\v1\PostsController@create');
    Route::post ('posts/store',         'Api\v1\PostsController@store');
    Route::get  ('posts/edit/{id}',         'Api\v1\PostsController@edit');
    Route::post  ('posts/update/{id}',         'Api\v1\PostsController@update'); 
    Route::delete  ('posts/delete/{id}',      'Api\v1\PostsController@delete'); 
    Route::post ('posts/{id}/comment',      'Api\v1\PostsController@addComment'); 
    // forum thread
    Route::get('topics/create', 'Api\v1\ThreadController@create');
    Route::post('threads/store', 'Api\v1\ThreadController@store');
    Route::delete('threads/delete/{id}', 'Api\v1\ThreadController@destroy');
    Route::post('/threads/{id}/replies', 'Api\v1\RepliesController@store');
    Route::patch('/replies/{reply}', 'Api\v1\RepliesController@update');
    Route::delete('/replies/{reply}', 'Api\v1\RepliesController@destroy');
 

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
    //blog
    Route::get  ('admin/posts/list',               'Admin\v1\PostsController@list');
    Route::get  ('admin/posts/details/{id}',       'Admin\v1\PostsController@getDetails');
    Route::get ('admin/posts/create',         'Admin\v1\PostsController@create');
    Route::post ('admin/posts/store',         'Admin\v1\PostsController@store');
    Route::post  ('admin/post/publish-blog',      'Admin\v1\PostsController@changePublishStatus');
    Route::delete  ('admin/posts/delete/{id}',      'Admin\v1\PostsController@adminDelete');
    Route::get  ('admin/posts/edit/{id}',         'Admin\v1\PostsController@adminEdit');
    Route::post  ('admin/posts/update/{id}',         'Admin\v1\PostsController@adminUpdate'); 

    // forum thread
    Route::post('admin/channels/create', 'Admin\v1\ThreadController@createChannel');
    Route::get('admin/channels/list', 'Admin\v1\ThreadController@indexChannel');
    Route::get('admin/channels/{id}/edit', 'Admin\v1\ThreadController@editChannel');
    Route::put('admin/channels/{id}/update', 'Admin\v1\ThreadController@updateChannel');
    Route::delete('admin/channels/{id}/delete', 'Admin\v1\ThreadController@deleteChannel');


    Route::get('topics/create', 'Api\v1\ThreadController@create');
    Route::post('admin/threads/store', 'Admin\v1\ThreadController@store');
    Route::delete('admin/threads/delete/{id}', 'Admin\v1\ThreadController@destroy');
    Route::post('admin/threads/{id}/replies', 'Admin\v1\RepliesController@store');
    Route::patch('admin/replies/{reply}', 'Admin\v1\RepliesController@update');
    Route::delete('ccc{reply}', 'Admin\v1\RepliesController@destroy');

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