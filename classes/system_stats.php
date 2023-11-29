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
 * Defines available state_types.
 * @package local_systemstatusmonitor
 * @author Andrew Normore<andrewnormore@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2019 onwards Advanced Software Engineering Corporation of Canada
 * @website https://www.advancedsoftware.ca
 */

namespace local_systemstatusmonitor;


class system_stats {

    /**
     * Determine database type
     *
     * @return string returns the database type
     */
    public function get_db_type() {

        global $CFG;

        // Support multiple database types.
        return $CFG->dbtype;

    }

    /**
     * Convert bytes to a human readable format
     *
     * @param int $bytes the number of bytes to convert
     * @param bool $binaryPrefix if true, uses binary prefixes (e.g. 1 KiB = 1024 bytes)
     *
     * @return string returns a human readable string
     */
    public function bytes_to_nicename($bytes, $binaryPrefix = true) {

        $unit = $binaryPrefix ? ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'] : ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        if ($bytes == 0) {
            return '0 ' . $unit[0];
        }

        $base = $binaryPrefix ? 1024 : 1000;
        $i = floor(log($bytes, $base));

        return round($bytes / pow($base, $i), 2) . ' ' . (isset($unit[$i]) ? $unit[$i] : 'B');

    }

    /**
     * Determine directory size
     *
     * @param string $directory the directory to check
     *
     * @return int returns the size of the directory in kilobytes
     */
    public function get_directory_size($directory) {
        $size = 0;
        $files = glob($directory.'/*');
        foreach($files as $path) {
            is_file($path) && $size += filesize($path);
            is_dir($path)  && $size += $this->get_directory_size($path);
        }

        return round(($size/1024));
    }

    /**
     * Determine Windows or Unix operating system
     *
     * @return string returns the operating system
     */
    public function get_os() {

        $isWindows = false;

        // Check PHP_OS constant.
        if (stristr(PHP_OS, 'win')) {
            $isWindows = true;
        }

        // Check for common Windows environment variables.
        if (getenv('WINDIR') || getenv('SystemRoot')) {
            $isWindows = true;
        }

        // Check for the existence of Windows-specific functions.
        if (function_exists('com_create_guid') || function_exists('com_message_pump')) {
            $isWindows = true;
        }

        // Check for a Windows-specific directory separator.
        if (DIRECTORY_SEPARATOR === '\\') {
            $isWindows = true;
        }

        if ($isWindows) {
            return 'windows';
        } else {
            return 'linux';
        }

    }

    /**
     * Get server load for a Windows server
     *
     * @return array
     */
    public function get_windows_server_load() {

        $json_response = array();

        $cmd = "wmic cpu get loadpercentage /all";
		@exec($cmd, $output);

		if ($output) {

			foreach ($output as $line) {
				if ($line && preg_match("/^[0-9]+\$/", $line)) {
					$load = $line;

					$json_response['cpu_load'] = round($load);

					break;
				}
			}
		}

        return $json_response;

    }

    /**
     * Get the momentary server load to compare again in 1 second to determine accurate server load on unix systems
     *
     * @return array
     */
    public function get_liniux_server_load_moment() {

        if (is_readable("/proc/stat")) {
            $stats = @file_get_contents("/proc/stat");

            if ($stats !== false) {
                // Remove double spaces to make it easier to extract values with explode()
                $stats = preg_replace("/[[:blank:]]+/", " ", $stats);

                // Separate lines
                $stats = str_replace(array("\r\n", "\n\r", "\r"), "\n", $stats);
                $stats = explode("\n", $stats);

                // Separate values and find line for main CPU load
                foreach ($stats as $statLine) {
                    $statLineData = explode(" ", trim($statLine));

                    // Found!
                    if(
                        (count($statLineData) >= 5) &&
                        ($statLineData[0] == "cpu")
                    ) {
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

    /**
     * Get server load for a Unix server
     *
     * @return array returns an array containing the load in percent for the last minute, 5 minutes, 15 minutes and the number of processes.
     */
    public function get_liniux_server_load() {

        $json_response = array();

        if (is_readable("/proc/stat")) {

			// Collect 2 samples - each with 1 second period
			// See: https://de.wikipedia.org/wiki/Load#Der_Load_Average_auf_Unix-Systemen
			$statData1 = $this->get_liniux_server_load_moment();
			sleep(1);
			$statData2 = $this->get_liniux_server_load_moment();

			if(
				(!is_null($statData1)) &&
				(!is_null($statData2))
			) {
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

        return $json_response;

    }

    /**
     * Get server memory usage
     *
     * @param bool $getPercentage if true, returns the percentage of memory used, if false, returns an array containing the total memory and free memory in bytes
     *
     * @return array returns an array containing the total memory and free memory in bytes
     */
    public function get_server_memory_useage($getPercentage=true) {

        $memoryTotal = null;
        $memoryFree = null;

        if ( $this->get_os() == "windows" ) {
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
                    "bytestotal" => $memoryTotal,
                    "bytesfree" => $memoryFree,
                );
            }
        }
    }

    /**
     * Get server disk usage
     *
     * @return array returns an array containing the total disk space and free disk space in bytes
     */
    public function disk_used () {

        if ( $this->get_os() == "windows" ) {
            $disk_total = disk_total_space("C:");
            $disk_free = disk_free_space("C:");
        } else {
            $disk_total = disk_total_space("/");
            $disk_free = disk_free_space("/");
        }

        $disk_used = $disk_total - $disk_free;

        return array(
            "bytestotal" => $disk_total,
            "bytesfree" => $disk_free,
            "bytesused" => $disk_used,
        );

    }

    /**
     * Get Moodle specific data directory disk usage. This can be very slow.
     *
     * @return array returns an array containing the total disk space and free disk space in bytes
     */
    public function get_moodle_data_disk_usage() {

        global $CFG, $DB;

        $json_response = array();

        $json_response['dirroot'] = $this->get_directory_size($CFG->dirroot);
        $json_response['libdir'] = $this->get_directory_size($CFG->libdir);
        $json_response['dataroot'] = $this->get_directory_size($CFG->dataroot);
        $json_response['tempdir'] = $this->get_directory_size($CFG->tempdir);
        $json_response['cachedir'] = $this->get_directory_size($CFG->cachedir);
        $json_response['localcachedir'] = $this->get_directory_size($CFG->localcachedir);

        $tables = ($DB->get_records_sql("SELECT * FROM {config_plugins} WHERE plugin='backup' AND name='backup_auto_destination' "));
        foreach($tables as $table) {

            if (is_dir($table->value)) {
                $json_response['disk_moodle_backup_auto_destination'] = round(get_dir_size($table->value)/1000);
                $json_response['disk_total_moodle_backup_auto_destination'] = round((100-(disk_free_space($table->value) / disk_total_space($table->value))*100)) ;
            }else{
                $json_response['disk_moodle_backup_auto_destination'] = 0;
                $json_response['disk_total_moodle_backup_auto_destination'] = 0;
            }

        }

        return $json_response;

    }

    /**
     * Get database size
     *
     * @return array returns an array containing the total database size and free database space in bytes
     */
    public function get_database_size() {

        global $CFG, $DB;

        $json_response = array();

        // MySQL, MySQLi, MariaDB support
        if ($this->get_db_type() == "mysqli") {
            $tables = ($DB->get_records_sql("SELECT table_name AS 'Table', ROUND(((data_length + index_length) ), 2) AS 'Size_b', table_rows AS table_rows FROM information_schema.TABLES WHERE table_schema = '".$CFG->dbname."' ORDER BY (table_name) ASC;"));

            $total_db_size = 0;
            $total_db_rows = 0;
            foreach($tables as $table) {

                $json_response['database_table'][$table->table]['size_b'] = round($table->size_b);
                $json_response['database_table'][$table->table]['table_rows'] = round($table->table_rows);

                $total_db_size += $table->size_b;
                $total_db_rows += $table->table_rows;

            }

            $json_response['database_total_size'] = round($total_db_size);
            $json_response['database_total_rows'] = round($total_db_rows);

        }

        // Postgres support
        if ($this->get_db_type() == "pgsql") {

            $tables = ($DB->get_records_sql("
                SELECT
                    pgClass.relname   AS tableName,
                    pgClass.reltuples AS rowCount,
                    pg_relation_size(quote_ident(pgClass.relname)) as size_b
                FROM
                    pg_class pgClass
                INNER JOIN
                    pg_namespace pgNamespace ON (pgNamespace.oid = pgClass.relnamespace)
                WHERE
                    pgNamespace.nspname NOT IN ('pg_catalog', 'information_schema') AND
                    pgClass.relkind='r'"));

            $total_db_size = 0;
            $total_db_rows = 0;
            foreach($tables as $table) {

                $json_response['database_table'][$table->tableName]['size_b'] = round(($table->size_b));
                $json_response['database_table'][$table->tableName]['table_rows'] = round($table->rowCount);

                $total_db_rows += $table->table_rows;
                $total_db_size += $table->size_b;

            }

            $json_response['database_total_size'] = round($total_db_size);
            $json_response['database_total_rows'] = round($total_db_rows);

        }

        return $json_response;

    }

    /**
     * Get courses count
     *
     * @return array returns an array containing the cron status
     */
    public function get_courses_count() {

        global $DB;

        $json_response = array();

        $courses_total = 0;
        $tables = ($DB->get_records_sql("SELECT id FROM {course}"));

        foreach($tables as $table) {

            $courses_total += 1;

        }

        $json_response['courses_total'] = round($courses_total);

        return $json_response;

    }

    /**
     * Get users count
     *
     * @return array returns an array containing the cron status
     */
    public function get_users_count() {

        global $DB;

        $json_response = array();

        $users_total = 0;
        $users_online_now = 0;
        $tables = ($DB->get_records_sql("SELECT id, lastaccess FROM {user} "));
        foreach($tables as $table) {

            $users_total += 1;
            if( time() - $table->lastaccess <= 300000) {
                $users_online_now += 1;
            }

        }
        $json_response['users_total'] = round($users_total);
        $json_response['users_online_now'] = round($users_online_now);

        return $json_response;

    }


}