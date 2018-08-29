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
class UserController extends HomebaseController {
	// 用户注册认证界面
	public function register()
	{
		$this->assign('tdid',$_GET['tdid']);
		$this->display();
	}
	// 通道一预约下单
	public function confirmorder()
	{
		$this->assign('tdid',$_GET['tdid']);
		$info =M('passageway')->find($_GET['tdid']);
		$this->assign('persont',  $info['persont']);
		$this->assign('nowday', date('Y-m-d'));
		$this->display();
	}
	// 创建订单
	public function topay()
	{
		$outTradeNo =I('outTradeNo','','trim');
		$firstAmt =I('firstAmt','','trim');
		$orderinfo =M('fanhuanorders')->where("ordersn='$outTradeNo'")->find();
		if($orderinfo)
		{
			$this->assign('ordersn',$outTradeNo);
			$this->assign('firstAmt',$firstAmt);
		}else
		{
			redirect(U('Portal/index/daihuan'));
		}
		$this->display();
	}
	// 查询子订单接口
	public function checkorderinfo()
	{
		$ordersn = I('ordersn','','trim');
		$url ="https://www.51mayun.com/mch/pay/repay/queryAppointOrder.do";

		$userinfo =M('users')->where("id='".$_SESSION['user']['id']."'")->find();
		$params=array(
			'mchCode'=>'1526456081515',
			'outTradeNo'=>$ordersn,
			'phoneNo'=>$userinfo['mobile']
			);
		$params['sign'] = $this->getSign2($params,'b88923c4cde7db92fa99505fe2ee66f8');
		$res =$this->_request($url,true,'POST',$params);
		$codestr =json_decode($res,true);
		print_r($codestr);
	}
	// 创建订单操作
	public function docreateorders()
	{
		if(IS_POST)
		{
			$pdata =I('post.');
			$url="https://www.51mayun.com/mch/pay/repay/createAppointOrder.do";
			if(empty($_SESSION['user']['id']))
			{
				$this->ajaxReturn(array('status'=>0,'msg'=>'请重新选择支付通道'));
			}
			$userinfo =M('users')->where("id='".$_SESSION['user']['id']."'")->find();
			$params=array(
					'mchCode'=>'1526456081515',
					'outTradeNo'=>$pdata['ordersn'],
					'phoneCode'=>$pdata['yzcode'],
					'phoneNo'=>$userinfo['mobile']
				);
			$params['sign'] =$this->getSign2($params,'b88923c4cde7db92fa99505fe2ee66f8');
			
			$res =$this->_request($url,true,'POST',$params);
			
			$codestr =json_decode($res,true);
			if($codestr['status'] ==1)
			{
				M('fanhuanorders')->where("ordersn='".$pdata['ordersn']."'")->setField('orderstatus',1);
				$this->ajaxReturn(array('status'=>1,'msg'=>'创建成功'));
			}else
			{
				$this->ajaxReturn(array('status'=>0,'msg'=>$codestr['message']));
			}
		}
	}
	// 创建预约订单操作
	public function  createorders()
	{
		if(IS_POST)
		{
			$pdata =I('post.');
			$info =M('passageway')->find($pdata['tdid']);			
			$userinfo =M('users')->find($_SESSION['user']['id']);
			if(empty($userinfo))
			{
				$this->ajaxReturn(array('status'=>0,'msg'=>'请重新选择支付通道'));
			}
			$ordersn =sp_get_order_sn();
			$pdata['ordersn'] =$ordersn;
			$pdata['userid'] =$_SESSION['user']['id'];
			$pdata['optionid'] =1;
			$pdata['coin'] =$pdata['money'];
			$pdata['mobile'] =$userinfo['creditmobile'];
			$pdata['username'] =$userinfo['truename'];
			$pdata['usercard'] =$userinfo['cardno'];
			$pdata['cvn2'] =$userinfo['cvn2'];
			$pdata['cardtime'] =$userinfo['cardtime'];
			$pdata['bankno'] =$userinfo['creditcardno'];
			$pdata['persont'] =$info['persont'];
			
			if(empty($pdata['endDate']))
			{
				$this->ajaxReturn(array('status'=>0,'msg'=>'请选择预约时间'));
			}
			M('fanhuanorders')->startTrans();
			$lastid=M('fanhuanorders')->add($pdata);
			
			if($lastid)
			{
				$coin =$pdata['coin']*100;
				$days =$this->diffBetweenTwoDays($pdata['endDate'],date('Y-m-d')) +1;
				$repayRate =$pdata['persont']*100;
				$url='https://www.51mayun.com/mch/pay/repay/appiontOrder.do';
				$params=array(
					'mchCode'=>'1526456081515',
					'outTradeNo'=>$ordersn,
					'creditCardNo'=>$userinfo['creditcardno'],
					'phoneNo'=>$userinfo['mobile'],
					'mobile'=>$userinfo['creditmobile'],
					'repayFee' =>(string)$coin,					
					'endDate'=>$pdata['endDate'],
					'repayRate' =>(string)$repayRate,
					'repaymentDate'=>$pdata['endDate'],
					'endDay'=>(string)$days,
					'isCushionCapital'=>'2',
					'creditCardName'=>$userinfo['creditcardname'],
					'cvv2'  =>$userinfo['cvn2'],
					'expiredDate'=>$userinfo['cardtime']
				);
				
				// 先调用信用卡预留金额
				$aurl ="https://www.51mayun.com/mch/pay/repay/queryLimit.do";
				$paramsstr=array(
					'mchCode'=>'1526456081515',
					'phoneNo'=>$userinfo['mobile'],
					'creditCardNo'=>$userinfo['creditcardno'],
					'repayFee' =>(string)$coin,					
					'endDate'=>$pdata['endDate'],
					'endDay'=>(string)$days,
					'isCushionCapital'=>'2'
				);
				$paramsstr['sign'] =$this->getSign2($paramsstr,'b88923c4cde7db92fa99505fe2ee66f8');
				
				$restr =$this->_request($aurl,true,'POST',$paramsstr);
				$codestr2 =json_decode($restr,true);
				
				if($codestr2['status'] ==0)
				{
					M('fanhuanorders')->rollback();
					$this->ajaxReturn(array('status'=>0,'msg'=>$codestr['message']));
				}else
				{
					$firstAmt =$codestr2['data']['firstAmt'];
				}
				$params['sign'] =$this->getSign2($params,'b88923c4cde7db92fa99505fe2ee66f8');
				$res =$this->_request($url,true,'POST',$params);
				$codestr =json_decode($res,true);
				
				if($codestr['status'] == 1)
				{
					M('fanhuanorders')->commit();
					$this->ajaxReturn(array('status'=>1,'url'=>U('Portal/User/topay',array('outTradeNo'=>$ordersn,'firstAmt'=>$firstAmt))));
				}else
				{
					M('fanhuanorders')->rollback();
					$this->ajaxReturn(array('status'=>0,'msg'=>$codestr['message']));
				}
			}			
		}
	}
	// 认证用户+绑卡
	public function doregister()
	{
		if(IS_POST)
		{
			$pdata= I('post.');
			$url="https://www.51mayun.com/mch/pay/repay/registMember.do";
            
            if(!preg_match('/^1[345789]{1}\d{9}$/', $pdata['mobile']))
            {
            	$this->ajaxReturn(array('status'=>0,'手机号格式有误'));
            }
            if(!preg_match('/^1[345789]{1}\d{9}$/', $pdata['creditmobile']))
            {
            	$this->ajaxReturn(array('status'=>0,'手机号格式有误'));
            }
            $data['mchCode'] ='1526456081515';
            $data['phoneNo'] = $pdata['mobile'];
            $data['name']    = $pdata['username'];
            $data['idCardNo'] =$pdata['usercard'];
           
            $data['sign']=$this->getSign2($data,'b88923c4cde7db92fa99505fe2ee66f8');
            $ares =$this->_request($url,true,'POST',$data);
            $resultcode =json_decode($ares,true);
			
            if($resultcode['status'] ==1)
            {
            	$datt['fhway']=1;
        		$datt['mobile']   =$pdata['mobile'];
        		$datt['truename'] =$pdata['username'];
        		$datt['cardno'] =$pdata['usercard'];
        		M('users')->where("id='".$_SESSION['user']['id']."'")->save($datt);
            	$url2='https://www.51mayun.com/mch/pay/repay/bindCard.do';
            	$bdata['mchCode'] =$data['mchCode'];
            	$bdata['phoneNo'] =$data['phoneNo'];
            	$bdata['creditCardNo'] =$pdata['bankno'];
            	$bdata['creditCardName'] =$pdata['bankname'];
            	$bdata['mobile'] =$pdata['creditmobile'];
            	$bdata['sign']=$this->getSign2($bdata,'b88923c4cde7db92fa99505fe2ee66f8');
            	$ares2 =$this->_request($url2,true,'POST',$bdata);
            	$resultcode2 =json_decode($ares2,true);
            	
            	if($resultcode2['status'] ==1)
            	{
            		$dat['fhway']=2;
            		$dat['creditcardno']   =$pdata['bankno'];
            		$dat['creditcardname'] =$pdata['bankname'];
            		$dat['cvn2'] =$pdata['cvn2'];
            		$dat['cardtime'] =$pdata['cardtime'];
            		$dat['creditmobile'] =$pdata['creditmobile'];
            		M('users')->where("id='".$_SESSION['user']['id']."'")->save($dat);
            		$this->ajaxReturn(array('status'=>1,'url'=>U('Portal/user/confirmorder',array('tdid'=>$pdata['tdid']))));
            	}else{
					$this->ajaxReturn(array('status'=>0,'msg'=>$resultcode2['message']));   
				}            	
            }
            else
            {
				$this->ajaxReturn(array('status'=>0,'msg'=>'认证失败'));            	
            }
            
		}
	}
}



?>