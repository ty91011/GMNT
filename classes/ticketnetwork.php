<?php


class TicketNetwork
{
    const PRODUCTION_URL = "https://www.tn-apis.com/inventory/v4";
    const SANDBOX_URL = "https://sandbox.tn-apis.com/inventory/v4";
    
    const PRODUCTION_CONSUMER_KEY = "DzFKAjSfuwJwa7Xtj4xBBH8IiDQa";
    const PRODUCTION_CONSUMER_SECRET = "pHoC72ej4XI7BC_1pfMaU9XAKRYa";
    
    const SANDBOX_CONSUMER_KEY = "eZNT3fo78Cw8M4IORH5N3V4AOJga";
    const SANDBOX_CONSUMER_SECRET = "2yQw03iIvVIZWzraGtozQZvnPEga";
    
    const ACCESS_TOKEN = "bd4a52b6-42bc-3105-98a8-6697b2af37ba";
    const SANDBOX_ACCESS_TOKEN = "a5b74194-506b-3c62-ad37-6e4accf6e400";
    
    const BROKER_ID = 9749;
    
    static function getAccessToken($sandbox=false)
    {

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, "https://www.tn-apis.com/token");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
	curl_setopt($ch, CURLOPT_POST, 1);

	$headers = array();
	if($sandbox)
	{
	    $headers[] = "Authorization: Basic ZVpOVDNmbzc4Q3c4TTRJT1JINU4zVjRBT0pnYToyeVF3MDNpSXZWSVpXenJhR3RvelFadm5QRWdh";
	}
	else
	{
	    $headers[] = "Authorization: Basic RHpGS0FqU2Z1d0p3YTdYdGo0eEJCSDhJaURRYTpwSG9DNzJlajRYSTdCQ18xcGZNYVU5WEFLUllh";
	}
	$headers[] = "Content-Type: application/x-www-form-urlencoded";
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$result = curl_exec($ch);
	if (curl_errno($ch)) {
	    echo 'Error:' . curl_error($ch);
	}
	curl_close ($ch);
	
	$data = json_decode($result);
	
	return $data->access_token;
    }
    
    // Start SKYBOX functionality
    static function uploadTickets($event, $maxPrice, $minGroups, $markup, $maxRows)
    {
	$inventory = getFilteredInventory($event['tmId'], $maxPrice, $minGroups, $markup, $maxRows);

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
	    //$inventory->inHandDate = date("d/m/y", strtotime($event['datetime'])-86400);
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
	error_log(json_encode($purchase, JSON_PRETTY_PRINT));
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
    
        // Start SKYBOX functionality
    static function uploadTourTickets($tour, $maxPrice, $minGroups, $markup, $maxRows)
    {
	$inventory = getFilteredTourInventory($tour, $maxPrice, $minGroups, $markup, $maxRows);

	$purchase = new stdClass();
	$purchase->vendorId = self::DEFALUT_VENDOR_ID;
	$purchase->lines = array();

	$inventoryIds = array();

	foreach($inventory AS $inventoryItem)
	{
	    $inventoryIds[] = $inventoryItem['id'];

	    $line = new stdClass();
	    $inventory = new stdClass();
	    $inventory->eventId = $inventoryItem['eventSbId'];
	    $inventory->quantity = $inventoryItem['quantity'];
	    $inventory->section = $inventoryItem['section'];
	    $inventory->row = $inventoryItem['row'];
	    $inventory->cost = round($inventoryItem['ticketPrice'] * $inventoryItem['quantity'], 2);
	    $inventory->listPrice = round($inventoryItem['ticketPrice'] * (100+$markup)/100, 2);
	    $inventory->expectedValue = round($inventoryItem['ticketPrice'] * (100+$markup)/100, 2);
	    $inventory->splitType = "NEVERLEAVEONE";
	    $inventory->inHandDaysBeforeEvent = 1;
	    //$inventory->inHandDate = date("d/m/y", strtotime($event['datetime'])-86400);
	    $inventory->notes = $inventoryItem['id'];
	    
	    // Make up a seat number
	    $inventory->lowSeat = rand(1,20000); //$inventoryItem['id'] + 100;
	    $inventory->highSeat = $inventory->lowSeat + $inventory->quantity - 1;
	    $inventory->stockType = "ELECTRONIC";
	    $inventory->seatType = "CONSECUTIVE";
	    $inventory->broadcast = false;
	    $inventory->hideSeatNumbers = true;

	    // TODO: Change later
	    $eventMapping = new stdClass();
	    $eventMapping->eventName = $inventoryItem['name'];
	    $eventMapping->venueName = $inventoryItem['venue'];
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
	error_log(json_encode($purchase, JSON_PRETTY_PRINT));
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
	    insertHistory($tour, "Uploaded tour to Skybox", $notification);
	    storeNotification("exportSkybox", $notification, "success");
	}
	
	$skybox = json_decode($out);

	foreach($skybox->lines AS $line)
	{
	    $sbId = $line->inventory->id;
	    $section = $line->inventory->section;
	    $row = $line->inventory->row;
	    $eventSbId = $line->inventory->eventId;
	    $query = "UPDATE inventory i left join events e on i.tmId=e.tmId SET skyboxStatus='ON SKYBOX', i.sbId='$sbId' WHERE section='$section' and row='$row' and e.sbId='$eventSbId' ";
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
