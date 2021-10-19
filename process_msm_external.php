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


if($msm_config_enabled != "true"){

	?>

	<h1>Moodle System Monitor is not enabled</h1>
	<a href="/local/msm/dashboard.php">Click here to enable</a>

	<?php
	die();
}


	// JSON Object
	$json_response = [];
	$json_response['runtime'] = time();


	// =================================
	//
	// Support multiple database types
	//
	// =================================
	$database_type = 1;
	if($CFG->dbtype == "mariadb"){
		$database_type = 1;
	}
	if($CFG->dbtype == "pgsql"){
		$database_type = 2;
	}
	

	

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
						
						$json_response['cpu_load'] = round($load);
						
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
					
					
					$json_response['cpu_load'] = round($load);
					
                }
            }
        }

   
    //----------------------------








	// MEMORY

	// Returns used memory (either in percent (without percent sign) or free and overall in bytes)
    function getServerMemoryUsage($getPercentage=true){
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
	
	$json_response['memory_load'] = round(getServerMemoryUsage(true));
	
	
	
	
	if (stristr(PHP_OS, "win")) {
		
		$json_response['disk_total'] = round(100-(disk_free_space("C:") / disk_total_space("C:"))*100) ;
		
	}else{
		
		$json_response['disk_total'] = round(100-(disk_free_space("/") / disk_total_space("/"))*100) ;
		
	}
	
	
	
	function get_dir_size($directory){
		$size = 0;
		$files = glob($directory.'/*');
		foreach($files as $path){
			is_file($path) && $size += filesize($path);
			is_dir($path)  && $size += get_dir_size($path);
		}
		
		return round(($size/1024));
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
	
	
	
	$json_response['disk_moodle_dirroot'] = get_dir_size($CFG->dirroot);
	$json_response['disk_moodle_libdir'] = get_dir_size($CFG->libdir);
	$json_response['disk_moodle_dataroot'] = get_dir_size($CFG->dataroot);
	$json_response['disk_moodle_tempdir'] = get_dir_size($CFG->tempdir);
	$json_response['disk_moodle_cachedir'] = get_dir_size($CFG->cachedir);
	$json_response['disk_moodle_localcachedir'] = get_dir_size($CFG->localcachedir);
	
	
	
	
	
	// =====================================
	//
	//
	// AUTOMATED BACKUP DIRECTORY
	//
	//
	// =====================================
	$tables = ($DB->get_records_sql("SELECT * FROM {config_plugins} WHERE plugin='backup' AND name='backup_auto_destination' "));
	foreach($tables as $table){
		
		if(is_dir($table->value)){
			//var_dump($table->value);
			//var_dump(disk_free_space($table->value));
			
			$json_response['disk_moodle_backup_auto_destination'] = round(get_dir_size($table->value)/1000);
			$json_response['disk_total_moodle_backup_auto_destination'] = round((100-(disk_free_space($table->value) / disk_total_space($table->value))*100)) ;
		
		}else{
			//var_dump("NOT FOUND");
			// TODO: THIS IS AN ALERT EXAMPLE
			// 
			$json_response['disk_moodle_backup_auto_destination'] = 0;
			$json_response['disk_total_moodle_backup_auto_destination'] = 0;
		}
		
		
	}
	
	
	
	
	
	// =====================================
	//
	//
	// DATABASE TABLES
	// DATABASE SIZE / ROWS 
	//
	//
	// =====================================	
	

	// ==============
	// DATABASE 1 
	// ==============
	if($database_type == 1){
		$tables = ($DB->get_records_sql("SELECT table_name AS 'Table', ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size_mb', table_rows AS table_rows FROM information_schema.TABLES WHERE table_schema = '".$CFG->dbname."' ORDER BY (table_name) ASC;"));
		
		$total_db_size = 0;
		$total_db_rows = 0;
		foreach($tables as $table){
			
			//var_dump($table);
			
			// echo ("$table->table | $table->size_mb | $table->table_rows <br />");
			
			$json_response['database_table'][$table->table]['size_mb'] = round($table->size_mb);
			$json_response['database_table'][$table->table]['table_rows'] = round($table->table_rows);
			
			$total_db_size += $table->size_mb;
			$total_db_rows += $table->table_rows;
			
		}
		$json_response['database_total_size'] = round($total_db_size);
		$json_response['database_total_rows'] = round($total_db_rows);
		
		
	}
	
	// ==============
	// DATABASE 2 
	// ==============
	if($database_type == 2){
		
		$tables = ($DB->get_records_sql("
			SELECT
			  pgClass.relname   AS tableName,
			  pgClass.reltuples AS rowCount
			  pg_relation_size(quote_ident(pgClass.relname)) as size_mb
			FROM
			  pg_class pgClass
			INNER JOIN
			  pg_namespace pgNamespace ON (pgNamespace.oid = pgClass.relnamespace)
			WHERE
			  pgNamespace.nspname NOT IN ('pg_catalog', 'information_schema') AND
			  pgClass.relkind='r'"));
		
		$total_db_size = 0;
		$total_db_rows = 0;
		foreach($tables as $table){
			
			$json_response['database_table'][$table->tableName]['size_mb'] = round(($table->size_mb)/1024);
			$json_response['database_table'][$table->tableName]['table_rows'] = round($table->rowCount);
			
			$total_db_size += $table->size_mb;
			$total_db_rows += $table->table_rows;
			
		}
		$json_response['database_total_size'] = round($total_db_size);
		$json_response['database_total_rows'] = round($total_db_rows);

	}
	
	
	// =====================================
	//
	//
	// COURSES
	//
	//
	// =====================================	
	$courses_total = 0;
	$tables = ($DB->get_records_sql("SELECT id FROM {course}"));
	foreach($tables as $table){
		
		$courses_total += 1;
		
	}
	$json_response['courses_total'] = round($courses_total);
	
	
	// =====================================
	//
	//
	// Users
	//
	//
	// =====================================	
	$users_total = 0;
	$users_online_now = 0;
	$tables = ($DB->get_records_sql("SELECT id, lastaccess FROM {user} "));
	foreach($tables as $table){
		
		$users_total += 1;
		if( time() - $table->lastaccess <= 300000){
			$users_online_now += 1;
		}		
		
	}
	$json_response['users_total'] = round($users_total);
	$json_response['users_online_now'] = round($users_online_now);
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	// =====================================
	//
	//
	// Finished
	//
	//
	// =====================================
	$json_response['license_key'] = $msm_config_license_key->value;
	$json_response['execution_time'] = (time() - $_SERVER['REQUEST_TIME']);
	
	$json_response = json_encode($json_response, JSON_HEX_QUOT);
	
	echo( $json_response );


die();



?>
