<?php
namespace App\Http\Controllers\Weixin;
use App\Model\WxUserModel;
use App\model\UserModel;
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
    public function wxEvent(){

        $content = file_get_contents("php://input");
        $time = date('Y-m-d H:i:s');
        is_dir('logs')or mkdir('logs',0777,true);
        $str = $time.$content."\n";
        file_put_contents("logs/wx_event.log",$str,FILE_APPEND);
        $data=simplexml_load_string($content);
//        echo '<pre>';print_r($data);echo '</pre>';die;
        $openid=$data->FromUserName;//用户openid
        $app=$data->ToUserName;//公众号id
        $event=$data->Event;
        $type=$data->MsgType;//消息类型
        $create_time=$data->CreateTime;
        $text=$data->Content;
        $client=new Client();
        if($event=='subscribe'){
            //根据openid判断用户是否已存在
            $Weixin_model=new WxUserModel();
            $local_user=$Weixin_model->where(['openid'=>$openid])->first();
            if($local_user){
                echo  '<xml>
                          <ToUserName><![CDATA[' . $openid . ']]></ToUserName>
                          <FromUserName><![CDATA[' . $app . ']]></FromUserName>
                          <CreateTime>' . time() . '</CreateTime>
                          <MsgType><![CDATA[news]]></MsgType>      
                          <ArticleCount>1</ArticleCount>
                          <Articles>
                            <item>
                              <Title><![CDATA['. '欢迎回来 '. $local_user['nickname'] .']]></Title>
                              <Description><![CDATA[' . $local_user->goods_desc . ']]></Description>
                              <PicUrl><![CDATA[' . 'http://1809liyijie.comcto.com/uploads/goodsImg/20190220/3a7b8dea4c6c14b2aa0990a2a2f0388e.jpg' . ']]></PicUrl>
                              <Url><![CDATA[' . 'http://1809liyijie.comcto.com/Goods/goodsdetail/' . $local_user->goods_id . ']]></Url>
                            </item>
                          </Articles>
                     </xml>';
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
                $Weixin_model=new WxUserModel();
                $res= DB::table('wx_user')->insert($u_info);
                echo '<xml>
                        <ToUserName><![CDATA[' . $openid . ']]></ToUserName>
                        <FromUserName><![CDATA[' . $app . ']]></FromUserName>
                        <CreateTime>' . time() . '</CreateTime>
                        <MsgType><![CDATA[text]]></MsgType>
                         <ArticleCount>1</ArticleCount>
                          <Articles>
                            <item>
                              <Title><![CDATA['. '欢迎关注 '. $u_info['nickname'] .']]]></Title>
                              <Description><![CDATA[ cddcdcd]></Description>
                              <PicUrl><![CDATA[' . 'http://1809liyijie.comcto.com/uploads/goodsImg/20190220/3a7b8dea4c6c14b2aa0990a2a2f0388e.jpg' . ']]></PicUrl>
                              <Url><![CDATA[' . 'http://1809liyijie.comcto.com/goods/goodsdetail'  . ']]></Url>
                            </item>
                          </Articles>
                      </xml>';
            }
        }
    }
    //获取access_token
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
    //获取微信用户
    public function getUserInfo($openid){
        $url='https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$this->getaccesstoken().'&openid='.$openid.'&lang=zh_CN';
        return json_decode(file_get_contents($url),true);
    }
    public function goodsdetail(){
        $res=DB::table('shop_goods')->where(['goods_id'=>36])->first();
        $data=[
            'res'=>$res
        ];
        return view('goods/goodsdetail',$data);
    }

}
