<?php


namespace Think;

/* 
 微信公众平台
 */
class WeChat{
    private $_appid; //
    private $_appsecret;
    private $_token;
    //构造函数 
    public function __construct($_appid,$_appsecret,$_token) {
        $this->_appid=$_appid;
        $this->_appsecret=$_appsecret;
        $this->_token=$_token;
    }
    
    public function _request($curl,$https=true,$method='GET',$data=null){
        //
        $ch=  curl_init();
        curl_setopt($ch,CURLOPT_URL,$curl);
        curl_setopt($ch,CURLOPT_HEADER,false);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        if($https){
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,TRUE);
        }
        if($method=='POST'){
           
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        }
        $content=  curl_exec($ch);
        curl_close($ch);
        return $content;
    }
    public function get_accesstoken(){//获取微信公众平台的accesstoken的方法
        $file='./accesstoken';
        if(file_exists($file)){//判断这个文件是否存在
            $content=  file_get_contents($file);//获取文件信息
            $content=  json_decode($content);//解码
            if(time()- filemtime($file)<$content->expires_in){
                return $content->access_token;
            }
        }
        $curl='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->_appid.'&secret='.$this->_appsecret;
        $content=  $this->_request($curl);
    
        $content=json_decode($content);
        return $content->access_token;
    }
    public function get_ticket($sceneid,$type='temp',$expire_seconds='604800',$access_token){//生成ticket（票据）
       
        if($type=='temp'){//如果是临时的
            $data = '{"expire_seconds": %s, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": %s}}}';
            $data = sprintf($data,$expire_seconds,$sceneid);
            //return $data;
        }else{//永久的
            $data = '{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": %s}}}';
            
             $data = sprintf($data,$sceneid);
        }
        $curl='https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$access_token;
       
        $content = $this->_request($curl,true,'POST',$data);
        $content=  json_decode($content,true);
        
        return $content['url'];
    }
    public function get_qrcode($sceneid,$type='temp',$expire_seconds='604800',$access_token){//用生成的票据生成二维码
        $ticket=$this->get_ticket($sceneid,$type,$expire_seconds,$access_token);
        $curl='https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.urlencode($ticket);
     
        $content=  $this->_request($curl);
        return $content;
    }
    
  
    public function get_userlist(){//返回用户列表（用户的openid）
        $curl='https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$this->get_accesstoken().'&next_openid=';
        $content=  $this->_request($curl);
        $content=  json_decode($content);
        return $content->data->openid;
    }
    public function get_user($openid){//返回用户的基本信息,数组形式
        $curl='https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$this->get_accesstoken().'&openid='.$openid.'&lang=zh_CN';
        $content=  $this->_request($curl);
        $content=  json_decode($content,1);
        return $content;
    }
    public function get_userAll(){//批量返回用户信息
        $curl='https://api.weixin.qq.com/cgi-bin/user/info/batchget?access_token='.$this->get_accesstoken();
        $data='{"user_list": [{"openid": "oAUwCwavaZAZr4AP4tUuTVVx-7zE","lang": "zh-CN"},{"openid": "oAUwCwX9sNJXdlHG0WB9tCVZfLz8", "lang": "zh-CN" }]}';
        $data=  urlencode($data);
        $content=  $this->_request($curl,true,'POST',$data);
        return $content;
    }
    public function add_menu($menu){//添加自定义菜单
        $curl='https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$this->get_accesstoken();
        //$curl='http://www.baidu.com';
        $content= $this->_request($curl,true,'POST',$menu);
        return $content;
        
    }
    public function add_sucai($type,$file){//添加素材
        $curl='https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$this->get_accesstoken().'&type='.$type;
        $data['type']=$type;
        $data['media']='@'.$file;
        file_put_contents('./add_sucai',$this->_request($curl,true,'POST',$data));
        
    }
    public function get_signpackage() {//获取参数
        $jsapiTicket = $this->get_jsapi_ticket();

        // 注意 URL 一定要动态获取，不能 hardcode.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
          "appId"     => $this->_appid,
          "nonceStr"  => $nonceStr,
          "timestamp" => $timestamp,
          "url"       => $url,
          "signature" => $signature,
          "rawString" => $string
        );
        return $signPackage; 
    }

    public function createNonceStr($length = 16) {//生成随机字符串
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
          $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    public function get_jsapi_ticket() {//获得jsapi_ticket，
        // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
        $data = json_decode(file_get_contents("./jsapi_ticket.json"));
        if ($data->expire_time < time()) {
          $accessToken = $this->get_accesstoken();
          // 如果是企业号用以下 URL 获取 ticket
          // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
          $curl = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
          $res = json_decode($this->_request($curl));
          $ticket = $res->ticket;
          if ($ticket) {
            $data->expire_time = time() + 7000;
            $data->jsapi_ticket = $ticket;
            $fp = fopen("jsapi_ticket.json", "w");
            fwrite($fp, json_encode($data));
            fclose($fp);
          }
        } else {
          $ticket = $data->jsapi_ticket;
        }

        return $ticket;
    }
    public function get_snsapi_base($code){//通过code换取网页授权参数，不弹出授权页面，直接跳转，只能获取用户openid
        $curl="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->_appid."&secret=".$this->_appsecret."&code=".$code."&grant_type=authorization_code";
        $openidarr=  json_decode($this->_request($curl),true);
        return $openidarr;
    }
    public function get_snsapi_userinfo($access_token,$openid){//弹出授权页面，可通过openid拿到昵称、性别、所在地。并且，即使在未关注的情况下，只要用户授权，也能获取其信息
        $curl="https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
        $userinfoarr=  json_decode($this->_request($curl),true);
        return $userinfoarr;
    }
    public function get_userapi_userinfo($access_token,$openid){//弹出授权页面，可通过openid拿到昵称、性别、所在地。并且，即使在未关注的情况下，只要用户授权，也能获取其信息
       $curl="https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
        $userinfoarr=  json_decode($this->_request($curl),true);
        return $userinfoarr;
    }
    
}
