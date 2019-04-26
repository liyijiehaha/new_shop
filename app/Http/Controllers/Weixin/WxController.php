<?php
namespace App\Http\Controllers\Weixin;
use App\Model\WxUsermodel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
class WxController extends Controller
{
    //第一次调用接口
    public function list(){
        echo $_GET['echostr'];
    }
    //点击关注
    public function WxEvent(){
        $content = file_get_contents("php://input");
        $time = date('Y-m-d H:i:s');
        is_dir('logs')or mkdir('logs',0777,true);
        $str = $time.$content."\n";
        file_put_contents("logs/wx_event.log",$str,FILE_APPEND);
        $data=simplexml_load_string($content);
        print_r($data);die;
        $openid=$data->FromUserName;//用户openid
        $app=$data->ToUserName;//公众号id
        $event=$data->Event;
        $type=$data->MsgType;//消息类型
        $create_time=$data->CreateTime;
        $text=$data->Content;
        $client=new Client();
        if($event=='subscribe'){
            //根据openid判断用户是否已存在
            $Weixin_model=new Weixinmodel();
            $local_user=$Weixin_model->where(['openid'=>$openid])->first();
            if($local_user){
                echo '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName><FromUserName><![CDATA['.$app.']]></FromUserName><CreateTime>'.time().'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['. '欢迎回来 '. $local_user['nickname'] .']]></Content></xml>';
            }else{
                //获取用户信息
                $u=$this ->getUserInfo($openid);
                //用户信息入库
                $u_info=[
                    'openid'=>$u['openid'],
                    'nickname'=>$u['nickname'],
                    'sex'=>$u['sex'],
                    'headimgurl'=>$u['headimgurl'],
                ];
                $Weixin_model=new Weixinmodel();
                $res= $Weixin_model->insert($u_info);
                echo '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName><FromUserName><![CDATA['.$app.']]></FromUserName><CreateTime>'.time().'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['. '欢迎关注 '. $u['nickname'] .']]></Content></xml>';
            }
        }elseif($type=='voice'){
            $media_id=$data->MediaId;
            $url='https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->getaccesstoken().'&media_id='.$media_id;
            $arm=file_get_contents($url);
            $file_name=time().mt_rand(1111,9999).'.amr';//文件名
            $arr=file_put_contents('wx/voice/'.$file_name,$arm);
            $voice='wx/voice/'.$file_name;
            $info=[
                'voice'=>$voice,
                'openid'=>$openid,
                'create_time'=>$create_time
            ];
            $res=DB::table('wx_material')->insertGetId($info);


        }elseif($type=='image'){
            $media_id=$data->MediaId;
            $url='https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->getaccesstoken().'&media_id='.$media_id;
            $response = $client->get($url);
            //获取响应头信息
            $headers= $response->getHeaders();
            $file_info= $headers['Content-disposition'][0];//获取文件名
            $file_name=rtrim(substr($file_info,-20),'"');
            $new_file_name=substr(md5(time().mt_rand(1111,9999)),10,8).'_'.$file_name;
            $arr=Storage::put('wx/img'.$new_file_name,$response->getBody());
            $img='public/wx/img'.$new_file_name;
            $info=[
                'img'=>$img,
                'openid'=>$openid,
                'create_time'=>$create_time
            ];
            $res=DB::table('wx_material')->insertGetId($info);

        }elseif($type=='text'){
            //自动回复天气
            if(strpos($data->Content,'+天气')){
                $city=explode('+',$data->Content)[0];
                $url='https://free-api.heweather.net/s6/weather/now?key=HE1904161044241545&location='.$city;
                $arr=json_decode(file_get_contents($url),true);
                if($arr['HeWeather6'][0]['status']=='ok') {
                    $fl=$arr['HeWeather6'][0]['now']['tmp'];//摄氏度
                    $wind_dir=$arr['HeWeather6'][0]['now']['wind_dir'];//风向
                    $wind_sc=$arr['HeWeather6'][0]['now']['wind_sc'];//风力
                    $hum=$arr['HeWeather6'][0]['now']['hum'];//温度
                    $str="城市：".$city."\n"."温度：".$fl."\n"."风向：".$wind_dir."\n"."风力：".$wind_sc."\n"."温度：".$hum."\n";
                    $response_xml='<xml><ToUserName><![CDATA['.$openid.']]></ToUserName>
                                    <FromUserName><![CDATA['.$app.']]></FromUserName>
                                    <CreateTime>'.time().'</CreateTime>
                                    <MsgType><![CDATA[text]]></MsgType>
                                    <Content><![CDATA['.$str.']]></Content></xml>';
                    $info=[
                        'text'=>$str,
                        'openid'=>$openid,
                        'create_time'=>$create_time
                    ];
                    $res=DB::table('wx_material')->insert($info);

                }else{
                    $response_xml='<xml><ToUserName><![CDATA['.$openid.']]></ToUserName>
                                    <FromUserName><![CDATA['.$app.']]></FromUserName>
                                    <CreateTime>'.time().'</CreateTime>
                                    <MsgType><![CDATA[text]]></MsgType>
                                    <Content><![CDATA[城市不正确]]></Content></xml>';
                }
                echo $response_xml;
            };


        }
    }
    //获取access_token
    public function getaccesstoken(){
        $key='wx_assess_token';
        $token=Redis::get($key);
        if($token){
            echo '有';
        }else{
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env('WX_APPID').'&secret='.env('WX_APPSECRET');
            $response=file_get_contents($url);
            $arr =json_decode($response,true);
            Redis::set($key,$arr['access_token']);
            Redis::expire($key,3600);
            $token=$arr['access_token'];
        }
        return $token;
    }
    //获取微信用户
    public function getUserInfo($openid){
        $url='https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$this->getaccesstoken().'&openid='.$openid.'&lang=zh_CN';
        $data=file_get_contents($url);
        $u=json_decode($data,true);
        return $u;
    }
    //创建微二级菜单
    public function create_menu(){
        $url='https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$this->getaccesstoken();
        $arr=[
            'button'=>[
                [
                    'type'=>'click',
                    'name'=>'123',
                    'key'=> 'V1001_TODAY_TWLY',
                ],
                [
                    'type'=>'click',
                    'name'=>'456',
                    'key'=> 'V1001_TODAY_JZSC',
                ]
            ]
        ];
        $str=json_encode($arr,JSON_UNESCAPED_UNICODE);
        $client=new Client();
        $respons=$client->request('POST',$url,[
            'body'=>$str
        ]);
        $ass=$respons->getBody();
        $ar=json_decode($ass,true);
        if($ar['errcode']>0){
            echo "创建菜单失败";
        }else{
            echo "创建菜单成功";
        }
    }
    /*根据openid消息群发*/
    public function sendMsg($openid_arr,$content){
        $msg=[
            'touser'=>$openid_arr,
            'msgtype'=>"text",
            "text"=>[
                "content"=> $content
            ]
        ];
        $data=json_encode($msg,JSON_UNESCAPED_UNICODE);
        $url='https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token='.$this->getaccesstoken();
        $client=new Client();
        $response=$client->request('post',$url,[
            'body'=>$data
        ]);
        return  $response->getBody();
    }
    public function send(){
        $where=[
            'status'=>1
        ];
        $userlist = DB::table('wx_user')->where($where)->get()->toArray();
        $openid_arr=array_column($userlist,'openid');
        $msg="李依杰可耐";
        $res =$this->sendmsg($openid_arr,$msg);
        if($res){
            echo '发送成功';
        }else{
            echo '发送失败';
        }
    }

}
