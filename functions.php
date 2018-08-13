<?php

function getEvent($eventId, $force=false)
{
    $fromCache = false;
    if($force)
    {
        $contents = getTMEventPage($eventId, $fromCache, "0 minute");
    }
    else
    {
        $contents = getTMEventPage($eventId, $fromCache);
    }
    
    // Grab event data
    $event = getEventInfo($contents);
    
    // Grab all offers for event
    $offers = getOffers($contents);
    
    // Grab all seats in venue
    $seats = getEventSeats($eventId);
    
    // Get credentials
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

        if(!$seat['seat'] || $seat['offer']['inventoryType'] != 'primary')
        {
            continue;
        }

        $tickets[] = array(
            "tmId" => $seatId,
            "section" => $seat['section'],
            "row" => $seat['row'],
            "seatFrom" => $seat['seat'],
            "seatTo" => $seat['seat'],
            "listPrice" => $seat['offer']['listPrice'],
            "totalPrice" => $seat['offer']['totalPrice'],
            "totalQuantity" => 1,
            "status" => "AVAILABLE",
            "eventId" => $eventId
        );
    }

    // Refresh all data sets
    if(!$fromCache)
    {
        // Cache event, page, and availability
        DB::insertUpdate("events", $event);
        
        // Set tickets to UNAVAILABLE so that we know which tickets will not be updated upon availability, this also freezes "updated" time in the DB to when the ticket was made unavailable
        DB::query("UPDATE tickets SET status='UNAVAILABLE' WHERE tmId='$eventId' and status = 'AVAILABLE'");
        echo "updating tickets to unavailabile<br>";
    
        // Bulk insert/update check
        DB::insertUpdate('tickets', $tickets, array("status" => "AVAILABLE"));
        echo "updating tickets to availabile<br>";
        
        
    }
    updateTicketGroups($eventId, $tickets);
    $event['tickets'] = $tickets;
    
    
    
    return $event;
}

function updateTicketGroups($eventId, $tickets = array())
{
    $validRows = getValidRows($tickets);
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
    unset($validRows['10-H'][54]);
    DB::insertIgnore("groups", $validGroups);
    
    // Check for now invalid rows
    $dbRows = DB::query("SELECT distinct section, row, status from groups where tmId='$eventId' and status='ON SKYBOX'");
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
            echo "INVALID $dbRow[section]:$dbRow[row]<br>";
            
            DB::query("UPDATE groups SET status='PENDING SKYBOX REMOVAL' WHERE section='$dbRow[section]' and row='$dbRow[row]' and tmId='$eventId'");
            
            // Take down from Skybox
            // TODO: 
            
            
            // Update status
            DB::query("UPDATE groups SET status='REMOVED FROM SKYBOX' WHERE section='$dbRow[section]' and row='$dbRow[row]' and tmId='$eventId'");
        }
    }

}

function getValidRows($tickets = array(), $consecutiveCount = 4, $minGroupsThreshold = 2)
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
            
            // Find $consecutiveCount in a row for each row
            for($seatNum = min($seats); $seatNum <= max($seats); $seatNum++)
            {
                $rangeToFind = range($seatNum, $seatNum+$consecutiveCount-1);
                if (count(array_intersect($rangeToFind, $seats)) == $consecutiveCount)
                {
                    //echo "Found consecutive from $seatNum to " . ($seatNum+$consecutiveCount-1) . "<br>";
                    $seatNum += $consecutiveCount-1;
                    
                    $validRows[$section][$row][] = array("seatFrom" => $seatNum, "seatTo" => $seatNum+$consecutiveCount-1, "totalPrice" => $rowInfo['totalPrice']);
                }
            }
        }
    }

    foreach($validRows AS $sectionKey => $section)
    {
        foreach($section AS $rowKey => $rows)
        {
            if(count($rows) < $minGroupsThreshold)
            {
                unset($validRows[$sectionKey][$rowKey]);
                continue;
            }
        }
    }
    
    return $validRows;
}

function getEventInfo($contents)
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
    $result = DB::query("select contents from cached where tmId='$eventId' and type='$eventPageType'and created > NOW() - interval $cacheTime ORDER BY created DESC");
    if(count($result))
    {
        //echo "Retrieving from cache";
        $contents = $result[0]['contents'];
        $fromCache = true;
    }
    else 
    {
        $htmlURL = "https://www1.ticketmaster.com/event/$eventId?SREF=P_HomePageModule_main&f_PPL=true&ab=efeat5787v1";
        $contents = file_get_contents($htmlURL); 
        
        $cacheContents = array(
            "type" => $eventPageType,
            "contents" => $contents,
            "tmId" => $eventId
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