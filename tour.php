<?php

include("include.php");

if(isset($_POST['tour']))
{
    $tour = $_POST['tour'];
}
else if(isset($_GET['tour']))
{
    $tour = $_GET['tour'];
}
else
{
    // Test ID
    $tour = "Taylor Swift - Reputation Stadium Tour";
}

// Defaults
$markup =  isset($_POST['markup']) && $_POST['markup'] != '' ? $_POST['markup'] : "20";
$minGroups = isset($_POST['minGroups']) && $_POST['minGroups'] != '' ? $_POST['minGroups'] : "2";
$minPrice = isset($_POST['minPrice']) && $_POST['minPrice'] != '' ? $_POST['minPrice'] : "0";
$maxPrice = isset($_POST['maxPrice']) && $_POST['maxPrice'] != '' ? $_POST['maxPrice'] : "1000000";
$maxRows = isset($_POST['maxRows']) && $_POST['maxRows'] != '' ? $_POST['maxRows'] : "2";

if(isset($_POST['UPLOAD']) && $_POST['UPLOAD'] != '')
{
    Skybox::uploadTourTickets($tour, $maxPrice, $minGroups, $markup, $maxRows);
    
    // Get newly updated event
    $event = getEvent($eventId);

}

$inventory = getFilteredTourInventory($tour, $maxPrice, $minGroups, $markup, $maxRows);

?>

<?php
    include("sections/header.php");
    include("sections/sidebar.php");
    include("sections/navigation.php");

?>

 


        <!-- page content -->
        <div class="right_col" role="main">
            <?php //include("sections/stats.php"); ?>
            <?php //include("sections/import.php"); ?>


                      <?php

                      $tour = DB::queryFirstRow("SELECT tour, count(1), sum(case when datetime > NOW() then 1 else 0 end) as futureEvents, count(distinct sbid) as mapped  
, (select image from events where tour = '$tour' order by datetime asc limit 1) as image
FROM events 
WHERE tour ='$tour' and datetime > NOW() 
group by tour");
                          ?>
	                <div class="x_panel">
                <div class="row x_title">
                    <div class="col-md-3">
                        <img src='<?=$tour[image];?>'>
                    </div>
                    <div class="col-md-6">
                        <?php echo "<h3>$tour[tour]</h3>"; ?>
                    </div>
                </div>

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

				<table id="datatable-buttons" class="table table-striped table-bordered bulk_action" data-page-length='100'>
				      <thead>
					<tr>
					    <th><input type="checkbox" id="check-all" class="flat">
					    <th>Event Details</th>
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
					    $vivid = $inventoryItem['vividPrice'] > 0 ? "<br>(V) $inventoryItem[vividPrice]" : "";

					    echo "
					    <tr>
						<td class='a-center '>
						    <input disabled type='checkbox' value='$inventoryItem[id]' class='flat' name='table_records[]'>
						</td>
						<td>$inventoryItem[venue]<br>$inventoryItem[datetime]</td>
						<td>$inventoryItem[section]</td>
						<td>$inventoryItem[row]</td>
						<td>$inventoryItem[ticketPrice]</td>
						<td>" . round($inventoryItem[ticketPrice] * $markup/100, 2) . "</td>
						<td>" . ($inventoryItem['ticketPrice'] + round($inventoryItem['ticketPrice'] * $markup/100, 2)) . $vivid . "</td>
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