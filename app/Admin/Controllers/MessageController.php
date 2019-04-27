<?php

namespace App\Admin\Controllers;
use Illuminate\Support\Facades\Redis;
use App\Model\MessageModel;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
class MessageController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
       $data=DB::table('wx_user')->get();
       return $content->header('用户管理')->description('群发消息')->body(view('admin.weixin.message',['data'=>$data]));
    }
    public function getaccesstoken(){
        $key = 'sdk_accesstoken';
        $token = Redis::get($key);
        if($token){
            return $token;
        }else{
            echo "没有  添加:";
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSECRET');
            $arr = json_decode(file_get_contents($url),true);
            Redis::set($key,$arr['access_token']);
            Redis::expire($key,3600);
            $token = $arr['access_token'];
            return $token;
        }
    }
    public function Add()
    {
        $client=new Client();
        $openid=$_GET['openid'];
        $text=$_GET['text'];
        $openid=explode(',',$openid);
        $arr=[
            'touser' => $openid,
            'msgtype' => 'text',
            'text' => [
                'content'=>$text
            ]
        ];
        $str=json_encode($arr,JSON_UNESCAPED_UNICODE);
        $access_token=$this->getaccesstoken();
        $url='https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token='.$access_token;
         $response=$client->request('POST',$url,[
                    'body'=>$str
         ]);
         return "发送成功";

    }
}
