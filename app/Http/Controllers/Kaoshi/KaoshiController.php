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
//        dd($xml_obj);
        $openid=$xml_obj->FromUserName;
        if($xml_obj->MsgType=='event'){
            $touser=$xml_obj->FromUserName;
            $fromuser=$xml_obj->ToUserName;
            $time=time();
             if($xml_obj->Event=='subscribe'){
                 $data=[
                       'openid'=>$openid,
                      'headimgurl'=>$userInfo['headimgurl'],
                     'sub_time'=>$xml_obj->CreateTime,
                     'sex'=>$userInfo['sex'],
                     'nickname'=>$userInfo['nickname'],
                 ];
    //                 dd($data['openid']);
                       //判断是否关注
                     $u=WxUserModel::where('openid',$openid)->first();
    //                 dd($u);
                     if($u){
                         $content="欢迎".$xml_obj->nickname."同学\n现在北京时间".date('Y-m-d H:i:s')."\n 欢迎回来";
                         $huifu='<xml>
                     <ToUserName><![CDATA[' . $touser . ']]></ToUserName>
                     <FromUserName><![CDATA[' . $fromuser . ']]></FromUserName>
                     <CreateTime>' . $time . '</CreateTime>
                       <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[' . $content . ']]></Content>
                  </xml>';
                         echo $huifu;
                     }else{
    //                     dd($data);
                         $a=WxUserModel::insert($data);
                         $content="欢迎".$xml_obj->nickname."同学\n现在北京时间".date('Y-m-d H:i:s')."\n 感谢您的关注";
                         $huifu='<xml>
                     <ToUserName><![CDATA[' . $touser . ']]></ToUserName>
                     <FromUserName><![CDATA[' . $fromuser . ']]></FromUserName>
                     <CreateTime>' . $time . '</CreateTime>
                       <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[' . $content . ']]></Content>
                  </xml>';

                         echo $huifu;

                 }

             }
             elseif ($xml_obj->Event=='CLICK'){
               if($xml_obj->EventKey=='qiandao'){
                     $time=date('Y-m-d');
//                   dd($time);
                   $qiandao_time=WxUserModel::where('openid',$openid)->select('qiandao_time','nickname','jifen')->get()->toArray();
//                   dd($qiandao_time['0']['qiandao_time']);
                   if($qiandao_time['0']['qiandao_time']==$time){
                       $content="你好".$qiandao_time['0']['nickname']."同学\n你今天已经签到\n点击下方菜单即可查询总积分";
                       $huifu='<xml>
                     <ToUserName><![CDATA[' . $touser . ']]></ToUserName>
                     <FromUserName><![CDATA[' . $fromuser . ']]></FromUserName>
                     <CreateTime>' . $time . '</CreateTime>
                       <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[' . $content . ']]></Content>
                  </xml>';
                       echo $huifu;
                   }else{
                       WxUserModel::where('openid',$openid)->increment('jifen',10);
                       WxUserModel::where('openid',$openid)->update(['qiandao_time'=>date('Y-m-d')]);
                       $content="你好".$qiandao_time['0']['nickname']."同学\n签到成功，积分增加10\n点击下方菜单即可查询总积分";
                       $huifu='<xml>
                     <ToUserName><![CDATA[' . $touser . ']]></ToUserName>
                     <FromUserName><![CDATA[' . $fromuser . ']]></FromUserName>
                     <CreateTime>' . $time . '</CreateTime>
                       <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[' . $content . ']]></Content>
                  </xml>';
                       echo $huifu;

                   }
                 }elseif($xml_obj->EventKey=='jifen'){
                   $jifen=WxUserModel::where('openid',$openid)->select('jifen','nickname')->get()->toArray();
//                  dd($jifen);
                   $content="你好".$jifen['0']['nickname']."同学\n你的积分为:".$jifen['0']['jifen'];
                   $huifu='<xml>
                     <ToUserName><![CDATA[' . $touser . ']]></ToUserName>
                     <FromUserName><![CDATA[' . $fromuser . ']]></FromUserName>
                     <CreateTime>' . $time . '</CreateTime>
                       <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[' . $content . ']]></Content>
                  </xml>';
                   echo $huifu;


               }


             }

            }






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

   //生成菜单
    public  function caidan()
    {
        $access_token = $this->GetAccessToken();
//        dd($access_token);
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=" . $access_token;
//        dd($url);
        $data =[
            'button'=>[
                [
                    'type'=>'click',
                    "name"=>"积分查询",
                    "key"=>"jifen"

                ],
                [
                    'type'=>'click',
                    "name"=>"签到",
                    "key"=>"qiandao"

                ],
                     ],
                ];

         $data=json_encode($data,JSON_UNESCAPED_UNICODE);
        $clienk= new  Client();
        $aaa=$clienk->request('POST',$url,[
            'body'=>$data
        ]);

        echo  $aaa->getBody();


    }

    //群发
    public function qunfa(){
     $content="尊敬的用户您好，目前公司开展签到送积分兑换活动，详情请进入公众号查看";
        $token=$this->GetAccessToken();
        $url="https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token=".$token;
        $openid=WxUserModel::select('openid')->get()->toArray();
        $data=[
            'touser'=>$openid,
            "msgtype"=>"text",
            "text"=>["content"=>$content],
        ];

        $client = new Client();

        $aaa=$client->request('POST',$url,[
            'body'=>json_encode($data,JSON_UNESCAPED_UNICODE),
        ]);
        echo $aaa->getBody();
    }



}
