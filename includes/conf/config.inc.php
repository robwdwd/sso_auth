<?php

$string = file_get_contents(dirname(__FILE__)."/config.json");
$configuration = json_decode($string, true);
