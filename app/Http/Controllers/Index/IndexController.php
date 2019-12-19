<?php

namespace App\Http\Controllers\Index;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public  function   index(){
        $code=$_GET['code'];
        $state=$_GET['state'];
        //获取code后，请求以下链接获取access_token
        $data=$this->access_token($code);
//        dd($data);
        //获取用户信息
        $info=$this->userInfo($data['access_token'],$data['openid']);

      return view('index.idnex',['info'=>$info]);


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
