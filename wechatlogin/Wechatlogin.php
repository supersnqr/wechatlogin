<?php
namespace supersnqr\wechatlogin;

class Wechatlogin
{
	private static $config;
	
	public static function config($option = null){
		if (is_array($option)) {
            self::$config = new DataArray($option);
        }
        if (self::$config instanceof DataArray) {
            return self::$config->get();
        }
        return [];
	}
	/**
     * 静态魔术加载方法
     * @param string $name 静态类名
     * @param array $arguments 参数集合
     * @return mixed
     * @throws InvalidInstanceException
     */
    public static function wechat_h5($callback=''){
    	var_dump(self::$config);exit;	
		$appid = config('site.appid_login');
		$callback = urlencode($callback);
		$url = "https://open.weixin.qq.com/connect/qrconnect?appid={$appid}&redirect_uri={$callback}&response_type=code&scope=snsapi_login&state=what#wechat_redirect";
		return $url;
    }
	public static function wechat_pub($callback=''){
		$appid = config('site.public_appid');
		$callback = urlencode($callback);
		$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$callback}&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";
		return $url;
    }
	public static function callback($code){
		if(!empty($code)){
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
//  public static function __callStatic($name, $arguments)
//  {
//      
//
//  }
}
?>