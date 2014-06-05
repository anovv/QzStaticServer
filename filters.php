<?php

/*
|--------------------------------------------------------------------------
| Application & Route Filters
|--------------------------------------------------------------------------
|
| Below you will find the "before" and "after" events for the application
| which may be used to do any work before or after a request into your
| application. Here you may also register your custom route filters.
|
*/

App::before(function($request)
{
	//
});


App::after(function($request, $response)
{
	//
});

/*
|--------------------------------------------------------------------------
| Authentication Filters
|--------------------------------------------------------------------------
|
| The following filters are used to verify that the user of the current
| session is logged into this application. The "basic" filter easily
| integrates HTTP Basic authentication for quick, simple checking.
|
*/

Route::filter('auth', function()
{
	//if (Auth::guest()) return Redirect::guest('login');
    if (Auth::guest()) return 'Denied';
});


Route::filter('auth.basic', function()
{
	return Auth::basic("username");
});

/*
|--------------------------------------------------------------------------
| Guest Filter
|--------------------------------------------------------------------------
|
| The "guest" filter is the counterpart of the authentication filters as
| it simply checks that the current user is not logged in. A redirect
| response will be issued if they are, which you may freely change.
|
*/

Route::filter('guest', function()
{
	if (Auth::check()) return Redirect::to('/');
});

/*
|--------------------------------------------------------------------------
| CSRF Protection Filter
|--------------------------------------------------------------------------
|
| The CSRF filter is responsible for protecting your application against
| cross-site request forgery attacks. If this special token in a user
| session does not match the one given in this request, we'll bail.
|
*/

Route::filter('csrf', function()
{
	if (Session::token() != Input::get('_token'))
	{
		throw new Illuminate\Session\TokenMismatchException;
	}
});


Route::filter('apifilter', function(){

    $headers = getallheaders();

    $signature = $headers['HTTP_SIGNATURE'];

    $id = $headers['HTTP_USERID'];

    $user = User::find($id);

    $_signature = hash_hmac('sha512', $user->email, $user->key);

    if($signature != $_signature){
        return Response::json(
            array(
               "success" => false,
               "message" => 'Not authenticated'
            ),
            400
        );
    }

});

Route::filter('loginfilter', function(){
    $headers = getallheaders();
    $id = $headers['HTTP_USERID'];
    $user = User::find($id);
    if(!$user || !$user->login){
        return Response::json(
            array(
                "success" => false,
                "message" => 'Not logged in'
            ),
            400
        );
    }
});

