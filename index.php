<?php
    define("YISHOP_PATH",getcwd()."/");
	require("include/core.php");
    $yishop = new Yishop();
    $yishop->run();
    echo "<br />Ê¹ÓÃÄÚ´æ".memory_get_usage();
?>
