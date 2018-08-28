<?php


class Skybox
{
    const API_TOKEN = "2794461f-7da7-4c86-a27c-87e2ae79ebda";
    const ACCOUNT_ID = 3372;
    const DEFALUT_VENDOR_ID = 1283525;
    
    // Start SKYBOX functionality
    static function uploadTickets($event, $maxPrice, $minGroups, $markup)
    {
	$inventory = getFilteredInventory($event['tmId'], $maxPrice, $minGroups, $markup);

	$purchase = new stdClass();
	$purchase->vendorId = self::DEFALUT_VENDOR_ID;
	$purchase->lines = array();

	$inventoryIds = array();

	foreach($inventory AS $inventoryItem)
	{
	    $inventoryIds[] = $inventoryItem['id'];

	    $line = new stdClass();
	    $inventory = new stdClass();
	    $inventory->eventId = $event['sbId'];
	    $inventory->quantity = $inventoryItem['quantity'];
	    $inventory->section = $inventoryItem['section'];
	    $inventory->row = $inventoryItem['row'];
	    $inventory->cost = round($inventoryItem['ticketPrice'] * $inventoryItem['quantity'], 2);
	    $inventory->listPrice = round($inventoryItem['ticketPrice'] * (100+$markup)/100, 2);
	    $inventory->expectedValue = round($inventoryItem['ticketPrice'] * (100+$markup)/100, 2);
	    $inventory->splitType = "NEVERLEAVEONE";
	    $inventory->inHandDaysBeforeEvent = 1;
	    $inventory->notes = $inventoryItem['id'];
	    
	    // Make up a seat number
	    $inventory->lowSeat = rand(1,20000); //$inventoryItem['id'] + 100;
	    $inventory->highSeat = $inventory->lowSeat + $inventory->quantity - 1;
	    $inventory->stockType = "ELECTRONIC";
	    $inventory->seatType = "CONSECUTIVE";
	    $inventory->broadcast = false;
	    $inventory->hideSeatNumbers = true;

	    $eventMapping = new stdClass();
	    $eventMapping->eventName = $event['name'];
	    $eventMapping->venueName = $event['venue'];
	    $eventMapping->eventDate = "2018-09-23T19:30:00.000";
	    $inventory->eventMapping = $eventMapping;


	    $line->inventory = $inventory;
	    $line->amount = round($inventoryItem['ticketPrice'] * (100+$markup)/100 * $inventoryItem['quantity'], 2);
	    $line->lineItemType="INVENTORY";

	    $purchase->lines[] = $line;
	}
	/*

	echo "<pre>";
	echo json_encode($purchase, JSON_PRETTY_PRINT);
	die();
	 * 
	 */

	$urlSuffix = "purchases";
	$requestType = "POST";
	$postFields = json_encode($purchase);
	$out = self::apiRequest($urlSuffix, $requestType, $postFields);
	
	// Error
	if(!$out)
	{
	    $notification = "Failed to upload " . count($inventoryIds) . " groups of tickets to Skybox";
	    storeNotification("exportSkybox", $notification, "error");
	}
	else
	{
	    $notification = "Uploaded " . count($inventoryIds) . " groups of tickets to Skybox";
	    insertHistory($event['tmId'], "Uploaded to Skybox", $notification);
	    storeNotification("exportSkybox", $notification, "success");
	}
	
	$skybox = json_decode($out);
	foreach($skybox->lines AS $line)
	{
	    $sbId = $line->inventory->id;
	    $section = $line->inventory->section;
	    $row = $line->inventory->row;
	    $query = "UPDATE inventory SET skyboxStatus='ON SKYBOX', sbId='$sbId' WHERE section='$section' and row='$row' and tmId='$event[tmId]' ";
	    DB::query($query);
	}

    }
    
    static function removeInventory($inventorySkyboxId)
    {
	$urlSuffix = "inventory/$inventorySkyboxId/tickets";
	$result = self::apiRequest($urlSuffix, "DELETE", $postFields);
	return $result;
    }
    
    static function apiRequest($urlSuffix, $requestType, $postFields)
    {
	$uri = 'https://skybox.vividseats.com/services/' . $urlSuffix;
	error_log("Hitting skybox $uri");
	$ch = curl_init($uri);
	curl_setopt_array($ch, array(
	    CURLOPT_CUSTOMREQUEST => $requestType,
	    CURLOPT_RETURNTRANSFER  =>true,
	    CURLOPT_VERBOSE     => 1,
	    CURLOPT_POSTFIELDS => $postFields,
	    CURLOPT_HTTPHEADER => array(                                                                          
	    'Content-Type: application/json',                                                                                
	    'Content-Length: ' . strlen($postFields),
		"x-api-token: " . self::API_TOKEN, "x-account: " . self::ACCOUNT_ID
		)  
	));
	$out = curl_exec($ch);
	
	if(!curl_error($ch))
	{
	    switch($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE))
	    {
		case 200: #ok
		    error_log("Skybox responded ($http_code): $out");
		    break;
		default: #error
		    error_log("Curl Error Response ($http_code): $out " . curl_error($ch));
		    return false;
	    }
	}
	
	curl_close($ch);
	
	return $out;
    }
    
    static function searchEvents($event, $venue = null, $datetime = null, $name=null)
    {
	$filters = array();

	$filters = [
	    "venue" => is_null($venue) ? $event['venue'] : $venue,
	    "eventDateFrom" => is_null($datetime) ? date("Y-m-d", strtotime($event['datetime'])) : date("Y-m-d", strtotime($datetime)),
	    "eventDateTo" => is_null($datetime) ? date("Y-m-d", strtotime($event['datetime']) + 86400) : date("Y-m-d", strtotime($datetime)+86400),
	    "excludeParking" => "true",
	    "event" => is_null($name) ? "" : $name,
	];
	
	$fields = http_build_query($filters);
	$urlSuffix = "events?$fields";
	//echo $urlSuffix . "<br>";
	$out = self::apiRequest($urlSuffix, "GET", $postFields);
	return json_decode($out, true);
    }
}
