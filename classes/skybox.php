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
	    $inventory->cost = round($inventoryItem['ticketPrice'] * (100+$markup)/100 * $inventoryItem['quantity'], 2);

	    // Make up a seat number
	    $inventory->lowSeat = $inventoryItem['id'] + 300;
	    $inventory->highSeat = $inventory->lowSeat + $inventory->quantity - 1;
	    $inventory->stockType = "ELECTRONIC";
	    $inventory->seatType = "CONSECUTIVE";
	    $inventory->broadcast = false;

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

	echo "<pre>";
	echo json_encode($purchase, JSON_PRETTY_PRINT);


	$urlSuffix = "purchases";
	$requestType = "POST";
	$postFields = json_encode($purchase);
	$out = self::apiRequest($urlSuffix, $requestType, $postFields);
	
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
	self::apiRequest($urlSuffix, "DELETE", $postFields);
    }
    
    static function apiRequest($urlSuffix, $requestType, $postFields)
    {
	$uri = 'https://skybox.vividseats.com/services/' . $urlSuffix;
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
	var_dump($out);
	var_dump(curl_getinfo($ch, CURLINFO_HTTP_CODE));
	print "<pre>";
	$skybox = json_decode($out, true);
	echo json_encode($skybox, JSON_PRETTY_PRINT);
	curl_close($ch);
	return $out;
    }
    
    static function searchEvents($event)
    {
	$filters = [
	    "venue" => $event['venue'],
	    "eventDateFrom" => date("Y-m-d", strtotime($event['datetime'])),
	    "eventDateTo" => date("Y-m-d", strtotime($event['datetime']) + 86400),
	    "excludeParking" => "true"
	];
	$fields = http_build_query($filters);
	$urlSuffix = "events?$fields";
	echo $urlSuffix . "<br>";
	$out = self::apiRequest($urlSuffix, "GET", $postFields);
	return json_decode($out, true);
    }
}
