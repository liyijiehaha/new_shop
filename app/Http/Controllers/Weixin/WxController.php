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
                              <Url><![CDATA[' . 'http://1809liyijie.comcto.com/goods/goodsdetail/' . $local_user->goods_id . ']]></Url>
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
        } elseif($type=='text'){
          $goods_name=$data->Content;
          $res = DB::table('shop_goods')->where(['goods_name'=>$goods_name])->first();
          if($res){
              echo  '<xml>
                          <ToUserName><![CDATA[' . $openid . ']]></ToUserName>
                          <FromUserName><![CDATA[' . $app . ']]></FromUserName>
                          <CreateTime>' . time() . '</CreateTime>
                          <MsgType><![CDATA[news]]></MsgType>      
                          <ArticleCount>1</ArticleCount>
                          <Articles>
                            <item>
                              <Title><![CDATA['.$res->goods_name.']]></Title>
                              <Description><![CDATA[' . $res->goods_desc . ']]></Description>
                              <PicUrl><![CDATA[' . 'http://1809liyijie.comcto.com/uploads/goodsImg/$res->goods_img' . ']]></PicUrl>
                              <Url><![CDATA[' . 'http://1809liyijie.comcto.com/goods/goodsdetail/' . $res->goods_id . ']]></Url>
                            </item>
                          </Articles>
                     </xml>';
          }else{
              $response=DB::select('SELECT * FROM shop_goods  ORDER BY  RAND() LIMIT 1');
              $response1=json_encode($response);
              $arr=json_decode($response1,true);
              $res=$arr[0];
              echo  '<xml>
                          <ToUserName><![CDATA[' . $openid . ']]></ToUserName>
                          <FromUserName><![CDATA[' . $app . ']]></FromUserName>
                          <CreateTime>' . time() . '</CreateTime>
                          <MsgType><![CDATA[news]]></MsgType>      
                          <ArticleCount>1</ArticleCount>
                          <Articles>
                            <item>
                              <Title><![CDATA['.$res['goods_name'].']]></Title>
                              <Description><![CDATA[' . $res['goods_name']. ']]></Description>
                              <PicUrl><![CDATA[' . 'http://1809liyijie.comcto.com/uploads/goodsImg/'.$res['goods_img'].']]></PicUrl>
                              <Url><![CDATA[' . 'http://1809liyijie.comcto.com/goods/goodsdetail/' . $res['goods_id'] . ']]></Url>
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
    public function goodsdetail($goods_id=0){
        $goods_id = intval($goods_id);
        $res=DB::table('shop_goods')->where(['goods_id'=>$goods_id])->first();
        $data=[
            'res'=>$res,
            'code_url'=>"http://1809liyijie.comcto.com/goods/goodsdetail?goods_id=36"
        ];

        return view('goods/goodsdetail',$data);
    }
    //创建微二级菜单
    public function create_menu(){
        $url='https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$this->getaccesstoken();
        $arr=[
            'button'=>[
                [
                    'type'=>'view',
                    'name'=>'最新福利',
                    'url'=>'https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxb6c09ceb8e8f8117&redirect_uri=http%3A%2F%2F1809liyijie.comcto.com%2Fweixin%2Fgetu&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect'
                ],
            ]
        ];
        $str=json_encode($arr,JSON_UNESCAPED_UNICODE);
        $client=new Client();
        $respons=$client->request('POST',$url,[
            'body'=>$str
        ]);
        $arr=$respons->getBody();
        $ar=json_decode($arr,true);
        if($ar['errcode']>0){
            echo "创建菜单失败";
        }else{
            echo "创建菜单成功";
        }
    }
    /*根据openid消息群发*/
//    public function sendMsg($openid_arr,$content){
//        $msg=[
//            'touser'=>$openid_arr,
//            'msgtype'=>"text",
//            "text"=>[
//                "content"=> $content
//            ]
//        ];
//        $data=json_encode($msg,JSON_UNESCAPED_UNICODE);
//        $url='https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token='.$this->getaccesstoken();
//        $client=new Client();
//        $response=$client->request('post',$url,[
//            'body'=>$data
//        ]);
//        return  $response->getBody();
//    }
//    public function send(){
//        $where=[
//            'status'=>1
//        ];
//        $userlist = DB::table('wx_user')->where($where)->get()->toArray();
//        $openid_arr=array_column($userlist,'openid');
//        $msg="李依杰可耐";
//        $res =$this->sendmsg($openid_arr,$msg);
//        if($res){
//            echo '发送成功';
//        }else{
//            echo '发送失败';
//        }
//    }
    public function getu(){
        $code = $_GET['code'];
        //获取access_token
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.env('WX_APPID').'&secret='.env('WX_APPSECRET').'&code='.$code.'&grant_type=authorization_code';
        $response = json_decode(file_get_contents($url),true);
        echo "<pre>";print_r($response);echo "</pre>";
        $access_token = $response['access_token'];
        $openid= $response['openid'];
        //获取用户信息
        $urll = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
        $response_user = json_decode(file_get_contents($urll),true);
        echo "<pre>";print_r($response_user);echo "</pre>";

        $res =DB::table('wx_sq_user')->where(['openid'=>$response_user['openid']])->first();
        if($res==NULL){
            $aa_info = [
                'openid' => $response_user['openid'],
                'nickname' => $response_user['nickname'],
                'sex' => $response_user['sex'],
                'headimgurl' => $response_user['headimgurl'],
            ];
            DB::table('wx_sq_user')->insertGetId($aa_info);
            echo "<h1>你好</h1>";
        }else{
            echo "<h1>回来啦</h1>";

        }

    }

}
