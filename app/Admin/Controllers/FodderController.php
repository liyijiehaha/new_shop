<?php

namespace App\Admin\Controllers;
use App\Model\FodderModel;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;

class FodderController extends Controller
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
            return $content
                ->header('素材管理')
                ->description('素材添加')
                ->body(view('admin.weixin.fodder'));
    }
    public function fodderAdd(Request $request){
        $img_name = $this->upload($request,'img');
        if($img_name){
            $url = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$this->getaccesstoken().'&type=image';
            $client = new Client();
           $response = $client->request('post',$url,[
               'multipart' => [
                   [
                      'name' => 'media',
                       'contents' => fopen('../storage/app/'.$img_name, 'r'),
                   ]
               ]
           ]);

           $json =  json_decode($response->getBody(),true);
//            dd($json);
           if(isset($json['media_id'])){
               DB::table('wx_fodder')->insert(['media_id'=>$json['media_id']]);
               echo '成功';
           }else{
               echo '失败';
           }
        }

    }
    public function upload($request,$imgName){
        if ($request->hasFile($imgName) && $request->file($imgName)->isValid()) {
            $photo = $request->file($imgName);
            $path='uploads/';
            $store_result = $photo->store($path.date('Ymd'));
            return $store_result;
        }
        return false;
    }
    public function getaccesstoken(){
        $key = 'sdk_accesstoken';
        $token = Redis::get($key);
        if($token){
            return $token;
        }else{
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSECRET');
            $arr = json_decode(file_get_contents($url),true);
            Redis::set($key,$arr['access_token']);
            Redis::expire($key,3600);
            $token = $arr['access_token'];
            return $token;
        }
    }

}
