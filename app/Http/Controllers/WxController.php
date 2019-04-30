<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Http\Controllers\WXBizDataCryptController;
use Illuminate\Support\Str;
class WxController extends Controller{
    public function valid(){
        echo $_GET['echostr'];
    }
    public function wxEvent(){
        $content = file_get_contents("php://input");
        $time = date('Y-m-d H:i:s');
        $str = $time . $content . "\n";
        file_put_contents("logs/wx_event.log",$str,FILE_APPEND);

        $objxml = simplexml_load_string($content);
        $ToUserName = $objxml->ToUserName;
        $FromUserName = $objxml->FromUserName;
        $CreateTime = $objxml->CreateTime;
        $MsgType = $objxml->MsgType;
        $Event = $objxml->Event;
        $EventKey = $objxml->EventKey;
        $Content = $objxml->Content;
        $MediaId = $objxml->MediaId;

        $openid = $FromUserName;
        $accessToken = $this->accessToken();
        $url="https://api.weixin.qq.com/cgi-bin/user/info?access_token=$accessToken&openid=$openid&lang=zh_CN";
        $response = file_get_contents($url);
        $arr = json_decode($response,true);
//        print_r($arr);die;
        $name = $arr['nickname'];
        $openid = $arr['openid'];
        $date = DB::table('user')->where('openid',$openid)->count();
//        print_r($date);die;
        if($Event=='subscribe'){
            if($date){
                $content = "$name,请输入商品名字字样";
                $str = "
                    <xml>
                      <ToUserName><![CDATA[$FromUserName]]></ToUserName>
                      <FromUserName><![CDATA[$ToUserName]]></FromUserName>
                      <CreateTime>$CreateTime</CreateTime>
                      <MsgType><![CDATA[text]]></MsgType>
                      <Content><![CDATA[$content]]></Content>
                    </xml>";
                echo $str;
            }else{
                $data=[
                    'user_name'=>$name,
                    'openid'=>$openid
                ];
                $array = DB::table('user')->insert($data);
                $content = "$name,欢迎回来,请输入商品名字字样";
                $str = "
                    <xml>
                      <ToUserName><![CDATA[$FromUserName]]></ToUserName>
                      <FromUserName><![CDATA[$ToUserName]]></FromUserName>
                      <CreateTime>$CreateTime</CreateTime>
                      <MsgType><![CDATA[text]]></MsgType>
                      <Content><![CDATA[$content]]></Content>
                    </xml>";
                echo $str;
            }
        }
        if($MsgType=='image'){
            $url="https://api.weixin.qq.com/cgi-bin/media/get?access_token=$accessToken&media_id=$MediaId";
            $response = file_get_contents($url);
            $file_name = rtrim(substr("QAZWSXEDCRFVTGBYHNUJMIKMOLqwertyuiopasdfghjklzxcvbnmP", -10), '"').".jpg";//取文件名后10位
            $img_name =  substr(md5(time() . mt_rand()), 10, 8) . '_' . $file_name;//最后的文件名;
            file_put_contents("/tmp/$img_name",$response,FILE_APPEND);
            $data = [
                'openid'=>$openid,
                'image_url'=>"/tmp/".$img_name
            ];
            $array = DB::table('sucai')->insert($data);
        }else if($Content=="彪马"){
                $good = DB::table('shop_goods')->where('goods_up',1)->orderBy('create_time','desc')->first();
                $good_name = $good->goods_name;
                $title = "哦呦";
                $picurl = "http://1809wanghaotian.comcto.com/goodsimg/$good->goods_img";
                $url = "http://1809wanghaotian.comcto.com/goodDetail";
                $str = "<xml>
                          <ToUserName><![CDATA[$FromUserName]]></ToUserName>
                          <FromUserName><![CDATA[$ToUserName]]></FromUserName>
                          <CreateTime>$CreateTime</CreateTime>
                          <MsgType><![CDATA[news]]></MsgType>
                          <ArticleCount>1</ArticleCount>
                          <Articles>
                            <item>
                              <Title><![CDATA[$title]]></Title>
                              <Description><![CDATA[$good_name]]></Description>
                              <PicUrl><![CDATA[$picurl]]></PicUrl>
                              <Url><![CDATA[$url]]></Url>
                            </item>
                          </Articles>
                        </xml>";
                echo $str;
            }else{
                $data = [
                    'openid'=>$openid,
                    'content'=>$Content
                ];
                $array = DB::table('sucai')->insert($data);
            }
        }else if($MsgType=='voice'){
            $url="https://api.weixin.qq.com/cgi-bin/media/get?access_token=$accessToken&media_id=$MediaId";
            $response = file_get_contents($url);
            $file_name = rtrim(substr("QAZWSXEDCRFVTGBYHNUJMIKMOLqwertyuiopasdfghjklzxcvbnmP", -10), '"').".mp3";//取文件名后10位
            $voice_name =  substr(md5(time() . mt_rand()), 10, 8) . '_' . $file_name;//最后的文件名;
            file_put_contents("/tmp/$voice_name",$response,FILE_APPEND);
            $data = [
                'openid'=>$openid,
                'voice_url'=>"/tmp/".$voice_name
            ];
            $array = DB::table('sucai')->insert($data);
        }
    }
    //获取accessToken
    public function accessToken(){
        $key = 'wx_access_token';
        $accessToken = Redis::get($key);
        if($accessToken){

        }else{
            $url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env('WX_APPID').'&secret='.env('WX_SECRET').'';
            $response = file_get_contents($url);
            $arr = json_decode($response,true);
            $access = $arr['access_token'];
//            print_r($arr);die;
            Redis::set($key,$access);
            Redis::expire($key,3600);
           $accessToken = $arr['access_token'];
//        print_r($accessToken);
        }
        return $accessToken;
    }
    //菜单
    public function menu(){
        $accessToken = $this->accessToken();
        $url="https://api.weixin.qq.com/cgi-bin/menu/create?access_token=$accessToken";
        $arr = array(
            "button"=> array(
                array(
                    'name'=>"最炫",
                    "type"=>"click",
                    "key"=>"aaaaa",
                    "sub_button"=>array(
                        array(
                            "type"=>"click",
                            "name"=>"王有才",
                            "key"=>"iii"
                        ),
                        array(
                            "type"=>"click",
                            "name"=>"罗猪猪",
                            "key"=>"iii"
                        ),
                    ),
                ),
                array(
                    'name'=>"哦哦",
                    "type"=>"click",
                    "key"=>"bbb",
                    "sub_button"=>array(
                        array(
                            "type"=>"click",
                            "name"=>"商家",
                            "key"=>"iii"
                        ),
                        array(
                            "type"=>"view",
                            "name"=>"百度",
                            "url"=>"https://www.baidu.com/"
                        ),
                    ),
                ),
                array(
                    'name'=>"扫我",
                    "type"=>"click",
                    "key"=>"bbb",
                    "sub_button"=>array(
                        array(
                            "type"=>"scancode_waitmsg",
                            "name"=>"微信扫码",
                            "key"=>"iii"
                        ),
                    ),
                ),
            ),
        );
        $strjson = json_encode($arr,JSON_UNESCAPED_UNICODE);
        $clinet = new Client();
        $response = $clinet ->request("POST",$url,[
            'body'=>$strjson
        ]);
        $res_str = $response->getBody();
        echo $res_str;
    }
    public $weixin_unifiedorder_url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';   // 统一下单接口
    public $notify_url = 'http://1809wanghaotian.comcto.com/notify';      // 支付回调
    /**
     * 用户授权
     */
    public function give(){
        $scope = "snsapi_userinfo";
        $url = urlencode("http://1809lancong.comcto.com/code");
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.env('WX_APPID').'&redirect_uri='.$url.'&response_type=code&scope='.$scope.'&state=STATE#wechat_redirect';
        return view('weixin.give',['url'=>$url]);
    }
}
?>