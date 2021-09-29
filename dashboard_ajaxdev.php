<?php

// MSM Dashboard
// ---------
?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.bundle.js"></script>
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
// or: $date = date_create('today midnight');
$today = $date->getTimestamp();




$msm_config_license_key = $DB->get_record_sql('SELECT * FROM mdl_config_plugins WHERE plugin = "local_msm" AND name="license_key"', array(1));
$msm_config_message = $DB->get_record_sql('SELECT * FROM mdl_config_plugins WHERE plugin = "local_msm" AND name="message"', array(1));
$msm_config_status = $DB->get_record_sql('SELECT * FROM mdl_config_plugins WHERE plugin = "local_msm" AND name="status"', array(1));
$msm_config_enabled = $DB->get_record_sql('SELECT * FROM mdl_config_plugins WHERE plugin = "local_msm" AND name="enabled"', array(1));


?>

<div class="w3-bar w3-black">
  <button class="w3-bar-item w3-button" onclick="open_msm_nav('msm_tab_1')">CPU, RAM, Disk</button>
  <button class="w3-bar-item w3-button" onclick="open_msm_nav('msm_tab_2')">Moodle Disk</button>
  <button class="w3-bar-item w3-button" onclick="open_msm_nav('msm_tab_3')">Database Overview</button>
  <button class="w3-bar-item w3-button" onclick="open_msm_nav('msm_tab_4')">Database Details</button>
  <button class="w3-bar-item w3-button" onclick="open_msm_nav('msm_tab_settings')">Settings</button>
</div>
<hr />

<script>
function open_msm_nav(navName) {
  var i;
  var x = document.getElementsByClassName("msm_nav");
  for (i = 0; i < x.length; i++) {
    x[i].style.display = "none";
  }
  document.getElementById(navName).style.display = "block";
}
</script>





<div id="msm_tab_1" class="msm_nav">

	<h2>CPU / Memory / Disk Size</h2>
	<canvas id="chart_system_realtime" width="400" height="100"></canvas>
	<script>


		var ctx1 = document.getElementById('chart_system_realtime');
		window["chart_system_realtime"] = new Chart(ctx1, {
			type: 'line',
			data: {
				labels: [],
				datasets: [
				
				{
					label: 'CPU Load',
					data: [],
					backgroundColor: [
						'rgba(0, 90, 0, 0.08)',
					],
					borderColor: [
						'rgba(0, 90, 0, 0.5)',
					],
					borderWidth: 1
				
				},{
					
					label: 'Memory Load',
					data: [],
					backgroundColor: [
						'rgba(90, 90, 0, 0.08)',
					],
					borderColor: [
						'rgba(90, 90, 0, 0.5)',
					],
					borderWidth: 1
				},{
					
					label: 'Disk Size',
					data: [],
					backgroundColor: [
						'rgba(90, 90, 90, 0.08)',
					],
					borderColor: [
						'rgba(90, 90, 90, 0.5)',
					],
					borderWidth: 1
				},
				
				]
			},
			options: {
				animation: {
					duration: 0 // general animation time
				},
				scales: {
					yAxes: [{
						ticks: {
							beginAtZero: true,
							suggestedMin: 0,
							suggestedMax: 100
						}
					}]
				}
			}
		});
		
		
		setTimeout(function(){
		
			jQuery.post({
				url:  "/local/msm/api/api.php?mode=cpuramdisk_labels",
				data: [],
			}).done(function(data) {
					
				console.log("------------ cpuramdisk_labels: SERVER RESPONSE WAS: ");
				console.log(data);
				
			});
			
			jQuery.post({
				url:  "/local/msm/api/api.php?mode=cpu",
				data: [],
			}).done(function(data) {
					
				console.log("------------ cpu: SERVER RESPONSE WAS: ");
				console.log(data);
				
				
				window["chart_system_realtime"].data.datasets[0].data = data;
				
			});
			
		},2000);
		
	</script>





	<hr />




</div><!-- END NAV -->




<div id="msm_tab_settings" class="msm_nav" style="display:none">

	<h1>Settings</h1>
	<hr />
	<h3>MSM Status: <strong class="msm_license_status"><?php echo $msm_config_status->value; ?></strong></h3>
	<p class="msm_license_message"><?php echo $msm_config_message->value; ?></p>
	<hr />
	<p><a target="_blank" href="https://moodlesystemmonitor.com/my-account">Manage your license keys</a></p>
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
							<hr>
						</div>
					</div>
				</div>
				
				<div class="form-item row" id="admin-license_key">
					<div class="form-label col-sm-3 text-sm-right">
						<label for="id_s_local_msm_license_key">
						License Key from <a target="_blank" href="https://MoodleSystemMonitor.com?src=moodle_admin_setting_click&amp;placement=https://dev.moodlesystemmonitor.com">MoodleSystemMonitor.com</a> (FREE TRIAL AVAILABLE!)
						</label>
					</div>
					<div class="form-setting col-sm-9">
						<div class="form-text defaultsnext">
							<input type="text" name="s_local_msm_license_key" value="<?php echo $msm_config_license_key->value; ?>" size="30" id="id_s_local_msm_license_key" class="form-control msm_license_key">
						</div>
						<div class="form-description mt-3"><hr></div>
					</div>
				</div>
				
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
				url:  "/local/msm/api/api.php?mode=activate&license_key="+document.querySelector(".msm_license_key").value+"&enabled="+document.getElementById("id_s_local_msm_enabled").checked,
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


<!-- The actual snackbar -->
<div id="msm_snackbar">unset</div>

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
</style>
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