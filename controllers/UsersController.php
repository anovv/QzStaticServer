<?php

class UsersController extends BaseController {

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */

    public function test2(){

        $redis = Redis::connection();
        $t_range = array('start' => '1', 'end' => '2');
        $redis->set('theme_range', json_encode($t_range));

        $theme1 = array('id' => '1', 'name' => 'General Knowledge', 'description' => 'About Everything', 'parent' => '0', 'popularity' => '0');
        $theme2 = array('id' => '2', 'name' => 'General Questions', 'description' => 'Test Your Might', 'parent' => '1', 'popularity' => '0');

        $redis->hMSet('theme_1', $theme1);
        $redis->hMSet('theme_2', $theme2);
    }

    public function testt(){

        $fields = array('user_id', 'theme_id', 'score', 'name');
        $redis = Redis::connection();
        $t_range = array('start' => '1', 'end' => '2');
        $redis->set('theme_range', json_encode($t_range));

        $theme1 = array('id' => '1', 'name' => 'General Knowledge', 'description' => 'About Everything', 'parent' => '0', 'popularity' => '0');
        $theme2 = array('id' => '2', 'name' => 'General Questions', 'description' => 'Test Your Might', 'parent' => '1', 'popularity' => '0');

        $redis->hMSet('theme_1', $theme1);
        $redis->hMSet('theme_2', $theme2);

        $score = 0;
        $scr = $score;
        $theme_id = 2;
        $user_id = 1;
        $theme_name = 'General Questions';
        $name = 'andrey';


        //$m = DB::table('top_scores')->where('theme_id', '=', $theme_id)->orderBy('score', 'DESC')->first();

        $top_score_fields = array('user_id', 'theme_id', 'score', 'name');
        $redis = Redis::connection();

        $ts_theme_key = 'ts_th_'.$theme_id;
        if($redis->exists($ts_theme_key)){
            $ids = $redis->sMembers($ts_theme_key);
            $redis->multi();
            foreach($ids as $i){
                $redis->hMGet('ts_'.$i.'_'.$theme_id);
            }
            $ret = $redis->exec();
            $res = array();
            foreach($ret as $r){
                array_push($res, array_combine($fields, $r));
            }
            if(!empty($res)){
                $max = -1;
                $m = $res[0];
                foreach($res as $r){
                    if($r['score'] > $max){
                        $m = $r;
                        $max = $r['score'];
                    }
                }
                if($m['score'] > $scr){
                    //do nothing
                }else{
                    $old_usr_id = $m['user_id'];
                    if($old_usr_id == $user_id){
                        $usr = User::find($user_id);
                        $usr->best_in = $theme_name;
                        $usr->save();
                    }else{
                        $usr = User::find($user_id);
                        $usr->best_in = $theme_name;
                        $usr->save();

                        $old_usr = User::find($old_usr_id);
                        $old_usr->best_in = '';
                        $old_usr->save();
                    }
                }
            }
        }else{
            $u = User::find($user_id);
            $u->best_in = $theme_name;
            $u->save();
        }

        $MAX_TOP_SCORE_USERS = 2;//TODO set 20

        if($redis->exists($ts_theme_key)){
            $ids = $redis->sMembers($ts_theme_key);
            $redis->multi();
            foreach($ids as $i){
                $redis->hMGet('ts_'.$i.'_'.$theme_id);
            }
            $ret = $redis->exec();
            $res = array();
            foreach($ret as $r){
                array_push($res, array_combine($fields, $r));
            }
            $ts_key = 'ts_'.$user_id.'_'.$theme_id;
            if(count($res) < $MAX_TOP_SCORE_USERS){
               if($redis->exists($ts_key)){
                   $redis->hIncrBy($ts_key, 'score', $score);
               }else{
                   $redis->multi();
                   $redis->hMSet($ts_key, array(
                       'user_id' => $user_id,
                       'theme_id' => $theme_id,
                       'score' => $score,
                       'name' => $name
                   ));
                   $redis->sAdd($ts_theme_key, $user_id);
                   $redis->exec();
               }
            }else{
                if($redis->exists($ts_key)){
                    $redis->hIncrBy($ts_key, 'score', $score);
                }else{
                    if(!empty($res)){
                        $mm = $res[0];
                        $min = $mm['score'];
                        foreach($res as $rr){
                            if($rr['score'] < $min){
                                $min = $rr['score'];
                                $mm = $rr;
                            }
                        }
                        if($new_s > $mm['score']){
                            $old_ts_key = 'ts_'.$mm['user_id'].'_'.$theme_id;
                            $redis->multi();
                            $redis->del($old_ts_key);
                            $redis->sRem($ts_theme_key, $mm['user_id']);
                            $redis->sAdd($ts_theme_key, $user_id);
                            $redis->hMSet($ts_key, array(
                                'user_id' => $user_id,
                                'theme_id' => $theme_id,
                                'score' => $new_s,
                                'name' => $name
                            ));
                            $redis->exec();
                        }
                    }
                }
            }
        }else{
            $ts_key = 'ts_'.$user_id.'_'.$theme_id;
            $redis->multi();
            $redis->sAdd($ts_theme_key, $user_id);
            $redis->hMSet($ts_key, array(
                'user_id' => $user_id,
                'theme_id' => $theme_id,
                'score' => $score,
                'name' => $name
            ));
            $redis->exec();
        }

        return Response::json(
            array(
                'success' => true,
                'message' => array(
                    //'ts' => $ts
                )
            ),
            400
        );

    }

    public function setVKFriends(){
        $MAX_FRIENDS = 900;
        $MAX_FRIENDS_TO_INSERT = 200;
        $headers = getallheaders();
        $vk_id = Input::get('vk_id');
        $fullname = Input::get('name');
        $user_id = $headers['HTTP_USERID'];

        $url = 'https://api.vk.com/method/friends.get?user_id='.$vk_id.'&order=id&fields=&count='.$MAX_FRIENDS;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $output = curl_exec($curl);
        if($output){
            curl_close($curl);
            $json = json_decode($output, true);
            $ids = $json['response'];
            $intersection = array();
            $count = 0;
            if(!empty($ids)){
                $intersection = DB::table('users')->whereIn('vk_id', $ids)->get();
                $count = count($intersection);
                array_slice($intersection, 0, $MAX_FRIENDS_TO_INSERT);
            }


            $insertions = array();

            foreach($intersection as $u){
                $friend_id = $u->id;
                $friend_name = $u->fullname;

                $first_key = $user_id.'_'.$friend_id;
                $second_key = $friend_id.'_'.$user_id;

                $first_insert = array();
                $first_insert['id'] = $first_key;
                $first_insert['uid1'] = $user_id;
                $first_insert['uid2'] = $friend_id;
                $first_insert['name1'] = $fullname;
                $first_insert['name2'] = $friend_name;
                if(!empty($first_insert)){
                    array_push($insertions, $first_insert);
                }

                $second_insert = array();
                $second_insert['id'] = $second_key;
                $second_insert['uid1'] = $friend_id;
                $second_insert['uid2'] = $user_id;
                $second_insert['name1'] = $friend_name;
                $second_insert['name2'] = $fullname;
                if(!empty($second_insert)){
                    array_push($insertions, $second_insert);
                }
            }

            if(!empty($insertions)){
                DB::table('friends')->insert($insertions);
            }



            return Response::json(
                array(

                    'success' => true,
                    'message' => array(
                        'count' => $count
                    )
                ),
                200
            );
        }else{
            $error = curl_error($curl);
            curl_close($curl);
            return Response::json(
                array(
                    'success' => false,
                    'message' => array(
                        'text' => $error
                    )
                ),
                400
            );
        }
    }

    public function finalize(){
        $headers = getallheaders();
        $user_id = $headers['HTTP_USERID'];
        $is_single = Input::get('is_single');
        $rid = '';
        if($is_single == '0'){
            $rid = Input::get('rid');
        }

        $theme_id = Input::get('theme_id');
        $name = Input::get('name');
        $theme_name = Input::get('theme_name');
        $score = Input::get('score');
        $hasWon = Input::get('has_won');
        $old_status = Input::get('status');

        $redis = Redis::connection();
        $theme_key = 'theme_'.$theme_id;
        $redis->hIncrBy($theme_key, 'popularity', 1);

        DB::table('users')->where('id', '=', $user_id)->increment('total');
        if($hasWon == 1){
            DB::table('users')->where('id', '=', $user_id)->increment('wins');
            //update duels if multiplayer
            if($is_single == '0'){
                if($user_id < $rid){
                    $key = $user_id.'_'.$rid;
                    if(DB::table('duels')->where('id', $key)->get()){
                        DB::table('duels')->where('id', $key)->increment('score1');
                    }else{
                        DB::table('duels')->insert(
                            array('id' => $key, 'score1' => 1, 'score2' => 0)
                        );
                    }
                }else{
                    $key = $rid.'_'.$user_id;
                    if(DB::table('duels')->where('id', $key)->get()){
                        DB::table('duels')->where('id', $key)->increment('score2');
                    }else{
                        DB::table('duels')->insert(
                            array('id' => $key, 'score1' => 0, 'score2' => 1)
                        );
                    }
                }
            }
        }
        //$s = DB::table('scores')->where('user_id', '=', $user_id)->where('theme_id', '=', $theme_id);
        $score_key = $user_id.'_'.$theme_id;
        $s = DB::table('scores')->where('id', $score_key)->get();
        $scr = $score;
        if($s){
            $new_s = $s[0]->score + $score;
            $scr = $new_s;
            DB::table('scores')->where('id', $score_key)->increment('score', $score);
            //TODO set status
            $new_status = '';
            if($new_s > 1000){
                $new_status = 'Experienced';
            }
            if($new_s > 5000){
                $new_status = 'Boss';
            }
            if($new_s > 10000){
                $new_status = 'Godlike';
            }
            if($new_s > 15000){
                $new_status = 'Beyond Godlike';
            }

            if($new_status != $old_status && $new_status != ''){
                $us = User::find($user_id);
                $us->status = $new_status;
                $us->save();
            }
        }else{
            DB::table('scores')->insert(
                array('id' => $score_key, 'user_id' => $user_id, 'theme_id' => $theme_id, 'score' => $score, 'theme_name' => $theme_name)
            );
        }
        //check in top score

        $top_score_fields = array('user_id', 'theme_id', 'score', 'name');
        $redis = Redis::connection();

        $ts_theme_key = 'ts_th_'.$theme_id;
        if($redis->exists($ts_theme_key)){
            $ids = $redis->sMembers($ts_theme_key);
            $redis->multi();
            foreach($ids as $i){
                $redis->hMGet('ts_'.$i.'_'.$theme_id, $top_score_fields);
            }
            $ret = $redis->exec();
            $res = array();
            foreach($ret as $r){
                array_push($res, array_combine($top_score_fields, $r));
            }
            if(!empty($res)){
                $max = -1;
                $m = $res[0];
                foreach($res as $r){
                    if($r['score'] > $max){
                        $m = $r;
                        $max = $r['score'];
                    }
                }
                if($m['score'] > $scr){
                    //do nothing
                }else{
                    $old_usr_id = $m['user_id'];
                    if($old_usr_id == $user_id){
                        $usr = User::find($user_id);
                        $usr->best_in = $theme_name;
                        $usr->save();
                    }else{
                        $usr = User::find($user_id);
                        $usr->best_in = $theme_name;
                        $usr->save();

                        $old_usr = User::find($old_usr_id);
                        $old_usr->best_in = '';
                        $old_usr->save();
                    }
                }
            }
        }else{
            $u = User::find($user_id);
            $u->best_in = $theme_name;
            $u->save();
        }

        $MAX_TOP_SCORE_USERS = 2;//TODO set 20

        if($redis->exists($ts_theme_key)){
            $ids = $redis->sMembers($ts_theme_key);
            $redis->multi();
            foreach($ids as $i){
                $redis->hMGet('ts_'.$i.'_'.$theme_id, $top_score_fields);
            }
            $ret = $redis->exec();
            $res = array();
            foreach($ret as $r){
                array_push($res, array_combine($top_score_fields, $r));
            }
            $ts_key = 'ts_'.$user_id.'_'.$theme_id;
            if(count($res) < $MAX_TOP_SCORE_USERS){
                if($redis->exists($ts_key)){
                    $redis->hIncrBy($ts_key, 'score', $score);
                }else{
                    $redis->multi();
                    $redis->hMSet($ts_key, array(
                        'user_id' => $user_id,
                        'theme_id' => $theme_id,
                        'score' => $score,
                        'name' => $name
                    ));
                    $redis->sAdd($ts_theme_key, $user_id);
                    $redis->exec();
                }
            }else{
                if($redis->exists($ts_key)){
                    $redis->hIncrBy($ts_key, 'score', $score);
                }else{
                    if(!empty($res)){
                        $mm = $res[0];
                        $min = $mm['score'];
                        foreach($res as $rr){
                            if($rr['score'] < $min){
                                $min = $rr['score'];
                                $mm = $rr;
                            }
                        }
                        if($new_s > $mm['score']){
                            $old_ts_key = 'ts_'.$mm['user_id'].'_'.$theme_id;
                            $redis->multi();
                            $redis->del($old_ts_key);
                            $redis->sRem($ts_theme_key, $mm['user_id']);
                            $redis->sAdd($ts_theme_key, $user_id);
                            $redis->hMSet($ts_key, array(
                                'user_id' => $user_id,
                                'theme_id' => $theme_id,
                                'score' => $new_s,
                                'name' => $name
                            ));
                            $redis->exec();
                        }
                    }
                }
            }
        }else{
            $ts_key = 'ts_'.$user_id.'_'.$theme_id;
            $redis->multi();
            $redis->sAdd($ts_theme_key, $user_id);
            $redis->hMSet($ts_key, array(
                'user_id' => $user_id,
                'theme_id' => $theme_id,
                'score' => $score,
                'name' => $name
            ));
            $redis->exec();
        }

        /*$m = DB::table('top_scores')->where('theme_id', '=', $theme_id)->orderBy('score', 'DESC')->first();
        if($m){
            if($m->score > $scr){
                //do nothing
            }else{
                $old_usr_id = $m->user_id;
                if($old_usr_id == $user_id){
                    $usr = User::find($user_id);
                    $usr->best_in = $theme_name;
                    $usr->save();
                }else{
                    $usr = User::find($user_id);
                    $usr->best_in = $theme_name;
                    $usr->save();

                    $old_usr = User::find($old_usr_id);
                    $old_usr->best_in = '';
                    $old_usr->save();
                }
            }
        }else{
            $u = User::find($user_id);
            $u->best_in = $theme_name;
            $u->save();
        }

        //TODO set to 20
        $MAX_TOP_SCORE_USERS = 20;
        $t = DB::table('top_scores')->where('theme_id', $theme_id)->take($MAX_TOP_SCORE_USERS)->get();

        $top_score_key = $user_id.'_'.$theme_id;
        if(count($t) < $MAX_TOP_SCORE_USERS){
            //$a = $t->where('user_id', '=', $user_id);
            $a = DB::table('top_scores')->where('id', $top_score_key)->get();
            if($a){
                DB::table('top_scores')->where('id', $top_score_key)->increment('score', $score);
            }else{
                DB::table('top_scores')->insert(
                    array('id' => $top_score_key, 'user_id' => $user_id, 'theme_id' => $theme_id, 'score' => $score, 'name' => $name)
                );
            }
        }else{
            //$a = $t->where('user_id', '=', $user_id);
            $a = DB::table('top_scores')->where('id', $top_score_key)->get();
            if($a){
                DB::table('top_scores')->where('id', $top_score_key)->increment('score', $score);
            }else{
                $p = DB::table('top_scores')->where('theme_id', $theme_id)->orderBy('score', 'ASC')->first();
                if($p){
                    if($new_s > $p->score){
                        $old_key = $p->user_id.'_'.$theme_id;
                        DB::table('top_scores')->where('id', $old_key)->delete();
                        DB::table('top_scores')->insert(
                            array('id' => $top_score_key, 'user_id' => $user_id, 'theme_id' => $theme_id, 'score' => $new_s, 'name' => $name)
                        );
                    }
                }
            }
        }*/

        return Response::json(
            array(
                'success' => true,
                'message' => array(
                    'text' => 'Done'
                )
            ),
            200
        );
    }

    public function register(){

        if(!Request::get('email')){
            return Response::json(array(
                    'success' => false,
                    'message' => array(
                        'text' => 'Enter email'
                    )
                ),
                400
            );
        }

        if(!Request::get('password')){
            return Response::json(array(
                    'success' => false,
                    'message' => array(
                        'text' => 'Enter password'
                    )
                ),
                400
            );
        }

        if(!Request::get('fullname')){
            return Response::json(array(
                    'success' => false,
                    'message' => array(
                        'text' => 'Enter full name'
                    )
                ),
                400
            );
        }

        if(!Request::get('gcm_id')){
            return Response::json(array(
                    'success' => false,
                    'message' => array(
                        'text' => 'No GCM id'
                    )
                ),
                400
            );
        }


        $validator = Validator::make(
            array(
                'email' => Request::get('email'),
                'password' => Request::get('password')
            ),
            array(
                'email' => 'required|unique:users|email',
                'password' => 'required|min:6'
            )
        );

        if($validator->fails()){
            if($validator->messages()->has('password')){
                return Response::json(
                    array(
                        'success' => false,
                        'message' => array(
                            'text' => 'Wrong Password'
                        )
                    ),
                    400
                );
            }else{
                return Response::json(
                    array(
                        'success' => false,
                        'message' => array(
                            'text' => 'Wrong Email'
                        )
                    ),
                    400
                );
            }
        }else{
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $randomString = '';
            for ($i = 0; $i < 10; $i++) {
                $randomString .= $characters[rand(0, strlen($characters) - 1)];
            }

            $user = new User;
            $user->email = Request::get('email');
            $user->password = Hash::make(Request::get('password'));
            $user->fullname = Request::get('fullname');
            $user->gcm_id = Request::get('gcm_id');
            $user->key = $randomString;
            $user->status = 'Beginner';//TODO
            $user->is_available = 1;

            $user->save();

            return Response::json(
                array(
                    'success' => true,
                    'message' => array(
                        'credentials' => $user->toArray()
                    )
                ),
                200
            );
        }
    }

    public function registerVk(){

        /*$headers = getallheaders();
        if(array_key_exists('HTTP_USERID', $headers)){
            $id = $headers['HTTP_USERID'];
        }*/
        $vk_id = Input::get('vk_id');
        $access_token = Input::get('access_token');
        $gcm_id = Input::get('gcm_id');

        $url = 'https://api.vk.com/method/users.get?access_token='.$access_token.'&fields=photo_max';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $output = curl_exec($curl);
        if($output == false){
            $error = curl_error($curl);
            curl_close($curl);
            return Response::json(
                array(
                    'success' => false,
                    'message' => array(
                        'text' => $error
                    )
                ),
                400
            );
        }else{
            curl_close($curl);
            $json = json_decode($output, true);
            if(!array_key_exists('response', $json)){
                return Response::json(
                    array(
                        'success' => false,
                        'message' => array(
                            'text' => 'No response'
                        )
                    ),
                    400
                );
            }
            $resp = $json['response'];
            $response = $resp[0];
            if(!array_key_exists('uid', $response)){
                return Response::json(
                    array(
                        'success' => true,
                        'message' => array(
                            'text' => 'No uid'
                        )
                    ),
                    400
                );
            }
            $uid = $response['uid'];
            $photo_url = $response['photo_max'];
            $first_name = $response['first_name'];
            $last_name = $response['last_name'];
            $name = $first_name.' '.$last_name;
            if($uid != $vk_id){
                return Response::json(
                    array(
                        'success' => false,
                        'message' => array(
                            'text' => 'No permission'
                        )
                    ),
                    400
                );
            }
            $user = User::where('vk_id', $vk_id)->first();

            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $randomString = '';
            for ($i = 0; $i < 10; $i++) {
                $randomString .= $characters[rand(0, strlen($characters) - 1)];
            }

            if($user){
                $user->login = true;
                $user->key = $randomString;
                $user->is_available = 1;
                if($gcm_id != ""){
                    $user->gcm_id = $gcm_id;
                }
                $user->save();

                return Response::json(
                    array(
                        'success' => true,
                        'message' => array(
                            'id' => $user->id,
                            'key' => $user->key,
                            'name' => $user->fullname,
                        )
                    ),
                    400
                );

            }else{
                $u = new User;
                $u->fullname = $name;
                $u->gcm_id = $gcm_id;
                $u->is_available = 1;
                $u->vk_id = $vk_id;
                $u->status = 'Beginner';//TODO
                $u->save();
                $usid = $u->id;

                $orig_img = imagecreatefromjpeg($photo_url);
                $orig_width = imagesx($orig_img);
                $orig_height = imagesy($orig_img);

                $big_width = 600;
                $big_height = 600;

                $small_width = 100;
                $small_height = 100;

                $small_tmpimg = imagecreatetruecolor($small_width, $small_height);
                $big_tmpimg = imagecreatetruecolor($big_width, $big_height);

                imagecopyresampled($small_tmpimg, $orig_img, 0, 0, 0, 0, $small_width, $small_height, $orig_width, $orig_height);
                imagecopyresampled($big_tmpimg, $orig_img, 0, 0, 0, 0, $big_width, $big_height, $orig_width, $orig_height);

                $thumbnail_name = "user_".$usid."_thumbnail.jpg";
                $profile_name = "user_".$usid."_profile.jpg";

                $dir = getcwd();
                $thumbnail_endfile = $dir."/images/users/thumbnails/" . $thumbnail_name;
                $profile_endfile = $dir."/images/users/profiles/" . $profile_name;

                imagejpeg($small_tmpimg, $thumbnail_endfile);
                imagejpeg($big_tmpimg, $profile_endfile);

                imagedestroy($small_tmpimg);
                imagedestroy($big_tmpimg);
                imagedestroy($orig_img);

                return Response::json(
                    array(
                        'success' => true,
                        'message' => array(
                            'id' => $u->id,
                            'key' => $u->key,
                            'name' => $u->fullname,
                            'vk_id' => $vk_id
                        )
                    ),
                    200
                );
            }
        }
    }

    public function sendPush(){
        //TODO decide what fields user needs
        $GOOGLE_API_KEY = 'AIzaSyDhECN-Qk935Qhd2Kbt4b3znPlgHJLQkJk';
        $headers = getallheaders();
        $id = $headers['HTTP_USERID'];

        $username = Input::get('fullname');
        $theme_id = Input::get('themeId');
        $email = Input::get('email');
        $isFriend = Input::get('isFriend');
        $theme_name = Input::get('themeName');
        $rid = Input::get('rid');

        $message = array(
            'id' => $id,
            'username' => $username,
            'theme_id' => $theme_id,
            'theme_name' => $theme_name,
            'email' => $email,
            'isFriend' => $isFriend);

        $user = User::find($rid);

        if($user == null){
            return Response::json(
                array(
                    'success' => false,
                    'message' => array(
                        'text' => 'user not found'
                    )
                ),
                400
            );
        }
        //insert request in game requests// old
        /*$req = DB::table('game_requests')->where('rid', $rid)->get();
        if($req){
            DB::table('game_requests')->where('rid', $rid)->update(array('id' => $id, 'username' => $username, 'theme_id' => $theme_id, 'theme_name' => $theme_name));
        }else{
            DB::table('game_requests')->insert(array('rid' => $rid, 'id' => $id, 'username' => $username, 'theme_id' => $theme_id, 'theme_name' => $theme_name));
        }*/

        $redis = Redis::connection();
        $REQUEST_TTL = 120;//2 mins

        $put_value = array(
            'id' => $id,
            'username' => $username,
            'theme_id' => $theme_id,
            'theme_name' => $theme_name,
            'checked' => '0'
        );
        $key = 'game_requests'.$rid;

        $redis->hMSet($key, $put_value);
        $redis->expire($key, $REQUEST_TTL);

        if($user->is_available == 1){
            $gcm_id = array($user->gcm_id);

            $gcm_url = 'https://android.googleapis.com/gcm/send';

            $fields = array(
                'registration_ids' => $gcm_id,
                'data' => $message,
            );

            $gcm_headers = array(
                'Authorization: key=' . $GOOGLE_API_KEY,
                'Content-Type: application/json'
            );
            // Open connection
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $gcm_url);

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $gcm_headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Disabling SSL Certificate support temporarly
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

            // Execute post for availability, set unavailable
            $result = curl_exec($ch);
            if($result == false){
                $error = curl_error($ch);
                curl_close($ch);
                return Response::json(
                    array(
                        'success' => false,
                        'message' => array(
                            'text' => $error
                        )
                    ),
                    400
                );
            }else{
                curl_close($ch);
                return Response::json(
                    array(
                        'success' => true,
                        'message' => array(
                            'message' => $message,
                            'gcm_id' => $gcm_id
                        )
                    ),
                    200
                );
            }
        }else{
            return Response::json(
                array(
                    'success' => false,
                    'message' => array(
                        'text' => 'not available'
                    )
                ),
                400
            );
        }
    }

    public function login($is_auto)
    {
        $credentials = [
            'email' => Input::get('email'),
            'password' => Input::get('password')
        ];
        $gcm_id = Input::get('gcm_id');
        if(Auth::attempt($credentials)){
            $user = User::where('email', $credentials['email'])->first();
            $user->login = true;

            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $randomString = '';
            for ($i = 0; $i < 10; $i++) {
                $randomString .= $characters[rand(0, strlen($characters) - 1)];
            }

            $user->key = $randomString;
            if($is_auto == 0){
                $user->is_available = 1;
            }
            if($gcm_id != ""){
                $user->gcm_id = $gcm_id;
            }
            $user->save();
            return Response::json(
                array(
                    'success' => true,
                    'message' => array(
                        'id' => $user->id,
                        'key' => $user->key,
                        'name' => $user->fullname,
                    )
                ),
                200
            );
        }else{
            return Response::json(
                array(
                    'success' => false,
                    'message' => array(
                        'text' => 'Wrong credentials'
                    )
                ),
                400
            );
        }
        /*if (Auth::attempt(array('email' => Request::get('email'), 'password' => Request::get('password')), true)){
            return Response::json(
                array(
                    'success' => true,
                    'message' => 'Successfully logged in'
                ),
                200
            );
        }else{
            return Response::json(
                array(
                    'success' => false,
                    'message' => 'Wrong credentials'
                ),
                400
            );
        }*/

    }

    public function setAvailability($is_available){//
        $headers = getallheaders();
        $id = $headers['HTTP_USERID'];
        $user = User::find($id);
        $user->is_available = $is_available;
        $user->save();

        return Response::json(
            array(
                'success' => true,
                'message' => array(
                    'text' => 'done'
                )
            ),
            200
        );
    }

    public function sendRequest(){
        $headers = getallheaders();
        $aid = $headers['HTTP_USERID'];
        $bid = Input::get('rid');
        $name1 = Input::get('name1');
        $name2 = Input::get('name2');
        $key = $aid.'_'.$bid;

        $second_key = $bid.'_'.$aid;
        $cnt = DB::table('requests')->where('id', $key)->get();
        $cnt2 = DB::table('requests')->where('id', $second_key)->get();
        if($cnt2){
            /*DB::table('requests')->where('id', $key)->delete();
            DB::table('requests')->where('id', $second_key)->delete();*/
            DB::table('requests')->where('id', $key)->orWhere('id', $second_key)->delete();
            DB::table('friends')->insert(array(
                array('id' => $key, 'uid1' => $aid, 'uid2' => $bid, 'name1' => $name1, 'name2' => $name2),
                array('id' => $second_key, 'uid1' => $bid, 'uid2' => $aid, 'name1' => $name2, 'name2' => $name1),
            ));
            return Response::json(array(
                    'success' => true,
                    'message' => array(
                        'text' => 'Friends'
                    )
                ),
                200
            );
        }
        if(!$cnt && $aid != $bid){

            DB::table('requests')->insert(array('id' => $key, 'aid' => $aid, 'bid' => $bid, 'name1' => $name1, 'name2' => $name2));
            return Response::json(array(
                    'success' => true,
                    'message' => array(
                        'text' => 'Done'
                    )
                ),
                200
            );
        }else{
            return Response::json(array(
                    'success' => true,
                    'message' => array(
                        'text' => 'Already sent'
                    )
                ),
                200
            );
        }

    }

    public function checkRequests(){
        $headers = getallheaders();
        $uid = $headers['HTTP_USERID'];
        //$mine_ids = DB::table('requests')->where('aid', $uid)->get();
        $forme_ids = DB::table('requests')->where('bid', $uid)->get();

        $mids = array();
        $fids = array();

        /*foreach($mine_ids as $mine_id){
            $a = array();
            $id = $mine_id->bid;
            $name = $mine_id->name2;
            $a['id'] = $id;
            $a['name'] = $name;
            array_push($mids, $a);
        }*/

        foreach($forme_ids as $forme_id){
            $a = array();
            $id = $forme_id->aid;
            $name = $forme_id->name1;
            $a['id'] = $id;
            $a['name'] = $name;
            array_push($fids, $a);
        }

        return Response::json(array(
                'success' => true,
                'message' => array(
                    'mine' => $mids,
                    'forme' => $fids
                )
            ),
            200
        );

    }

    public function confirmRequest($aid){
        $headers = getallheaders();
        $bid = $headers['HTTP_USERID'];

        //$c1 = DB::table('requests')->where('aid', '=', $aid)->where('bid', '=', $bid);
        $first_key = $aid.'_'.$bid;
        $c1 = DB::table('requests')->where('id', $first_key)->get();
        $name1 = '';
        $name2 = '';
        if($c1){
            //delete
            $name1 = $c1[0]->name1;
            $name2 = $c1[0]->name2;
            //DB::table('requests')->where('aid', '=', $aid)->where('bid', '=', $bid)->delete();
            DB::table('requests')->where('id', $first_key)->delete();
        }

        $second_key = $bid.'_'.$aid;
        //$c2 = DB::table('requests')->where('aid', '=', $bid)->where('bid', '=', $aid);
        $c2 = DB::table('requests')->where('id', $second_key)->get();
        if($c2){
            //delete
            $name2 = $c2[0]->name1;
            $name1 = $c2[0]->name2;
            //DB::table('requests')->where('aid', '=', $bid)->where('bid', '=', $aid)->delete();
            DB::table('requests')->where('id', $second_key)->delete();
        }
        if($aid > $bid){
            $temp = $bid;
            $bid = $aid;
            $aid = $temp;
            $tempname = $name1;
            $name1 = $name2;
            $name2 = $tempname;
        }
        $first_friend_key = $aid.'_'.$bid;
        $second_friend_key = $bid.'_'.$aid;
        //$cnt3 = DB::table('friends')->where('uid1', '=', $aid)->where('uid2', '=', $bid)->count();
        $c3 = DB::table('friends')->where('id', $first_friend_key)->get();
        if(!$c3){
            //insert
            DB::table('friends')->insert(array(
                array('id' => $first_friend_key, 'uid1' => $aid, 'uid2' => $bid, 'name1' => $name1, 'name2' => $name2),
                array('id' => $second_friend_key, 'uid1' => $bid, 'uid2' => $aid, 'name1' => $name2, 'name2' => $name1),
            ));
        }

        return Response::json(array(
                'success' => true,
                'message' => array(
                    'text' => 'Friends'
                )
            ),
            200
        );
    }

    public function cancelRequest($bid){
        $headers = getallheaders();
        $aid = $headers['HTTP_USERID'];
        $key = $aid.'_'.$bid;
        //DB::table('requests')->where('aid', '=', $aid)->where('bid', '=', $bid)->delete();
        DB::table('requests')->where('id', $key)->delete();
        return Response::json(array(
                'success' => true,
                'message' => array(
                    'text' => 'Canceled'
                )
            ),
            200
        );
    }

    public function declineRequest($aid){
        $headers = getallheaders();
        $bid = $headers['HTTP_USERID'];
        $key = $aid.'_'.$bid;
        $second_key = $bid.'_'.$aid;
        //DB::table('requests')->where('aid', '=', $aid)->where('bid', '=', $bid)->delete();
        DB::table('requests')->where('id', $key)->delete();
        DB::table('requests')->where('id', $second_key)->delete();

        return Response::json(array(
                'success' => true,
                'message' => array(
                    'text' => 'Declined'
                )
            ),
            200
        );
    }

    public function findUser(){
        //TODO add memcached
        $headers = getallheaders();
        $name = $headers['name'];

        $up = 50;
        $users = User::where('fullname', 'LIKE', $name)->take($up)->get()->toArray();

        $res = array();
        $m = (count($users) > $up) ? $up : count($users);

        for($i = 0; $i < $m; $i++){
            $a = array();
            $a['id'] = $users[$i]['id'];
            $a['name'] = $users[$i]['fullname'];
            array_push($res, $a);
        }

        return Response::json(array(
                'success' => true,
                'message' => array(
                    'users' => $res
                )
            ),
            200
        );
    }

    public function getFriends(){
        //TODO add memcached
        $headers = getallheaders();
        $uid = $headers['HTTP_USERID'];

        /*$u1 = DB::table('friends')->where('uid1', '=', $uid)->get();

        $u2 = DB::table('friends')->where('uid2', '=', $uid)->get();*/

        $f = DB::table('friends')->where('uid1', $uid)->get();

        $friends = array();

        foreach($f as $u){
            $a = array();
            $a['id'] = $u->uid2;
            $a['name'] = $u->name2;
            array_push($friends, $a);
        }

        return Response::json(array(
                'success' => true,
                'message' => array(
                    'friends' => $friends
                )
            ),
            200
        );
    }

    public function unfriend($id){
        $headers = getallheaders();
        $uid = $headers['HTTP_USERID'];

        $first_key = $id.'_'.$uid;
        $second_key = $uid.'_'.$id;

        DB::table('friends')->where('id', $first_key)->delete();
        DB::table('friends')->where('id', $second_key)->delete();

        return Response::json(array(
                'success' => true,
                'message' => array(
                    'text' =>  'Success',
                )
            ),
            200
        );
    }

    public function uploadImage(){
        $base = $_REQUEST["image"];
        $headers = getallheaders();
        $uid = $headers['HTTP_USERID'];
        if (isset($base)) {

            $binary = base64_decode($base);
            header("Content-Type: bitmap; charset=utf-8");
            $image_name = "user_".$uid."_profile.jpg";
            $dir = getcwd();
            $file = fopen($dir."/images/users/profiles/" . $image_name, "wb");

            fwrite($file, $binary);

            fclose($file);

            $orig_img = imagecreatefromjpeg($dir."/images/users/profiles/" . $image_name);
            $orig_width = imagesx($orig_img);
            $orig_height = imagesy($orig_img);

            $big_width = 600;
            $big_height = 600;

            $small_width = 100;
            $small_height = 100;

            $small_tmpimg = imagecreatetruecolor($small_width, $small_height);
            $big_tmpimg = imagecreatetruecolor($big_width, $big_height);

            imagecopyresampled($small_tmpimg, $orig_img, 0, 0, 0, 0, $small_width, $small_height, $orig_width, $orig_height);
            imagecopyresampled($big_tmpimg, $orig_img, 0, 0, 0, 0, $big_width, $big_height, $orig_width, $orig_height);

            $thumbnail_name = "user_".$uid."_thumbnail.jpg";
            $profile_name = "user_".$uid."_profile.jpg";

            $thumbnail_endfile = $dir."/images/users/thumbnails/" . $thumbnail_name;
            $profile_endfile = $dir."/images/users/profiles/" . $profile_name;

            imagejpeg($small_tmpimg, $thumbnail_endfile);
            imagejpeg($big_tmpimg, $profile_endfile);

            imagedestroy($small_tmpimg);
            imagedestroy($big_tmpimg);
            imagedestroy($orig_img);


            return Response::json(array(
                    'success' => true,
                    'message' => array(
                        'text' =>  'Success',
                    )
                ),
                200
            );

        }else{

            return Response::json(array(
                    'success' => false,
                    'message' => array(
                        'text' =>  'Smths wrong',
                    )
                ),
                200
            );
        }
    }

    public function getScoreById($id){

        $scores = DB::table('scores')->where('user_id', $id)->get();

        $sc = array();

        foreach($scores as $s){
            $theme_id = $s->theme_id;
            $scr = $s->score;
            $theme_name = $s->theme_name;
            //$score[$themed_id] = array('theme_name' => $theme_name, 'score' => $scr);
            $score = array();
            $score['theme_id'] = $theme_id;
            $score['theme_name'] = $theme_name;
            $score['score'] =  $scr;
            $sc[] = $score;
        }

        return Response::json(array(
                'success' => true,
                'message' => array(
                    'score' => $sc
                )
            ),
            200
        );
    }

    public function getUser($id){
        $headers = getallheaders();
        $uid = $headers['HTTP_USERID'];

        $user = User::find($id)->toArray();

        if(!$user){
            return Response::json(array(
                    'success' => false,
                    'message' => array(
                        'text' => 'No permission or not found'
                    )
                ),
                400
            );
        }

        $isFriend = "0";
        $user_key = $id.'_'.$uid;
        $friend_exists = DB::table('friends')->where('id', $user_key)->get();
        if($friend_exists){
            $isFriend = "1";
        }
        $user['isFriend'] = $isFriend;
        $user_duel_score = '0';
        $op_duel_score = '0';
        if($uid != $id){
            if($uid < $id){
                $key = $uid.'_'.$id;
                $duel_score = DB::table('duels')->where('id', $key)->get();
                if($duel_score){
                    $user_duel_score = $duel_score[0]->score1;
                    $op_duel_score = $duel_score[0]->score2;
                }
            }else{
                $key = $id.'_'.$uid;
                $duel_score = DB::table('duels')->where('id', $key)->get();
                if($duel_score){
                    $user_duel_score = $duel_score[0]->score2;
                    $op_duel_score = $duel_score[0]->score1;
                }
            }
        }
        $user['user_duel_score'] = $user_duel_score;
        $user['op_duel_score'] = $op_duel_score;

        return Response::json(array(
                'success' => true,
                'message' => array(
                    'user' =>  $user,
                )
            ),
            200
        );
    }
}