<?php
$mysql_server="localhost";
$mysql_username="root";
$mysql_password="Test1235";
$mysql_database="thinkcard";
//建立数据库链接
$conn = mysqli_connect($mysql_server,$mysql_username,$mysql_password,$mysql_database) or die("数据库链接错误");

$k=0;
while ($k <= 49) {
	$code = func_randStr(8);
	$result= mysqli_query($conn,"insert into cmd_vipcode(code,atype)values('".$code."',1)");
	$k++;
}
$j=0;
while ($j <= 49) {
	$codee = func_randStr(8);
	$result= mysqli_query($conn,"insert into cmd_vipcode(code,atype)values('".$codee."',2)");
	$j++;
}
mysqli_close($conn);
function func_randStr($length=8) {
    // 密码字符集，可任意添加你需要的字符
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    $str = "";
    for($i = 0; $i < $length; $i++) {
        $str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $str;
}
