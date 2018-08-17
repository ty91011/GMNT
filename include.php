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

// Classes
include("classes/skybox.php");

// Security
if(!isset($_GET['letmein']) && $_SERVER['REMOTE_ADDR'] != '::1'){
    echo "Unauthorized user"; die();
}
/*
echo "EXECUTING CURL";

//Creates a purchase. The minimum required field to insert a purchase is the vendorId. \
//Inventory purchase lines must include quantity, section, row, cost, lowSeat, highSeat, stockType, seatType and either an eventId or eventMapping.


$purchase = new stdClass();
$purchase->vendorId = $skyboxAPI['defaultVendorId'];
$purchase->lines = array();

$line = new stdClass();
$inventory = new stdClass();
$inventory->quantity = 4;
$inventory->section = "15-L";
$inventory->row = "56";
$inventory->cost = 98.23;
$inventory->lowSeat = 8;
$inventory->highSeat = 11;
$inventory->stockType = "ELECTRONIC";
$inventory->seatType = "CONSECUTIVE";
$inventory->broadcast = false;

$eventMapping = new stdClass();
$eventMapping->eventName = "JAY-Z and BEYONCÃ‰ - OTR II";
$eventMapping->venueName = "Rose Bowl";
$eventMapping->eventDate = "2018-09-23T19:30:00.000";
$inventory->eventMapping = $eventMapping;


$line->inventory = $inventory;
$line->amount = 100.00;
$line->lineItemType="INVENTORY";


$purchase->lines[] = $line;


echo "<pre>";
 echo json_encode($purchase, JSON_PRETTY_PRINT);



$data_string = "{}";





$uri = 'https://skybox.vividseats.com/services/purchases?api-token=2794461f-7da7-4c86-a27c-87e2ae79ebda';
$ch = curl_init($uri);
curl_setopt_array($ch, array(
    CURLOPT_HTTPHEADER  => array("x-api-token: $skyboxAPI[token]"),
    CURLOPT_RETURNTRANSFER  =>true,
    CURLOPT_VERBOSE     => 1
));
$out = curl_exec($ch);

print "<pre>";
var_dump(json_decode($out, true));
curl_close($ch);

die();

$uri = 'https://skybox.vividseats.com/services/purchases?api-token=2794461f-7da7-4c86-a27c-87e2ae79ebda';
$ch = curl_init($uri);
curl_setopt_array($ch, array(
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_RETURNTRANSFER  =>true,
    CURLOPT_VERBOSE     => 1,
    CURLOPT_POSTFIELDS => $data_string,
    CURLOPT_HTTPHEADER => array(                                                                          
    'Content-Type: application/json',                                                                                
    'Content-Length: ' . strlen($data_string),
	"x-api-token: $skyboxAPI[token]", "x-account: $skyboxAPI[accountId]"
	)  
    
    
));
$out = curl_exec($ch);
var_dump(curl_getinfo($ch, CURLINFO_HTTP_CODE));
print "<pre>";
var_dump($out);
curl_close($ch);

die();

*/

