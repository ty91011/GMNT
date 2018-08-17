<?php
    // Get stats
$query = "
        select sum(availability) as num, round(avg(ticketPrice),2) as avgPrice, sum(case when skyboxStatus='ON SKYBOX' then 1 else 0 end) as skybox, max(lastUpdated) as lastUpdated
        from inventory
        where tmId='$eventId'      
    ";
    $data = DB::queryFirstRow($query);
    
    $timeTilEvent = floor((strtotime($event['datetime']) - time())/86400) . " days";
?>

<!-- top tiles -->
          <div class="row tile_count">
	    <div class="col-md-2 col-sm-4 col-xs-6 tile_stats_count">
              <span class="count_top"><i class="fa fa-user"></i> Ticket Groups in Skybox</span>
              <div class="count blue"><?php echo $data['skybox']; ?></div>
            </div>
            <div class="col-md-2 col-sm-4 col-xs-6 tile_stats_count">
              <span class="count_top"><i class="fa fa-user"></i> Total Available Ticket Groups</span>
              <div class="count"><?php echo $data['num']; ?></div>
            </div>
            <div class="col-md-2 col-sm-4 col-xs-6 tile_stats_count">
              <span class="count_top"><i class="fa fa-dollar"></i> Average Price</span>
              <div class="count"><?php echo "$" . $data['avgPrice']; ?></div>
              <!--<span class="count_bottom"><i class="green"><i class="fa fa-sort-asc"></i>3% </i> From last Week</span>-->
            </div>
            <div class="col-md-2 col-sm-4 col-xs-6 tile_stats_count">
              <span class="count_top"><i class="fa fa-clock-o"></i> Time Til Event</span>
              <div class="count"><?php echo $timeTilEvent; ?></div>
            </div>
	    <div class="col-md-2 col-sm-4 col-xs-6 tile_stats_count">
              <span class="count_top"><i class="fa fa-clock-o"></i> Last Updated</span>
              <div class="count"><?php echo $data['lastUpdated']; ?></div>
            </div>
          </div>
          <!-- /top tiles -->