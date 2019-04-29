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
        $redirect_uri = urlencode('http://1809liyijie.comcto.com/weixin/sign2');  //授权后跳转的地址
        $arr=[
            'button'=>[
                [
                    'type'=>'view',
                    'name'=>'最新福利',
                    'url'=>'https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxb6c09ceb8e8f8117&redirect_uri=http%3A%2F%2F1809liyijie.comcto.com%2Fweixin%2Fgetu&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect'
                ],
                [
                    'type'=>'view',
                    'name'=>'签到',
                    'url'=>'https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxb6c09ceb8e8f8117&redirect_uri='.$redirect_uri.'&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect'
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
    public function sign2(){
        return '123456';
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
}
