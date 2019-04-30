<?php

namespace App\Http\Controllers\Weixin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class Weixincontroller extends Controller
{
    public function getaccesstoken(){
        $key='wx_assess_token';
        $token=Redis::get($key);
        if($token){
            return $token;
        }else{
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env('WX_APPID').'&secret='.env('WX_APPSECRET');
            $response=file_get_contents($url);
            $arr =json_decode($response,true);
            Redis::set($key,$arr['access_token']);
            Redis::expire($key,3600);
            $token=$arr['access_token'];
            return $token;
        }

    }
    public function getu(){
        $code = $_GET['code'];
        //获取access_token
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.env('WX_APPID').'&secret='.env('WX_APPSECRET').'&code='.$code.'&grant_type=authorization_code';
        $response = json_decode(file_get_contents($url),true);
        $access_token = $response['access_token'];
        $openid= $response['openid'];
        //获取用户信息
        $urll = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
        $response_user = json_decode(file_get_contents($urll),true);
        $res =DB::table('p_sq_user')->where(['openid'=>$response_user['openid']])->first();
        if($res==NULL){
            $aa_info = [
                'openid' => $response_user['openid'],
                'nickname' => $response_user['nickname'],
                'sex' => $response_user['sex'],
                'headimgurl' => $response_user['headimgurl'],
            ];
            DB::table('p_sq_user')->insertGetId($aa_info);
            header('Refresh:3;url=/goods/goodsdetail/9');
            return "<h1>欢迎小可爱授权</h1>";
        }else{
            header('Refresh:3;url=/goods/goodsdetail/9');
            return "<h1>欢迎小可爱回来</h1>";

        }

    }
}
