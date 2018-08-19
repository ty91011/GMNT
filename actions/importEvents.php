<?php

include("../include.php");

if(isset($_POST['importEventIds']))
{
    // Import IDS
    $parts = preg_split('/\s+/', $_POST['importEventIds']);
    
    $events = array();
    foreach($parts AS $key => $eventId)
    {
	$event = getEvent($eventId);
	
	// Weed out invalid events
	if($event)
	{
	    $events[] = $event;
	}
    }
    $importCount = count($events);
    
    $importAlert =  "Holy guacamole!</strong> Imported $importCount Event(s)<br><ul>";
    
    foreach($events AS $event)
    {
	$importAlert .= "<li>$event[name] @ $event[venue] ON $event[date]</li>";
    }
    $importAlert .= "</ul>";
    
    storeNotification("importAlert", $importAlert);
}

redirect("/index.php");