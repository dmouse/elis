<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    local_datahub
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

//database table constants
define('RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE', 'dhimport_version1elis_map');

/**
 * Performs page setup work needed on the page for configuring field mapping
 * for the import
 *
 * @param string $baseurl The page's base url
 */
function rlipimport_version1elis_page_setup($baseurl) {
    global $PAGE, $SITE;

    //set up the basic page info
    $PAGE->set_url($baseurl);
    $PAGE->set_context(context_system::instance());
    $displaystring = get_string('configuretitle', 'dhimport_version1elis');
    $PAGE->set_title("$SITE->shortname: ".$displaystring);
    $PAGE->set_heading($SITE->fullname);

    //use the default admin layout
    $PAGE->set_pagelayout('admin');
}

/**
 * Performs tab setup work needed on the page for configuring field mapping
 * for the import
 *
 * @param string $baseurl The page's base url
 * @return array An array of appropriate tab objects
 */
function rlipimport_version1elis_get_tabs($baseurl) {
    $entitytypes = array('user', 'course', 'enrolment');

    $tabs = array();

    foreach ($entitytypes as $entitytype) {
        $url = new moodle_url($baseurl, array('tab' => $entitytype));
        $displaystring = get_string("{$entitytype}tab", 'dhimport_version1elis');

        $tabs[] = new tabobject($entitytype, $url, $displaystring);
    }
    return $tabs;
}

/**
 * Retrieves a complete mapping from standard import field names to custom
 * field names
 *
 * @param string $entitytype The entity type to retrieve the mapping for
 * @return array The appropriate mapping
 */
function rlipimport_version1elis_get_mapping($entitytype) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/local/datahub/lib/rlip_dataplugin.class.php');
    $file = get_plugin_directory('dhimport', 'version1elis').'/lib.php';
    require_once($file);

    //obtain the list of supported fields
    $plugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
    $fields = $plugin->get_available_fields($entitytype);

    if ($fields == false) {
        //invalid entitytype was supplied
        return false;
    }

    //by default, map each field to itself
    $result = array();
    foreach ($fields as $field) {
        $result[$field] = $field;
    }

    //apply mapping info from the database
    $params = array('entitytype' => $entitytype);
    if ($mappings = $DB->get_recordset(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $params)) {
        foreach ($mappings as $mapping) {
            $result[$mapping->standardfieldname] = $mapping->customfieldname;
        }
    }

    return $result;
}

/**
 * Saves field mappings to the database
 *
 * @param string $entitytype The type of entity was are saving mappings for
 * @param array $options The list of available fields that are supported
 * @param array $data The data submitted by the form
 */
function rlipimport_version1elis_save_mapping($entitytype, $options, $formdata) {
    global $CFG, $DB;

    //need to collect data from our defaults and form data
    $data = array();

    //defaults
    foreach ($options as $option) {
        $data[$option] = $option;
    }

    //form data
    foreach ($formdata as $key => $value) {
        if (in_array($key, $options)) {
            $data[$key] = $value;
        }
    }

    //clear out previous values
    $params = array('entitytype' => $entitytype);
    $DB->delete_records(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $params);

    //write to database
    foreach ($data as $key => $value) {
        $record = new stdClass;
        $record->entitytype = $entitytype;
        $record->standardfieldname = $key;
        $record->customfieldname = $value;
        $DB->insert_record(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $record);
    }
}

/**
 * Resets field mappings to their default state
 *
 * @param string $entitytype The type of entity we are resetting mappings for
 */
function rlipimport_version1elis_reset_mappings($entitytype) {
    global $CFG, $DB;
    $file = get_plugin_directory('dhimport', 'version1elis').'/lib.php';
    require_once($file);

    $sql = "UPDATE {".RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE."}
            SET customfieldname = standardfieldname
            WHERE entitytype = ?";
    $DB->execute($sql, array($entitytype));
}
