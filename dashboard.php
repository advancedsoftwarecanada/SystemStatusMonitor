<?php

// MSM Dashboard
// ---------
?>
	
	<style>
		/* The snackbar - position it at the bottom and in the middle of the screen */
		#msm_snackbar {
		  visibility: hidden; /* Hidden by default. Visible on click */
		  min-width: 250px; /* Set a default minimum width */
		  margin-left: -125px; /* Divide value of min-width by 2 */
		  background-color: #056101; /* Black background color */
		  color: #fff; /* White text color */
		  text-align: center; /* Centered text */
		  border-radius: 2px; /* Rounded borders */
		  padding: 16px; /* Padding */
		  position: fixed; /* Sit on top of the screen */
		  z-index: 1; /* Add a z-index if needed */
		  left: 50%; /* Center the snackbar */
		  bottom: 30px; /* 30px from the bottom */
		}

		/* Show the snackbar when clicking on a button (class added with JavaScript) */
		#msm_snackbar.show {
		  visibility: visible; /* Show the snackbar */
		  /* Add animation: Take 0.5 seconds to fade in and out the snackbar.
		  However, delay the fade out process for 2.5 seconds */
		  -webkit-animation: fadein 0.5s, fadeout 0.5s 2.5s;
		  animation: fadein 0.5s, fadeout 0.5s 2.5s;
		}

		/* Animations to fade the snackbar in and out */
		@-webkit-keyframes fadein {
		  from {bottom: 0; opacity: 0;}
		  to {bottom: 30px; opacity: 1;}
		}

		@keyframes fadein {
		  from {bottom: 0; opacity: 0;}
		  to {bottom: 30px; opacity: 1;}
		}

		@-webkit-keyframes fadeout {
		  from {bottom: 30px; opacity: 1;}
		  to {bottom: 0; opacity: 0;}
		}

		@keyframes fadeout {
		  from {bottom: 30px; opacity: 1;}
		  to {bottom: 0; opacity: 0;}
		}


		.msm-btn {border:1px solid black; padding:4px; margin:4px;}
		
		
		

	</style>


<?php

require_once("../../config.php");
global $DB;

// Security.
$context = context_system::instance();
require_login();
require_capability('moodle/site:config', $context);

// Page boilerplate stuff.
$url = new moodle_url('/local/msm/dashboard.php');
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$title = "MSM Dashboard";
$PAGE->set_title($title);
$PAGE->set_heading($title);




echo $OUTPUT->header();

$date = new DateTime('today midnight');
$today = $date->getTimestamp();


$view = $_GET['view'];
if($view == ""){
	$view = "analytics";
}


$msm_config_license_key = $DB->get_record_sql("SELECT * FROM {config_plugins} WHERE plugin = 'local_msm' AND name='license_key'", array(1));
$msm_config_message = $DB->get_record_sql("SELECT * FROM {config_plugins} WHERE plugin = 'local_msm' AND name='message'", array(1));
$msm_config_status = $DB->get_record_sql("SELECT * FROM {config_plugins} WHERE plugin = 'local_msm' AND name='status'", array(1));
$msm_config_enabled = $DB->get_record_sql("SELECT * FROM {config_plugins} WHERE plugin = 'local_msm' AND name='enabled'", array(1));
$msm_config_useinternalcron = $DB->get_record_sql("SELECT * FROM {config_plugins} WHERE plugin = 'local_msm' AND name='useinternalcron'", array(1));


$msm_config_developermode = $DB->get_record_sql("SELECT * FROM {config_plugins} WHERE plugin = 'local_msm' AND name='developermode'", array(1));
$msm_wp_url = "https://moodlesystemmonitor.com/";
if($msm_config_developermode->value=='true'){
	$msm_wp_url = "https://wordpressdev.moodlesystemmonitor.com/";	
}


$url_parameters = "";





var_dump("MAINTANCE MODE:     ".$CFG->maintenance_enabled);


?>

<a href="<?php echo $CFG->wwwroot; ?>/local/msm/dashboard.php?view=analytics" class="msm-btn" >Analytics</a>
<a href="<?php echo $CFG->wwwroot; ?>/local/msm/dashboard.php?view=settings" class="msm-btn" >Settings</a>
<a href="<?php echo $CFG->wwwroot; ?>/local/msm/dashboard.php?view=msmbar" class="msm-btn" >MSM Bar</a>
<hr />


  
</div>

<div style="float:right; width:20%;">


</div>
<br style="clear:both;">


<?php 
$msm_valid_report_time = true;
if($msm_valid_report_time == true){
	
	//var_dump("SELECT * FROM {msm_datacache} WHERE type = 'disk_total' AND data1 >= '".$msm_time1."' AND data1 <= '".$msm_time2."' ORDER BY data1 DESC");
	
	?>
		<div class="msm_content">

			<?php
			
			if( $view == "analytics" ){
				
				if($msm_config_developermode->value=='true'){
					?>
						<iframe src="https://processordev.moodlesystemmonitor.com/msmreport/?license_key=<?php echo $msm_config_license_key->value; ?>" style="width:100%; height:600px;" frameBorder="0"></iframe>
					<?php
				}else{
					?>
						<iframe src="https://processor.moodlesystemmonitor.com/msmreport/?license_key=<?php echo $msm_config_license_key->value; ?>" style="width:100%; height:600px;" frameBorder="0"></iframe>
					<?php
				}
				
			}
			?>

			
			<?php

			if( $view == "settings" ){
				
				
				if($msm_config_developermode->value=='true'){
					
					?>
					<p style="color:red;">DEVELOPER MODE ACTIVE</p>
					<?php
				}
				
			
				?>

					<div id="msm_tab_settings" class="msm_nav" style="">

						<h1>Settings</h1>
						<hr />
						<h3>MSM Status: <strong class="msm_license_status"><?php echo $msm_config_status->value; ?></strong></h3>
						<p class="msm_license_message"><?php echo $msm_config_message->value; ?></p>
						<hr />
						<p><a target="_blank" href="<?php echo $msm_wp_url; ?>/my-account">Manage your license keys</a></p>
						<hr />
					  
						<?php
						
						?>
					  
						<form action="#" method="post" id="adminsettings">
							<div class="settingsform" id="yui_3_17_2_1_1631673344765_221">
								
								<input type="hidden" name="section" value="local_msm">
								<input type="hidden" name="action" value="save-settings">
								<input type="hidden" name="sesskey" value="uSY1WcqmfW">
								<input type="hidden" name="return" value="">
								
								<fieldset id="yui_3_17_2_1_1631673344765_220">
									
									<div class="clearer"></div>
									
									<div class="form-item row" id="admin-enabled">
										<div class="form-label col-sm-3 text-sm-right">
											<label for="id_s_local_msm_enabled">
											Enable MSM
											</label>
										</div>
										<div class="form-setting col-sm-9">
											<div class="form-checkbox defaultsnext">
												<input type="hidden" name="s_local_msm_enabled" value="0">
												<input type="checkbox" name="s_local_msm_enabled" value="1" id="id_s_local_msm_enabled" <?php if($msm_config_enabled->value == "true"){ ?>checked="true" <?php }else{ ?> <?php }?>>
											</div>
											<div class="form-description mt-3">
												This is required to be checked for server data to be processed and collected
											</div>
										</div>
									</div>
									
									<hr>
									
									<div class="form-item row" id="admin-enabled">
										<div class="form-label col-sm-3 text-sm-right">
											<label for="id_s_local_msm_enabled">
											Process Data With Server Cron
											</label>
										</div>
										<div class="form-setting col-sm-9">
											<div class="form-checkbox defaultsnext">
												<input type="hidden" name="s_local_msm_useinternalcron" value="0">
												<input type="checkbox" name="s_local_msm_useinternalcron" value="1" id="id_s_local_msm_useinternalcron" <?php if($msm_config_useinternalcron->value == "true"){ ?>checked="true" <?php }else{ ?> <?php }?>>
											</div>
											<div class="form-description mt-3">
												Not usually needed unless your Moodle instance is VPN internal and not accessible to the outside internet
												<br />
												Must create a 1 minute cron job for /local/msm/process_msm_internal_cron_every_minute.php
												<br />
												This setting will push data out every 1 minute, rather than use polling your Moodle site for data every 1 minute from our servers
												<br />
												This is completely independant of Moodle Cron
											</div>
										</div>
									</div>
									
									<hr>
			
									<div class="form-item row" id="admin-enabled">
										<div class="form-label col-sm-3 text-sm-right">
											<label for="id_s_local_msm_enabled">
											Enable Developer Mode
											</label>
										</div>
										<div class="form-setting col-sm-9">
											<div class="form-checkbox defaultsnext">
												<input type="hidden" name="s_local_msm_developermode" value="0">
												<input type="checkbox" name="s_local_msm_developermode" value="1" id="id_s_local_msm_developermode" <?php if($msm_config_developermode->value == "true"){ ?>checked="true" <?php }else{ ?> <?php }?>>
											</div>
											<div class="form-description mt-3">
												(Very likely not needed!)
											</div>
										</div>
									</div>
									
									<hr>
									
									<div class="form-item row" id="admin-license_key">
										<div class="form-label col-sm-3 text-sm-right">
											<label for="id_s_local_msm_license_key">
											License Key from <a target="_blank" href="https://MoodleSystemMonitor.com">MoodleSystemMonitor.com</a> 
											</label>
										</div>
										<div class="form-setting col-sm-9">
											<div class="form-text defaultsnext">
												<input type="text" name="s_local_msm_license_key" value="<?php echo $msm_config_license_key->value; ?>" size="30" id="id_s_local_msm_license_key" class="form-control msm_license_key">
											</div>
											<div class="form-description mt-3"></div>
										</div>
									</div>
									
									<hr>
									
								</fieldset>
								
								<div class="row">
									<div class="offset-sm-3 col-sm-3">
										<button type="button" class="btn btn-primary" onClick="msm_submit();">Save changes & activate license</button>
									</div>
								</div>
								
							</div>
						</form>
						
						<script>
							function msm_submit(){
								msm_snackbar("Submitting...");
								jQuery.post({
									url:  "<?php echo $CFG->wwwroot; ?>/local/msm/api/api.php?mode=activate&license_key="+document.querySelector(".msm_license_key").value+"&enabled="+document.getElementById("id_s_local_msm_enabled").checked+"&developermode="+document.getElementById("id_s_local_msm_developermode").checked+"&useinternalcron="+document.getElementById("id_s_local_msm_useinternalcron").checked,
									data: [],
								}).done(function(data) {
									
									//console.log(data);
									var data_json = JSON.parse(data);
									//console.log(data_json);
									console.log(data_json.status);
									console.log(data_json.message);
									
									msm_snackbar("Saved!");
									
									document.querySelector(".msm_license_status").innerText = data_json.status;
									document.querySelector(".msm_license_message").innerText = data_json.message;
									
									
									//alert(data);
									//location.reload();
									
								});
							}
						</script>


					</div><!-- END NAV -->
					
			<?php } 
			
			if( $view == "msmbar" ){
				
				?>
				
				MSM Bar Settings
				<hr />
				
<p><a target="_blank" href="<?php echo $CFG->wwwroot; ?>/admin/settings.php?section=additionalhtml">Open Additional HTML Settings</a></p>
					
					
					Copy this into your additional "Before BODY is closed":
					<textarea style="width:100%; height:300px;">
<!-- www.MoodleSystemMonitor.com MSMBAR Loader -->
<script>

	var xhttp = new XMLHttpRequest();
	console.log("Checking MSM Bar permission");
	xhttp.open("GET",  '<?php echo $CFG->wwwroot; ?>/local/msm/api/api.php/?mode=permissions_check', true);
	xhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
	xhttp.onreadystatechange = function() {
	   if (this.readyState == 4 && this.status == 200) {

		// Response
		var response = this.responseText;
		console.log(response);
		if(response=="admin"){
			var body = document.body;
			var script = document.createElement('script');
			script.type = 'text/javascript';
			
			<?php
			if($msm_config_developermode->value=='true'){
				?>script.src = "https://processordev.moodlesystemmonitor.com/api/msmbar?license_key=<?php echo $msm_config_license_key->value; ?>";<?php
			}else{
				?>script.src = "https://processor.moodlesystemmonitor.com/api/msmbar?license_key=<?php echo $msm_config_license_key->value; ?>";<?php
			}
			?>
			body.appendChild(script); 
		}

	   }
	};
	xhttp.send();
	
</script>
</textarea>
<?php
			}
		?>
			
		</div><!-- END msm_content -->
		
	<?php
}

?>

<!-- The actual snackbar -->
<div id="msm_snackbar">unset</div>

<script>
function msm_snackbar(message) {
  // Get the snackbar DIV
  var x = document.getElementById("msm_snackbar");

  document.querySelector("#msm_snackbar").innerText = message;
  x.className = "show";

  // After 3 seconds, remove the show class from DIV
  setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000);
}

</script>



<?php




echo $OUTPUT->footer();