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
     * 静态魔术加载方法
     * @param string $name 静态类名
     * @param array $arguments 参数集合
     * @return mixed
     * @throws InvalidInstanceException
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
			$jsonResult = file_get_contents($url);
			$resultArray = json_decode($jsonResult, true);
            $access_token = $resultArray["access_token"];
            $openid = $resultArray["openid"];
            $infoUrl = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $access_token . "&openid=" . $openid;
            $infoResult = file_get_contents($infoUrl);
            $infoArray = json_decode($infoResult, true);
			return $infoArray;
		}else{
			return false;
		}
	}
	//获取基础acctoken
    public static function gettoken(){
		//先判断access_token  wx_token 的文件存不存在 并且 有效期在2小时内
	    $token_expires = 7000;
	    $file = 'wx_token';
	    if(file_exists($file) && filemtime($file)+$token_expires > time()){
	        $token = file_get_contents($file);
			return $token;
	    }
		$appid = self::$config['appid'];
		$appSecret = self::$config['appsecret'];
		//获取token
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appsecret}";
		$res = json_decode($this->curl_post($url),true);
		if(isset($res ['access_token'])){
			$token = $res ['access_token'];
		    file_put_contents($file,$token);
		    return $token;
		}else{
			return $res['errmsg'];
		} 
	}
}
?>