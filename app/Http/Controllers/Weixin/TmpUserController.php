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
        $data=[
            "action_name"=>"QR_LIMIT_SCENE",
            "action_info"=>[
                "scene"=> [
                     "scene_id"=>1234
                ],
            ],
        ];
        $client=new Client();
        $str=json_encode($data,JSON_UNESCAPED_UNICODE);
        $response=$client->request('POST',$url,[
            'body'=>$str
        ]);
        $res= $response->getBody();
        $arr=json_decode($res,true);
        $ticket=$arr['ticket'];
        $url1='https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$ticket;
        return redirect($url1);
    }

}
