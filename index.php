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



$records = $DB->get_records_sql("SELECT * FROM {config_plugins} WHERE plugin='local_msm' AND name='enabled' ", array(1));
foreach($records as $record){
	$msmEnabled = $record->value;
}

if($msmEnabled != "true"){

	?>

	<h1>Moodle System Monitor is not enabled</h1>
	<a href="/local/msm/dashboard.php">Click here to enable</a>

	<?php
	die();
}


	$runtime = time();

	

	function _getServerLoadLinuxData(){
        if (is_readable("/proc/stat")){
            $stats = @file_get_contents("/proc/stat");

            if ($stats !== false){
                // Remove double spaces to make it easier to extract values with explode()
                $stats = preg_replace("/[[:blank:]]+/", " ", $stats);

                // Separate lines
                $stats = str_replace(array("\r\n", "\n\r", "\r"), "\n", $stats);
                $stats = explode("\n", $stats);

                // Separate values and find line for main CPU load
                foreach ($stats as $statLine){
                    $statLineData = explode(" ", trim($statLine));

                    // Found!
                    if(
                        (count($statLineData) >= 5) &&
                        ($statLineData[0] == "cpu")
                    ){
                        return array(
                            $statLineData[1],
                            $statLineData[2],
                            $statLineData[3],
                            $statLineData[4],
                        );
                    }
                }
            }
        }

        return null;
    }

    // Returns server load in percent (just number, without percent sign)
   
		
        $load = null;
		

		// WINDOWS
		// ----------
        if (stristr(PHP_OS, "win")){
            $cmd = "wmic cpu get loadpercentage /all";
            @exec($cmd, $output);

            if ($output){
				
                foreach ($output as $line){
                    if ($line && preg_match("/^[0-9]+\$/", $line)){
                        $load = $line;
						
						// --------
						// INSERT
						// --------
						$data = new stdClass();
						$data->type = "cpu_load";
						$data->data1 = $runtime;
						$data->data2 = $load;
						$lastinsertid = $DB->insert_record('msm_datacache', $data, false);
						
                        break;
                    }
                }
            }
        }else{
			
			// LINUX
			// ----------
            if (is_readable("/proc/stat")){
                // Collect 2 samples - each with 1 second period
                // See: https://de.wikipedia.org/wiki/Load#Der_Load_Average_auf_Unix-Systemen
                $statData1 = _getServerLoadLinuxData();
                sleep(1);
                $statData2 = _getServerLoadLinuxData();

                if(
                    (!is_null($statData1)) &&
                    (!is_null($statData2))
                ){
                    // Get difference
                    $statData2[0] -= $statData1[0];
                    $statData2[1] -= $statData1[1];
                    $statData2[2] -= $statData1[2];
                    $statData2[3] -= $statData1[3];

                    // Sum up the 4 values for User, Nice, System and Idle and calculate
                    // the percentage of idle time (which is part of the 4 values!)
                    $cpuTime = $statData2[0] + $statData2[1] + $statData2[2] + $statData2[3];

                    // Invert percentage to get CPU time, not idle time
                    $load = 100 - ($statData2[3] * 100 / $cpuTime);
                }
            }
        }

   
    //----------------------------








	// MEMORY

	// Returns used memory (either in percent (without percent sign) or free and overall in bytes)
    function getServerMemoryUsage($getPercentage=true)
    {
        $memoryTotal = null;
        $memoryFree = null;

        if (stristr(PHP_OS, "win")) {
            // Get total physical memory (this is in bytes)
            $cmd = "wmic ComputerSystem get TotalPhysicalMemory";
            @exec($cmd, $outputTotalPhysicalMemory);

            // Get free physical memory (this is in kibibytes!)
            $cmd = "wmic OS get FreePhysicalMemory";
            @exec($cmd, $outputFreePhysicalMemory);

            if ($outputTotalPhysicalMemory && $outputFreePhysicalMemory) {
                // Find total value
                foreach ($outputTotalPhysicalMemory as $line) {
                    if ($line && preg_match("/^[0-9]+\$/", $line)) {
                        $memoryTotal = $line;
                        break;
                    }
                }

                // Find free value
                foreach ($outputFreePhysicalMemory as $line) {
                    if ($line && preg_match("/^[0-9]+\$/", $line)) {
                        $memoryFree = $line;
                        $memoryFree *= 1024;  // convert from kibibytes to bytes
                        break;
                    }
                }
            }
        
		} else {
			
            if (is_readable("/proc/meminfo"))
            {
                $stats = @file_get_contents("/proc/meminfo");

                if ($stats !== false) {
                    // Separate lines
                    $stats = str_replace(array("\r\n", "\n\r", "\r"), "\n", $stats);
                    $stats = explode("\n", $stats);

                    // Separate values and find correct lines for total and free mem
                    foreach ($stats as $statLine) {
                        $statLineData = explode(":", trim($statLine));

                        //
                        // Extract size (TODO: It seems that (at least) the two values for total and free memory have the unit "kB" always. Is this correct?
                        //

                        // Total memory
                        if (count($statLineData) == 2 && trim($statLineData[0]) == "MemTotal") {
                            $memoryTotal = trim($statLineData[1]);
                            $memoryTotal = explode(" ", $memoryTotal);
                            $memoryTotal = $memoryTotal[0];
                            $memoryTotal *= 1024;  // convert from kibibytes to bytes
                        }

                        // Free memory
                        if (count($statLineData) == 2 && trim($statLineData[0]) == "MemFree") {
                            $memoryFree = trim($statLineData[1]);
                            $memoryFree = explode(" ", $memoryFree);
                            $memoryFree = $memoryFree[0];
                            $memoryFree *= 1024;  // convert from kibibytes to bytes
                        }
                    }
                }
            }
        }

        if (is_null($memoryTotal) || is_null($memoryFree)) {
            return null;
        } else {
            if ($getPercentage) {
                return (100 - ($memoryFree * 100 / $memoryTotal));
            } else {
                return array(
                    "total" => $memoryTotal,
                    "free" => $memoryFree,
                );
            }
        }
    }

    function getNiceFileSize($bytes, $binaryPrefix=true) {
        if ($binaryPrefix) {
            $unit=array('B','KiB','MiB','GiB','TiB','PiB');
            if ($bytes==0) return '0 ' . $unit[0];
            return @round($bytes/pow(1024,($i=floor(log($bytes,1024)))),2) .' '. (isset($unit[$i]) ? $unit[$i] : 'B');
        } else {
            $unit=array('B','KB','MB','GB','TB','PB');
            if ($bytes==0) return '0 ' . $unit[0];
            return @round($bytes/pow(1000,($i=floor(log($bytes,1000)))),2) .' '. (isset($unit[$i]) ? $unit[$i] : 'B');
        }
    }

	//echo '<br />------MEMORY---------<br />';
    // Memory usage: 4.55 GiB / 23.91 GiB (19.013557664178%)
    $memUsage = getServerMemoryUsage(false);
    
	/*
	echo sprintf("Memory usage: %s / %s (%s%%)",
        getNiceFileSize($memUsage["total"] - $memUsage["free"]),
        getNiceFileSize($memUsage["total"]),
        getServerMemoryUsage(true)
    );
	*/
	
	// --------
	// INSERT
	// --------
	$data = new stdClass();
	$data->type = "memory_load";
	$data->data1 = $runtime;
	$data->data2 = getServerMemoryUsage(true);
	$lastinsertid = $DB->insert_record('msm_datacache', $data, false);
	
	
	
	
	
	if (stristr(PHP_OS, "win")) {
		// --------
		// INSERT
		// --------
		$data = new stdClass();
		$data->type = "disk_total";
		$data->data1 = $runtime;
		$data->data2 = (100-(disk_free_space("C:") / disk_total_space("C:"))*100) ;
		$lastinsertid = $DB->insert_record('msm_datacache', $data, false);
	}else{
		
		// --------
		// INSERT
		// --------
		$data = new stdClass();
		$data->type = "disk_total";
		$data->data1 = $runtime;
		$data->data2 = (100-(disk_free_space("/") / disk_total_space("/"))*100) ;
		$lastinsertid = $DB->insert_record('msm_datacache', $data, false);
		
	}
	
	
	
	function get_dir_size($directory){
		$size = 0;
		$files = glob($directory.'/*');
		foreach($files as $path){
			is_file($path) && $size += filesize($path);
			is_dir($path)  && $size += get_dir_size($path);
		}
		return $size/1000;
	} 
	
	
	// =====================================
	//
	//
	// MOODLE SPECIFIC DIRECTORIES
	//
	//
	// =====================================
	
	
	
	//echo '<BR/><BR/>CURRENT PATH:'.getcwd();
	
	//echo '<br /> dirroot : '.$CFG->dirroot ;
	//echo '<br /> Data Root: '.$CFG->dataroot;
	
	//echo '<br /> dirroot : '.$CFG->wwwroot.' ('.get_dir_size($CFG->wwwroot).')   - $CFG->wwwroot  - Path to moodle index directory in url format.';
	
	
	/*
	echo '<br /> dirroot : '.$CFG->dirroot.' ('.get_dir_size($CFG->dirroot).')   - $CFG->dirroot  - Path to moodles library folder on servers filesystem.';
	echo '<br /> dirroot : '.$CFG->libdir.' ('.get_dir_size($CFG->libdir).')   - $CFG->libdir   - Path to moodles library folder on servers filesystem.';

	echo '<br />';echo '<br />';

	echo '<br /> dirroot : '.$CFG->dataroot.' ('.get_dir_size($CFG->dataroot).')   - $CFG->dataroot - Path to moodle data files directory on servers filesystem.';
	echo '<br /> dirroot : '.$CFG->tempdir.' ('.get_dir_size($CFG->tempdir).')  - $CFG->tempdir  - Path to moodles temp file directory on servers filesystem.';
	echo '<br /> dirroot : '.$CFG->cachedir.' ('.get_dir_size($CFG->cachedir).')  - $CFG->cachedir - Path to moodles cache directory on servers filesystem (shared by cluster nodes).';
	echo '<br /> dirroot : '.$CFG->localcachedir.' ('.get_dir_size($CFG->localcachedir).')   - $CFG->localcachedir - Path to moodles local cache directory (not shared by cluster nodes).';
	*/
	
	
		
	// --------
	// INSERT
	// --------
	$data = new stdClass();
	$data->type = "disk_moodle_dirroot";
	$data->data1 = $runtime;
	$data->data2 = get_dir_size($CFG->dirroot);
	$lastinsertid = $DB->insert_record('msm_datacache', $data, false);	
	
	// --------
	// INSERT
	// --------
	$data = new stdClass();
	$data->type = "disk_moodle_libdir";
	$data->data1 = $runtime;
	$data->data2 = get_dir_size($CFG->libdir);
	$lastinsertid = $DB->insert_record('msm_datacache', $data, false);	
	
	// --------
	// INSERT
	// --------
	$data = new stdClass();
	$data->type = "disk_moodle_dataroot";
	$data->data1 = $runtime;
	$data->data2 = get_dir_size($CFG->dataroot);
	$lastinsertid = $DB->insert_record('msm_datacache', $data, false);	
	
	// --------
	// INSERT
	// --------
	$data = new stdClass();
	$data->type = "disk_moodle_tempdir";
	$data->data1 = $runtime;
	$data->data2 = get_dir_size($CFG->tempdir);
	$lastinsertid = $DB->insert_record('msm_datacache', $data, false);	
	
	// --------
	// INSERT
	// --------
	$data = new stdClass();
	$data->type = "disk_moodle_cachedir";
	$data->data1 = $runtime;
	$data->data2 = get_dir_size($CFG->cachedir);
	$lastinsertid = $DB->insert_record('msm_datacache', $data, false);	
	
	// --------
	// INSERT
	// --------
	$data = new stdClass();
	$data->type = "disk_moodle_localcachedir";
	$data->data1 = $runtime;
	$data->data2 = get_dir_size($CFG->localcachedir);
	$lastinsertid = $DB->insert_record('msm_datacache', $data, false);	
	
	
	
	
	
	
	
	
	
	
	
	// =====================================
	//
	//
	// DATABASE TABLES
	// DATABASE SIZE / ROWS 
	//
	//
	// =====================================	
	
	$tables = ($DB->get_records_sql('SELECT table_name AS "Table", ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size_mb", table_rows AS table_rows FROM information_schema.TABLES WHERE table_schema = "moodledev" ORDER BY (table_name) ASC;'));
	$total_db_size = 0;
	$total_db_rows = 0;
	foreach($tables as $table){
		
		//var_dump($table);
		
		// echo ("$table->table | $table->size_mb | $table->table_rows <br />");
		
		// --------
		// INSERT
		// --------
		$data = new stdClass();
		$data->type = "database_table";
		$data->data1 = $runtime;
		$data->data2 = $table->table;
		$data->data3 = $table->size_mb;
		$data->data4 = $table->table_rows;
		
		$lastinsertid = $DB->insert_record('msm_datacache', $data, false);	
		
		
		$total_db_size += $table->size_mb;
		$total_db_rows += $table->table_rows;
		
	}
	
	// echo " <br /> TOTAL DB SIZE: $total_db_size <br />";
	// echo " <br /> TOTAL DB ROWS: $total_db_rows <br />";
		
	// --------
	// INSERT
	// --------
	$data = new stdClass();
	$data->type = "database_total_size";
	$data->data1 = $runtime;
	$data->data2 = $total_db_size;
	$lastinsertid = $DB->insert_record('msm_datacache', $data, false);	

	// --------
	// INSERT
	// --------
	$data = new stdClass();
	$data->type = "database_total_rows";
	$data->data1 = $runtime;
	$data->data2 = $total_db_rows;
	$lastinsertid = $DB->insert_record('msm_datacache', $data, false);		
		
	
	
	//echo 'success';
	echo (time() - $_SERVER['REQUEST_TIME']);
	
	



die();


/*
$thisIsmsm = true; // Used to process frontend and backend plugins

$msmKey = get_config('local_msm', 'key');
$msmAmountToProcess = 10;

$msmPlugin_plugin_NED_block = get_config('local_msm', 'plugin_ned_block_teacher_tools');
$msmPlugin_plugin_YU_overdueAssignmentsToZero = get_config('local_msm', 'plugin_msm_overdue_assignments');



echo "msm processing: ".$msmAmountToProcess ;


if($_GET['key'] != $msmKey){
	echo "<h1>invalid key</h1>";
	die();
}

function debugMessage($type, $message){

	if( isset($_GET['debugMessages']) ){

		if($_GET['debugMessages'] == "true"){

			if( is_string($message) ){
				echo "<$type>$message</$type>";
			}

			if( $type=="dump" ){
				echo "<pre>";
				var_dump($message);
				echo "</pre>";
			}


		}

	}

	return false;

}








$startTime = microtime(true);


// Start Moodle & Libraries
// ------------------------

//require_once $CFG->libdir.'/gradelib.php';
//require_once $CFG->dirroot.'/grade/lib.php';
//require_once $CFG->dirroot.'/grade/report/lib.php';

//require_once $CFG->dirroot.'/mod/assign/externallib.php'; // New attempt to save external grades -- ONLY works for assignments darnit...
//$externalGrade = new mod_assign_external;

//require_once $CFG->dirroot.'/mod/quiz/lib.php'; // Maybe we can pull directly from this lib?

//global $USER, $DB;


// RTMS Que Check & Build
// ----------------------

$refillQue = "empty";
$ques = $DB->get_records_sql('SELECT id FROM mdl_msm_courseque LIMIT 1', array(1));
foreach($ques as $que){
	$refillQue = "full";
}
debugMessage ("h1", "Overdue que status: ".$refillQue);

if($refillQue == "empty"){

	debugMessage ("h2", "Refilling Que with ALL Courses!");

	$channelCount = 1;

	$courses = $DB->get_records_sql('SELECT id FROM mdl_course', array(1));
	foreach($courses as $course){

		$theCourse = new stdClass();
		$theCourse->quechannel = $channelCount;
		$theCourse->courseid = $course->id;
		$theCourse->timeadded = time();

		if($theCourse->courseid != 1){ // 1 is the homepage, skip this
			$lastinsertid = $DB->insert_record('msm_courseque', $theCourse, false);
		}

		$channelCount++;
		if($channelCount >= 5){
			$channelCount = 1;
		}

	}

	$batches = $DB->get_records_sql('SELECT id FROM {msm_logs} WHERE data="new"', array(1));
	foreach($batches as $batch){
		$DB->execute("UPDATE {msm_logs} SET runtime=".time().", data='complete' WHERE data='new'");
	}

	$DB->execute("INSERT INTO {msm_logs} (time, runtime, amount, type, data) VALUES (".time().", 0, '".count($courses)."', 'batch', 'new')");

}else{
	debugMessage("h2", "Que has data, process!");
}


// STEP DOWN HIGH CHANNEL COURSES

// Channel 1
$channel1Found = false;
$scans1 = $DB->get_records_sql('SELECT id FROM mdl_msm_courseque WHERE quechannel = 1 ', array(1));
foreach($scans1 as $scan){
	$channel1Found = true;
}

// Channel 2
$channel2Found = false;
$scans2 = $DB->get_records_sql('SELECT id FROM mdl_msm_courseque WHERE quechannel = 2 ', array(1));
foreach($scans2 as $scan){
	$channel2Found = true;
}

// Channel 3
$channel3Found = false;
$scans3 = $DB->get_records_sql('SELECT id FROM mdl_msm_courseque WHERE quechannel = 3 ', array(1));
foreach($scans3 as $scan){
	$channel3Found = true;
}

// Channel 4
$channel4Found = false;
$scans4 = $DB->get_records_sql('SELECT id FROM mdl_msm_courseque WHERE quechannel = 4 ', array(1));
foreach($scans4 as $scan){
	$channel4Found = true;
}


debugMessage("h2", "Channel 1: ".$channel1Found);
debugMessage("h2", "Channel 2: ".$channel2Found);
debugMessage("h2", "Channel 3: ".$channel3Found);
debugMessage("h2", "Channel 4: ".$channel4Found);


if($channel4Found && !$channel3Found){

	$newQueChannel = 3;
	foreach($scans4 as $scan){
		$DB->execute("UPDATE mdl_msm_courseque SET quechannel=".$newQueChannel." WHERE id=".$scan->id);
		$newQueChannel --;
		if($newQueChannel == 0){
			$newQueChannel = 3;
		}
	}

}
if($channel3Found && !$channel2Found){
	
	$newQueChannel = 2;
	foreach($scans3 as $scan){
		$DB->execute("UPDATE mdl_msm_courseque SET quechannel=".$newQueChannel." WHERE id=".$scan->id);
		$newQueChannel --;
		if($newQueChannel == 0){
			$newQueChannel = 2;
		}
	}

}
if($channel2Found && !$channel1Found){
	
	$newQueChannel = 1;
	foreach($scans2 as $scan){
		$DB->execute("UPDATE mdl_msm_courseque SET quechannel=".$newQueChannel." WHERE id=".$scan->id);
		$newQueChannel --;
		if($newQueChannel == 0){
			$newQueChannel = 1;
		}
	}

}


// DEFINE LOCKS



// CHECK FOR LOCKS, CLEAR OR DIE
// -----------------------------

for($selectedQueChannel = 1; $selectedQueChannel <= 4; $selectedQueChannel++){

	debugMessage ("h1", "<div style='color:darkgreen;'>SCANNING CHANNEL: ".$selectedQueChannel."</div>");

	$processThisQue = true;
	
	// If there is a match detected, 1/2/3/4 it will die, the previous job hasn't finished.
	$queLocks = $DB->get_records_sql('SELECT * FROM mdl_msm_coursequelocks WHERE quechannel = '.$selectedQueChannel.'', array(1));
	foreach($queLocks as $queLock){

		debugMessage ("h1", "Previous Que Still Running, EXIT -> Channel: ".$selectedQueChannel);


		debugMessage ("p", var_dump($queLock) );
		debugMessage("h2", "Lock Duration: ". (time() - $queLock->time));

		if((time() - $queLock->time) >= 10){ //Five Minutes = 300s
			debugMessage("h2", "Que has been running for some time and is perhaps stuck. Removing lock");
			$DB->execute('DELETE FROM mdl_msm_coursequelocks WHERE id='.$queLock->id);
		}

		$processThisQue = false;

		debugMessage ("h1", "Next Channel: ".$selectedQueChannel);
		break;

	}


	if($processThisQue){
		// CREATE NEW LOCK
		// ---------------
		$theLock = new stdClass();
		$theLock->quechannel = $selectedQueChannel;
		$theLock->status = "running";
		$theLock->time = time();
		$theLockId = $DB->insert_record('msm_coursequelocks', $theLock, true);
		debugMessage ("p","LOCKING CHANNEL: ".$selectedQueChannel);
		debugMessage ("p",$theLockId);





		// BEGIN COURSE SCANNING
		$quedCourses = $DB->get_records_sql('SELECT * FROM mdl_msm_courseque WHERE quechannel = '.$selectedQueChannel.' LIMIT '.$msmAmountToProcess, array(1));
		debugMessage ("p","Processing Channel: " .$selectedQueChannel );
		debugMessage ("p", count($quedCourses) );
		debugMessage ("p","===========================");





		foreach($quedCourses as $theCourse){

			debugMessage ("p","===========================");
			debugMessage ("p","=== COURSE ID: ".$theCourse->courseid." ============");
			debugMessage ("p","===========================");

			$courses = $DB->get_records_sql('SELECT id FROM {course} WHERE id='.$theCourse->courseid, array(1));
			if($courses){
				debugMessage ("p","COURSE FOUND, YOU MAY PROCEED");
			}else{
				debugMessage ("p","NO COURSE WAS FOUND -- THE COURSE WAS LIKELY DELETED BETWEEN REAL TIME CYCLE -- REMOVING FROM QUE");
				$DB->execute('DELETE FROM mdl_msm_courseque WHERE courseid='.$theCourse->courseid);
			}


			$startTimeCourseProcess = microtime(true);
			foreach($courses as $course){


				// no longer using the /plugins/enabled structure, let us run from a database
				
				//foreach (glob("plugins/enabled/*.php") as $filename){
				//    include $filename;
				//}
				

				if(get_config('local_msm', 'plugin_ned_block_teacher_tools') == "1"){
					debugMessage ("p","RUNNING NED BLOCK");
					require($CFG->dirroot."/blocks/ned_teacher_tools/msm/refresh_all_data.php");
				}
				
				if(get_config('local_msm', 'plugin_msm_overdue_assignments') == "1"){
					debugMessage ("p","YU Overdue Assignments");
					require("plugins/YU_overdueAssignmentsToZero.php");
				}
				
				if(get_config('local_msm', 'plugin_msm_midterm_grades_cmc') == "1"){
					debugMessage ("p","YU Midterms CMC");
					require("plugins/YU_midterm_grades_CMC.php");
				}

				if(get_config('local_msm', 'plugin_msm_ghosted_users') == "1"){
					debugMessage ("p","YU Ghosted Users");
					require("plugins/YU_ghosted_users.php");
				}
				
				if(get_config('local_msm', 'plugin_attendance') == "1"){
					debugMessage ("p","YU Attendance");
					require("plugins/YU_attendance.php");
				}

				

				debugMessage ("p","clearing qued course");
				$DB->execute('DELETE FROM {msm_courseque} WHERE courseid='.$course->id);

				$runtimeCourse = "".round( (microtime(true) - $startTimeCourseProcess), 5);
				$DB->execute("INSERT INTO {msm_logs} (time, runtime, amount, type, data) VALUES (".time().", $runtimeCourse, '1', 'course', $course->id)");

			}

		}


		debugMessage ("p","clearing qued course:".$theLockId);
		$DB->execute('DELETE FROM {msm_coursequelocks} WHERE id='.$theLockId);

		debugMessage ("p", '<hr />');
		debugMessage ("p", '<h1 style="color:green;">COMPLETED '.$msmAmountToProcess.' JOBS SUCCESSFULLY: '.(microtime(true) - $startTime).' seconds</h1>');

		$runtime = "".round( (microtime(true) - $startTime), 5);

		echo "completed $msmAmountToProcess jobs in: ".$runtime." seconds";


		// ADD LOG
		// ---------------
		$msmLog = new stdClass();
		$msmLog->time = time();
		$msmLog->runtime = $runtime;
		$msmLog->amount = $msmAmountToProcess;

		var_dump($msmLog);
		//$lastinsertid = $DB->insert_record('msm_logs', $msmLog, false);
		$DB->execute("INSERT INTO {msm_logs} (time, runtime, amount, type, data, quechannel) VALUES ($msmLog->time, $msmLog->runtime, $msmLog->amount, 'time', '', $selectedQueChannel)");


		// DIE
		// DIE
		die();
		// DIE
		// DIE

		// We do not want to re-run any logic here, other msm jobs should be running already

	}

}
*/



?>
