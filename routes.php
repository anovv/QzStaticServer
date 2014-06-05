<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

/*Route::get('/authtest', array('before' => 'auth.basic', function()
{
    return View::make('hello');
}));

// Route group for API versioning
Route::group(array('prefix' => 'api/v1', 'before' => 'auth.basic'), function()
{
    Route::resource('url', 'UrlController');
});*/


Route::post('api/user/login/{is_auto}', 'UsersController@login');

Route::post('api/user/register','UsersController@register');

Route::post('api/user/registervk', 'UsersController@registerVk');

Route::post('api/user/sendpush', 'UsersController@sendPush');//add filter

Route::post('api/user/finalize', 'UsersController@finalize');//add filter

Route::get('api/user/getscorebyid/{id}',  'UsersController@getScoreById');

Route::get('api/user/test',  'UsersController@testt');

Route::get('api/user/test2',  'UsersController@test2');

Route::post('api/user/setvkfriends',  'UsersController@setVKFriends');


//Route::get('api/user/logout', array('before' => 'apifilter|loginfilter', 'uses' => 'UsersController@logout'));//TODO uncomment

//Route::get('api/user/{id}', array('before' => 'apifilter|loginfilter', 'uses' => 'UsersController@read'));//TODO uncomment

//Route::post('api/user/{id}', array('before' => 'apifilter|loginfilter', 'uses' => 'UsersController@update'));//TODO uncomment

Route::get('api/user/setavailability/{is_available}',  'UsersController@setAvailability');

Route::get('api/question/gettopscore/{theme_id}', 'QuestionsController@getTopScore');

Route::get('api/question/getthemes', 'QuestionsController@getThemes');

Route::post('api/question/getscorefortheme/{rid}', 'QuestionsController@getScoreForTheme');//add filter

Route::post('api/question/getscore/{rid}', 'QuestionsController@getScore');//add filter

Route::post('api/question/getquestionsbyids/{theme_id}', 'QuestionsController@getquestionsbyids');//add filter

Route::post('api/user/uploadimage', 'UsersController@uploadImage');//add filter

Route::get('api/user/unfriend/{id}', 'UsersController@unfriend');//add filter

Route::get('api/user/getuserbyid/{id}', 'UsersController@getUser');//add filter

Route::get('api/user/finduser', 'UsersController@findUser');//add filter

Route::get('api/user/delete/{id}', array('before' => 'apifilter|loginfilter', 'uses' => 'UsersController@delete'));

Route::get('api/question/{theme_id}', array('before' => 'apifilter|loginfilter', 'uses' => 'QuestionsController@getGame'));

Route::post('api/user/sendrequest', 'UsersController@sendRequest');//add filter

Route::get('api/user/checkrequests', 'UsersController@checkRequests');//add filter

Route::get('api/user/confirmrequest/{aid}', 'UsersController@confirmRequest');//add filter

Route::get('api/user/cancelrequest/{bid}', 'UsersController@cancelRequest');//add filter

Route::get('api/user/declinerequest/{aid}', 'UsersController@declineRequest');//add filter

Route::get('api/user/getfriends', 'UsersController@getFriends');//add filter

Route::get('api/question/{theme_id}', 'QuestionsController@getGame');//add filter

Route::post('api/question/{theme_id}', 'QuestionsController@saveGame');//add filter

Route::get('api/question/single/{theme_id}', 'QuestionsController@getSinglePlayer');//add filter
//Route::get('api/question/test', 'QuestionsController@test');




