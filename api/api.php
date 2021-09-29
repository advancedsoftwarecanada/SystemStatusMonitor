<?php

	require_once("../../../config.php");
	global $DB;


if($_GET['mode'] == "activate"){
		

	// https://woosoftwarelicense.com/documentation/api-methods/

	//extract data from the post
	//set POST variables

	$url = 'https://moodlesystemmonitor.com/';
	
	$msm_license_key = $_GET['license_key']; // 'free-2017a27d-f55844dc-88cac637';
	$msm_enabled = $_GET['enabled']; // 'free-2017a27d-f55844dc-88cac637';
	
	
	$pieces = explode('-',$msm_license_key);
	$license = "";

	if($pieces[0]=="free"){
		$license = "MSM-FREE";
	}
	if($pieces[0]=="business"){
		$license = "MSM-BUSINESS";
	}
	if($pieces[0]=="enterprise"){
		$license = "MSM-ENTERPRISE";
	}



	$fields = array(
		'woo_sl_action'     => 'activate',
		'licence_key'       => $msm_license_key,
		'product_unique_id' => $license,
		'domain'            => $CFG->wwwroot,
	);

	//url-ify the data for the POST
	$fields_string = "";
	foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
	rtrim($fields_string, '&');

	//open connection
	$ch = curl_init();

	//set the url, number of POST vars, POST data
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, count($fields));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	//execute post
	$result = curl_exec($ch);
	$strip_brackets = substr($result, 1, -1);
	
	echo $strip_brackets;

	curl_close($ch);
	
	
	$decode = json_decode($result);
	//var_dump($decode);

	// var_dump($decode[0]->status);
	// var_dump($decode[0]->status_code);
	// var_dump($decode[0]->message);
	// var_dump($decode[0]->licence_status);
	// var_dump($decode[0]->licence_start);
	// var_dump($decode[0]->licence_expire);

	// object(stdClass)[95]
	// public 'status' => string 'success' (length=7)
	// public 'status_code' => string 's101' (length=4)
	// public 'message' => string 'Licence Key was already Successfully activated for http://moodledev.test' (length=72)
	// public 'licence_status' => string 'active' (length=6)
	// public 'licence_start' => string '2021-09-14' (length=10)
	// public 'licence_expire' => string '2021-10-14' (length=10)
	
	
	
	
	$DB->execute("DELETE FROM {config_plugins} WHERE plugin='local_msm' AND name='enabled'");
	$DB->execute("INSERT INTO {config_plugins} (plugin, name, value) VALUES ('local_msm', 'enabled', '".$msm_enabled."')");
	
	$DB->execute("DELETE FROM {config_plugins} WHERE plugin='local_msm' AND name='license_key'");
	$DB->execute("INSERT INTO {config_plugins} (plugin, name, value) VALUES ('local_msm', 'license_key', '".$msm_license_key."')");

	$DB->execute("DELETE FROM {config_plugins} WHERE plugin='local_msm' AND name='status'");
	$DB->execute("INSERT INTO {config_plugins} (plugin, name, value) VALUES ('local_msm', 'status', '".$decode[0]->status."')");
	
	$DB->execute("DELETE FROM {config_plugins} WHERE plugin='local_msm' AND name='message'");
	$DB->execute("INSERT INTO {config_plugins} (plugin, name, value) VALUES ('local_msm', 'message', '".$decode[0]->message."')");
	

}


if($_GET['mode'] == "cpuramdisk_labels"){

	$records = $DB->get_records_sql("SELECT * FROM {msm_datacache} WHERE type = 'cpu_load' AND data1 >= '".$today."' ORDER BY data1 DESC LIMIT 120 ", array(1));
	foreach($records as $record){
		echo date('"h:i"', $record->data1).",";
	}
	
}

if($_GET['mode'] == "cpu"){
	
	$records = $DB->get_records_sql("SELECT * FROM {msm_datacache} WHERE type = 'cpu_load' AND data1 >= '".$today."' ORDER BY data1 DESC LIMIT 120 ", array(1));
	foreach($records as $record){
		echo $record->data2.",";
	}
	
}


if($_GET['mode'] == "ram"){

	$records = $DB->get_records_sql("SELECT * FROM {msm_datacache} WHERE type = 'memory_load' AND data1 >= '".$today."' ORDER BY data1 DESC LIMIT 120 ", array(1));
	foreach($records as $record){
		echo $record->data2.",";
	}

}


if($_GET['mode'] == "disk"){

	$records = $DB->get_records_sql("SELECT * FROM {msm_datacache} WHERE type = 'disk_total' AND data1 >= '".$today."' ORDER BY data1 DESC LIMIT 120 ", array(1));
	foreach($records as $record){
		echo $record->data2.",";
	}

}






