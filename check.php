<?php

include("meekrodb.2.3.class.php");
include("functions.php");

// Classes
include("classes/skybox.php");

$events = DB::query("
select tmId, lastCached
FROM
(
select e.tmId, e.datetime, max(created) as lastCached from events e left join cached c on e.tmId=c.tmId
group by e.tmId
) a
 where datetime >= NOW() and lastCached <= date_sub(now(), INTERVAL 1 hour) 
limit 5
");
error_log("BEGIN CRON AUTO CHECK");

foreach($events AS $event)
{
        echo "get event $event[tmId]";
    $e =  getEvent($event['tmId']);
unset($e);
    error_log("AUTO Checking event $event[tmId]");
}

?>
~    