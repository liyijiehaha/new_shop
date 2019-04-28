<?php
namespace App\Http\Controllers\Weixin;
use App\Model\WxUserModel;
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
    public function event(){

        $content = file_get_contents("php://input");
        $time = date('Y-m-d H:i:s');
        $str = $time . $content . "\n";
        file_put_contents("logs/wx_event.log", $str, FILE_APPEND);
        $data = simplexml_load_string($content);
        $openid = $data->FromUserName;   //用户openid
        $app_id = $data->ToUserName;    //公总号id
        $event = $data->Event;
        //扫码关注
        if ($event == 'subscribe') {
            //根据openid判断用户是否已存在
            $localuser = DB::table('wx_user')->where(['openid' => $openid])->first();
            $res = DB::table('shop_goods')->orderBy('create_time', 'desc')->first();
            if ($localuser) {
                //用户关注过
                echo '
                      <xml>
                          <ToUserName><![CDATA[' . $openid . ']]></ToUserName>
                          <FromUserName><![CDATA[' . $app_id . ']]></FromUserName>
                          <CreateTime>' . time() . '</CreateTime>
                          <MsgType><![CDATA[news]]></MsgType>      
                          <ArticleCount>1</ArticleCount>
                          <Articles>
                            <item>
                              <Title><![CDATA['. '欢迎回来 '. $localuser['nickname'] .']]></Title>
                              <Description><![CDATA[' . $res->goods_desc . ']]></Description>
                              <PicUrl><![CDATA[' . 'http://1809liyijie.comcto.com/uploads/goodsImg/20190220/3a7b8dea4c6c14b2aa0990a2a2f0388e.jpg' . ']]></PicUrl>
                              <Url><![CDATA[' . 'http://1809liyijie.comcto.com/Goods/goodsdetail/' . $res->goods_id . ']]></Url>
                            </item>
                          </Articles>
                     </xml>';
            } else {
                //用户关注
                //获取用户信息
                $arr = $this->getaccesstoken($openid);
                //用户信息入户
                $info = [
                    'openid' => $arr['openid'],
                    'nickname' => $arr['nickname'],
                    'sex' => $arr['sex'],
                    'headimgurl' => $arr['headimgurl'],
                ];
                DB::table('tmp_wx_user')->insertGetId($info);
                echo '<xml>
                        <ToUserName><![CDATA[' . $openid . ']]></ToUserName>
                        <FromUserName><![CDATA[' . $app_id . ']]></FromUserName>
                        <CreateTime>' . time() . '</CreateTime>
                        <MsgType><![CDATA[text]]></MsgType>
                         <ArticleCount>1</ArticleCount>
                          <Articles>
                            <item>
                              <Title><![CDATA['. '欢迎关注 '. $info['nickname'] .']]]></Title>
                              <Description><![CDATA[' . $arr->goods_desc . ']]></Description>
                              <PicUrl><![CDATA[' . 'http://1809liyijie.comcto.com/uploads/goodsImg/20190220/3a7b8dea4c6c14b2aa0990a2a2f0388e.jpg' . ']]></PicUrl>
                              <Url><![CDATA[' . 'http://1809liyijie.comcto.com/Goods/goodsdetail/' . $arr->goods_id . ']]></Url>
                            </item>
                          </Articles>
                      </xml>';
            }
        }
//
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
//    public function goodsdetail(){
//        $res=DB::table('shop_goods')->where(['gooods_id'=>36])->first();
//        return
//    }

}
