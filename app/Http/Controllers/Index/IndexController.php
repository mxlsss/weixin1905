<?php

namespace App\Http\Controllers\Index;

use App\Http\Controllers\Controller;
use App\Model\GoodsModel;
use App\Model\WxUserModel;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public  function index(){
        $code=$_GET['code'];
        //获取code后，请求以下链接获取access_token
        $data=$this->access_token($code);
//        dd($data);
        //获取用户信息
        $info=$this->userInfo($data['access_token'],$data['openid']);
         //获取商品信息
        $goodsinfo=GoodsModel::get();
      return view('index.index',['info'=>$info,'goodsinfo'=>$goodsinfo]);

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


    public function newYear()
    {
        $wx_appid = env('WX_APPID');
        $noncestr = Str::random(8);
        $timestamp = time();
        $url = env('APP_URL') . $_SERVER['REQUEST_URI'];    //当前页面的URL
        $signature = $this->signature($noncestr,$timestamp,$url);

        $data = [
            'appid'         => $wx_appid,
            'timestamp'     => $timestamp,
            'noncestr'      => $noncestr,
            'signature'     => $signature
        ];

        return view('index.index',$data);
    }
    // 计算jsapi签名
    public function signature($noncestr,$timestamp,$url)
    {
        $noncestr = $noncestr;
        // 1 获取 jsapi ticket
        $ticket = WxUserModel::getJsapiTicket();
        // 拼接带签名字符串
        $string1 = "jsapi_ticket={$ticket}&noncestr={$noncestr}&timestamp={$timestamp}&url={$url}";
        // sha1
        return  sha1($string1);
    }
}
