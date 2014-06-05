<?php

class QuestionsController extends BaseController {


    public function getTopScore($theme_id){
        //$s = DB::table('top_scores')->where('theme_id', '=', $theme_id)->get();
        $s = array();
        $redis = Redis::connection();
        $top_score_fields = array('user_id', 'theme_id', 'score', 'name');
        $ts_theme_key = 'ts_th_'.$theme_id;
        if($redis->exists($ts_theme_key)){
            $ids = $redis->sMembers($ts_theme_key);
            $redis->multi();
            foreach($ids as $id){
                $ts_key = 'ts_'.$id.'_'.$theme_id;
                $redis->hMGet($ts_key, $top_score_fields);
            }
            $users = $redis->exec();

            foreach($users as $user){
                $user = array_combine($top_score_fields, $user);
                if($user['user_id'] != null){
                    array_push($s, $user);
                }
            }
        }

        return Response::json(array(
                'success' => true,
                'message' => array(
                    'score' => $s
                )
            ),
            200
        );
    }

    public function getScore($rid){//heavy
        $scores = DB::table('scores')->where('user_id', $rid)->get();
        $score = array();
        foreach($scores as $s){
            $themed_id = $s->theme_id;
            $scr = $s->score;
            $theme_name = $s->theme_name;
            $score[$themed_id] = array('theme_name' => $theme_name, 'score' => $scr);
        }
        return Response::json(array(
                'success' => true,
                'message' => array(
                    'score' => $score
                )
            ),
            200
        );
    }

    public function getScoreForTheme($rid){//light
        $theme_id = Input::get('theme_id');
        $score_key = $rid.'_'.$theme_id;
        $s = DB::table('scores')->where('id', $score_key)->get();
        if($s){
            $score = $s[0]->score;
            return Response::json(array(
                    'success' => true,
                    'message' => array(
                        'score' => $score
                    )
                ),
                200
            );

        }else{
            return Response::json(array(
                    'success' => true,
                    'message' => array(
                        'score' => 0
                    )
                ),
                200
            );
        }
    }

    public function getThemes(){
        //TODO add memcached
        $headers = getallheaders();
        $id = $headers['HTTP_USERID'];
        $redis = Redis::connection();
        /*$theme_range = $redis->get('theme_range');
        $ts = array();
        if($theme_range){
            $theme_range = json_decode($theme_range, true);
            $start = $theme_range['start'];
            $end = $theme_range['end'];
            $keys = array();
            for($i = $start; $i <= end; $i++){
                array_push($keys, 'theme_'.$i);
            }
            $themes = $redis->mget($keys);
            foreach($themes as $theme){
                array_push($ts, json_decode($theme, true));
            }
        }
        //$ts = DB::table('themes')->where('id', '>', 0)->get();
        /*$rs = DB::table('game_requests')->where('rid', $id)->get();
        if($rs){
            DB::table('game_requests')->where('rid', $id)->delete();
        }*/

        $theme_fields = array('id', 'name', 'description', 'parent', 'popularity');
        $theme_range = $redis->get('theme_range');
        $ts = array();
        if($theme_range){
            $theme_range = json_decode($theme_range, true);
            $start = $theme_range['start'];
            $end = $theme_range['end'];

            $redis->multi();
            for($i = $start; $i <= $end; $i++){
                $redis->hMGet('theme_'.$i, $theme_fields);
            }

            $ret = $redis->exec();
            foreach($ret as $theme){
                $theme = array_combine($theme_fields, $theme);
                if($theme['id'] != null){
                    array_push($ts, $theme);
                }
            }
        }

        $REQUEST_AFTER_GET_TTL = 10;
        $key = 'game_requests'.$id;
        $req_fields = array('id', 'username', 'theme_id', 'theme_name', 'checked');

        $res = $redis->hMGet($key, $req_fields);
        $rs = array();
        if($res){
            $res = array_combine($req_fields, $res);
            if($res['checked'] != null){
                if($res['checked'] == '0'){
                    $redis->hSet($key, 'checked', '1');
                    $redis->expire($key, $REQUEST_AFTER_GET_TTL);
                }
                $rs = array($res);
            }
        }

        return Response::json(array(
                'success' => true,
                'message' => array(
                    'themes' => $ts,
                    'requests' => $rs
                )
            ),
            200
        );
    }

    public function getQuestionsByIds($theme_id){
        $table_name = 'questions_'.$theme_id;
        $qids = array();
        for($i=0; array_key_exists("qid_{$i}",$_POST); $i++){
            $qids[] = $_POST["qid_{$i}"];
        }
        $qs = array();
        foreach($qids as $qid){
            $q = DB::table($table_name)->where('id', $qid)->get();
            $qs[$qid] = $q[0];
        }

        return Response::json(array(
                'success' => true,
                'message' => array(
                    'questions' => $qs
                )
            ),
            200
        );
    }

    public function getSinglePlayer($theme_id){
        //$q = new Question;
        //$q->setTable('questions_'.$theme_id);
        $table_name = 'questions_'.$theme_id;
        //$r =  DB::table($table_name)->orderBy(DB::raw('RAND()'))->take(6)->get();
        $n = DB::table($table_name)->count();
        $numbers = range(1, $n);
        shuffle($numbers);
        $m = array();
        for($i = 0; $i < 6; $i++){
            $m[$i] = $numbers[$i];
        }
        $r = DB::table($table_name)->whereIn('id', $m)->get();

        if($n){
            return Response::json(array(
                    'success' => true,
                    'message' => array(
                        'questions' => $r
                    )
                ),
                200
            );
        }else{
            return Response::json(array(
                    'success' => false,
                    'message' => array(
                        'text' => 'Smths wrong'
                    )
                ),
                400
            );
        }
    }

    /*public function getGame($theme_id){
        $headers = getallheaders();
        $uid = $headers['HTTP_USERID'];
        $id = $uid;

        $counter = 0;//just in case

        while($id == $uid && $counter < 100){
            $user = User::orderBy(DB::raw('RAND()'))->take(1)->get(); //get random user;
            $temp = $user->toArray()['0'];
            $id = $temp['id'];
            $counter++;
        }
        $tablename = 'questions_users_'.$theme_id;
        //$results = DB::select('select * from questions_users_' . $theme_id . 'where id = ?', array($id));
        $link = mysqli_connect('localhost', 'root', '', 'qz');//TODO

        $query = '
            SELECT question_id, ans_seq
            FROM (
                SELECT id
                FROM '.$tablename.'
                WHERE user_id = '.$id.'
                ORDER BY RAND()
                LIMIT 10
            )
            AS ids JOIN '.$tablename.' ON '.$tablename.'.id = ids.id
        ';//TODO SQL Injection

        if(mysqli_multi_query($link, $query)){
            $results = array();
            if ($result = mysqli_store_result($link)){
                while($row = mysqli_fetch_row($result))
                {
                    $qid = $row['0'];
                    $ans_seq = $row['1'];
                    $q = new Question;
                    $q->setTable('questions_'.$theme_id);

                    $r =  $q->where('id', $qid)->take(1)->get()->toArray();
                    /*$p = array();
                    foreach($r['0'] as $key => $val){
                        $p[$key] = $val;
                    }
                    $p['ans_seq'] = $ans_seq;
                    $r['ans_seq'] = $ans_seq;
                    $results[] = $r;
                }
            }
            mysqli_close($link);
            return Response::json(array(
                    'success' => true,
                    'message' => array(
                        'user' => $temp,
                        'questions' => $results
                    )
                ),
                200
            );
        }else{
            mysqli_close($link);
            return Response::json(array(
                    'success' => false,
                    'message' => array(
                        'text' => 'Smths wrong'
                    )
                ),
                400
            );
        }
    }

    public function saveGame($theme_id){
        $score = Request::get('score');
        $headers = getallheaders();
        $uid = $headers['HTTP_USERID'];
        $sequences = array();
        for($i = 0; $i < 10; $i++){
            $ans_seq = Request::get('ans_seq_'.$i);
            $qid = Request::get('question_id_'.$i);
            if($ans_seq && $qid){
                $sequences[$qid] = $ans_seq;
            }
        }
        $tablename = 'questions_users_'.$theme_id;

        foreach($sequences as $qid => $ans_seq){
            $res = DB::table($tablename)->where('user_id', '=', $uid)->where('question_id', '=', $qid)->count();
            if($res > 0){
                //update
                DB::table($tablename)->where('user_id', '=', $uid)->where('question_id', '=', $qid)->update(array('ans_seq' => $ans_seq));
            }else{
                //insert
                DB::table($tablename)->insert(array('user_id' => $uid, 'question_id' => $qid, 'ans_seq' => $ans_seq));
            }
        }

        //save score
        $cnt = DB::table('scores')->where('user_id', '=', $uid)->where('theme_id', '=', $theme_id)->count();
        if($cnt > 0){
            //update
            DB::table('scores')->where('user_id', '=', $uid)->where('theme_id', '=', $theme_id)->update(array('score' => $score));
        }else{
            //insert
            DB::table('scores')->insert(array('user_id' => $uid, 'theme_id' => $theme_id, 'score' => $score));
        }

        $nscore = DB::table('scores')->where('user_id', '=', $uid)->where('theme_id', '=', $theme_id)->pluck('score');

        return Response::json(array(
                'success' => true,
                'message' => array(
                    'theme_id' => $theme_id,
                    'score' => $nscore.""
                )
            ),
            200
        );
    }

    public function test(){

        /*$con = mysqli_connect('localhost', 'anov1992', 'kuzya1', 'test_base');//
        $qry = '
                SELECT id
                FROM users
                ORDER BY RAND()
                LIMIT 1
        ';//rak

        $rst = mysqli_query($con, $qry);

        mysqli_close($con);
        return mysqli_fetch_row($rst);
        $user = User::orderBy(DB::raw('RAND()'))->take(1)->get()->toArray();
        $r = $user['0'];
        return $r['id'];
    }*/
}