<?php

namespace App\Http\Controllers\Index;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public  function   index(){
        $code=$_GET['code'];
       dd($code);


    }

}
