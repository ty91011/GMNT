<?php

include("include.php");

$events = DB::query("select * from events where datetime >= NOW()");

error_log("BEGIN CRON AUTO CHECK");

foreach($events AS $event)
{
    getEvent($event['tmId']);
    error_log("AUTO Checking event $event[tmId]");
}

?>