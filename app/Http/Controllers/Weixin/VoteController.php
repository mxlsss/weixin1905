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
        $keys='h:info'.$info['openid'];
        Redis::hMset($keys,$info);
        $key='vote:1905wx';
        if(Redis::zrank($key,$info['openid'])){
            echo "已经投过票了";echo '</br>';
        }else{
            Redis::zadd($key,time(),$info['openid']);
            echo "投票成功";
            echo '</br>';
        }

        $total = Redis::zCard($key);        // 获取总数
        echo '投票总人数： '.$total;echo '</br>';
        $members = Redis::zRange($key,0,-1,true);       // 获取所有投票人的openid
//        echo '<pre>';print_r($members);echo '</pre>';
        foreach($members as $k=>$v){
//            echo "用户： ".$k . ' 投票时间: '. date('Y-m-d H:i:s',$v);echo '</br>';
            $u_k = 'h:info'.$k;
            $u = Redis::hgetAll($u_k);
            //$u = Redis::hMget($u_k,['openid','nickname','sex','headimgurl']);
            echo ' <img src="'.$u['headimgurl'].'"> ';
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
