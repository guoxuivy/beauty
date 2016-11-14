<?php
$ivy=dirname(__FILE__).'/framework/Ivy.php';

defined('IVY_DEBUG') or define('IVY_DEBUG',false);
error_reporting(E_ALL & ~E_NOTICE);
require_once($ivy);
$app = Ivy::createApplication()->run();
