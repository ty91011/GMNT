<?php

function getEvent($eventId, $force=false)
{
    $result = DB::query("SELECT * FROM events WHERE tmId='$eventId'");
    if(count($result))
    {
	$event = $result[0];
	$tickets = DB::query("SELECT * FROM tickets WHERE tmId='$eventId'");
	$event['tickets'] = $tickets;
    }
    // Event does not exist yet
    else
    {
	// Get basic event Info
	$contents = getTMEventPage($eventId);
	
	// Invalid Event or cannot retrieve TM page
	if(!$contents)
	{
	    return false;
	}
	
	$event = parseEventInfo($contents);
	// Insert Event into DB
        DB::insertUpdate("events", $event);
	
	insertHistory($eventId, "New Event Added", "Added <strong>$event[name]</strong> @ $event[venue] on $event[datetime]");
    }
    
    // Grab all needed information from TM's page contents
    $event = populateEvent($event, $force);


    return $event;
}

function populateEvent($event, $force=false)
{
    $eventId = $event['tmId'];
    
    $fromCache = false;
    if($force)
    {
	$contents = getTMEventPage($eventId, $fromCache, "0 minute");
    }
    else
    {
	$contents = getTMEventPage($eventId, $fromCache);
    }

    // Grab all offers for event
    $offers = getOffers($contents);

    // Grab all seats in venue
    $seats = getEventSeats($eventId);

    // Get TM API credentials
    $credentials = getCredentials($contents);	

    // Get available seats
    $facets = getFacets($eventId, $credentials['apiKey'], $credentials['apiSecret']);
    foreach($facets AS $facet)
    {

	foreach($facet['places'] AS $seatId)
	{
	    $seats[$seatId]['offer'] = $offers[$facet['offers'][0]];
	}
    }

    $tickets = array();

    foreach($seats AS $seatId => $seat)
    {

	if(!$seat['seat'] || $seat['offer']['inventoryType'] != 'primary' || strstr($seat['offer']['name'], "Citi") || strstr($seat['offer']['name'], "Visa"))
	{
	    
	    continue;
	}

	$tickets[] = array(
	    "tmId" => $eventId,
	    "section" => $seat['section'],
	    "row" => $seat['row'],
	    "seatFrom" => $seat['seat'],
	    "seatTo" => $seat['seat'],
	    "listPrice" => $seat['offer']['listPrice'],
	    "totalPrice" => $seat['offer']['totalPrice'],
	    "totalQuantity" => 1,
	    "status" => "AVAILABLE",
	    "seatId" => $seatId,
	    "offerType" => $seat['offer']['offerType']
	);
    }
    
    $event['tickets'] = $tickets;

    // Refresh all data sets
    // TODO GET RID OF TRUE
    if(!$fromCache || true)
    {
	updateInventory($eventId, $tickets);
    }

    return $event;
}

function updateInventory($eventId, $tickets = array())
{
    // Reset ticket to identify new groupings
    DB::query("UPDATE tickets SET status='UNAVAILABLE' WHERE tmId='$eventId' and status = 'AVAILABLE'");
    DB::insertUpdate('tickets', $tickets, array("status" => "AVAILABLE"));
    
    $validRows = getValidRows($eventId, $tickets);

    // Check for now invalid rows that are already on Skybox
    $dbRows = DB::query("SELECT id, sbId, section, row from inventory where tmId='$eventId' and skyboxStatus='ON SKYBOX'");
    foreach($dbRows AS $dbRow)
    {
        // There is corresponding section/row pair in valid row in the dbRows
        if(isset($validRows[$dbRow['section']][$dbRow['row']]))
        {
            // Do nothing
        }
        else
        {
            // Do something
            //echo "INVALID $dbRow[section]:$dbRow[row]<br>";
            
            DB::query("UPDATE inventory SET skyboxStatus='PENDING SKYBOX REMOVAL' WHERE section='$dbRow[section]' and row='$dbRow[row]' and tmId='$eventId'");
            
	    insertHistory($eventId, "PENDING SKYBOX REMOVAL", "Section $dbRow[section] and Row $dbRow[row]");
	    
            // Take down from Skybox
            if(SkyBox::removeInventory($dbRow['sbId']) !== false)
	    {            
		// Update upon success
		DB::query("UPDATE inventory SET skyboxStatus='REMOVED FROM SKYBOX' WHERE section='$dbRow[section]' and row='$dbRow[row]' and tmId='$eventId'");
		insertHistory($eventId, "REMOVED FROM SKYBOX", "Section $dbRow[section] and Row $dbRow[row]");
	    }
        }
    }
    
    // Reset rows
    DB::query("UPDATE inventory SET tmStatus='UNAVAILABLE' WHERE tmId='$eventId' AND tmStatus='AVAILABLE'");
    
    // Update inventory
    $inventory = array();
    foreach($validRows AS $section)
    {
	foreach($section AS $row)
	{
	    $inventory[] = $row;
	}
    }
    DB::insertUpdate("inventory", $inventory, array("tmStatus" => "AVAILABLE"));
}

function insertHistory($tmId, $type, $status)
{
    $history = array(
	"type" => $type,
	"status" => $status,
	"tmId" => $tmId
    );
    DB::insertIgnore("history", $history);
}


function getValidRows($eventId, $tickets = array(), $consecutiveCount = 4, $minGroupsThreshold = 2)
{
    $venue = array();
    
    // Populate heirarchy
    foreach($tickets as $ticket)
    {
        $venue[$ticket['section']][$ticket['row']]['seats'][] = $ticket['seatFrom'];
        $venue[$ticket['section']][$ticket['row']]['totalPrice'] = $ticket['totalPrice'];
    }
    
    $validRows = array();
    
    // Populate valid rows
    foreach($venue AS $section => $rows)
    {
        foreach($rows AS $row => $rowInfo)
        {
            $seats = $rowInfo['seats'];
            // Sort numerically ascending
            sort($seats);
	    
	    $validGroups = 0;
	    $seatFroms = array();
	    
            // Find $consecutiveCount in a row for each row
            for($seatNum = min($seats); $seatNum <= max($seats); $seatNum++)
            {
                $rangeToFind = range($seatNum, $seatNum+$consecutiveCount-1);
                if (count(array_intersect($rangeToFind, $seats)) == $consecutiveCount)
                {
		    // Start iterator on next seat from last
                    $seatNum += $consecutiveCount-1;
		    
		    // Increase incrementor for found valid groups
                    $validGroups++;
                    $seatFroms[] = $seatNum;
                }
            }
	    
	    // Valid groups threshold
	    if($validGroups >= $minGroupsThreshold)
	    {
		$validRows[$section][$row] = array(
		    "tmId" => $eventId,
		    "tmStatus" => "AVAILABLE",
		    "skyboxStatus" => "NOT ON SKYBOX",
		    "section" => $section,
		    "row" => $row,
		    "quantity" => $consecutiveCount,
		    "availability" => $validGroups,
		    "seatFroms" => implode(",", $seatFroms),
		    "ticketPrice" => $rowInfo['totalPrice']
		);
	    }
	    
        }
    }

    return $validRows;
}

function getValidGroups($eventId, $validRows)
{
    $validGroups = array();
    
    foreach($validRows AS $sectionName => $section)
    {
        foreach($section AS $rowName => $row)
        {
            if(is_array($row))
            {
                foreach($row AS $validGroup)
                {
                    // Push to groups to update
                    $validGroups[] = array(
                        "tmId" => $eventId,
                        "lowSeat" => $validGroup['seatFrom'],
                        "quantity" => $validGroup['seatTo'] - $validGroup['seatFrom'] + 1,
                        "section" => $sectionName,
                        "row" => $rowName,
                        "ticketPrice" => $validGroup['totalPrice'],
                        "status" => "AVAILABLE"
                    );
                }
            }
        }
    }
    
    return $validGroups;
}

function parseEventInfo($contents)
{
    $matches = array();
    preg_match("/storeUtils\['eventJSONData']=(\{.*\})/", $contents, $matches);
    $event = json_decode($matches[1], true);

    $event = array(
        "tmId" => $event['id'],
        "name" => $event['name'],
        "image" => $event['eventImageUrl'],
        "venue" => $event['venue']['name'],
        "date" => $event['formattedDateFull'],
        "datetime" => date("Y-m-d H:i:s", strtotime($event['seoEventDate'])),
        "lastUpdated" => date("Y-m-d H:i:s", time()),
        "contents" => $contents
    );

    return $event;
}

function getCredentials($contents)
{
    $matches = array();
    preg_match_all("/apiKey: \'([a-z0-9]*)\'/", $contents, $matches);
    $apiKey = $matches[1][1];

    $matches = array();
    preg_match_all("/apiSecret: \'([a-z0-9]*)\'/", $contents, $matches);
    $apiSecret = $matches[1][0];

    return array("apiKey" => $apiKey, "apiSecret" => $apiSecret);
}

function getOffers($contents)
{
    $offers = array();
    
    $matches = array();
    preg_match("/storeUtils\['eventOfferJSON']=(\[.*\])/", $contents, $matches);
    
    $offersJSON = json_decode($matches[1], true);
    
    // Parse each offer group
    foreach($offersJSON AS $offer)
    {
        $offers[$offer['offerId']] = array(
            'offerId' => $offer['offerId'],
            'name' => $offer['name'],
            'inventoryType' => $offer['inventoryType'],
	    'offerType' => $offer['offerType'],
            'section' => $offer['section'],
            'row' => $offer['row'],
            'seatFrom' => $offer['seatFrom'],
            'seatTo' => $offer['seatTo'],
            'listPrice' => $offer['listPrice'],
            'totalPrice' => $offer['totalPrice'],
            'quantity' => $offer['sellableQuantities'][0]
        );
    }
    
    return $offers;
}

// Grab TM page
function getTMEventPage($eventId, &$fromCache=false, $cacheTime="8 hour")
{
    $eventPageType = "eventPage";
    
   // Try to find most recent cache within $cacheTime
    $query = "select contents from cached where tmId='$eventId' and type='$eventPageType'and created > NOW() - interval $cacheTime ORDER BY created DESC";
    $result = DB::query($query);
    if(count($result))
    {
        error_log("Event $eventId: Retrieving from cache");
        $contents = $result[0]['contents'];
        $fromCache = true;
    }
    else 
    {
        $htmlURL = "https://www1.ticketmaster.com/event/$eventId?SREF=P_HomePageModule_main&f_PPL=true&ab=efeat5787v1";
	$agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
	
	$ghostKey = "5b74c5bed80211534379454";
	$proxyURL = "https://ghostproxies.com/proxies/api.json?key=$ghostKey";
	$proxyJSON = file_get_contents($proxyURL);
	$proxyList = json_decode($proxyJSON, true);
	
	$proxy = array_rand($proxyList['data']);
	$proxyIP = $proxy['panel_user'] . ":" . $proxy['panel_pass'] . "@" . $proxy['ip'] . ":" . $proxy['portNum'];
	
	error_log("Hitting $htmlURL to cache");
	
	$ch = curl_init();
	
        curl_setopt($ch, CURLOPT_PROXY, $proxy['ip']);
        curl_setopt($ch, CURLOPT_PORT, $proxy['portNum']);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['panel_user'] . ":" . $proxy['panel_pass']);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_URL,$htmlURL);
        $contents = curl_exec($ch);

	if($contents == "")
	{
	    return false;
	}
	
       // $contents = file_get_contents($htmlURL); 
	
        $cacheContents = array(
            "type" => $eventPageType,
            "contents" => $contents,
            "tmId" => $eventId,
	    "proxyUsed" => $proxy['ip']
        );
        
        // Cache event page contents
        DB::insert("cached", $cacheContents);
    }
    return $contents;
}

// Grab seat availability
function getFacets($eventId, $apiKey, $apiSecret, $cacheTime="30 minute")
{
    $eventPageType = "availability";
    
    $result = DB::query("select contents from cached where tmId='$eventId' and type='$eventPageType'and created > NOW() - interval $cacheTime ORDER BY created DESC");
    if(count($result))
    {
        //echo "Retrieving availability from cache";
        $contents = $result[0]['contents'];
    }
    else 
    {
        
        $offersURL = "https://services.ticketmaster.com/api/ismds/event/$eventId/facets?q=available&by=shape+attributes+available+accessibility+offer+placeGroups+inventoryType+offerType+description&show=places&embed=description&resaleChannelId=internal.ecommerce.consumer.desktop.web.browser.ticketmaster.us&unlock=&apikey=$apiKey&apisecret=$apiSecret";
        //echo "Retrieving facets from $offersURL<br>";
        error_log("Hitting $offersURL");
        $agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_URL,$offersURL);
        $contents = curl_exec($ch);
        
        $cacheContents = array(
            "type" => $eventPageType,
            "contents" => $contents,
            "tmId" => $eventId
        );
        
        // Cache event page contents
        DB::insert("cached", $cacheContents);
        
    }
    
    $link = json_decode($contents, true);
    $facets = $link['facets'];
    
    return $facets;    
}

function getEventSeats($eventId)
{
    $url = "https://mapsapi.tmol.io/maps/geometry/3/event/$eventId/placeDetailNoKeys?systemId=HOST&useHostGrids=true&app=CCP";
    $geometry = file_get_contents("https://mapsapi.tmol.io/maps/geometry/3/event/$eventId/placeDetailNoKeys?systemId=HOST&useHostGrids=true&app=CCP");
    error_log("Hitting $url");

    $seats = json_decode($geometry, true);
    
    $segments = $seats['pages'][0]['segments'];

    foreach($segments AS $outerSection)
    {
        if(is_array($outerSection['segments']))
        {
            foreach($outerSection['segments'] AS $innerSection)
            {
                foreach($innerSection['segments'] AS $row)
                {
                    foreach($row['placesNoKeys'] AS $seat)
                    {
                        $seats[$seat[0]] = array(
                            "section" => $innerSection['name'],
                            "row" => $row['name'],
                            "seat" => $seat[1]
                        );
                    }
                }
            }
        }
    }

    unset($seats['pages']);
    unset($seats['totalPlaces']);
    unset($seats['venueConfigId']);
    
    return $seats;
}

function getFilteredInventory($eventId, $maxPrice, $minGroups, $markup)
{
    // Build query
    $parameters = "";
    $parameters .= " and ticketPrice <= $maxPrice and availability >= $minGroups ";


    $query = "SELECT * "
	    . "FROM inventory "
	    . "WHERE tmId='$eventId' and tmStatus='AVAILABLE' and skyboxStatus != 'ON SKYBOX' and tmId='$eventId'
" . $parameters
	    . "ORDER BY section asc, row asc ";
    
    $inventory = DB::query($query);   

    return $inventory;
}

function redirect($url)
{
    header("Location: $url");
    exit;
}

function showNotification($type)
{
    if(isset($_SESSION['notifications'][$type]))
    {
	echo $_SESSION['notifications'][$type];
    }
    deleteNotification($type);
}

function storeNotification($type, $notification, $color="success")
{
    $notification = <<<EOT
	    <div class="alert alert-$color alert-dismissible fade in" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
                    </button>
                    <strong>$notification</strong>
                  </div>
EOT;
    
    $_SESSION['notifications'][$type] = $notification;
}

function deleteNotification($type)
{
    unset($_SESSION['notifications'][$type]);
}