<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2012 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    rlip
 * @subpackage blocks_rlip
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

// External RLIP 'cron' processing file
define('CLI_SCRIPT', 1);

require_once(dirname(__FILE__) .'/../../config.php');
require_once($CFG->dirroot .'/elis/core/lib/tasklib.php');
require_once($CFG->dirroot .'/blocks/rlip/lib.php');
require_once($CFG->dirroot .'/blocks/rlip/rlip_dataplugin.class.php');
require_once($CFG->dirroot .'/blocks/rlip/rlip_fileplugin.class.php');
require_once($CFG->dirroot .'/blocks/rlip/rlip_importprovider_csv.class.php');

$filename = basename(__FILE__);
$disabledincron = get_config('rlip', 'disableincron');
if (empty($disabledincron)) {
    exit(0);
}

global $USER;
$USER = get_admin();

// TBD: adjust some php variables for the execution of this script
set_time_limit(0);
@ini_set('max_execution_time', '3000');
if (empty($CFG->extramemorylimit)) {
    raise_memory_limit('128M');
} else {
    raise_memory_limit($CFG->extramemorylimit);
}

mtrace('RLIP external cron start - Server Time: '. date('r', time()) ."\n");

$pluginstorun = array('rlipimport', 'rlipexport');

$timenow = time();
$params = array('timenow' => $timenow);
$tasks = $DB->get_recordset_select('elis_scheduled_tasks', 'nextruntime <= :timenow', $params, 'nextruntime ASC');
if ($tasks && $tasks->valid()) {
    foreach ($tasks as $task) {
        // Make sure we have an import/export task
        $taskparts = explode('_', $task->taskname);
        if (count($taskparts) < 2 || $taskparts[0] !== 'ipjob') {
            continue;
        }
        $id = $taskparts[1];

        // Get ipjob from ip_schedule
        $ipjob = $DB->get_record(RLIP_SCHEDULE_TABLE, array('id' => $id));
        if (empty($ipjob)) {
            mtrace("{$filename}: DB Error retrieving IP schedule record for taskname '{$task->taskname}' - aborting!");
            continue;
        }

        // validate plugin
        $plugin = $ipjob->plugin;
        $plugparts = explode('_', $plugin);
        if (!in_array($plugparts[0], $pluginstorun)) {
            mtrace("{$filename}: RLIP plugin '{$plugin}' not configured to run externally - aborting!");
            continue;
        }

        $rlip_plugins = get_plugin_list($plugparts[0]);
        //print_object($rlip_plugins);
        if (!array_key_exists($plugparts[1], $rlip_plugins)) {
            mtrace("{$filename}: RLIP plugin '{$plugin}' unknown!");
            continue;
        }

        mtrace("{$filename}: Processing external cron function for: {$plugin}, taskname: {$task->taskname} ...");

        //determine the "ideal" target start time
        $targetstarttime = $ipjob->nextruntime;

        // Set the next run time & lastruntime
        //record last runtime
        $lastruntime = $ipjob->lastruntime;

        $data = unserialize($ipjob->config);
        $state = isset($data['state']) ? $data['state'] : null;

        //update next runtime on the scheduled task record
        $nextruntime = $ipjob->nextruntime;
        $timenow = time();
        do {
            $nextruntime += (int)rlip_schedule_period_minutes($data['period']) * 60;
        } while ($nextruntime <= ($timenow + 59));
        $task->nextruntime = $nextruntime;
        $task->lastruntime = $timenow;
        $DB->update_record('elis_scheduled_tasks', $task);

        //update the next runtime on the ip schedule record
        $ipjob->nextruntime = $task->nextruntime;
        $ipjob->lastruntime = $timenow;
        $DB->update_record(RLIP_SCHEDULE_TABLE, $ipjob);

        switch ($plugparts[0]) {
            case 'rlipimport':
                $baseinstance = rlip_dataplugin_factory::factory($plugin);
                $entity_types = $baseinstance->get_import_entities();
                $files = array();
                $dataroot = rtrim($CFG->dataroot, DIRECTORY_SEPARATOR);
                $path = $dataroot . DIRECTORY_SEPARATOR . get_config($plugin, 'schedule_files_path');
                $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                $temppath = sprintf($CFG->dataroot . RLIP_IMPORT_TEMPDIR, $plugin);
                if (!file_exists($temppath) && !mkdir($temppath, 0777, true)) {
                    mtrace("run_ipjob({$taskname}): Error creating directory '{$temppath}' ... using '{$path}'");
                    //TBD*** just use main directory???
                    $temppath = $path;
                }
                $continuing = false;
                foreach ($entity_types as $entity) {
                    if (!$continuing && $state !== null &&
                        (!isset($state->entity) || $state->entity == $entity)) {
                        $continuing = true;
                    }
                    $entity_filename = get_config($plugin, $entity .'_schedule_file');
                    if (empty($entity_filename)) {
                        // TBD: need dummy so we're not testing directories!
                        $entity_filename = $entity .'.csv';
                    }
                    //echo "\n get_config('{$plugin}', '{$entity}_schedule_file') => {$entity_filename}";
                    $files[$entity] = $temppath . $entity_filename;
                    if (!$continuing && $path !== $temppath &&
                        file_exists($path . $entity_filename) &&
                        !@rename($path . $entity_filename,
                                 $temppath . $entity_filename)) {
                        mtrace("run_ipjob({$taskname}): Error moving '".
                               $path . $entity_filename . "' to '".
                               $temppath . $entity_filename . "'");
                    }
                }
                $importprovider = new rlip_importprovider_csv($entity_types, $files);
                $instance = rlip_dataplugin_factory::factory($plugin, $importprovider);
                break;

            case 'rlipexport':
                $tz = $DB->get_field('user', 'timezone',
                                     array('id' => $ipjob->userid));
                $export = rlip_get_export_filename($plugin,
                          ($tz === false) ? 99 : $tz);
                $fileplugin = rlip_fileplugin_factory::factory($export, NULL, false);
                $instance = rlip_dataplugin_factory::factory($plugin, NULL, $fileplugin);
                break;

            default:
                mtrace("{$filename}: RLIP plugin '{$plugin}' not supported!");
                continue;
        }

        $instance->run($targetstarttime, $lastruntime);
    }
}

mtrace("\nRLIP external cron end - Server Time: ". date('r', time()) ."\n\n");

// end of file
