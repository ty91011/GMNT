<?php

include("../include.php");

if(isset($_POST['importEventIds']))
{
    // Import IDS
    $parts = preg_split('/\s+/', $_POST['importEventIds']);
    

    
    $importCount = 0;
    $events = array();
    foreach($parts AS $key => $eventId)
    {
	$event = getEvent($eventId);
	
	// Weed out invalid events
	if($event)
	{
	    $importCount++;
	}
	unset($event);
    }
    
    // Update tour info for events
    if(isset($_POST['tour']))
    {
	$query = "UPDATE events SET tour=$tour WHERE tmId IN (" . implode(",", $parts);
	DB::query($query);
    }
    
    $importAlert =  "Holy guacamole!</strong> Imported $importCount Event(s)<br><ul>";
    
    foreach($events AS $event)
    {
	$importAlert .= "<li>$event[name] @ $event[venue] ON $event[date]</li>";
    }
    $importAlert .= "</ul>";
    
    storeNotification("importAlert", $importAlert);
}

redirect("/index.php");