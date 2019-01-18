<?php

if (!function_exists('curl_init')) {
    throw new Exception('Truevo needs the CURL PHP extension.');
}
if (!function_exists('json_decode')) {
    throw new Exception('Truevo needs the JSON PHP extension.');
}

require_once dirname(__FILE__). '/src/Truevo.php';
require_once dirname(__FILE__). '/src/TruevoException.php';