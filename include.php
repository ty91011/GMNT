<?php

$skyboxAPI = array(
    "token" => "2794461f-7da7-4c86-a27c-87e2ae79ebda",
    "accountId" => 3372,
    "defaultVendorId" => 1283525
);

ini_set('display_errors', TRUE);
error_reporting(E_ALL ^ E_NOTICE ^E_WARNING);
include("meekrodb.2.3.class.php");
include("functions.php");


// Security
if(!isset($_GET['letmein']) && $_SERVER['REMOTE_ADDR'] != '::1'){
    echo "Unauthorized user"; die();
}

/*
$uri = 'https://skybox.vividseats.com/services/inventory?api-token=2794461f-7da7-4c86-a27c-87e2ae79ebda';
$ch = curl_init($uri);
curl_setopt_array($ch, array(
    CURLOPT_HTTPHEADER  => array("x-api-token: $skyboxAPI[token]"),
    CURLOPT_RETURNTRANSFER  =>true,
    CURLOPT_VERBOSE     => 1
));
$out = curl_exec($ch);
var_dump($out);
curl_close($ch);

die();
*/


