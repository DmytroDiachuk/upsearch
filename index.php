<?php

header('Content-Type:text/html;charset=utf-8');
ini_set("display_errors", 1);
ini_set("track_errors", 1);
ini_set("html_errors", 1);
error_reporting(E_ALL);
require 'config.php';global $settings;
require 'upsearch.php';

$u = new Upsearch($settings);

echo 'TEST<br/>';

//$u->fullUpdate();
$u->doRealUpdate();

echo 'all done!';
