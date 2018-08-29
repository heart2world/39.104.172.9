<?php
/**
 * 微信授权登录
 */
namespace Portal\Controller;
use Think\Controller;
class WexinController extends Controller {
    const APPID = "wx432a8a193c2e1737";
    const APPSECRET = "fe94be0468da872a521690f8a6569614";
	const TOKEN ="weixin";
    public function get_user()
    {
    	$atype =$_GET['atype'];
        $wechatObj = new \Think\WeChat(self::APPID,self::APPSECRET,self::TOKEN);
        $code=$_GET['code'];
        $openidarr=$wechatObj->get_snsapi_base($code);        
        if($openidarr['scope']=='snsapi_base'){
            dump($openidarr['openid']);
        }
        $access_token=$openidarr['access_token'];
        $openid=$openidarr['openid'];
                
        if($openidarr['scope']=='snsapi_userinfo'){           
            $info=$wechatObj->get_snsapi_userinfo($access_token, $openid); 
        }   
		$minfo =M('users')->where("openid='".$info['openid']."'")->find();
        if($minfo)
		{
			$_SESSION['user'] =$minfo;
			switch ($atype) {
				case '1':
					redirect(U('Portal/index/index'));exit();
					break;
				case '2':
					redirect(U('Portal/index/daihuan'));exit();
					break;
				case '3':
					redirect(U('Portal/index/tequan'));exit();
					break;
				case '6':
					redirect(U('Portal/index/getfhorder'));exit();
					break;
				default:
					# code...
					break;
			}
			
		}else
		{
			$data['openid']=$info['openid'];
			$data['nicename'] =$info['nickname']; 
			$data['user_status']=1;
			$data['avatar'] =$info['headimgurl'];  
			$data['create_time'] =date('Y-m-d H:i:s');
			$user_id=M('users')->add($data);
			$data['id']=$user_id;
			$_SESSION['user'] =$data;
			switch ($atype) {
				case '1':
					redirect(U('Portal/index/index'));exit();
					break;
				case '2':
					redirect(U('Portal/index/daihuan'));exit();
					break;
				case '3':
					redirect(U('Portal/index/tequan'));exit();
					break;
				default:
					# code...
					break;
			}
		}   
    }
}