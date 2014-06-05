<?php
require_once('TestThread.php');
set_time_limit (0);
$address = '127.0.0.1';

$port = 1228;

$sock = socket_create(AF_INET, SOCK_STREAM, 0);

socket_bind($sock, $address, $port);
socket_listen($sock);
$client = socket_accept($sock);

//$t = new TestThread($client);
//$t->start();
//sendMsg($client, 'test');
new TT($client);

class TT extends Thread{
    public function __construct($socket){
        $this->socket = $socket;
        $this->start();
    }

    public function run(){
        $client = $this->socket;
        //socket_write($client, 'dsds1');
        $i = 0;
        while(++$i < 5){
            echo 'hey\n';
        }
        //$this->sleep(1);
        //socket_write($client, 'dsds2');
        //sleep(1);
        //socket_write($client, 'dsds3');
    }
}

class TTT extends Thread{
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