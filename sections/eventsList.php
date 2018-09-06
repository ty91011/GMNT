                        <table id="datatable" class="table table-striped table-bordered" data-page-length='25'>
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
				  
				  <th>Last Cached</th>
                                </tr>
                              </thead>


                              <tbody>
                                <?php

                                $events = DB::query("SELECT e.*, coalesce(num, 0) as num, coalesce(avgPrice, 'NA') avgPrice, coalesce(skybox, 0) skybox, coalesce(tickets, 0) tickets, coalesce(rows, 0) rows, coalesce(platinum,0) platinum, case when datetime > NOW() then 1 else 0 end as future, case when datetime > NOW() and datetime < date_add(NOW(), interval 4 day) then 1 else 0 end as critical
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
						    WHERE e.datetime > NOW()
						    GROUP BY e.id
						    ORDER BY future desc, critical desc, skybox desc, e.id DESC");
                                foreach($events AS $event)
                                {
				    $timeTilEvent = floor((strtotime($event['datetime']) - time())/86400) . " days";
				    
				    $criticalDays = constants::CRITICAL_DAYS_TIL_EVENT;
				    $criticalAvailablePercentage = constants::CRITICAL_AVAILABILITY;
				    
				    $daysStyle = floor((strtotime($event['datetime']) - time())/86400) <= $criticalDays ? "style='border: 3px solid red'" : "";
				    $skyboxStyle = $event['skybox'] > 0 &&  $event['skybox'] / $event['num'] > $criticalAvailablePercentage ? "style='border: 3px solid red'" : "";

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
                                        <td $daysStyle>$timeTilEvent</td>
                                        <td>$event[num] groups<br>in $event[rows] rows<br>out of $event[tickets] tickets</td>
                                        <td>$event[avgPrice]</td>
                                        <td $skyboxStyle>$event[skybox]</td>
					
					    <td>$event[lastCached]<br>
					    <form method=POST><input type=hidden name=tmId value='$event[tmId]'><input type=text size='3' name=cacheTime value='$event[cacheTime]'> minutes <input name='cache' type=submit value='Change'></form></td>
                                    </tr>
                                    ";
                                }
                                ?>

                              </tbody>
                        </table>