<?php
/**
 * Yishop 基本配置文件
 * ============================================================================
 * 版权所有 2011-2012 叶兆滨，并保留所有权利。
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: 叶兆滨 $
 * $Id: config.php 2011-4-30 $
*/

global $database ;
$database = array("host"=>"localhost","db_name"=>"yishop","username"=>"root","password"=>"","charset"=>"utf8");
define("SITE_LANGUAGE","zh-cn");
define("SITE_NAME","Yishop");
define("ROUTE_STYLE","path");
define("COOKIE_PREF","m712_");
define("ROUTE_TYPE","path");
define("SYSTME_PATH","/");
