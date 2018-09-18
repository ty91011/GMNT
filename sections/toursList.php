                        <table id="datatable" class="table table-striped table-bordered" data-page-length='25'>
                              <thead>
                                <tr>
				    <th>Manage Tour</th>
				    <th>Mapped</th>
                                  <th>Tour Name</th>
                                  <th>Available Inventory</th>
                                  <th>On Skybox</th>
                                  <th>Next Event</th>
                                </tr>
                              </thead>


                              <tbody>
                                <?php

                                $tours = DB::query("select tour, min(datetime) as nextEvent, sum(case when skyboxStatus='ON SKYBOX' then 1 else 0 end) skyboxTickets, count(i.id) as inventory, count(distinct e.id) as events, count(distinct e.sbid) as mapped
				    from events e left join inventory i on e.tmId = i.tmId
				    where tour is not null and i.tmStatus='AVAILABLE' and e.datetime > NOW()
				    group by tour
				    order by tour asc;
				    ");
                                foreach($tours AS $tour)
                                {
				    $timeTilEvent = floor((strtotime($tour['nextEvent']) - time())/86400) . " days";

                                    echo "
                                    <tr>
					<td><a href='/tour.php?tour=$tour[tour]'><button type='button' class='btn btn-success btn-sm'>MANAGE</button></a><td>
					    ";
				    
				    $color = ($tour['mapped'] == $tour['events']) ? "green" : "red";
				    echo "<span style='color: $color'>$tour[mapped] / $tour[events]</span>";

				    
				    echo " </td>
                                        <td>$tour[tour]</td>
                                        <td>$tour[inventory]</td>
					    <td>$tour[skyboxTickets]</td>
					
					    <td>$timeTilEvent<br>$tour[nextEvent]</td>
					 </tr>
                                    ";
                                }
                                ?>

                              </tbody>
                        </table>