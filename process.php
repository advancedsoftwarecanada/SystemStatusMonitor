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
 * Extends cron to give you system status information.
 * Note: Disk checking takes a considerable amount of time
 *
 * @package local_systemstatusmonitor
 * @author Andrew Normore<andrewnormore@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2019 onwards Advanced Software Engineering Corporation of Canada
 * @website https://www.advancedsoftware.ca
 */

require_once("../../config.php");
require_once("classes/system_stats.php");

// Is the plugin enabled?
$enabled = false;
$checkenabled = $DB->get_record('config_plugins', ['plugin' => 'local_systemstatusmonitor', 'name' => 'enabled']);
if ($checkenabled) {
    $enabled = $checkenabled->value;
}

// Create an instance of the server_load class using the correct namespace.
$systemstats = new \local_systemstatusmonitor\system_stats();

// Create the stats object.
$stats = new \stdClass();

// Determine database type.
$stats->dbtype = $systemstats->get_db_type(); // comes from classes/system_stats.php

// Determine operating system.
$stats->os = $systemstats->get_os(); // comes from classes/system_stats.php

// Determine server load depending on the operating system.
if ($stats->os == "windows") {
	$stats->serverload = $systemstats->get_windows_server_load();
}else{
	$stats->serverload = $systemstats->get_liniux_server_load();
}

// Determine memory load depending on the operating system.
$stats->memoryload = $systemstats->get_server_memory_useage(false);
$stats->memoryloadpercent = $systemstats->get_server_memory_useage(true);

// Determine Disk Health.
$stats->diskload = $systemstats->disk_used();

// Determine Moodle specific directory sizes
// IMPACT: High wait time, should reduce the polling time for this.
$stats->moodledirs = $systemstats->get_moodle_data_disk_usage();

// Get Database health.
$stats->database = $systemstats->get_database_size();

// Get Courses count.
$stats->courses = $systemstats->get_courses_count();

// Get users count.
$stats->users = $systemstats->get_users_count();

// Determine execution time of this script.
$stats->executiontimeseconds = (time() - $_SERVER['REQUEST_TIME']);

// Complete output.
// Convert stats to a json output
echo json_encode($stats);

die();

?>
