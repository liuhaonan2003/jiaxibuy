<?php
//if (!is_file('./config.php')) header("location: ./Install");
header("Content-type: text/html; charset=utf-8");
define('RUNTIME_ALLINONE', false);
define('THINK_PATH', './Core');
define('APP_NAME', 'webapp');
define('APP_PATH', './hh');
require(THINK_PATH."/Core.php");
App::run();
?>