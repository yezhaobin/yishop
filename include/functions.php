<?php
/**
 * Yishop 公共函数文件
 * ============================================================================
 * 版权所有 2011-2012 叶兆滨，并保留所有权利。
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: 叶兆滨 $
 * $Id: functions.php 2011-4-30 $
*/

if(!defined("IN_YISHOP")){
    die("try to hack");
}

function i18n($key, $lang = "default"){
    global $i18n;//系统的语言
    global $i18n_tpl;//模板的语言

    if(array_key_exists($key, $i18n)){
        return $i18n[$key];
    }elseif(@array_key_exists($key , $i18n_tpl)){
        return $i18n_tpl[$key];
    }else{
        return system_error("lang_file_does't_contain_the_words",$key);
    }
}
    
function system_error($message_code,$e=""){
    $message = i18n($message_code);
    echo "<html><head><meta charset=\"utf-8\" /></head><body>";
    echo "<p style='background-color:yellow;color:red;'>$message</p>";
    if(is_array($e)){
        foreach($e as $key => $value){
            echo "<p style='background-color:yellow;font-size:12px;color:red;text-indent:2em;'>".$key.":".$value."</p>";
        }
    }else{
        echo "<p style='background-color:yellow;font-size:12px;color:red;text-indent:2em;'>".$e."</p>";
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
    $do != 'ENCODE' && $str = base64_decode($str);
    $code = '';
    $key  = substr(md5($_SERVER['HTTP_USER_AGENT'].SECRET_TOKEN),8,18);
    $keylen = strlen($key); $strlen = strlen($str);
    for ($i=0;$i<$strlen;$i++) {
        $k = $i % $keylen;
        $code  .= $str[$i] ^ $key[$k];
    }
    return ($do!='DECODE' ? base64_encode($code) : $code);
}