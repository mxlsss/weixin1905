<?php

namespace App\Http\Controllers\Weixin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VoteController extends Controller
{


    public  function index(){

       $code=$_GET['code'];
        $state=$_GET['state'];
        //获取code后，请求以下链接获取access_token
        $data=$this->access_token($code);
        dd($data);

    }

    protected function access_token($code){

        $url='https://api.weixin.qq.com/sns/oauth2/access_token?appid='.env('WX_APPID').'&secret='.env('WX_APPSECREET').'&code='.$code.'&grant_type=authorization_code';
        $data=file_get_contents($url);

        return json_decode($data,true);

    }


}
