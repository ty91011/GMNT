<?php
include("include.php");

if(isset($_GET['keyword']))
{
    $keyword = $_GET['keyword'];
    $url = "https://app.ticketmaster.com/discovery/v2/events.json?apikey=AkelJvQ7d8AxT8ECIORrZDsNkSl6yuHr&keyword=$keyword";    
    echo $url;
    $json = file_get_contents($url);
    $json = json_decode($json);

    $events = $json->_embedded->events;
    foreach($events AS $event)
    {
	$urlParts = explode("/", $event->url);
	$tmId = array_pop($urlParts);
	echo "TMID: $tmId<br>Name: {$event->name}<br>";
	$venue = $event->_embedded->venues[0];
	echo "Veune: {$venue->name}<br>";
	$date = $event->dates->start->localDate;
	$time = $event->dates->start->localTime;
	echo "Datetime: $date $time<hr>";
    }
    
}

?>

<form method="GET">
    <input type="textbox" name="keyword" value="<?php echo $_GET['keyword'];?>"/>
    <input type="submit" />
</form>

<?php

