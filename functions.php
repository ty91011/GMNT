<?php

function getEvent($eventId, $force=false)
{
    $result = DB::query("SELECT * FROM events WHERE tmId='$eventId'");
    if(count($result))
    {

	$event = $result[0];
	/*
	$tickets = DB::query("SELECT * FROM tickets WHERE tmId='$eventId'");
	$event['tickets'] = $tickets;
	 * 
	 */
    }
    // Event does not exist yet
    else
    {
	// Get basic event Info
	$contents = getTMEventPage($eventId);
	
	// Invalid Event or cannot retrieve TM page
	if(!$contents)
	{
	    error_log("could not retrieve event id: $eventId");
	    return false;
	}
	
	$event = parseEventInfo($contents);
	

	if($event['tmId'] === null)
	{
	   $string = preg_replace('/\s+/', '', $contents);

	}
	// Insert Event into DB
	DB::insertUpdate("events", $event);
	insertHistory($eventId, "New Event Added", "Added <strong>$event[name]</strong> @ $event[venue] on $event[datetime]");
	
	unset($contents);
	$contents = null;
    }

    // Grab all needed information from TM's page contents
    $event = populateEvent($event, $force);

    return $event;
}

function populateEvent($event, $force=false)
{
    $eventId = $event['tmId'];
    
    $fromCache = false;
    
    $contents = getTMEventPage($eventId, $fromCache, $force);
    if(!$contents)
    {
	error_log("error with getting TM Event Page: $eventId");
	return false;
    }
    
    // Grab all offers for event
    $offers = getOffers($contents);

    // Grab all seats in venue
    $allSeats = getEventSeats($eventId);

    // Get TM API credentials
    $credentials = getCredentials($contents);	

    // Get available seats
    $facets = getFacets($eventId, $credentials['apiKey'], $credentials['apiSecret'], $force);

    if(!$facets)
    {
	error_log("error with getting TM facets Page: $eventId");
	return false;
    }
    
    $availableSeats = array();
    foreach($facets AS $facet)
    {
	foreach($facet['places'] AS $seatId)
	{
	    // Populate seat information
	    $availableSeats[$seatId] = $allSeats[$seatId];
	    
	    // Populate seat offer information
	    $availableSeats[$seatId]['offer'] = $offers[$facet['offers'][0]];
	}
    }

    unset($facets);
    $facets = null;

    
    // Remove not available seats from arena
    $tickets = array();

    foreach($availableSeats AS $seatId => $seat)
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

    unset($allSeats);
    $allSeats = null;
    unset($availableSeats);
    $availableSeats = null;

    // Refresh all data sets
    if(!$fromCache || $force)
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
    error_log("HISTORY LOG for $tmId: ($type) $status");
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
    
    // Not valid TM event contents page
    if(count($matches) == 0)
    {
	return false;
    }
    $event = json_decode($matches[1], true);

    $event = array(
        "tmId" => $event['id'],
        "name" => $event['name'],
        "image" => $event['eventImageUrl'],
        "venue" => $event['venue']['name'],
        "date" => $event['formattedDateFull'],
        "datetime" => date("Y-m-d H:i:s", strtotime($event['seoEventDate'])),
        "lastUpdated" => date("Y-m-d H:i:s", time()),
        "contents" => $contents,
	"cacheTime" => constants::DEFAULT_CACHE_TIME_MINUTES
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

function getProxy()
{
    $ghostKey = "5b74c5bed80211534379454";
    $proxyURL = "https://ghostproxies.com/proxies/api.json?key=$ghostKey";
    $proxyJSON = file_get_contents($proxyURL);
    $proxyList = json_decode($proxyJSON, true);
    $proxyRand = array_rand($proxyList['data']);
    $proxy = $proxyList['data'][$proxyRand]['Proxy'];
    //$proxyIP = $proxy['panel_user'] . ":" . $proxy['panel_pass'] . "@" . $proxy['ip'] . ":" . $proxy['portNum'];
    
    return $proxy;
}

// Grab TM page
function getTMEventPage($eventId, &$fromCache=false, $force=false)
{
    $eventPageType = "eventPage";
    
   // Try to find most recent cache within $cacheTime
    //$query = "select contents from cached where tmId='$eventId' and type='$eventPageType'and created > NOW() - interval $cacheTime ORDER BY created DESC";
    $query = "select c.contents from cached c left join events e on c.tmId=e.tmId where c.tmId='$eventId' and type='$eventPageType'and created > NOW() - interval e.cacheTime minute ORDER BY created DESC";
    $result = DB::query($query);
    if(count($result) && !$force)
    {
        error_log("Event $eventId: Retrieving from cache");
        $contents = $result[0]['contents'];
        $fromCache = true;
    }
    else 
    {
        $htmlURL = "https://www1.ticketmaster.com/event/$eventId?SREF=P_HomePageModule_main&f_PPL=true&ab=efeat5787v1";
	$agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
	
	$proxy = getProxy();

	error_log("Hitting $htmlURL to cache");
	
	$ch = curl_init();
	
        curl_setopt($ch, CURLOPT_PROXY, $proxy['ip'] . ":" . $proxy['portNum']);
        // Weird setting to port 80 made it to go to 1080
	//curl_setopt($ch, CURLOPT_PORT, $proxy['portNum']);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['panel_user'] . ":" . $proxy['panel_pass']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_URL,$htmlURL);
        $contents = curl_exec($ch);

	if($contents == "" || !parseEventInfo($contents))
	{
	    error_log("ERROR RETRIEVING TM EVENT PAGE for $eventId with Proxy $proxy[ip]: $contents");
	    return false;
	}
	
       // $contents = file_get_contents($htmlURL); 
	$proxyIP = $proxy['panel_user'] . ":" . $proxy['panel_pass'] . "@" . $proxy['ip'] . ":" . $proxy['portNum'];
	
        $cacheContents = array(
            "type" => $eventPageType,
            "contents" => $contents,
            "tmId" => $eventId,
	    "proxyUsed" => $proxyIP
        );
	
        // Cache event page contents
        DB::insert("cached", $cacheContents);

	// Update last cached time
	$lastCached = date("Y-m-d H:i:s");
	DB::update("events", array('lastCached' => $lastCached), "tmId='$eventId'");
    }
    return $contents;
}

// Grab seat availability
function getFacets($eventId, $apiKey, $apiSecret, $force=false)
{
    $eventPageType = "availability";
    
    $query = "select c.contents from cached c left join events e on c.tmId=e.tmId where c.tmId='$eventId' and type='$eventPageType'and created > NOW() - interval e.cacheTime minute ORDER BY created DESC";
    
    $result = DB::query($query);
    
    if(count($result) && !$force)
    {
        error_log("Retrieving availability from cache");
        $contents = $result[0]['contents'];
    }
    else 
    {
        
        $offersURL = "https://services.ticketmaster.com/api/ismds/event/$eventId/facets?q=available&by=shape+attributes+available+accessibility+offer+placeGroups+inventoryType+offerType+description&show=places&embed=description&resaleChannelId=internal.ecommerce.consumer.desktop.web.browser.ticketmaster.us&unlock=&apikey=$apiKey&apisecret=$apiSecret";
        //echo "Retrieving facets from $offersURL<br>";
	
	error_log("Hitting $offersURL");
	$contents = file_get_contents($offersURL);
	
        /*
        $agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
        
	$proxy = getProxy();
	
        curl_setopt($ch, CURLOPT_PROXY, $proxy['ip']);
        curl_setopt($ch, CURLOPT_PORT, $proxy['portNum']);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['panel_user'] . ":" . $proxy['panel_pass']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_URL,$offersURL);
	curl_setopt($ch, CURLOPT_HEADER, array("X-Api-Key: $apiKey"));
        $contents = curl_exec($ch);
	*/
	if($contents == "")
	{
	    return false;
	}
        
	$proxyIP = $proxy['panel_user'] . ":" . $proxy['panel_pass'] . "@" . $proxy['ip'] . ":" . $proxy['portNum'];
	
        $cacheContents = array(
            "type" => $eventPageType,
            "contents" => $contents,
            "tmId" => $eventId,
	    "proxyUsed" => $proxyIP
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

    $seats = array();
    $seatsJSON = json_decode($geometry, true);
    unset($geometry);
    
    $segments = $seatsJSON['pages'][0]['segments'];
    unset($seatsJSON);
    $seatsJSON = null;
    
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

    unset($segments);
    
    unset($seats['pages']);
    unset($seats['totalPlaces']);
    unset($seats['venueConfigId']);
    
    return $seats;
}

function getFilteredInventory($eventId, $maxPrice, $minGroups, $markup, $maxRows)
{
    // Build query
    $parameters = "";
    $parameters .= " and ticketPrice <= $maxPrice and availability >= $minGroups ";

    $query = "
	select t1.*, t2.price as vividPrice
	from
	(
	    select *, @row_number :=CASE when @section = section then @row_number+1 else 1 end as a, @section := section 
	    from inventory 
	    where tmId='$eventId' and tmStatus='AVAILABLE' and skyboxStatus != 'ON SKYBOX' $parameters
	    order by section asc, row asc
	) t1  left join vividComps t2 on t1.tmId=t2.tmId and t1.section=t2.section and t1.row=t2.row
	where a <= $maxRows
	order by t1.section asc, t1.row asc
     ";

    $mysqli = mysqli_connect("db.gmntt.com", "gmntt", "Chester123!@#", "gmntt");
    if ($mysqli->connect_errno) {
	printf("Connect failed: %s\n", $mysqli->connect_error);
	exit();
    }

    $res = mysqli_query($mysqli, "set @row_number:=1");
    $res = mysqli_query($mysqli, "set @section := '1'");
    $res = mysqli_query($mysqli, $query);

    $inventory = array();
    while($row = $res->fetch_assoc())
    {
	$inventory[] = $row;
    }

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

    function echo_memory_usage() { 
        $mem_usage = memory_get_usage(true); 
        
        if ($mem_usage < 1024) 
            echo $mem_usage." bytes"; 
        elseif ($mem_usage < 1048576) 
            echo round($mem_usage/1024,2)." kilobytes"; 
        else 
            echo round($mem_usage/1048576,2)." megabytes"; 
            
        echo "<br/>\n"; 
    } 