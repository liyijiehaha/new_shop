<?php
namespace App\Http\Controllers\Weixin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
class TmpUserController extends Controller
{
    //获取access_token
    public function getaccesstoken(){
        $key='wx_assess_token';
        $token=Redis::get($key);
        if($token){
            return $token;
        }else {
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . env('WX_APPID') . '&secret=' . env('WX_APPSECRET');
            $response = file_get_contents($url);
            $arr = json_decode($response, true);
            Redis::set($key, $arr['access_token']);
            Redis::expire($key, 3600);
            $token = $arr['access_token'];
            return $token;
        }
    }
    /*永久二维码*/
    public function tmper(){
        $url='https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$this->getaccesstoken();
        $data='{"action_name": "QR_LIMIT_STR_SCENE", "action_info": {"scene": {"scene_str": "test"}}}';
        $arr=json_decode($this->https_post($url,$data),true);
        $ticket=$arr['ticket'];
        $url1='https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$ticket;
       return redirect($url1);
    }
    function https_post($url, $data = null){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
}
