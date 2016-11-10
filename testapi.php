<?php
error_reporting(E_ALL ^ E_NOTICE);
header("Content-type: text/html; charset=utf-8");
$uri='api/user/login';
if($_POST&&$_POST['uri']){
	$uri=$_POST['uri'];
	function curlrequest($url,$data,$method='post'){
		$name=$_POST['name'];
		$pass=$_POST['pass'];
		$http_authorization=base64_encode("1qasw2".$name.":".$pass."34sasa");
		$header = array();
		$header[] = "X-HTTP-Method-Override: $method";
		$header[] = "HTTP_AUTHORIZATION: $http_authorization";
		$header[] = "AUTHORIZATION: $http_authorization";
        //$header[] = "Authorization: Basic $http_authorization"; //此为标准写法，兼容大多web服务器
        
		$ch = curl_init(); //初始化CURL句柄 
		curl_setopt($ch, CURLOPT_URL, $url); //设置请求的URL
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); //设为TRUE把curl_exec()结果转化为字串，而不是直接输出 
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); //设置请求方式
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//设置提交的字符串
		curl_setopt($ch, CURLOPT_HTTPHEADER,$header);//设置HTTP头信息
		
		$document = curl_exec($ch);//执行预定义的CURL 
		if(curl_errno($ch)){ 
		  echo 'Curl error: ' . curl_error($ch); 
		}
		curl_close($ch);
		return $document;
	}
	
	$url =  'http://'.$_SERVER['HTTP_HOST'].str_replace('testapi', 'index', $_SERVER['SCRIPT_NAME']).'?r='.$uri;
	//$url = 'http://localhost/beauty/index.php?r='.$uri;
    
    
    $_p=array();//参数列表
    foreach($_POST['p'] as $p){
        if($p['name']!=''){
            $_p[]=$p['name']."=".urlencode($p['value']);
        }
    }
    $data=implode("&",$_p);
	$return = curlrequest($url, $data, $_POST['type']);
	//echo $return;
}

?>

<form id="form"  action="" method="post">

    测试接口： <input type="type" style="width: 300px;"  name="uri" value="<?php echo $uri; ?>" />
    
    接口类型： <select name="type">
    <option value="POST"  selected="selected">POST</option>
    <option value="GET">GET</option>
    <option value="PUT" >PUT</option>
    <option value="DELETE" >DELETE</option>
    </select>
    <br /><br />
    测试账号： <input type="type"  name="name" value="<?php echo $_POST['name']?$_POST['name']:'陈友花' ?>" /> 密码<input type="type"  name="pass" value="123456" />
    
    <input type="submit" value="提交">
    <br /><br />
    参数1 <input type="text" name="p[1][name]" value="<?php echo $_POST['p']['1']['name'];?>" /> : <input type="text" name="p[1][value]" value="<?php echo $_POST['p']['1']['value'];?>" /><br /><br />
    
    参数2 <input type="text" name="p[2][name]" value="<?php echo $_POST['p']['2']['name'];?>" /> : <input type="text" name="p[2][value]" value="<?php echo $_POST['p']['2']['value'];?>" /><br /><br />
    
    参数3 <input type="text" name="p[3][name]" value="<?php echo $_POST['p']['3']['name'];?>" /> : <input type="text" name="p[3][value]" value="<?php echo $_POST['p']['3']['value'];?>" /><br /><br />
    
    参数4 <input type="text" name="p[4][name]" value="<?php echo $_POST['p']['4']['name'];?>" /> : <input type="text" name="p[4][value]" value="<?php echo $_POST['p']['4']['value'];?>" /><br /><br />
    
    参数5 <input type="text" name="p[5][name]" value="<?php echo $_POST['p']['5']['name'];?>" /> : <input type="text" name="p[5][value]" value="<?php echo $_POST['p']['5']['value'];?>" /><br /><br />

</form>
<div>
    <?php if(isset($return)){
        $end = strpos($return,"<!--");
        if($end>0){
            $return = substr($return,0,$end);
        }
        $return_obj=json_decode($return);
        var_dump($return_obj);
        
        echo "<br>返回原json字符串：<br>". $return ;
        
        
        
    }
    ?>
    
</div>
