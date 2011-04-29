<?php
/*
*公用函数
*/
    function i18n($lang){
        global $i18n;
        return $i18n[$lang];
    }

	function system_error($message_code,$e=""){
		$message = i18n($message_code);
        echo "<html><head><meta charset=\"utf-8\" /></head><body>";
		echo "<p style='background-color:yellow;color:red;'></p>";
        if(is_array($e)){
            foreach($e as $key => $value){
                echo "<p style='background-color:#cccccc;font-size:12px;color:red;'>".$key.":".$value."</p>";
            }
        }             
        echo " </body></html>";
		exit();
	}

    function get_file_content($path){
        $f = fopen($path, "r") or die(system_error("layout_temple_does_not_exists",$path));
        $content = fread($f,filesize($path)); 
        fclose($f);
        return $content;
    }

    function get_views_folder($controller_name){
        return preg_replace("/Controller/","",lcfirst($controller_name));
    }
    
    function set_cookie($name, $value,$expire = 432000, $path="/"){
        return setcookie($name, $value, time()+$expire, $path);
    }

    function delete_cookie($name,$path="/"){
        return setcookie($name,"",time()-100000, $path);
    }

    function authcode($str, $do = "ENCODE"){
        $do != 'ENCODE' && $str = base64_decode($string);
	    $code = '';
	    $key  = substr(md5($_SERVER['HTTP_USER_AGENT'].SECRET_TOKEN),8,18);
	    $keylen = strlen($key); $strlen = strlen($str);
	    for ($i=0;$i<$strlen;$i++) {
		    $k		= $i % $keylen;
		    $code  .= $str[$i] ^ $key[$k];
	    }
	    return ($do!='DECODE' ? base64_encode($code) : $code);
    }
