<?php

ini_set('display_errors', TRUE);
error_reporting(E_ALL ^ E_NOTICE ^E_WARNING);

$path = realpath(dirname(__FILE__));

include($path . "meekrodb.2.3.class.php");
include($path . "functions.php");

// Classes
include($path . "classes/skybox.php");
include($path . "classes/constants.php");

$events = DB::query("
select tmId, lastCached, cacheTime
FROM
(
select e.tmId, e.datetime, max(created) as lastCached, e.cacheTime from events e left join cached c on e.tmId=c.tmId
group by e.tmId
) a
 where datetime >= NOW() and lastCached <= date_sub(now(), INTERVAL cacheTime minute) 
 order by lastCached ASC
limit 25
");
error_log("BEGIN CRON AUTO CHECK");

ob_end_flush();
  ob_implicit_flush();

foreach($events AS $event)
{
    echo "begin event: "; echo_memory_usage();
        echo "get event $event[tmId]";
    $e =  getEvent($event['tmId']);
    unset($e['tickets']);
unset($e);
echo "unset event: "; echo_memory_usage();
gc_collect_cycles();
echo "gc collect: "; echo_memory_usage();
    error_log("AUTO Checking event $event[tmId]");
}

?>
~    