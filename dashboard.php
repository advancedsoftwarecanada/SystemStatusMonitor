<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package local_systemstatusmonitor
 * @author Andrew Normore<andrewnormore@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2019 onwards Advanced Software Engineering Corporation of Canada
 */

?>

	<style>
		/* The snackbar - position it at the bottom and in the middle of the screen */
		#ssm_snackbar {
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
		#ssm_snackbar.show {
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


		.ssm-btn {border:1px solid black; padding:4px; margin:4px;}




	</style>


<?php

require_once("../../config.php");
global $DB;

// Security.
$context = context_system::instance();
require_login();
require_capability('moodle/site:config', $context);

// Page boilerplate stuff.
$url = new moodle_url('/local/systemstatusmonitor/dashboard.php');
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$title = "ssm Dashboard";
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();

$date = new DateTime('today midnight');
$today = $date->getTimestamp();

$view = "";
if( isset($_GET['view']) ) {

	$_GET['view'];
	if($view == ""){
		$view = "analytics";
	}

}

$ssm_config_license_key = $DB->get_record_sql("SELECT * FROM {config_plugins} WHERE plugin = 'local_systemstatusmonitor' AND name='license_key'", array(1));
$ssm_config_message = $DB->get_record_sql("SELECT * FROM {config_plugins} WHERE plugin = 'local_systemstatusmonitor' AND name='message'", array(1));
$ssm_config_status = $DB->get_record_sql("SELECT * FROM {config_plugins} WHERE plugin = 'local_systemstatusmonitor' AND name='status'", array(1));
$ssm_config_enabled = $DB->get_record_sql("SELECT * FROM {config_plugins} WHERE plugin = 'local_systemstatusmonitor' AND name='enabled'", array(1));
$ssm_config_useinternalcron = $DB->get_record_sql("SELECT * FROM {config_plugins} WHERE plugin = 'local_systemstatusmonitor' AND name='useinternalcron'", array(1));


$ssm_config_developermode = $DB->get_record_sql("SELECT * FROM {config_plugins} WHERE plugin = 'local_systemstatusmonitor' AND name='developermode'", array(1));
if($ssm_config_developermode){
	$ssm_wp_url = "https://moodlesystemmonitor.com/";
	if($ssm_config_developermode->value=='true'){
		$ssm_wp_url = "https://wordpressdev.moodlesystemmonitor.com/";
	}
}

$url_parameters = "";
//var_dump("MAINTANCE MODE:     ".$CFG->maintenance_enabled);

?>

<a href="<?php echo $CFG->wwwroot; ?>/local/systemstatusmonitor/dashboard.php?view=analytics" class="ssm-btn" >Analytics</a>
<a href="<?php echo $CFG->wwwroot; ?>/local/systemstatusmonitor/dashboard.php?view=settings" class="ssm-btn" >Settings</a>
<a href="<?php echo $CFG->wwwroot; ?>/local/systemstatusmonitor/dashboard.php?view=ssmbar" class="ssm-btn" >ssm Bar</a>
<hr />



</div>

<div style="float:right; width:20%;">


</div>
<br style="clear:both;">


<?php
$ssm_valid_report_time = true;
if($ssm_valid_report_time == true){

	//var_dump("SELECT * FROM {ssm_datacache} WHERE type = 'disk_total' AND data1 >= '".$ssm_time1."' AND data1 <= '".$ssm_time2."' ORDER BY data1 DESC");

	?>
		<div class="ssm_content">

			<?php

			if( $view == "analytics" ){

				if($ssm_config_developermode->value=='true'){
					?>
						<iframe src="https://processordev.moodlesystemmonitor.com/ssmreport/?license_key=<?php echo $ssm_config_license_key->value; ?>" style="width:100%; height:600px;" frameBorder="0"></iframe>
					<?php
				}else{
					?>
						<iframe src="https://processor.moodlesystemmonitor.com/ssmreport/?license_key=<?php echo $ssm_config_license_key->value; ?>" style="width:100%; height:600px;" frameBorder="0"></iframe>
					<?php
				}

			}
			?>


			<?php

			if( $view == "settings" ){


				if($ssm_config_developermode->value=='true'){

					?>
					<p style="color:red;">DEVELOPER MODE ACTIVE</p>
					<?php
				}


				?>

					<div id="ssm_tab_settings" class="ssm_nav" style="">

						<h1>Settings</h1>
						<hr />
						<h3>ssm Status: <strong class="ssm_license_status"><?php echo $ssm_config_status->value; ?></strong></h3>
						<p class="ssm_license_message"><?php echo $ssm_config_message->value; ?></p>
						<hr />
						<p><a target="_blank" href="<?php echo $ssm_wp_url; ?>/my-account">Manage your license keys</a></p>
						<hr />

						<?php

						?>

						<form action="#" method="post" id="adminsettings">
							<div class="settingsform" id="yui_3_17_2_1_1631673344765_221">

								<input type="hidden" name="section" value="local_systemstatusmonitor">
								<input type="hidden" name="action" value="save-settings">
								<input type="hidden" name="sesskey" value="uSY1WcqmfW">
								<input type="hidden" name="return" value="">

								<fieldset id="yui_3_17_2_1_1631673344765_220">

									<div class="clearer"></div>

									<div class="form-item row" id="admin-enabled">
										<div class="form-label col-sm-3 text-sm-right">
											<label for="id_s_local_systemstatusmonitor_enabled">
											Enable ssm
											</label>
										</div>
										<div class="form-setting col-sm-9">
											<div class="form-checkbox defaultsnext">
												<input type="hidden" name="s_local_systemstatusmonitor_enabled" value="0">
												<input type="checkbox" name="s_local_systemstatusmonitor_enabled" value="1" id="id_s_local_systemstatusmonitor_enabled" <?php if($ssm_config_enabled->value == "true"){ ?>checked="true" <?php }else{ ?> <?php }?>>
											</div>
											<div class="form-description mt-3">
												This is required to be checked for server data to be processed and collected
											</div>
										</div>
									</div>

									<hr>

									<div class="form-item row" id="admin-enabled">
										<div class="form-label col-sm-3 text-sm-right">
											<label for="id_s_local_systemstatusmonitor_enabled">
											Process Data With Server Cron
											</label>
										</div>
										<div class="form-setting col-sm-9">
											<div class="form-checkbox defaultsnext">
												<input type="hidden" name="s_local_systemstatusmonitor_useinternalcron" value="0">
												<input type="checkbox" name="s_local_systemstatusmonitor_useinternalcron" value="1" id="id_s_local_systemstatusmonitor_useinternalcron" <?php if($ssm_config_useinternalcron->value == "true"){ ?>checked="true" <?php }else{ ?> <?php }?>>
											</div>
											<div class="form-description mt-3">
												Not usually needed unless your Moodle instance is VPN internal and not accessible to the outside internet
												<br />
												Must create a 1 minute cron job for /local/systemstatusmonitor/process_ssm_internal_cron_every_minute.php
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
											<label for="id_s_local_systemstatusmonitor_enabled">
											Enable Developer Mode
											</label>
										</div>
										<div class="form-setting col-sm-9">
											<div class="form-checkbox defaultsnext">
												<input type="hidden" name="s_local_systemstatusmonitor_developermode" value="0">
												<input type="checkbox" name="s_local_systemstatusmonitor_developermode" value="1" id="id_s_local_systemstatusmonitor_developermode" <?php if($ssm_config_developermode->value == "true"){ ?>checked="true" <?php }else{ ?> <?php }?>>
											</div>
											<div class="form-description mt-3">
												(Very likely not needed!)
											</div>
										</div>
									</div>

									<hr>

									<div class="form-item row" id="admin-license_key">
										<div class="form-label col-sm-3 text-sm-right">
											<label for="id_s_local_systemstatusmonitor_license_key">
											License Key from <a target="_blank" href="https://MoodleSystemMonitor.com">MoodleSystemMonitor.com</a>
											</label>
										</div>
										<div class="form-setting col-sm-9">
											<div class="form-text defaultsnext">
												<input type="text" name="s_local_systemstatusmonitor_license_key" value="<?php echo $ssm_config_license_key->value; ?>" size="30" id="id_s_local_systemstatusmonitor_license_key" class="form-control ssm_license_key">
											</div>
											<div class="form-description mt-3"></div>
										</div>
									</div>

									<hr>

								</fieldset>

								<div class="row">
									<div class="offset-sm-3 col-sm-3">
										<button type="button" class="btn btn-primary" onClick="ssm_submit();">Save changes & activate license</button>
									</div>
								</div>

							</div>
						</form>

						<script>
							function ssm_submit(){
								ssm_snackbar("Submitting...");
								jQuery.post({
									url:  "<?php echo $CFG->wwwroot; ?>/local/systemstatusmonitor/api/api.php?mode=activate&license_key="+document.querySelector(".ssm_license_key").value+"&enabled="+document.getElementById("id_s_local_systemstatusmonitor_enabled").checked+"&developermode="+document.getElementById("id_s_local_systemstatusmonitor_developermode").checked+"&useinternalcron="+document.getElementById("id_s_local_systemstatusmonitor_useinternalcron").checked,
									data: [],
								}).done(function(data) {

									//console.log(data);
									var data_json = JSON.parse(data);
									//console.log(data_json);
									console.log(data_json.status);
									console.log(data_json.message);

									ssm_snackbar("Saved!");

									document.querySelector(".ssm_license_status").innerText = data_json.status;
									document.querySelector(".ssm_license_message").innerText = data_json.message;


									//alert(data);
									//location.reload();

								});
							}
						</script>


					</div><!-- END NAV -->

			<?php }

			if( $view == "ssmbar" ){

				?>

				ssm Bar Settings
				<hr />

<p><a target="_blank" href="<?php echo $CFG->wwwroot; ?>/admin/settings.php?section=additionalhtml">Open Additional HTML Settings</a></p>


					Copy this into your additional "Before BODY is closed":
					<textarea style="width:100%; height:300px;">
<!-- www.MoodleSystemMonitor.com ssmBAR Loader -->
<script>

	var xhttp = new XMLHttpRequest();
	console.log("Checking ssm Bar permission");
	xhttp.open("GET",  '<?php echo $CFG->wwwroot; ?>/local/systemstatusmonitor/api/api.php/?mode=permissions_check', true);
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
			if($ssm_config_developermode->value=='true'){
				?>script.src = "https://processordev.moodlesystemmonitor.com/api/ssmbar?license_key=<?php echo $ssm_config_license_key->value; ?>";<?php
			}else{
				?>script.src = "https://processor.moodlesystemmonitor.com/api/ssmbar?license_key=<?php echo $ssm_config_license_key->value; ?>";<?php
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

		</div><!-- END ssm_content -->

	<?php
}

?>

<!-- The actual snackbar -->
<div id="ssm_snackbar">unset</div>

<script>
function ssm_snackbar(message) {
  // Get the snackbar DIV
  var x = document.getElementById("ssm_snackbar");

  document.querySelector("#ssm_snackbar").innerText = message;
  x.className = "show";

  // After 3 seconds, remove the show class from DIV
  setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000);
}

</script>



<?php




echo $OUTPUT->footer();