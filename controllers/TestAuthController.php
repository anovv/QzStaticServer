<?php

class TestAuthController extends BaseController {

    public function __construct(){

        $this->beforeFilter('apifilter', array('only' =>
            array('showuser')));
    }

    public function showuser(){
        return Response::json(
            array(
                "success" => false,
                "message" => User::find(1)
            ),
            400
        );
    }

    public function login(){
        if (Auth::attempt(array('email' => 'test@test.com', 'password' => '123456'), true)){
            return 'good';
        }else{
            return 'dayum';
        }
    }
}