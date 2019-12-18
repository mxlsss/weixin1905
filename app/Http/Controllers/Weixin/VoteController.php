<?php

namespace App\Http\Controllers\Weixin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;


class VoteController extends Controller
{


    public  function index(){

       $code=$_GET['code'];
        $state=$_GET['state'];
        //获取code后，请求以下链接获取access_token
        $data=$this->access_token($code);
//        dd($data);
        //获取用户信息
     $info=$this->userInfo($data['access_token'],$data['openid']);
      /**$key='vote:1905';
        $scard=Redis::scard($key);
        $smembers=Redis::smembers($key);
        if(Redis::sismember($key,$info['openid'])) {
            echo "已经投票了,总票数:".$scard;
            echo '<pre>';print_r($smembers);echo '</pre>';
        }else{
            Redis::sadd($key,$info['openid']);
            $scard=Redis::scard($key);
            echo "投票成功";

        }**/
        $key='vote:1905wx';
        if(Redis::zadd($key,time(),$info['openid'])){
            echo "已经投过票了";
        }else{
            Redis::zadd($key,time(),$info['openid']);
        }








    }

    protected function access_token($code){

        $url='https://api.weixin.qq.com/sns/oauth2/access_token?appid='.env('WX_APPID').'&secret='.env('WX_APPSECREET').'&code='.$code.'&grant_type=authorization_code';
        $data=file_get_contents($url);

        return json_decode($data,true);

    }
    protected  function userInfo($access_token,$openid){
        $url='https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';

        $data= file_get_contents($url);
        return json_decode($data,true);
    }


}
