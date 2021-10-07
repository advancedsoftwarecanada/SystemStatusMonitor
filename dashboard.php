<?php

// MSM Dashboard
// ---------
?>

	<script src="/local/msm/js/rome.min.js"></script>
	<link rel="stylesheet" href="/local/msm/js/rome.min.css" crossorigin="anonymous">
	
	<script src="/local/msm/js/chart.js"></script>
	
	<link rel="stylesheet" href="/local/msm/js/loader.css?v=1.2" crossorigin="anonymous">
	
	
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
		
		.msm-generating {text-align:center;}
		.msm_content {display:none;}

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
	$view = "cpu_ram_disk";
}

$msm_config_license_key = $DB->get_record_sql("SELECT * FROM {config_plugins} WHERE plugin = 'local_msm' AND name='license_key'", array(1));
$msm_config_message = $DB->get_record_sql("SELECT * FROM {config_plugins} WHERE plugin = 'local_msm' AND name='message'", array(1));
$msm_config_status = $DB->get_record_sql("SELECT * FROM {config_plugins} WHERE plugin = 'local_msm' AND name='status'", array(1));
$msm_config_enabled = $DB->get_record_sql("SELECT * FROM {config_plugins} WHERE plugin = 'local_msm' AND name='enabled'", array(1));

$msm_time1 = date("Y-m-d"). " 00:00";
$msm_time2 = date("Y-m-d"). " 23:59";

$url_parameters = "";

if($_GET['msm_time1']!=""){
	
	$msm_time1 = $_GET['msm_time1'];
	$msm_time2 = $_GET['msm_time2'];
	
}else{
	
	$msm_time1 = date("Y-m-d") . " 00:00";
	$msm_time2 = date("Y-m-d") . " 23:59";
	
}

//var_dump($msm_time1);var_dump($msm_time2);



?>

<div style="float:left; width:80%;">
  <a href="/local/msm/dashboard.php?view=cpu_ram_disk&msm_time1=<?php echo $msm_time1; ?>&msm_time2=<?php echo $msm_time2; ?>" class="msm-btn" onclick="open_msm_nav('msm_tab_1')">CPU, RAM, Disk</a>
  <a href="/local/msm/dashboard.php?view=moodle_disk&msm_time1=<?php echo $msm_time1; ?>&msm_time2=<?php echo $msm_time2; ?>" class="msm-btn" onclick="open_msm_nav('msm_tab_2')">Moodle Disk</a>
  <a href="/local/msm/dashboard.php?view=database_overview&msm_time1=<?php echo $msm_time1; ?>&msm_time2=<?php echo $msm_time2; ?>" class="msm-btn" onclick="open_msm_nav('msm_tab_3')">Database Overview</a>
  <a href="/local/msm/dashboard.php?view=database_details&msm_time1=<?php echo $msm_time1; ?>&msm_time2=<?php echo $msm_time2; ?>" class="msm-btn" onclick="open_msm_nav('msm_tab_4')">Database Details</a>
  <a href="/local/msm/dashboard.php?view=settings&msm_time1=<?php echo $msm_time1; ?>&msm_time2=<?php echo $msm_time2; ?>" class="msm-btn" onclick="open_msm_nav('msm_tab_settings')">Settings</a>
</div>

<div style="float:right; width:20%;">

	<form action="/local/msm/dashboard.php" method="GET">
		<input id='msm_time1' name='msm_time1' class='input' value='<?php echo $msm_time1; ?>' style="width:100%;" />
		<input id='msm_time2' name='msm_time2' class='input' value='<?php echo $msm_time2; ?>' style="width:100%;" />
		<div id="msm_get_report" onclick="msm_get_report()" class="msm-btn" style="width:100%; background-color:green; color:white; text-align:center; cursor:pointer;">Get Report</div>
	</form>
	
</div>
<br style="clear:both;">


<hr />
<div class="msm-generating">
	GENERATING...
	<br />
	<div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
</div>




<script>
	setTimeout(function(){
		
		
		var moment = rome.moment;

		rome(msm_time1);
		rome(msm_time2);
		
		/*
		rome(sm, { weekStart: 1 });
		rome(d, { time: false });
		rome(t, { date: false });

		var picker = rome(ind);

		toggle.addEventListener('click', function () {
		  if (picker.restore) {
			picker.restore();
		  } else {
			picker.destroy();
		  }
		  toggle.innerHTML = picker.restore ? 'Restore <code>rome</code> instance!' : 'Destroy <code>rome</code> instance!';
		});

		rome(mm, { min: '2013-12-30', max: '2014-10-01' });
		rome(mmt, { min: '2014-04-30 19:45', max: '2014-09-01 08:30' });

		rome(iwe, {
		  dateValidator: function (d) {
			return moment(d).day() !== 6;
		  }
		});

		rome(win, {
		  dateValidator: function (d) {
			var m = moment(d);
			var y = m.year();
			var f = 'MM-DD';
			var start = moment('12-21', f).year(y).startOf('day');
			var end = moment('03-19', f).year(y).endOf('day');
			return m.isBefore(start) && m.isAfter(end);
		  }
		});

		rome(tim, {
		  timeValidator: function (d) {
			var m = moment(d);
			var start = m.clone().hour(12).minute(59).second(59);
			var end = m.clone().hour(18).minute(0).second(1);
			return m.isAfter(start) && m.isBefore(end);
		  }
		});
		*/
		

	},1000);
	
	function msm_get_report(){

		window.location.href = "/local/msm/dashboard.php?view=<?php echo $view; ?>&msm_time1="+document.getElementById("msm_time1").value+"&msm_time2="+document.getElementById("msm_time2").value;

	
	}

	function open_msm_nav(navName, open_msm_nav_auto = false) {
		
		return false;

		/*
		if(open_msm_nav_auto == false){
			window["open_msm_nav_auto"] = false;
		}

		var i;
		var x = document.getElementsByClassName("msm_nav");
		for (i = 0; i < x.length; i++) {
			x[i].style.display = "none";
		}
		document.getElementById(navName).style.display = "block";
		
		window.scrollTo(0,0);
		*/
	  
	}

	/*
	var hash = window.location.hash.substr(1);
	if(hash.length <= 1){
		hash = "msm_tab_1";
	}
	window["open_msm_nav_auto"] = true;

	setInterval(function(){
		if(window["open_msm_nav_auto"]==true){
			open_msm_nav(hash, open_msm_nav_auto, ); // catch all active tab
		}
	},1000);
	*/

</script>




<?php

$msm_valid_report_time = true;
if( strtotime($msm_time2) - strtotime($msm_time1) <= 0 ){
	
	$msm_valid_report_time = false;
	
	?>
		<center>
			<p>INVALID REPORT TIME!</p>
		</center>
		<script>
			setInterval(function(){
				
				jQuery(".msm_content").show();
			},1000);
		</script>
	<?php	
	
}
?>


<?php 

if($msm_valid_report_time == true){
	
	//var_dump("SELECT * FROM {msm_datacache} WHERE type = 'disk_total' AND data1 >= '".$msm_time1."' AND data1 <= '".$msm_time2."' ORDER BY data1 DESC");
	
	?>
		<div class="msm_content">

			<?php
			
			if( $view == "cpu_ram_disk" ){
				
				
				?>
				
				<div id="msm_tab_1" class="msm_nav">

					<h2>CPU / Memory / Disk Size</h2>
					<canvas id="chart_system_realtime" width="400" height="100"></canvas>
					<script>


					var ctx1 = document.getElementById('chart_system_realtime');
					var chart_system_realtime = new Chart(ctx1, {
						type: 'line',
						data: {
							labels: [
								<?php
									$records = $DB->get_records_sql("SELECT * FROM {msm_datacache} WHERE type = 'cpu_load' AND data1 >= '".strtotime($msm_time1)."' AND data1 <= '".strtotime($msm_time2)."' ORDER BY data1 DESC ", array(1));
									foreach($records as $record){
										echo date('"m/d h:i"', $record->data1).",";
									}
								?>
							],
							datasets: [
							
							{
								label: 'CPU Load',
								data: [
									<?php 
										$records = $DB->get_records_sql("SELECT * FROM {msm_datacache} WHERE type = 'cpu_load' AND data1 >= '".strtotime($msm_time1)."' AND data1 <= '".strtotime($msm_time2)."' ORDER BY data1 DESC", array(1));
										foreach($records as $record){
										  echo $record->data2.",";
										}
									?>
								],
								backgroundColor: [
									'rgba(0, 90, 0, 0.08)',
								],
								borderColor: [
									'rgba(0, 90, 0, 0.5)',
								],
								borderWidth: 1
							
							},{
								
								label: 'Memory Load',
								data: [
									<?php 
										$records = $DB->get_records_sql("SELECT * FROM {msm_datacache} WHERE type = 'memory_load' AND data1 >= '".strtotime($msm_time1)."' AND data1 <= '".strtotime($msm_time2)."' ORDER BY data1 DESC", array(1));
										foreach($records as $record){
										  echo $record->data2.",";
										}
									?>
								],
								backgroundColor: [
									'rgba(90, 90, 0, 0.08)',
								],
								borderColor: [
									'rgba(90, 90, 0, 0.5)',
								],
								borderWidth: 1
							},{
								
								label: 'Disk Used %',
								data: [
									<?php 
										$records = $DB->get_records_sql("SELECT * FROM {msm_datacache} WHERE type = 'disk_total' AND data1 >= '".strtotime($msm_time1)."' AND data1 <= '".strtotime($msm_time2)."' ORDER BY data1 DESC", array(1));
										foreach($records as $record){
										  echo $record->data2.",";
										}
									?>
								],
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
					</script>
					
				</div><!-- END NAV -->
				
			<?php
			}?>


			<?php
			if( $view == "moodle_disk" ){
				?>


					<div id="msm_tab_2" class="msm_nav">

						<h2>Moodle System Disk Sizes</h2>
						<canvas id="chart_moodle_disk" width="400" height="100"></canvas>
						<script>


						var ctx2 = document.getElementById('chart_moodle_disk');
						var chart_moodle_disk = new Chart(ctx2, {
							type: 'line',
							data: {
								labels: [
									<?php
										$records = $DB->get_records_sql("SELECT * FROM {msm_datacache} WHERE type = 'cpu_load' AND data1 >= '".strtotime($msm_time1)."' AND data1 <= '".strtotime($msm_time2)."'  ORDER BY data1 DESC", array(1));
										foreach($records as $record){
											echo date('"m/d h:i"', $record->data1).",";
										}
									?>
								],
								datasets: [
								
								{
									label: 'dirroot',
									data: [
										<?php 
											$records = $DB->get_records_sql("SELECT * FROM {msm_datacache} WHERE type = 'disk_moodle_dirroot' AND data1 >= '".strtotime($msm_time1)."' AND data1 <= '".strtotime($msm_time2)."'  ORDER BY data1 DESC", array(1));
											foreach($records as $record){
											  echo $record->data2.",";
											}
										?>
									],
									backgroundColor: [
										'rgba(0, 90, 0, 0.08)',
									],
									borderColor: [
										'rgba(0, 90, 0, 0.5)',
									],
									borderWidth: 1
								
								},{
									
									label: 'Libdir',
									data: [
										<?php 
											$records = $DB->get_records_sql("SELECT * FROM {msm_datacache} WHERE type = 'disk_moodle_libdir' AND data1 >= '".strtotime($msm_time1)."' AND data1 <= '".strtotime($msm_time2)."'  ORDER BY data1 DESC", array(1));
											foreach($records as $record){
											  echo $record->data2.",";
											}
										?>
									],
									backgroundColor: [
										'rgba(77, 77, 0, 0.08)',
									],
									borderColor: [
										'rgba(77, 77, 0, 0.5)',
									],
									borderWidth: 1
								},{
									
									label: 'dataroot',
									data: [
										<?php 
											$records = $DB->get_records_sql("SELECT * FROM {msm_datacache} WHERE type = 'disk_moodle_dataroot' AND data1 >= '".strtotime($msm_time1)."' AND data1 <= '".strtotime($msm_time2)."'  ORDER BY data1 DESC", array(1));
											foreach($records as $record){
											  echo $record->data2.",";
											}
										?>
									],
									backgroundColor: [
										'rgba(9, 66, 66, 0.08)',
									],
									borderColor: [
										'rgba(9, 66, 66, 0.5)',
									],
									borderWidth: 1
								},{
									
									label: 'tempdir',
									data: [
										<?php 
											$records = $DB->get_records_sql("SELECT * FROM {msm_datacache} WHERE type = 'disk_moodle_tempdir' AND data1 >= '".strtotime($msm_time1)."' AND data1 <= '".strtotime($msm_time2)."'  ORDER BY data1 DESC", array(1));
											foreach($records as $record){
											  echo $record->data2.",";
											}
										?>
									],
									backgroundColor: [
										'rgba(55, 55, 155, 0.08)',
									],
									borderColor: [
										'rgba(55, 55, 155, 0.5)',
									],
									borderWidth: 1
								},{
									
									label: 'cachedir',
									data: [
										<?php 
											$records = $DB->get_records_sql("SELECT * FROM {msm_datacache} WHERE type = 'disk_moodle_cachedir' AND data1 >= '".strtotime($msm_time1)."' AND data1 <= '".strtotime($msm_time2)."'  ORDER BY data1 DESC", array(1));
											foreach($records as $record){
											  echo $record->data2.",";
											}
										?>
									],
									backgroundColor: [
										'rgba(44, 144, 44, 0.08)',
									],
									borderColor: [
										'rgba(44, 144, 44, 0.5)',
									],
									borderWidth: 1
								},{
									
									label: 'localcachedir',
									data: [
										<?php 
											$records = $DB->get_records_sql("SELECT * FROM {msm_datacache} WHERE type = 'disk_moodle_localcachedir' AND data1 >= '".strtotime($msm_time1)."' AND data1 <= '".strtotime($msm_time2)."'  ORDER BY data1 DESC", array(1));
											foreach($records as $record){
											  echo $record->data2.",";
											}
										?>
									],
									backgroundColor: [
										'rgba(133, 33, 33, 0.08)',
									],
									borderColor: [
										'rgba(133, 33, 33, 0.5)',
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
						</script>

					</div>
					
				<?php } ?>
			
			
			<?php
			if( $view == "database_overview" ){
				
				?>
				
					<div id="msm_tab_3" class="msm_nav">


						<h2>Database Size / Database Rows</h2>
						<canvas id="chart_database_size_rows" width="400" height="100"></canvas>
						<script>
						var ctx3 = document.getElementById('chart_database_size_rows');
						var chart_database_size_rows = new Chart(ctx3, {
							type: 'line',
							data: {
								labels: [
									<?php
										$records = $DB->get_records_sql("SELECT * FROM {msm_datacache} WHERE type = 'cpu_load' AND data1 >= '".strtotime($msm_time1)."' AND data1 <= '".strtotime($msm_time2)."'  ORDER BY data1 DESC", array(1));
										foreach($records as $record){
											echo date('"m/d h:i"', $record->data1).",";
										}
									?>
								],
								datasets: [
								
								{
									label: 'Database Size',
									data: [
										<?php 
											$records = $DB->get_records_sql("SELECT * FROM {msm_datacache} WHERE type = 'database_total_size' AND data1 >= '".strtotime($msm_time1)."' AND data1 <= '".strtotime($msm_time2)."'  ORDER BY data1 DESC", array(1));
											foreach($records as $record){
											  echo $record->data2.",";
											}
										?>
									],
									backgroundColor: [
										'rgba(0, 90, 0, 0.08)',
									],
									borderColor: [
										'rgba(0, 90, 0, 0.5)',
									],
									borderWidth: 1
								
								},{
									
									label: 'Database Rows',
									data: [
										<?php 
											$records = $DB->get_records_sql("SELECT * FROM {msm_datacache} WHERE type = 'database_total_rows' AND data1 >= '".strtotime($msm_time1)."' AND data1 <= '".strtotime($msm_time2)."'  ORDER BY data1 DESC", array(1));
											foreach($records as $record){
											  echo $record->data2.",";
											}
										?>
									],
									backgroundColor: [
										'rgba(90, 90, 0, 0.08)',
									],
									borderColor: [
										'rgba(90, 90, 0, 0.5)',
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
						</script>


					</div>
					
			<?php } ?>

			<?php
			if( $view == "database_details" ){
				?>

					<div id="msm_tab_4" class="msm_nav">

						<h2>Database Detailed View</h2>
						
						<?php
						
						?>
						
						
						<canvas id="chart_database_tables" width="400" height="100"></canvas>
						<script>
						var ctx4 = document.getElementById('chart_database_tables');
						var chart_database_tables = new Chart(ctx4, {
							type: 'line',
							data: {
							
									<?php 
									
									
									$records = $DB->get_records_sql("SELECT * FROM {msm_datacache} WHERE type = 'database_table' AND data1 >= '".strtotime($msm_time1)."' AND data1 <= '".strtotime($msm_time2)."'  ORDER BY data1 DESC", array(1));
									
									?>
									labels:[
										<?php
										foreach($records as $record){
											echo date('"m/d h:i"', $record->data1).",";
										}
										?>
									],
									datasets: [
										<?php
										$this_record_type = "";
										foreach($records as $record){
											$this_record_type = $record->data2;
										?>
											{
												label: "<?php echo $record->data1; ?>",
												data:[
													<?php
													
														foreach($records as $record){
															if($record->data2 == $this_record_type){
																echo $record->data3.",";
															}
														}
													
													?>
													,
												],
												backgroundColor: [
													'rgba(0, 90, 0, 0.08)',
												],
												borderColor: [
													'rgba(0, 90, 0, 0.5)',
												],
												borderWidth: 1
											},
										<?php
										}
									?>
									]
										
										
										
								 
								
								
								
							},
							options: {
								animation: {
									duration: 0 // general animation time
								},
								plugins: {
									legend: false // Hide legend
								},
								scales: {
									xAxes:[{
										//display:false,
									}],
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
						</script>
					</div>
					
			<?php } ?>
			
			<?php

			if( $view == "settings" ){
			
				?>

					<div id="msm_tab_settings" class="msm_nav" style="">

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
					
			<?php } ?>
			
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

setInterval(function(){
	jQuery(".msm-generating").hide();
	jQuery(".msm_content").show();
},1000);

</script>



<?php




echo $OUTPUT->footer();