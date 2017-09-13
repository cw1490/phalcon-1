<?php

use Phalcon\Mvc\Application;

define('BASE_DIR', dirname(__DIR__));
define('APP_DIR', BASE_DIR . '/app');
include APP_DIR . "/bootstrap/loader.php";
include APP_DIR . "/bootstrap/services.php";
$application = new Application($di);
echo $application->handle()->getContent();