<?php

include("include.php");

if(isset($_GET['eventId']))
{
    $eventId = $_GET['eventId'];
    $event = getEvent($eventId);
}
else
{
    // Test ID linsey sterling 9/1/2018
    $eventId = "0B00546E63D3145E";
}

?>

<?php
    include("sections/header.php");
    include("sections/sidebar.php");
    include("sections/navigation.php");

?>

 


        <!-- page content -->
        <div class="right_col" role="main">
            <?php //include("sections/tiles.php"); ?>
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
                        <?php echo "<h3>$event[name]<br>$event[venue]<br>$event[date]</h3><small><a target='_blank' href='https://www1.ticketmaster.com/event/$event[tmId]?SREF=P_HomePageModule_main&f_PPL=true&ab=efeat5787v1'>TICKETMASTER LINK</a></small>"; ?>
                    </div>
                </div>
              <div class="row">
                  <div class="col-md-12 col-sm-12 col-xs-12">
                      <div class="x_content">
                        <table id="datatable-buttons" class="table table-striped table-bordered bulk_action">
                              <thead>
                                <tr>
                                    <th><input type="checkbox" id="check-all" class="flat"></th>
                                  <th>Section</th>
                                  <th>Row</th>
                                  <th>Ticket Price</th>
                                  <th>Status</th>
                                  <th>Number of Groups</th>
                                  <th>Low Seats</th>
                                  <th>Last Updated</th>
                                </tr>
                              </thead>


                              <tbody>
                                <?php

                                $groups = DB::query("SELECT section, row, ticketPrice, status, GROUP_CONCAT(lowSeat) as lowSeats, count(1) as numGroups, max(lastUpdated) as lastUpdated FROM groups WHERE tmId='$event[tmId]' GROUP BY status, section, row, ticketPrice ORDER BY section asc, row asc LIMIT 5");
                                foreach($groups AS $group)
                                {
                                    echo "
                                    <tr>
                                        <td class='a-center '>
                                            <input type='checkbox' class='flat' name='table_records'>
                                        </td>
                                        <td>$group[section]</td>
                                        <td>$group[row]</td>
                                        <td>$group[ticketPrice]</td>
                                        <td>$group[status]</td>
                                        <td>$group[numGroups]</td>
                                        <td>$group[lowSeats]</td>
                                        <td>$group[lastUpdated]</td>
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