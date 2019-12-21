<?php

namespace App\Http\Controllers\Weixin;

use App\Http\Controllers\Controller;
use App\Model\WxUserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;


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

        $total = Redis::  zCard($key);        // 获取总数
        echo '投票总人数： '.$total;echo '</br>';
        $members = Redis::zRange($key,0,-1,true);       // 获取所有投票人的openid
//        echo '<pre>';print_r($members);echo '</pre>';

        foreach($members as $k=>$v){
//            echo "用户： ".$k . ' 投票时间: '. date('Y-m-d H:i:s',$v);echo '</br>';
            $u_k = 'h:info'.$k;
            $u = Redis::hgetAll($u_k);
            //$u = Redis::hMget($u_k,['openid','nickname','sex','headimgurl']);
//            echo ' <img src="'.$u['headimgurl'].'"> ';
            echo '<img src="'.Redis::hget('h:info'.$k,'headimgurl').'">';
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

    public  function  quefa(){
        //天气链接
        $url="https://free-api.heweather.net/s6/weather/now?location=beijing&key=0d739e0b250a41d18e29ce498727d9ec";
        //读取天气
        $url_info=file_get_contents($url);
        //转换成数组
        $url_info_arr=json_decode($url_info,true);
        //获取城市
        $location=$url_info_arr['HeWeather6']['0']['basic']['location'];
        //获取天气状况
        $cond_txt=$url_info_arr['HeWeather6']['0']['now']['cond_txt'];
        //获取天气温度
        $tmp=$url_info_arr['HeWeather6']['0']['now']['tmp'];
        //获取天气风向
        $wind_dir=$url_info_arr['HeWeather6']['0']['now']['wind_dir'];
        //获取天气风力
        $wind_sc=$url_info_arr['HeWeather6']['0']['now']['wind_sc'];
        $res='北京时间:'.date('Y-m-d H:i:s').$location.' 天气:'.$cond_txt.' 温度:'.$tmp."\n".' 风向:'.$wind_dir.' 风力'.$wind_sc;

           //获取token
        $keys = "wx_access_token";
        $access_token = Redis::get($keys);
        if (!$access_token) {
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env('WX_APPID').'&secret='.env('WX_APPSECREET');
            $data_json = file_get_contents($url);
            $arr = json_decode($data_json, true);
            Redis::set($keys, $arr['access_token']);
            Redis::expire($keys, 3600);
            $access_token=$arr['access_token'];
        }

            $url="https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token=".$access_token;
//      $openid=WxUserModel::get('openid')->toArray();
        $openid=WxUserModel::select('openid')->get()->toArray();
        $openid=array_column($openid,'openid');
//         dd($openid);
        $data=[
            'filter'=>[
                "is_to_all"=>true,
            ],
            'msgtype'=>'text',
            'text'=>[ 'content'=>$res]
        ];
//        dd(json_encode($data,JSON_UNESCAPED_UNICODE));
        $client= new Client();
        $response=$client->request('POST',$url,[
            'body' =>json_encode($data,JSON_UNESCAPED_UNICODE)
        ]);

        echo  $response->getBody();

    }


}
