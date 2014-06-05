<?php

class TestThread extends Thread{
    public function __construct($socket){
        $this->socket = $socket;
        $this->start();
    }

    public function run(){
        for($i = 0; $i < 5; $i++){
            $welcome = 'hey '.$i;
            $client = $this->socket;
            socket_write($client, $welcome);
            //echo $welcome;
            sleep(1);
        }
    }
}
