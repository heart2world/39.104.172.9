<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 业余爱好者 <649180397@qq.com>
// +----------------------------------------------------------------------
namespace Portal\Controller;
use Common\Controller\HomebaseController; 
class IndexController extends HomebaseController {
    const APPID = "wx432a8a193c2e1737";
    const APPSECRET = "fe94be0468da872a521690f8a6569614";
    //首页产品列表
	public function index() {
    	if(!$_SESSION['user']['openid'])
		{	
		  $str=urlencode('http://'.$_SERVER['HTTP_HOST'].'/index.php?g=Portal&m=Wexin&a=get_user&atype=1');
		  $url='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.self::APPID.'&redirect_uri='.$str.'&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
		  header("location:$url");
		}
		// 消费项目
		$nowtime =date('H:i');
		$mlist = M('cashoptions')->where("starttime <'$nowtime' and endtime >'$nowtime'")->select();
		$this->assign('mlist',$mlist);
		
		$this->display(':index');
    }
    // 套现产品提交订单
    public function detail()
    {
    	$id=I('id','','intval');
    	$this->check_login();
    	$info =M('cashoptions')->find($id);
    	if($info)
    	{
            $uinfo =M('userlog')->where("userid='".$_SESSION['user']['id']."' and atype=0")->order('createtime desc')->find();
            if(strpos($info['nobank'],$uinfo['bankname']) !== false){
                $uinfo['banksn'] = '';
                $uinfo['bankcard'] = '';
            }           
            $this->assign('uinfo',$uinfo);
    		// 项目产品列表
    		$plist =M('cashoptions')->field('id,name')->select();
    		$this->assign('plist',$plist);
    		// 银行列表
    		$bank =M('bank')->select();
    		$this->assign('bank',$bank);
    		$this->assign('info',$info);
    		$this->display(':detail');
    	}else
    	{
    		redirect(U('Portal/index/index'));
    	}
    }
    // 套现记录
    public function orderlist()
    {
    	$this->check_login();

    	$olist=M('cashorders')->where("userid='".$_SESSION['user']['id']."' and orderstatus >0")->order('id desc')->select();
		$orderlist=array();
    	foreach ($olist as $key => $value) {
    		$orderlist[$key]['tit'] =M('cashoptions')->where("id='".$value['optionid']."'")->getField('name');
    		$orderlist[$key]['times']  = date('Y-m-d H:i',strtotime($value['createtime']));
    		$orderlist[$key]['payNum'] =$value['ordersn'];
    		$orderlist[$key]['cardid'] =$value['bankcard'];
    		$orderlist[$key]['money']  =$value['coin'];
    		$orderlist[$key]['state']  =$value['orderstatus'];
    		$orderlist[$key]['reason'] =$value['reason'];
    	}
    	
    	$this->assign('orderlist', json_encode($orderlist));
    	$this->display(':orderlist');
    }
    // 代还
    public function daihuan()
    {
    	if(!$_SESSION['user']['openid'])
		{	
		  $str=urlencode('http://'.$_SERVER['HTTP_HOST'].'/index.php?g=Portal&m=Wexin&a=get_user&atype=2');
		  $url='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.self::APPID.'&redirect_uri='.$str.'&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
		  header("location:$url");
		}
		$info =M('uservipcode')->where("userid='".$_SESSION['user']['id']."' and to_days(endtime) = to_days(now())")->find();
		if(!empty($info))
		{
			$this->assign('isvip',1);
		}else{
			$this->assign('isvip',0);
		}
        $tdlist =M('passageway')->select();
        $this->assign('tdlist',$tdlist);
    	$this->display(':daihuan');
    }

    // 代还下单界面
    public function nextorder()
    {
        $tdid =I('tdid','','intval');
        if($tdid == 0 || !$_SESSION['user']['id'])
        {
            redirect(U('Portal/index/daihuan'));
        }
        if($tdid=='1')
        {
            // 代还接口注册
            $userinfo =M('users')->where("id='".$_SESSION['user']['id']."'")->find();
            if($userinfo['fhway'] == '2')
            {
                redirect(U('Portal/user/confirmorder',array('tdid'=>1)));
            }else
            {
                redirect(U('Portal/user/register',array('tdid'=>1)));
            }
        }
        else if($tdid==2)
        {
        
            $uinfo =M('userlog')->where("userid='".$_SESSION['user']['id']."' and atype=1")->order('createtime desc')->find(); 
			
            $this->assign('uinfo',$uinfo);
            $this->assign('tdid',$tdid);
            // 银行列表
            $bank =M('bank')->select();
            $this->assign('bank',$bank);
            $this->display(':nextorder');
        }
        
    }
    // 通道2下单
    public function createfankuai()
    {
        if(IS_POST)
        {
            $pdata =I('post.');           
            $pdata['ordersn'] = sp_get_order_sn();
            // 选择产品的费率
            $info =M('passageway')->where("id='".$pdata['tdid']."'")->find();
            $pdata['bankname'] =M('bank')->where("banksn='".$pdata['banksn']."'")->getField('bankname');
            $pdata['persont'] =$info['persont'];
            $pdata['coin'] =$pdata['money'];
            $pdata['servicecoin'] =round(round($pdata['coin']*$pdata['persont']/100,2)+$info['addpersont'],2);
            $pdata['userid'] =$_SESSION['user']['id'];
            M('fanhuanorders')->startTrans();
            $orderid=M('fanhuanorders')->add($pdata);
            if($orderid)
            {
                $liastid=M('userlog')->add(array('userid'=>$pdata['userid'],'coin'=>$pdata['coin'],'bankno'=>$pdata['bankno'],'cvn2'=>$pdata['cvn2'],'cardtime'=>$pdata['cardtime'],'mobile'=>$pdata['mobile'],'banksn'=>$pdata['banksn'],'bankname'=>$pdata['bankname'],'bankcard'=>$pdata['bankcard'],'username'=>$pdata['username'],'usercard'=>$pdata['usercard'],'atype'=>1));
                if(!$liastid)
                {
                    M('fanhuanorders')->rollback();
                    $this->ajaxReturn(array('status'=>0,'msg'=>'提交失败，请重试'));
                }else
                {                   
                    M('userlog')->where("id!=$liastid and userid='".$pdata['userid']."' and atype=1")->delete();
                }
                $orderinfo =M('fanhuanorders')->find($orderid);
                $url ='http://cash.palmf.cn/api/cash/apply';
                $coin =$pdata['coin']*100;
                $params =array(
                    'service'=>'service.api.cash.apply',
                    'appid'=>'0000000008',
                    'mchntOrderNo'=>$pdata['ordersn'],
                    'powerId' =>'9000000001',
                    'subject'=>urlencode('代还'),
                    'amount' =>(string)$coin,
                    'cardId'=>$pdata['bankno'],
                    'mobileNo'=>$pdata['mobile'],
                    'acctName'=>urlencode($pdata['username']),
                    'acctIdcard'=>$pdata['usercard'],
                    'bankNum' =>$pdata['banksn'],
                    'acctCardno'=>$pdata['bankcard'],
                    'cvn2'=>$pdata['cvn2'],
                    'expDt' =>$pdata['cardtime'],
                    'busType' =>'WKPAY',
                    'tradeRate'=>$orderinfo['persont'],
                    'drawFee' =>$orderinfo['servicecoin'],
                    'notifyUrl'=> urlencode('http://'.$_SERVER['HTTP_HOST'].'/Portal/index/response2'),
                    'returnUrl'=>urlencode('http://'.$_SERVER['HTTP_HOST'].'/Portal/index/orderlist2'),
                    'version'=>'01'
                    );
                
                $params['signature'] =$this->getSign2($params,'3a16cf177f04e031a938b3ed10b3d7a3');
                ksort($params);
                $params =json_encode($params);               
                $res =$this->_request($url,true,'POST',$params);
                $codestr =json_decode($res,true);
                if($codestr['code'] == '10000')
                {
                    M('fanhuanorders')->commit();
                     // 完善用户信息
                    $userinfo=M('users')->where("id='".$pdata['userid']."'")->find();
                    if(empty($userinfo['mobile']))
                    {
                        M('users')->where("id='".$pdata['userid']."'")->save(array('mobile'=>$pdata['mobile'],'cardno'=>$pdata['usercard'],'truename'=>$pdata['username']));
                    } 
                    M('paylog')->add(array('mchntOrderNo'=>$codestr['mchntOrderNo'],'orderNo'=>$codestr['orderNo'],'paySt'=>$codestr['paySt'],'createDt'=>$codestr['createDt']));
                    $this->ajaxReturn(array('status'=>1,'url'=>$codestr['payInfo']));
                }else
                {
                    M('fanhuanorders')->rollback();
                    $this->ajaxReturn(array('status'=>0,'msg'=>$codestr['msg']));
                }
            }
        }
    }
    // 返还接口异步通知地址
    public function response2()
    {        
        $pdata =file_get_contents('php://input');
        $codestr =json_decode($pdata,true);
        if($codestr['mchntOrderNo'])
        {
            $info=M('fanhuanorders')->where("ordersn='".$codestr['fanhuanorders']."'")->find();
            if($info['orderstatus'] == 0)
            {
                M('fanhuanorders')->where("ordersn='".$codestr['mchntOrderNo']."'")->setField('orderstatus',1);
            }
        }
    }
    // 返还支付成功后跳转页面
    public function orderlist2()
    {
        $pdata =I('get.');
        if($pdata['mchntOrderNo'] && $pdata['orderNo'])
        {
            $this->assign('paySt',   $pdata['paySt']);
           $this->display(':orderlist2');
        }else
        {
            redirect(U('Portal/index/daihuan'));
        }        
    }
    // 返还订单列表
    public function getfhorder()
    {
        if(!$_SESSION['user']['openid'])
        {   
          $str=urlencode('http://'.$_SERVER['HTTP_HOST'].'/index.php?g=Portal&m=Wexin&a=get_user&atype=6');
          $url='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.self::APPID.'&redirect_uri='.$str.'&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
          header("location:$url");
        }
        $list=M('fanhuanorders')->where("userid='".$_SESSION['user']['id']."'")->select();
        $orderlist=array();
        foreach ($list as $key => $val) {
            $orderlist[$key]['payNum'] =$val['bankno'];
            $orderlist[$key]['cardid'] =$val['bankcard'];
            $orderlist[$key]['money'] =$val['coin'];
            $orderlist[$key]['reason'] =$val['reason'];
            $orderlist[$key]['state'] =$val['orderstatus'];
            $orderlist[$key]['ordersn']=$val['ordersn'];
        }
        $this->assign('orderlist', json_encode($orderlist));
        $this->display(':getfhorder');
    }
   
   
    // 特权
    public function tequan()
    {
    	if(!$_SESSION['user']['openid'])
		{	
		  $str=urlencode('http://'.$_SERVER['HTTP_HOST'].'/index.php?g=Portal&m=Wexin&a=get_user&atype=3');
		  $url='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.self::APPID.'&redirect_uri='.$str.'&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
		  header("location:$url");
		}else
		{
			$info =M('uservipcode')->where("userid='".$_SESSION['user']['id']."' and to_days(endtime) = to_days(now())")->find();
			if($info)
			{
				redirect(U('Portal/index/myvip'));
			}else
			{
				redirect(U('Portal/index/bangdingvip'));
			}
		}
    }
     // 未成功vip
    public function getvip()
    {
        $this->display(':getvip');
    }
     //  绑定vip
    public function bangdingvip()
    {
        $this->display(':bangdingvip');
    }
    // 我的vip码
    public function myvip()
    {
    	$this->check_login();
    	$info=M('uservipcode')->where("userid='".$_SESSION['user']['id']."' and to_days(endtime) = to_days(now())")->find();
        if(empty($info))
        {
            redirect(U('Portal/index/bangdingvip'));
        }
        $info['vipclass'] =M('vipcode')->where("id='".$info['parentid']."'")->getField('atype');
    	$nowh =date('H');
    	$qian = round($nowh/24,2)*100;
    	$hou  = 100-$qian;
    	$this->assign('qian', $qian);
    	$this->assign('hou', $hou);
    	$this->assign('info', $info);
    	$this->display(':myvip');
    }
    // 绑定vip
    public function dobdvip()
    {
    	if(IS_POST)
    	{
    		$code =I('code','','trim');
    		$info=M('vipcode')->where("code='$code' and to_days(createtime) = to_days(now())")->find();
    		if($info)
    		{
				if($info['status'] == 1)
				{
					$this->ajaxReturn(array('status'=>0,'msg'=>'vip码已被使用'));
				}
    			$data['userid'] =$_SESSION['user']['id'];
    			$data['code'] =$code;
                $data['parentid'] =$info['id'];
    			$data['endtime'] =date('Y-m-d')." 23:59:59";
    			$data['createtime'] =time();
    			M('uservipcode')->add($data);
				M('vipcode')->where("id='".$info['id']."'")->setField('status',1);
    			$this->ajaxReturn(array('status'=>1,'msg'=>'绑定成功','url'=>U('Portal/index/myvip')));
    		}
    		else
    		{
    			$this->ajaxReturn(array('status'=>0,'msg'=>'vip码不存在'));
    		}
    	}    	
    }    
    // 套现下单，跳转支付
    public function createorders2()
    {
        if(IS_POST)
        {
            $pdata =I('post.');
            $pdata['ordersn'] = sp_get_order_sn();
            $pdata['bankname'] =M('bank')->where("banksn='".$pdata['banksn']."'")->getField('bankname');
            // 选择产品的费率
            $info =M('cashoptions')->where("id='".$pdata['optionid']."'")->find();
            // 用户的当天等级
            $pinfo=D()->field('v.atype,uv.code')->table(C('DB_PREFIX')."uservipcode uv,".C('DB_PREFIX')."vipcode v")->where("v.code=uv.code and uv.userid='".$_SESSION['user']['id']."' and  to_days(uv.endtime) = to_days(now())")->find();
            if(empty($pinfo))
            {
                $pdata['persont'] =$info['persont'];
            }else if($pinfo['atype'] ==1)
            {
                $pdata['persont'] =$info['vipper1'];
            }else
            {
                $pdata['persont'] =$info['vipper2'];
            }
            $pdata['servicecoin'] = round(($pdata['coin']*$pdata['persont']/100+1),2);
            $pdata['userid'] =$_SESSION['user']['id'];
            $pdata['creditcard'] =$pdata['bankno'];
            M('cashorders')->startTrans();
            $orderid=M('cashorders')->add($pdata);
            if($orderid)
            {
                // 完善用户信息
                $userinfo=M('users')->where("id='".$pdata['userid']."'")->find();
                if(empty($userinfo['mobile']))
                {
                    M('users')->where("id='".$pdata['userid']."'")->save(array('mobile'=>$pdata['mobile'],'cardno'=>$pdata['usercard'],'truename'=>$pdata['username']));
                }                
                $liastid=M('userlog')->add(array('userid'=>$pdata['userid'],'coin'=>$pdata['coin'],'bankno'=>$pdata['bankno'],'cvn2'=>$pdata['cvn2'],'cardtime'=>$pdata['cardtime'],'mobile'=>$pdata['mobile'],'banksn'=>$pdata['banksn'],'bankname'=>$pdata['bankname'],'bankcard'=>$pdata['bankcard'],'username'=>$pdata['username'],'usercard'=>$pdata['usercard']));
                if(!$liastid)
                {
                    M('cashorders')->rollback();
                }else
                {
                    M('cashorders')->commit();
                    M('userlog')->where("id!=$liastid and userid='".$pdata['userid']."' and atype=0")->delete();
                }
                $orderinfo =M('cashorders')->where("id='$orderid'")->find();
                // 组装加密数组
                $poststr =array(
                    'version' => '01',
                    'cust_id' => $info['cust_id'], // 测试商户号
                    'ord_id' =>$pdata['ordersn'],  // 订单号
                    'sub_mer_id' => $pdata['ordersn'], // 子商户号
                    'subject' =>urlencode($info['name']), //商品名称
                    'gate_id' =>'1010',
                    'trans_amt' =>$orderinfo['coin'],
                    'card_id'  =>$pdata['bankno'],
                    'mobile_no'=>$pdata['mobile'],
                    'acct_name'=>$pdata['username'],
                    'acct_idcard'=>$pdata['usercard'],
                    'bank_num' =>$pdata['banksn'],
                    'acct_cardno'=>$pdata['bankcard'],
                    'trade_rate'=>number_format($pdata['persont']*10,2),
                    'draw_fee'=>$orderinfo['servicecoin'],                    
                    'ret_url' =>urlencode("http://".$_SERVER['HTTP_HOST'].'/'.U('Portal/index/orderlist')),
                    'bg_ret_url'=>urlencode("http://".$_SERVER['HTTP_HOST'].'/'.U('Portal/index/response')),
                    'mer_priv'=>'',
                    'extension'=>''
                    );
                $check_value =$this->getSign($poststr,$info['mac_key']);
                $poststr['check_value'] =$check_value;
				file_put_contents('a256.txt',var_export($poststr,true));
                $url ="http://pay.mastershop.cn:9876/netrecv/merchant/unionPay";
                $result=$this->create_auto_html($poststr,$url);
                echo $result;
            }
        }
    }
    // 套现异步通知
    public function response()
    {
        if(IS_POST)
        {
            $pdata =I('post.');   
            file_put_contents('apay.txt', var_export($pdata,true),FILE_APPEND);
            $datastr['notify_id'] =$pdata['notify_id'];  
            $cust_id=D()->table(C('DB_PREFIX')."cashoptions c,".C('DB_PREFIX')."cashorders o")->where("c.id=o.optionid and o.id='".$pdata['ord_id']."'")->getField('c.cust_id');
            $datastr['cust_id'] =$cust_id;  
            $url ="http://pay.dingshuapay.com:9876/netrecv/merchant/orderVerify";
            $result =$this->_request($url,true,'POST',$datastr);
            if($result == true)
            {
                switch ($pdata['resp_code']) {
                    case '000':// 套现成功                       
                        $data['orderstatus'] =1;
                        $data['reason'] = urldecode($pdata['resp_desc']);
                        M('cashorders')->where("ordersn='".$pdata['ord_id']."'")->save($data);
                        break;
                    case '100':// 重复成功
                        $data['orderstatus'] =1;
                        $data['reason'] = urldecode($pdata['resp_desc']);
                        M('cashorders')->where("ordersn='".$pdata['ord_id']."'")->save($data);
                        break;
                    case '102':// 交易失败
                        $data['orderstatus'] =2;
                        $data['reason'] = urldecode($pdata['resp_desc']);
                        M('cashorders')->where("ordersn='".$pdata['ord_id']."'")->save($data);
                        break;
                    default:
                        $data['orderstatus'] =3;
                        $data['reason'] = urldecode($pdata['resp_desc']);
                        M('cashorders')->where("ordersn='".$pdata['ord_id']."'")->save($data);
                        break;
                }
            } 
        }
    }
   
}


