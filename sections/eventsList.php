                        <table id="datatable" class="table table-striped table-bordered">
                              <thead>
                                <tr>
				    <th>Edit</th>
				    <th>Mapped</th>
                                  <th>Name</th>
                                  <th>Venue</th>
                                  <th>Time Til Event</th>
                                  
                                  <th>Available Ticket Groups</th>
                                  <th>Average Price</th>
                                  <th>Skybox</th>
				  
				  <th>Last Updated</th>
                                </tr>
                              </thead>


                              <tbody>
                                <?php

                                $events = DB::query("SELECT e.*, coalesce(num, 0) as num, coalesce(avgPrice, 'NA') avgPrice, coalesce(skybox, 0) skybox, coalesce(tickets, 0) tickets, coalesce(rows, 0) rows, coalesce(platinum,0) platinum
						    FROM events e 
							    left join 
							    (
								    select tmId s, count(1) as rows, sum(coalesce(availability,0)) as num, round(avg(ticketPrice),2) as avgPrice, sum(case when skyboxStatus='ON SKYBOX' then 1 else 0 end) as skybox
								    from inventory
								    group by tmId
							    ) i
							    on e.tmId = i.s
							    left join 
							    (
							    	select tmId, count(tmId) as tickets, sum(case when offerType='platinum' then 1 else 0 end) platinum
							    	from tickets
							    	group by tmId
							    ) t
							    ON e.tmId = t.tmId
						    GROUP BY e.id
						    ORDER BY e.id DESC");
                                foreach($events AS $event)
                                {
				    $timeTilEvent = floor((strtotime($event['datetime']) - time())/86400) . " days";
				    if($event['platinum'] > 0)
				    {
					$platinum = "<br>$event[platinum] platinum tickets available!";
				    }
				    else
				    {
					$platinum = "";
				    }

                                    echo "
                                    <tr>
					<td><a href='/event.php?eventId=$event[tmId]'><button type='button' class='btn btn-success btn-sm'>EDIT</button></a><td>
					    ";
				    echo $event['sbId'] == "" ? "<span class='glyphicon glyphicon-remove' style='color: red'></span>" : "<span class='glyphicon glyphicon-ok' style='color: green'></span>";
				    echo " </td>
                                        <td>$event[name]$platinum</td>
                                        <td>$event[venue]<br>$event[date]</td>
                                        <td>$timeTilEvent</td>
                                        <td>$event[num] groups<br>in $event[rows] rows<br>out of $event[tickets] tickets</td>
                                        <td>$event[avgPrice]</td>
                                        <td>$event[skybox]</td>
					
					    <td>$event[lastUpdated]</td>
                                    </tr>
                                    ";
                                }
                                ?>

                              </tbody>
                        </table>