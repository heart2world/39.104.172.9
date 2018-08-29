
/**设置图标层的高度及添加图片层的背景图**/
/**
 obj  需设置图片层的class
 h    需设置图片层高的比例(比例的计算方式为UI图片的 高度/宽度)
 **/
$.toast.prototype.defaults.duration=2000;
function bgImg(obj, h) {
    var width = $('.' + obj).width();
    $('.' + obj).height(width * h);
    $('.' + obj).each(function() {
        var src = $(this).find('img').attr('src');
        $(this).css('background', 'url(' + src + ') center no-repeat');
        $(this).css('background-size', 'cover');
    });
}


//拨打电话
function call(obj,tel){
	$('body').append('<div class="callBox meng"><div class="content"><p>确认拨打 '+tel+' ?</p><div class="btnBox disbox"><a class="cancel disflex" onclick="cancel(this)">取消</a><a class="sure disflex" href="tel:'+tel+'">确认</a></div></div></div>');
}

//点击取消
function cancel(obj){
	$(obj).parents('.meng').hide();
}


//获取验证码
var bj = 0;
var countdown = 60;
function settime(val) {
	if(countdown == 0) {
		$('.getCode').text('获取验证码').addClass('on');
		$('.getCode').removeClass("active");
		countdown = 60;
		bj = 0;
		return;
	} else {
		$('.getCode').text(countdown + 's后再获取');
		$('.getCode').addClass("active");
		countdown--;
	}
	setTimeout(function() {
		settime(val)
	}, 1000)
}
function getCode(obj,phone){
	console.log(phone);
	if($(obj).hasClass("on")){
        var phone=$('#telInput').val();
        settime(countdown);
		$(obj).removeClass('on').addClass('active');
    }
}

$(function(){
	
	//点击头部返回
	$('.back').click(function(){
		back();
	})
})
