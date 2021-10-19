<?php

// Load RTMS
// ---------

require_once("../../config.php");

//  Install as a Cron Job and run this as fast as possible
//  There are internal braking methods to prevent a CPU overload
//  To acheive "real time" performance, we try to run this file as fast as possible, at all times, forever!
//
//  # Moodle Real Time Scanner, must run every minute
//  */1 * * * * wget -O - https://yourwebsite.com/local/RealTimeMonitoringService/?key=YOUR_WEB_KEY > /dev/null 2>&1

$msm_config_license_key = $DB->get_record_sql("SELECT * FROM {config_plugins} WHERE plugin = 'local_msm' AND name='license_key'", array(1));
$msm_config_message = $DB->get_record_sql("SELECT * FROM {config_plugins} WHERE plugin = 'local_msm' AND name='message'", array(1));
$msm_config_status = $DB->get_record_sql("SELECT * FROM {config_plugins} WHERE plugin = 'local_msm' AND name='status'", array(1));
//$msm_config_enabled = $DB->get_record_sql("SELECT * FROM {config_plugins} WHERE plugin = 'local_msm' AND name='enabled'", array(1));


$records = $DB->get_records_sql("SELECT * FROM {config_plugins} WHERE plugin='local_msm' AND name='enabled' ", array(1));
foreach($records as $record){
	$msm_config_enabled = $record->value;
}


$msm_config_developermode = $DB->get_record_sql("SELECT * FROM {config_plugins} WHERE plugin = 'local_msm' AND name='developermode'", array(1));
$msm_wp_url = "https://moodlesystemmonitor.com/";
if($msm_config_developermode->value=='true'){
	$msm_wp_url = "https://wordpressdev.moodlesystemmonitor.com/";	
}



if($msm_config_enabled != "true"){

	?>

	<h1>Moodle System Monitor is not enabled</h1>
	<a href="/local/msm/dashboard.php">Click here to enable</a>

	<?php
	die();
}


	//open connection
	$ch = curl_init();

	//set the url, number of POST vars, POST data
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_URL, $CFG->wwwroot."/local/msm/process_msm_external.php");
	//curl_setopt($ch, CURLOPT_POST, count($fields));
	//curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	//execute post
	$msm_stats_json_text = curl_exec($ch);
	
	echo $msm_stats_json_text;

	curl_close($ch);
	
	
	
	
	
	
	
	
	//open connection
	$ch2 = curl_init();
	$post_data = json_encode($msm_stats_json_text);

	//set the url, number of POST vars, POST data
	curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch2, CURLOPT_URL, $msm_wp_url."/api/process_from_internal_cron/?license_key=".$msm_config_license_key->value);
	curl_setopt($ch2, CURLOPT_POST, 1);
	curl_setopt($ch2, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);

	//execute post
	$result = curl_exec($ch2);
	
	curl_close($ch2);


?>
