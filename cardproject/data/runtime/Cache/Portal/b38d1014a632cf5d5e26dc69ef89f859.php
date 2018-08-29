<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
	    <meta name="viewport" content="maximum-scale=1.0,minimum-scale=1.0,user-scalable=0,width=device-width,initial-scale=1.0"/>
	    <meta name="format-detection" content="telephone=no,email=no,date=no,address=no">
		<title>消费项目</title>
	    <link rel="stylesheet" href="/public/app/lib/weui/jquery-weui.css" />
	    <link rel="stylesheet" href="/public/app/lib/weui/weui.min.css" />
	    <link rel="stylesheet" href="/public/app/lib/swiper/swiper.min.css" />
	    <link rel="stylesheet" href="/public/app/css/public.css" />
	    <link rel="stylesheet" href="/public/app/css/style.css" />
	    <script type="text/javascript" src="/public/app/lib/jq/jquery-1.10.2.js" ></script>
	    <script type="text/javascript" src="/public/app/lib/weui/jquery-weui.js" ></script>
	    <script type="text/javascript" src="/public/app/js/v.min.js" ></script>
	    <script type="text/javascript" src="/public/app/js/common.js" ></script>
	</head>
	<style>
		.weui-cells{ margin-top: 0;}
		.weui-cells:after, .weui-cells:before{ display: none;}
		.weui-cell:before{ left: 0;}
		.weui-cells_checkbox .weui-check:checked+.weui-icon-checked:before{ color: #2E7DFF;}
		.weui-check__label:active{ background-color: rgba(0,0,0,0);}
	</style>
	<body>
		<section id="app" class="mainSec" style="background: #f6f6f6;">
			<div class="disbox title">
				<div class="disflex">请选择项目</div>
				<a href="<?php echo U('Portal/index/orderlist');?>">记录</a>
			</div>
			<div class="listUl">
				<?php if(is_array($mlist)): $i = 0; $__LIST__ = $mlist;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$va): $mod = ($i % 2 );++$i;?><a class="list" href="<?php echo U('Portal/index/detail',array('id'=>$va['id']));?>" style="display: block;">
						<div class="disbox tit">
							<div class="disflex"><?php echo ($va["name"]); ?></div>
							<div style="font-size: 15px;">费率:(<?php echo ($va["persont"]); ?>%)</div>
						</div>
						<div class="content">
							<div class="disbox">
								<div class="disflex"><span>单笔限额: </span><?php echo ($va["mincoin"]); ?>~<?php echo ($va["maxcoin"]); ?></div>
								<div><span>开放时间: </span><?php echo ($va["starttime"]); ?>~<?php echo ($va["endtime"]); ?></div>
							</div>
							<?php if($va['nobank'] != ''): ?><div class="disbox"><span>不支持的银行: </span> <?php echo ($va["nobank"]); ?></div><?php endif; ?>
							<?php if($va['cashdesc'] != ''): ?><div class="disbox"><span>描述: </span><p class="disflex">&nbsp;<?php echo ($va["cashdesc"]); ?></p></div><?php endif; ?>
						</div>
					</a><?php endforeach; endif; else: echo "" ;endif; ?>
			</div>
		</section>
	</body>
</html>