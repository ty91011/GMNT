                  <form method="POST" id="demo-form" data-parsley-validate class="form-horizontal form-label-left">
		    <div class="form-group">
<input type="hidden" name="eventId" value="<?php echo $eventId; ?>" />
                        <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">Consecutive Seats
                        </label>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <input type="text" disabled="disabled" placeholder="4" name="consecutive" class="form-control col-md-7 col-xs-12">
                        </div>
                      </div>
		    <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">Max Price
                        </label>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <input type="text" placeholder="<?php echo $maxPrice; ?>" value="<?php echo $_POST['maxPrice']; ?>" name="maxPrice" class="form-control col-md-7 col-xs-12">
                        </div>
                      </div>
		    <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">Minimum # of Groups Available Per Row
                        </label>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <input type="text" placeholder="<?php echo $minGroups; ?>" name="minGroups" value="<?php echo $_POST['minGroups']; ?>" class="form-control col-md-7 col-xs-12">
                        </div>
                      </div>
		    <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">Markup (%)
                        </label>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <input type="text" placeholder="<?php echo $markup; ?>" name="markup" value="<?php echo $_POST['markup']; ?>" class="form-control col-md-7 col-xs-12">
                        </div>
                      </div>
		    <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">Maximum Rows Selected per Section
                        </label>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <input type="text" placeholder="<?php echo $maxRows; ?>" name="maxRows" value="<?php echo $_POST['maxRows']; ?>" class="form-control col-md-7 col-xs-12">
                        </div>
                      </div>
		    <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">Minimum Rows a Section Needs to Have Available
                        </label>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <input type="text" placeholder="<?php echo $minRowsInSection; ?>" name="minRowsInSection" value="<?php echo $_POST['minRowsInSection']; ?>" class="form-control col-md-7 col-xs-12">
                        </div>
                      </div>
			<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">Ignore Best Available Row in Section
                        </label>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                           <input type="checkbox" class="flat" disabled="disabled" checked="checked">
                        </div>
                      </div>
		    <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">Upload only 1 Ticket Group per Row
                        </label>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                           <input type="checkbox" class="flat" disabled="disabled" checked="checked">
                        </div>
                      </div>
			
                      <div class="form-group">
                        <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
			    <button type="submit" class="btn btn-success">Filter Groups</button>
			    <button type="submit" name="UPLOAD" value="true" class="btn btn-danger">UPLOAD TO SKYBOX</button>
                        </div>
                      </div>
		  </form>
                    
		    <div class="ln_solid"></div>