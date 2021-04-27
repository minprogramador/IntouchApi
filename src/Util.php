<?php

namespace App;

class Util
{
	static public function getCookies($get) {
		preg_match_all('/ookie: (.*);/U',$get,$temp);
		$cookie  = $temp[1];
		$cookies = implode('; ',$cookie);
		return $cookies;
	}

	static public function corta($str, $left, $right) {
		$str 	  = substr ( stristr ( $str, $left ), strlen ( $left ) );
		@$leftLen = strlen ( stristr ( $str, $right ) );
		$leftLen  = $leftLen ? - ($leftLen) : strlen ( $str );
		$str      = substr ( $str, 0, $leftLen );
		return $str;
	}
    
    static public function parseForm($data)
    {
        $post = array();
        if(preg_match_all('/<input(.*)>/U', $data, $matches)){
            foreach($matches[0] as $input){
                if(!stristr($input, "name=")) continue;
                if(preg_match('/name=(".*"|\'.*\')/U', $input, $name))
                {
                    $key = substr($name[1], 1, -1);
                    if(preg_match('/value=(".*"|\'.*\')/U', $input, $value)) $post[$key] = substr($value[1], 1, -1);
                    else $post[$key] = "";
                }
            }
        }
        return $post;
    }

	static public function curl($url,$cookies,$post,$referer=null,$header=1,$follow=false, $proxy=null) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, $header);
		if ($cookies) curl_setopt($ch, CURLOPT_COOKIE, $cookies);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $follow);
		if(isset($referer)){ curl_setopt($ch, CURLOPT_REFERER,$referer); }
		else{ curl_setopt($ch, CURLOPT_REFERER,$url); }
		if(strlen($post) > 5) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 

			$headers = array();
			$headers[] = "Origin: https://intouch.unitfour.com.br";
			$headers[] = "Upgrade-Insecure-Requests: 1";
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}

		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30); 
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 20);
		if($proxy){
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
		}
		$res = curl_exec( $ch);

		curl_close($ch); 

		if(strlen($post) > 5) {
			return utf8_decode($res);
		}else{
			return $res;
		}
	}

	static public function lerCookie(){
		$f  = fopen("Bin/cookie_x1.txt", "r");
		$cc = fgets($f); 
		return $cc;
		fclose($f);
	}

	static public function salvaCookie($val){
		$fh = fopen("Bin/cookie_x1.txt", 'w');
		fwrite($fh, $val);
		fclose($fh);
		@chmod("Bin/cookie_x1.txt", 0666);
		return true;
	}

	static public function sanitizeString($str){
    	return preg_replace('{\W}', '', preg_replace('{ +}', '_', strtr(
        utf8_decode(html_entity_decode($str)),
        utf8_decode('ÀÁÃÂÉÊÍÓÕÔÚÜÇÑàáãâéêíóõôúüçñ'),
        'AAAAEEIOOOUUCNaaaaeeiooouucn')));
	}

}