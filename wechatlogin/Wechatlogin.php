<?php
namespace supersnqr\wechatlogin;

class Wechatlogin
{
	private static $config = null;
	private static $cache = null;
	public static function config (array $options)
    {
       self::$config = $options;
	   $key = md5(get_called_class() . serialize($options));
        if (isset(self::$cache[$key])) return self::$cache[$key];
        return self::$cache[$key] = new static($options);
    }
	
	/**
     * @param string $callback 回调路径
     */
    public static function wechat_h5($callback=''){
		$appid = self::$config['appid'];
		$callback = urlencode($callback);
		$url = "https://open.weixin.qq.com/connect/qrconnect?appid={$appid}&redirect_uri={$callback}&response_type=code&scope=snsapi_login&state=what#wechat_redirect";
		return $url;
    }
	public static function wechat_pub($callback=''){
		
		$appid = self::$config['appid'];
		$callback = urlencode($callback);
		$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$callback}&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";
		return $url;
    }
	public static function callback($code){
		if(!empty($code)){
			$appid = self::$config['appid'];
			$appSecret = self::$config['appsecret'];
			$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$appSecret."&code=".$code."&grant_type=authorization_code";
			$resultArray = self::httpRequest($url);
            $access_token = $resultArray["access_token"];
            $openid = $resultArray["openid"];
            $infoUrl = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $access_token . "&openid=" . $openid;
            $infoResult = file_get_contents($infoUrl);
            $infoArray = json_decode($infoResult, true);
			return $infoArray;
		}else{
			throw new \Exception('code不能为空');
			return false;
		}
	}
	//获取基础acctoken
    public static function gettoken(){
		//先判断access_token  wx_token 的文件存不存在 并且 有效期在2小时内
	    $token_expires = 7000;
	    $file = 'wx_token.txt';
	    if(file_exists($file) && filemtime($file)+$token_expires > time()){
	        $token = file_get_contents($file);
			return $token;
	    }
		$appid = self::$config['appid'];
		$appsecret = self::$config['appsecret'];
		//获取token
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appsecret}";
		$res = self::httpRequest($url);
		if(isset($res ['access_token'])){
			$token = $res ['access_token'];
		    file_put_contents($file,$token);
		    return $token;
		}else{
			return $res['errmsg'];
		} 
	}
	public static function getSignature($ticket,$str, $time, $url){
	  $string1 = "jsapi_ticket=".$ticket."&noncestr=".$str."&timestamp=".$time."&url=".$url;
	  $sha1 = sha1($string1);
	  return $sha1;
   }
	/*
     * 获取jsConfig
     * @params string $url
     * */
    public static function getConfig($url = ''){
		$ticket = self::getJsapi_ticket();
		$str = "x".rand(10000,100000)."x";  //随机字符串
		$time = time(); //时间戳
//		$url = substr(substr($url,1),0,-1);
		$url = 'http://gwww.utools.club';
		$signature = self::getSignature($ticket,$str, $time, $url);
		$result = array("appid"=>self::$config['appid'],"nonceStr"=>$str,"timestamp"=>$time,"signature"=>$signature);
		return $result;
	}
	/*
     * 获取Jsapi_ticket
     * */
	public static function getJsapi_ticket(){
		$file = "ticket_jsaoi.txt";
		$token_expires = 7000;
	    if(file_exists($file) && filemtime($file)+$token_expires > time()){
	        $jsapi_ticket = file_get_contents($file);
			return $jsapi_ticket;
	    }
		$token = self::gettoken();
	    $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token='.$token; 
		$res = self::httpRequest($url);
		if(isset($res ['ticket'])){
			$ticket = $res ['ticket'];
		    file_put_contents($file,$ticket);
		    return $ticket;
		}else{
			throw new \Exception('------异常消息-----获取ticket失败');
		}  
	}
	
	/*
    *小程序 获取openid
    * @params string $code
    *  */
   //获取openid
    public static function getopenid($code){
		$url = "https://api.weixin.qq.com/sns/jscode2session?appid=".self::$config['appid']."&secret=".self::$config['appsecret']."&js_code={$code}&grant_type=authorization_code";
		$data = self::httpRequest($url);
		return $data;
	}
	/*
	 *小程序获取token
	 * 
	 */
	public static function getSmallToken(){
		$file = "small_token.txt";
		$token_expires = 7000;
	    if(file_exists($file) && filemtime($file)+$token_expires > time()){
	        $smallToken = file_get_contents($file);
			return $smallToken;
	    }
	    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".self::$config['appid']."&secret=".self::$config['appsecret'];
	    $res = self::httpRequest($url);
	    if (isset($data['access_token'])) {
	    	file_put_contents($file,$data['access_token']);
	    	return $data['access_token'];
	    }
	   	throw new \Exception('------异常消息-----获取tokent失败');
	}
	public static function getSmallImg($path,$width=430){
		$token = self::getSmallToken();
	    $url = "https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=".$token;
	    $data = array();
	    $data['path'] = $path;
	    //最大32个可见字符，只支持数字，大小写英文以及部分特殊字符：!#$&'()*+,/:;=?@-._~，其它字符请自行编码为合法字符（因不支持%，中文无法使用 urlencode 处理，请使用其他编码方式）
	    $data['width'] = $width;
	    //二维码的宽度，默认为 430px
	    $res = self::httpRequest($url);
	    return $res;
	    //"http://zixuephp.net/inc/qrcode_img.php?url=".$siteUrl."/login?spread=".$uid;
	}
	/**
	 * curl方式访问url
	 * @param $url  访问url
	 * @param int $flbg 返回结果是否通过json_decode转换成数组 0 转换 1 不转换
	 * @param int $type 访问方式 0 get 1 post
	 * @param array $post_data post访问时传递的数据
	 * @param array $headers 访问时需要传递的header参数
	 * @return mixed
	 */
	public static function httpRequest($url,$method='GET',$json = true, $data='' )
	{
		$curl = curl_init();
	    curl_setopt($curl, CURLOPT_URL, $url);  
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);  
	    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);  
	    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);  
	    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);  
	    curl_setopt($curl, CURLOPT_AUTOREFERER, 1);  
	    if($method=='POST')
	    {
	        curl_setopt($curl, CURLOPT_POST, 1); 
	        if ($data != '')
	        {
	            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);  
	        }
	    }
	    curl_setopt($curl, CURLOPT_TIMEOUT, 30);  
	    curl_setopt($curl, CURLOPT_HEADER, 0);  
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  
	    $result = curl_exec($curl);  
	    curl_close($curl);
	    if($json) $result = json_decode($result,true);  
	    return $result;
	}
}
?>