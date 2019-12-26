<?php

namespace App\Http\Controllers\Kaoshi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\WxImgModel;
use App\Model\WxLiuyanModel;
use App\Model\WxUserModel;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;


class KaoshiController extends Controller
{


    //获取token
    public function GetAccessToken()
    {
        $keys = "wx_access_token";
        $access_token = Redis::get($keys);
        if ($access_token) {
            return $access_token;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . env('WX_APPID') . '&secret=' . env('WX_APPSECREET');
        $data_json = file_get_contents($url);
        $arr = json_decode($data_json, true);
        Redis::set($keys, $arr['access_token']);
        Redis::expire($keys, 3600);
        return $arr['access_token'];
    }
    //get接入微信
    public function wx()
    {
        $token = '90d162aa1f38ee74a8a7041bd2201ba4';
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $echostr = $_GET['echostr'];

        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);


        if ($tmpStr == $signature) {
            echo $echostr;
        } else {
            die('not ok');
        }

    }
    // 传参
    public function receiv()
    {
        $log_file = 'wx.log';
        $xml_str = file_get_contents("php://input");
        //将接收的数据记录到日志文件
        $data = date('Y-m-d H:i:s') . $xml_str;
        file_put_contents($log_file, $data, FILE_APPEND);         //追加写
        //处理xml数据
        $xml_obj = simplexml_load_string($xml_str);
        //获取TOKEN
        $access_token = $this->GetAccessToken();
        //调用微信用户信息
        $yonghu = $this->getUserInfo($access_token, $xml_obj->FromUserName);
        //转换用户信息
        $userInfo = json_decode($yonghu, true);


    }


    //获取用户基本信息
    public function getUserInfo($access_token, $openid)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $access_token . '&openid=' . $openid . '&lang=zh_CN';
        //发送网络请求
        $json_str = file_get_contents($url);
        $log_file = 'wx.user.log';
        file_put_contents($log_file, $json_str, FILE_APPEND);
        return $json_str;
    }

}
