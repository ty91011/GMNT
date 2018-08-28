<?php

include("include.php");

if(isset($_POST['eventId']))
{
    $eventId = $_POST['eventId'];
}
else if(isset($_GET['eventId']))
{
    $eventId = $_GET['eventId'];
}
else
{
    // Test ID
    $eventId = "0B00546E63D3145E";
}

// Defaults
$markup =  isset($_POST['markup']) && $_POST['markup'] != '' ? $_POST['markup'] : "20";
$minGroups = isset($_POST['minGroups']) && $_POST['minGroups'] != '' ? $_POST['minGroups'] : "2";
$minPrice = isset($_POST['minPrice']) && $_POST['minPrice'] != '' ? $_POST['minPrice'] : "0";
$maxPrice = isset($_POST['maxPrice']) && $_POST['maxPrice'] != '' ? $_POST['maxPrice'] : "1000000";

$event = getEvent($eventId);
   
$inventory = getFilteredInventory($event['tmId'], $maxPrice, $minGroups, $markup);

if(isset($_POST['UPLOAD']) && $_POST['UPLOAD'] != '')
{
    Skybox::uploadTickets($event, $maxPrice, $minGroups, $markup);
}

if(isset($_POST['map']) && $_POST['sbId'] != '')
{
    DB::query("UPDATE events SET sbId='$_POST[sbId]' WHERE tmId = '$eventId'");
    insertHistory($eventId, "Mapped Event to Skybox", "Mapped event $eventid: $event[name] @ $event[venue] on $event[datetime]");
}

?>

<?php
    include("sections/header.php");
    include("sections/sidebar.php");
    include("sections/navigation.php");

?>

 


        <!-- page content -->
        <div class="right_col" role="main">
            <?php include("sections/stats.php"); ?>
            <?php //include("sections/import.php"); ?>


                      <?php

                      $event = DB::queryFirstRow("SELECT * FROM events WHERE tmId='$eventId'");

                          ?>
	                <div class="x_panel">
                <div class="row x_title">
                    <div class="col-md-3">
                        <img src='<?=$event[image];?>'>
                    </div>
                    <div class="col-md-6">
                        <?php echo "<h3>$event[name]<br>$event[venue]<br>$event[date]</h3><small><a target='_blank' href='https://www1.ticketmaster.com/event/$event[tmId]?SREF=P_HomePageModule_main&f_PPL=true&ab=efeat5787v1'>TICKETMASTER LINK</a></small>"
				. "<br><small><a target='_blank' href='http://www.vividseats.com/shop/viewTickets.shtml?productionId=$event[sbId]'>VIVID SEATS LINK</a></small>"; ?>
                    </div>
                </div>
			    <?php 
			    $sbId = DB::queryFirstField("SELECT sbId FROM events WHERE tmId='$eventId'");

			    if(is_null($sbId) || $sbId == "" )
			    { 
				?>
			    
			    <div class="x_content" style='background-color: yellow'>
				<div class="col-md-12">
				   <?php
				   
				   $results = Skybox::searchEvents($event, $_POST['venue'], $_POST['datetime'], $_POST['name']);
				   ?>
				    <h3>Mapping Search Filters</h3>
				    <form method="POST">
					<div class="form-group">
					    <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">Event Name
					    </label>
					    <div class="col-md-6 col-sm-6 col-xs-12">
					      <input type="text" name="name" class="form-control col-md-7 col-xs-12" value="<?php echo isset($_POST['name']) ? $_POST['name'] : $event['name']; ?>">
					    </div>
					  </div>
					<br><br>
					    <div class="form-group">
					    <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">Venue
					    </label>
					    <div class="col-md-6 col-sm-6 col-xs-12">
					      <input type="text" name="venue" class="form-control col-md-7 col-xs-12" value="<?php echo isset($_POST['venue']) ? $_POST['venue'] : $event['venue']; ?>">
					    </div>
					  </div>
					<br><br>
					<div class="form-group">
					    <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">Datetime
					    </label>
					    <div class="col-md-6 col-sm-6 col-xs-12">
					      <input type="text" name="datetime" class="form-control col-md-7 col-xs-12" value="<?php echo isset($_POST['datetime']) ? $_POST['datetime'] : $event['datetime']; ?>">
					    </div>
					  </div>
					<br><br><input type=submit name='search' value='Search for Mappings' class='btn-success'>
				    </form>
				    <div class="ln_solid"></div>
				    <h3>Mapping Search Results</h3><br />
				    <?php
				   if(count($results['rows']))
				    {
					foreach($results['rows'] AS $row)
					{
					    echo "<form method=POST><input type=submit name='map' value='MAP IT' class='btn-danger'>&nbsp;&nbsp;<input type='hidden' name='sbId' value='$row[id]' />$row[name] @ " . $row['venue']['name'] . " ON $row[date]</form>";
					}
				    }
				    else {
					echo "No results from auto mapping";
				    }
				   ?>
				</div>
			    </div>
			    <?php } ?>
	    </div>
	    <div class="x_panel">
                <div class="row x_title">
		    <?php showNotification("exportSkybox"); ?>
                    <div class="col-md-3">
                        <h3>Upload to Skybox</h3>
                    </div>
                </div>
		<div class="row">
                  <div class="col-md-12 col-sm-12 col-xs-12">
                      <div class="x_content">
			  
			  <?php include("sections/form.php"); ?>
		      </div>
		  </div>
		<div class="col-md-12 col-sm-12 col-xs-12">
                      <div class="x_content">
			  <br />

				<table id="datatable-buttons" class="table table-striped table-bordered bulk_action">
				      <thead>
					<tr>
					    <th><input type="checkbox" id="check-all" class="flat">
					  <th>Section</th>
					  <th>Row</th>
					  <th>Ticket Price</th>
					  <th>Markup @ <?php echo $markup . '%'; ?></th>
					  <th>Total Price</th>
					  <th>Availability</th>
					  <th>Low Seats</th>
					  <th>Last Updated</th>
					</tr>
				      </thead>


				      <tbody>
					<?php
					
					//echo $query;
					foreach($inventory AS $inventoryItem)
					{
					    echo "
					    <tr>
						<td class='a-center '>
						    <input disabled type='checkbox' value='$inventoryItem[id]' class='flat' name='table_records[]'>
						</td>
						<td>$inventoryItem[section]</td>
						<td>$inventoryItem[row]</td>
						<td>$inventoryItem[ticketPrice]</td>
						<td>" . round($inventoryItem[ticketPrice] * $markup/100, 2) . "</td>
						<td>" . ($inventoryItem['ticketPrice'] + round($inventoryItem['ticketPrice'] * $markup/100, 2)) . "</td>
						<td>$inventoryItem[availability]</td>
						<td>$inventoryItem[seatFroms]</td>
						<td>$inventoryItem[lastUpdated]</td>
					    </tr>
					    ";
					}
					?>

				      </tbody>
				</table>
			

		      </div>
		  </div>
		</div>
	    </div>


        </div>

          <br />
          

<?php include("sections/footer.php"); ?>