﻿<?php
/**
 * Yishop 入口文件
 * ============================================================================
 * 版权所有 2011-2012 叶兆滨，并保留所有权利。
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: 叶兆滨 $
 * $Id: index.php 2011-4-30 $
*/
    define("YISHOP_PATH",getcwd()."/");
    define("IN_YISHOP",true);
    require("include/core.php");
    $yishop = new Yishop();
    $yishop->run();
    echo "<br />user memory".memory_get_usage();
?>
