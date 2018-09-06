<?php

session_start();

// AUTHENTICATE
if(is_null($_SESSION['user']))
{
    header('Location: /login.php');
    exit;
}



$skyboxAPI = array(
    "token" => "2794461f-7da7-4c86-a27c-87e2ae79ebda",
    "accountId" => 3372,
    "defaultVendorId" => 1283525
);

ini_set('display_errors', TRUE);
error_reporting(E_ALL ^ E_NOTICE ^E_WARNING);
include("meekrodb.2.3.class.php");

// Classes
include("classes/skybox.php");
include("classes/constants.php");
include("classes/proxies.php");
include("classes/vividseats.php");


include("functions.php");




