<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2011 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    elis
 * @subpackage programmanagement
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

// This file keeps track of upgrades to
// the curr_admin block
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_block_curr_admin_upgrade($oldversion = 0) {
    global $CFG, $THEME, $DB;
    require_once($CFG->dirroot . '/elis/program/lib/setup.php');
    $dbmanager = $DB->get_manager();

    $result = true;

    if ($oldversion < 2009010102) {
        $context = get_context_instance(CONTEXT_SYSTEM, SITEID);

        if ($role = $DB->get_record('role', array('shortname' => 'curriculumadmin'))) {
            if ($role->name == 'Bundle Administrator') {
                $role->name = 'Curriculum Administrator';
                $DB->update_record('role', $role);
            }
        }

        if (!empty($role->id)) {
            require_once(dirname(__FILE__) . '/access.php');

            if (!empty($block_curr_admin_capabilities)) {
                foreach ($block_curr_admin_capabilities as $capname => $caprules) {
                    $result = $result && assign_capability($capname, CAP_ALLOW, $role->id, $context->id);
                }
            }
        }
        upgrade_block_savepoint($result, 2009010102, 'curr_admin');
    }

    if ($oldversion < 2009010103) {
        $table = new XMLDBTable('crlm_curriculum');
        $field = new XMLDBField('timetocomplete');
        $field->setAttributes(XMLDB_TYPE_CHAR, '64', NULL, XMLDB_NOTNULL, NULL, NULL, NULL, '0h, 0d, 0w, 0m, 0y', 'timemodified');
        $result = $result && $dbmanager->add_field($table, $field);

        $field = new XMLDBField('frequency');
        $field->setAttributes(XMLDB_TYPE_CHAR, '64', NULL, XMLDB_NOTNULL, NULL, NULL, NULL, '0h, 0d, 0w, 0m, 0y', 'timetocomplete');
        $result = $result && $dbmanager->add_field($table, $field);
        upgrade_block_savepoint($result, 2009010103, 'curr_admin');
    }

    if ($oldversion < 2009010104) {
        $table = new XMLDBTable('crlm_config');
        $table->comment = 'Curriculum management configuration values.';

        // fields
        $f = $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', false, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $f = $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '255', null, false, null, null, null, null);
        $f = $table->addFieldInfo('value', XMLDB_TYPE_TEXT, 'medium', null, false, null, null, null, null);

        // PK and indexes
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addIndexInfo('name_ix', XMLDB_INDEX_UNIQUE, array('name'));
        $result = $result && $dbmanager->create_table($table);
        upgrade_block_savepoint($result, 2009010104, 'curr_admin');
    }

    if ($oldversion < 2009010105) {
        $table = new XMLDBTable('crlm_coursetemplate');
        $table->comment = 'Course templates';

        // fields
        $f = $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', false, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $f = $table->addFieldInfo('courseid', XMLDB_TYPE_INTEGER, '10', false, XMLDB_NOTNULL, null, null, null, null);
        $f = $table->addFieldInfo('location', XMLDB_TYPE_CHAR, '255', null, false, null, null, null, null);
        $f = $table->addFieldInfo('templateclass', XMLDB_TYPE_CHAR, '255', null, false, null, null, null, null);

        // PK and indexes
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addIndexInfo('courseid_ix', XMLDB_INDEX_UNIQUE, array('courseid'));
        $result = $result && $dbmanager->create_table($table);
        upgrade_block_savepoint($result, 2009010105, 'curr_admin');
    }

    if ($oldversion < 2009010106) {
        $table = new XMLDBTable('crlm_cluster_curriculum');
        $table->comment = 'Association between clusters and curricula';

        // fields
        $f = $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', false, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $f = $table->addFieldInfo('clusterid', XMLDB_TYPE_INTEGER, '10', null, true, null, null, null, null, 'id');
        $f->comment = 'Foreign key to cluster id';
        $f = $table->addFieldInfo('curriculumid', XMLDB_TYPE_INTEGER, '10', null, true, null, null, null, null, 'clusterid');
        $f->comment = 'Foreign key to curriculum id';

        // PK and indexes
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addIndexInfo('cluster_idx', XMLDB_INDEX_NOTUNIQUE, array('clusterid'));
        $result = $result && $dbmanager->create_table($table);
        upgrade_block_savepoint($result, 2009010106, 'curr_admin');
    }

    if ($oldversion < 2009010108) {
        $table = new XMLDBTable('crlm_cluster_track');
        $table->comment = 'Association between clusters and tracks';

        // fields
        $f = $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', false, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $f = $table->addFieldInfo('clusterid', XMLDB_TYPE_INTEGER, '10', null, true, null, null, null, null, 'id');
        $f->comment = 'Foreign key to cluster id';
        $f = $table->addFieldInfo('trackid', XMLDB_TYPE_INTEGER, '10', null, true, null, null, null, null, 'clusterid');
        $f->comment = 'Foreign key to track id';
        $f = $table->addFieldInfo('autounenrol', XMLDB_TYPE_INTEGER, '1', null, true, null, null, null, null, 'trackid');
        $f->comment = 'Whether or not to remove a user from classes when removed from cluster';

        // PK and indexes
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addIndexInfo('cluster_idx', XMLDB_INDEX_NOTUNIQUE, array('clusterid'));
        $result = $result && $dbmanager->create_table($table);


        $table = new XMLDBTable('crlm_usercluster');

        $f = new XMLDBField('autoenrol');
        $f->setAttributes(XMLDB_TYPE_INTEGER, '1', null, true, null, null, null, 1, 'clusterid');
        $f->comment = 'Whether users should be autoenrolled in tracks associated with this cluster.';

        $result = $result && $dbmanager->add_field($table, $f);
        upgrade_block_savepoint($result, 2009010108, 'curr_admin');
    }

    if ($oldversion < 2009010109) {
    /// Define table crlm_class_moodle to be created
        $table = new XMLDBTable('crlm_class_moodle');

    /// Adding fields to table crlm_class_moodle
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('classid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('moodlecourseid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('enroltype', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, null, null, null, null, '0');
        $table->addFieldInfo('enrolplugin', XMLDB_TYPE_CHAR, '20', null, null, null, null, null, 'crlm');
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

    /// Adding keys to table crlm_class_moodle
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addKeyInfo('mdl_currclasmood_clamoo_uix', XMLDB_KEY_UNIQUE, array('classid', 'moodlecourseid'));

    /// Launch create table for crlm_class_moodle
        $result = $result && $dbmanager->create_table($table);
        upgrade_block_savepoint($result, 2009010109, 'curr_admin');
    }

    if ($oldversion < 2009010110) {
        $table = new XMLDBTable('crlm_user_track');
        $table->comment = 'User enrolment in tracks';

        // fields
        $f = $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', false, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $f = $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', null, true, null, null, null, null, 'id');
        $f->comment = 'Foreign key to user id';
        $f = $table->addFieldInfo('trackid', XMLDB_TYPE_INTEGER, '10', null, true, null, null, null, null, 'userid');
        $f->comment = 'Foreign key to track id';

        // PK and indexes
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && $dbmanager->create_table($table);
        upgrade_block_savepoint($result, 2009010110, 'curr_admin');
    }

    if ($result && $oldversion < 2009010112) {

    /// Define table crlm_notification_log to be created
        $table = new XMLDBTable('crlm_notification_log');

    /// Adding fields to table crlm_notification_log
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('event', XMLDB_TYPE_CHAR, '166', null, null, null, null, null, null);
        $table->addFieldInfo('instance', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('data', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

    /// Adding keys to table crlm_notification_log
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table crlm_notification_log
        $table->addIndexInfo('event_inst_user_ix', XMLDB_INDEX_NOTUNIQUE, array('event', 'instance', 'userid'));

    /// Launch create table for crlm_notification_log
        $result = $result && $dbmanager->create_table($table);
        upgrade_block_savepoint($result, 2009010112, 'curr_admin');
    }

    if ($result && $oldversion < 2009010113) {
    /// Define index event_inst_user_ix (not unique) to be dropped from crlm_notification_log
        $table = new XMLDBTable('crlm_notification_log');
        $index = new XMLDBIndex('event_inst_user_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('event', 'instance', 'userid'));

    /// Launch drop index event_inst_user_ix
        $result = $result && $dbmanager->drop_index($table, $index);

    /// Define index event_inst_user_ix (not unique) to be added to crlm_notification_log
        $table = new XMLDBTable('crlm_notification_log');
        $index = new XMLDBIndex('event_inst_user_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('userid', 'instance', 'event'));

    /// Launch add index event_inst_user_ix
        $result = $result && $dbmanager->add_index($table, $index);
        upgrade_block_savepoint($result, 2009010113, 'curr_admin');
    }

    if ($result && $oldversion < 2009010114) {
        // Creating track table
        $table = new XMLDBTable('crlm_track');
        $table->comment = 'Track table';

        // fields
        $f = $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', false, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $f = $table->addFieldInfo('curid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $f = $table->addFieldInfo('idnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, null);
        $f = $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null);
        $f = $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, null, null);
        $f = $table->addFieldInfo('startdate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $f = $table->addFieldInfo('enddate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $f = $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $f = $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

        // PK and indexes
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addIndexInfo('track_curr_idx', XMLDB_INDEX_NOTUNIQUE, array('curid'));
        $result = $result && $dbmanager->create_table($table);

        $table = new XMLDBTable('crlm_track_class');
        $table->comment = 'Track class table';
        $f = $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', false, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $f = $table->addFieldInfo('trackid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $f = $table->addFieldInfo('classid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $f = $table->addFieldInfo('courseid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $f = $table->addFieldInfo('requried', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $f = $table->addFieldInfo('autoenrol', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $f = $table->addFieldInfo('default', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $f = $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $f = $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addIndexInfo('trackid_idx', XMLDB_INDEX_NOTUNIQUE, array('trackid'));
        $table->addIndexInfo('track_classid_idx', XMLDB_INDEX_NOTUNIQUE, array('classid'));
        $table->addIndexInfo('track_courseid_idx', XMLDB_INDEX_NOTUNIQUE, array('courseid'));

        $result = $result && $dbmanager->create_table($table);
        upgrade_block_savepoint($result, 2009010114, 'curr_admin');
    }

    if ($result && $oldversion < 2009010115) {

    /// Define table crlm_cluster_profile to be created
        $table = new XMLDBTable('crlm_cluster_profile');

    /// Adding fields to table crlm_cluster_profile
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('clusterid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('fieldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('value', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table crlm_cluster_profile
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for crlm_cluster_profile
        $result = $result && $dbmanager->create_table($table);


    /// Define table crlm_cluster_assignments to be created
        $table = new XMLDBTable('crlm_cluster_assignments');

    /// Adding fields to table crlm_cluster_assignments
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('clusterid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('plugin', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table crlm_cluster_assignments
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for crlm_cluster_assignments
        $result = $result && $dbmanager->create_table($table);
        upgrade_block_savepoint($result, 2009010115, 'curr_admin');
    }

    if ($result && $oldversion < 2009010116) {
        $table = new XMLDBTable('crlm_track_class');

        $field = new XMLDBField('default');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, '0', 'autoenrol');
        $result = $result && $dbmanager->drop_field($table,$field);

        $field = new XMLDBField('defaulttrack');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, '0', 'autoenrol');
        $result = $result && $dbmanager->add_field($table,$field);
        upgrade_block_savepoint($result, 2009010116, 'curr_admin');
    }

    if ($result && $oldversion < 2009010117) {
    /// Remove obsolete job code tables if they exist.
        $table = new XMLDBTable('crlm_jobcode_list');
        if ($dbmanager->table_exists($table)) {
            $dbmanager->drop_table($table);
        }

        $table = new XMLDBTable('crlm_curriculum_jobcode');
        if ($dbmanager->table_exists($table)) {
            $dbmanager->drop_table($table);
        }
        upgrade_block_savepoint($result, 2009010117, 'curr_admin');
    }

    if ($result && $oldversion < 2009010118) {
    /// Removing defaulttrack column from table
        $table = new XMLDBTable('crlm_track_class');

        $field = new XMLDBField('defaulttrack');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, '0', 'autoenrol');
        $result = $result && $dbmanager->drop_field($table,$field);

    /// Adding defaulttrack column to table
        $table = new XMLDBTable('crlm_track');
        $field = new XMLDBField('defaulttrack');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, '0', 'enddate');
        $result = $result && $dbmanager->add_field($table,$field);
        upgrade_block_savepoint($result, 2009010118, 'curr_admin');
    }

    if ($result && $oldversion < 2009010119) {

    /// Define field completed to be added to crlm_curriculum_assignment
        $table = new XMLDBTable('crlm_curriculum_assignment');
        $field = new XMLDBField('completed');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'curriculumid');

    /// Launch add field completed
        $result = $result && $dbmanager->add_field($table, $field);

    /// Define field completiontime to be added to crlm_curriculum_assignment
        $field = new XMLDBField('timecompleted');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'completed');

    /// Launch add field completiontime
        $result = $result && $dbmanager->add_field($table, $field);

    /// Define field credits to be added to crlm_curriculum_assignment
        $field = new XMLDBField('credits');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'timecompleted');

    /// Launch add field credits
        $result = $result && $dbmanager->add_field($table, $field);

    /// Define field locked to be added to crlm_curriculum_assignment
        $table = new XMLDBTable('crlm_curriculum_assignment');
        $field = new XMLDBField('locked');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'credits');

    /// Launch add field locked
        $result = $result && $dbmanager->add_field($table, $field);

    /// Define key mdl_currcurrassi_usecur_uix (unique) to be dropped from crlm_curriculum_assignment
        $key = new XMLDBKey('mdl_currcurrassi_usecur_uix');
        $key->setAttributes(XMLDB_KEY_UNIQUE, array('userid', 'curriculumid'));

    /// Launch drop key mdl_currcurrassi_usecur_uix
        $result = $result && $dbmanager->drop_key($table, $key);

    /// Define index mdl_currcurrassi_usecurcom_ix (not unique) to be added to crlm_curriculum_assignment
        $index = new XMLDBIndex('mdl_currcurrassi_usecurcom_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('userid', 'curriculumid', 'completed'));

    /// Launch add index mdl_currcurrassi_usecurcom_ix
        $result = $result && $dbmanager->add_index($table, $index);

    /// Define index completed_ix (not unique) to be added to crlm_curriculum_assignment
        $index = new XMLDBIndex('completed_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('completed'));

    /// Launch add index completed_ix
        $result = $result && $dbmanager->add_index($table, $index);
        upgrade_block_savepoint($result, 2009010119, 'curr_admin');
    }

    if ($result && $oldversion < 2009010120) {

    /// Define field autoenrol to be added to crlm_cluster_assignments
        $table = new XMLDBTable('crlm_cluster_assignments');
        $field = new XMLDBField('autoenrol');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '1', 'plugin');

    /// Launch add field autoenrol
        $result = $result && $dbmanager->add_field($table, $field);
        upgrade_block_savepoint($result, 2009010120, 'curr_admin');
    }

    if ($result && $oldversion < 2009010121) {
        if (!record_exists('mnet_application', array('name' => 'java'))) {
            $application = new stdClass();
            $application->name = 'java';
            $application->display_name = 'Java servlet';
            $application->xmlrpc_server_url = '/mnet/server';
            $application->sso_land_url = '/mnet/land.jsp';
            $result = $result && $DB->insert_record('mnet_application', $application, false);
        }
        upgrade_block_savepoint($result, 2009010121, 'curr_admin');
    }

    if ($result && $oldversion < 2009010122) {
        $table = new XMLDBTable('crlm_track_class');

        $field = new XMLDBField('requried');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'courseid');
        $result = $result && $dbmanager->drop_field($table,$field);
        upgrade_block_savepoint($result, 2009010122, 'curr_admin');
    }

    if ($result && $oldversion < 2009010126) {
        $table = new XMLDBTable('crlm_cluster_curriculum');
        $table->comment = 'Association between clusters and curricula';

        // fields
        $f = $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', false, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $f = $table->addFieldInfo('clusterid', XMLDB_TYPE_INTEGER, '10', null, true, null, null, null, null, 'id');
        $f->comment = 'Foreign key to cluster id';
        $f = $table->addFieldInfo('curriculumid', XMLDB_TYPE_INTEGER, '10', null, true, null, null, null, null, 'clusterid');
        $f->comment = 'Foreign key to curriculum id';

        // PK and indexes
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addIndexInfo('cluster_idx', XMLDB_INDEX_NOTUNIQUE, array('clusterid'));
        $result = $result && ($dbmanager->table_exists($table) || $dbmanager->create_table($table));


        $table = new XMLDBTable('crlm_cluster_track');
        $table->comment = 'Association between clusters and tracks';

        // fields
        $f = $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', false, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $f = $table->addFieldInfo('clusterid', XMLDB_TYPE_INTEGER, '10', null, true, null, null, null, null, 'id');
        $f->comment = 'Foreign key to cluster id';
        $f = $table->addFieldInfo('trackid', XMLDB_TYPE_INTEGER, '10', null, true, null, null, null, null, 'clusterid');
        $f->comment = 'Foreign key to track id';
        $f = $table->addFieldInfo('autounenrol', XMLDB_TYPE_INTEGER, '1', null, true, null, null, null, null, 'trackid');
        $f->comment = 'Whether or not to remove a user from classes when removed from cluster';

        // PK and indexes
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addIndexInfo('cluster_idx', XMLDB_INDEX_NOTUNIQUE, array('clusterid'));
        $result = $result && ($dbmanager->table_exists($table) || $dbmanager->create_table($table));
        upgrade_block_savepoint($result, 2009010126, 'curr_admin');
    }

    if ($result && $oldversion < 2009010127) {
        // fix silly typos
        $newtable = new XMLDBTable('crlm_user_track');
        $oldtable = new XMLDBTable('clrm_user_track');
        $result = $result && ($dbmanager->table_exists($newtable) || $dbmanager->rename_table($oldtable, 'crlm_user_track'));
        $oldtable = new XMLDBTable('clrm_cluster_track');
        $result = $result && (!$dbmanager->table_exists($oldtable) || $dbmanager->drop_table($oldtable));
        $oldtable = new XMLDBTable('clrm_cluster_curriculum');
        $result = $result && (!$dbmanager->table_exists($oldtable) || $dbmanager->drop_table($oldtable));
        upgrade_block_savepoint($result, 2009010127, 'curr_admin');
    }

    if ($result && $oldversion < 2009010128) {
        require_once(elispm::lib('lib.php'));
        pm_migrate_moodle_users();
        upgrade_block_savepoint($result, 2009010128, 'curr_admin');
    }

    if ($result && $oldversion < 2009010131) {
    /// Get rid of any outdated cluster data we might have lying around.
        if ($CFG->dbfamily == 'postgres') {
            $sql = "DELETE FROM {$CFG->prefix}crlm_cluster_assignments
                    WHERE id IN (
                        SELECT ca.clusterid
                        FROM {$CFG->prefix}crlm_cluster_assignments ca
                        LEFT JOIN {$CFG->prefix}crlm_cluster c ON c.id = ca.clusterid
                        WHERE c.id IS NULL
                    )";

            $result = $result && $DB->execute($sql);

            $sql = "DELETE FROM {$CFG->prefix}crlm_cluster_curriculum
                    WHERE id IN (
                        SELECT cc.clusterid
                        FROM {$CFG->prefix}crlm_cluster_curriculum cc
                        LEFT JOIN {$CFG->prefix}crlm_cluster c ON c.id = cc.clusterid
                        WHERE c.id IS NULL
                    )";

            $result = $result && $DB->execute($sql);

            $sql = "DELETE FROM {$CFG->prefix}crlm_cluster_profile
                    WHERE id IN (
                        SELECT cp.clusterid
                        FROM {$CFG->prefix}crlm_cluster_profile cp
                        LEFT JOIN {$CFG->prefix}crlm_cluster c ON c.id = cp.clusterid
                        WHERE c.id IS NULL
                    )";

            $result = $result && $DB->execute($sql);

            $sql = "DELETE FROM {$CFG->prefix}crlm_cluster_track
                    WHERE id IN (
                        SELECT ct.clusterid
                        FROM {$CFG->prefix}crlm_cluster_track ct
                        LEFT JOIN {$CFG->prefix}crlm_cluster c ON c.id = ct.clusterid
                        WHERE c.id IS NULL
                    )";

            $result = $result && $DB->execute($sql);

            $sql = "DELETE FROM {$CFG->prefix}crlm_usercluster
                    WHERE id IN (
                        SELECT uc.clusterid
                        FROM {$CFG->prefix}crlm_usercluster uc
                        LEFT JOIN {$CFG->prefix}crlm_cluster c ON c.id = uc.clusterid
                        WHERE c.id IS NULL
                    )";

            $result = $result && $DB->execute($sql);
        } else {
            $sql = "DELETE ca FROM {$CFG->prefix}crlm_cluster_assignments ca
                    LEFT JOIN {$CFG->prefix}crlm_cluster c ON c.id = ca.clusterid
                    WHERE c.id IS NULL";

            $result = $result && $DB->execute($sql);

            $sql = "DELETE cc FROM {$CFG->prefix}crlm_cluster_curriculum cc
                    LEFT JOIN {$CFG->prefix}crlm_cluster c ON c.id = cc.clusterid
                    WHERE c.id IS NULL";

            $result = $result && $DB->execute($sql);

            $sql = "DELETE cp FROM {$CFG->prefix}crlm_cluster_profile cp
                    LEFT JOIN {$CFG->prefix}crlm_cluster c ON c.id = cp.clusterid
                    WHERE c.id IS NULL";

            $result = $result && $DB->execute($sql);

            $sql = "DELETE ct FROM {$CFG->prefix}crlm_cluster_track ct
                    LEFT JOIN {$CFG->prefix}crlm_cluster c ON c.id = ct.clusterid
                    WHERE c.id IS NULL";

            $result = $result && $DB->execute($sql);

            $sql = "DELETE uc FROM {$CFG->prefix}crlm_usercluster uc
                    LEFT JOIN {$CFG->prefix}crlm_cluster c ON c.id = uc.clusterid
                    WHERE c.id IS NULL";

            $result = $result && $DB->execute($sql);
        }
        upgrade_block_savepoint($result, 2009010131, 'curr_admin');
    }

    if ($result && $oldversion < 2009010133) {
    /// Define field leader to be added to crlm_cluster_assignments
        $table = new XMLDBTable('crlm_cluster_assignments');
        $field = new XMLDBField('leader');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'autoenrol');

    /// Launch add field leader
        $result = $result && $dbmanager->add_field($table, $field);

    /// Define field leader to be added to crlm_usercluster
        $table = new XMLDBTable('crlm_usercluster');
        $field = new XMLDBField('leader');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'autoenrol');

    /// Launch add field leader
        $result = $result && $dbmanager->add_field($table, $field);
        upgrade_block_savepoint($result, 2009010133, 'curr_admin');
    }

    if ($result && $oldversion < 2009010134) {

    /// Define field inactive to be added to crlm_user
        $table = new XMLDBTable('crlm_user');
        $field = new XMLDBField('inactive');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, null, null, '0', 'timemodified');

    /// Launch add field inactive
        $result = $result && $dbmanager->add_field($table, $field);
        upgrade_block_savepoint($result, 2009010134, 'curr_admin');
    }

    if ($result && $oldversion < 2009010137) {
        $roleid = $DB->get_field('role', 'id', array('shortname' => 'curriculumadmin'));

        if (!empty($roleid)) {
            $context = get_context_instance(CONTEXT_SYSTEM, SITEID);
            require_once(dirname(__FILE__) . '/access.php');

            if (!empty($block_curr_admin_capabilities)) {
                foreach ($block_curr_admin_capabilities as $capname => $caprules) {
                    $result = $result && assign_capability($capname, CAP_ALLOW, $roleid, $context->id);
                }
            }
        }
        upgrade_block_savepoint($result, 2009010137, 'curr_admin');
    }

    if($result && $oldversion < 2009010139) {
        global $CURMAN;

        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        $moodleclasses = moodle_get_classes();

        if (!empty($moodleclasses)) {
            foreach ($moodleclasses as $class) {
                $context = get_context_instance(CONTEXT_COURSE, $class->moodlecourseid);

                list($gradebookrolessql, $params) = $DB->get_in_or_equal(explode(',', $CFG->gradebookroles), SQL_PARAMS_NAMED, 'grbr0', false);
                $sql = "DELETE cmce
                    FROM {user} u
                    JOIN {role_assignments} ra ON u.id = ra.userid
                    JOIN {crlm_class_enrolment} cmce ON u.idnumber = cmce.user_idnumber
                    WHERE ra.roleid {$gradebookrolessql}
                    AND ra.contextid " . get_related_contexts_string($context) .
                    "AND cmce.classid = {$class->classid}";

                $params['classid'] = $class->classid;

                $result = $result && $DB->execute($sql, $params);
            }
        }
        upgrade_block_savepoint($result, 2009010139, 'curr_admin');
    }

    if ($result && $oldversion < 2009010140) {
        $DB->delete_records('crlm_cluster_profile', array('fieldid' => 0));
        upgrade_block_savepoint($result, 2009010140, 'curr_admin');
    }

    if($result && $oldversion < 2009010141) {
        set_config('field_lock_idnumber', 'locked', 'auth/manual');
        upgrade_block_savepoint($result, 2009010141, 'curr_admin');
    }

    if ($result && $oldversion < 2009010143) {

    /// Define table crlm_wait_list to be created
        $table = new XMLDBTable('crlm_wait_list');

    /// Adding fields to table crlm_wait_list
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('classid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('position', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);

    /// Adding keys to table crlm_wait_list
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for crlm_wait_list
        $result = $result && $dbmanager->create_table($table);
        upgrade_block_savepoint($result, 2009010143, 'curr_admin');
    }

    if($result && $oldversion < 2009010145) {
        $table = new XMLDBTable('crlm_wait_list');

        $field = new XMLDBField('enrolmenttime');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

        $result = $result && $dbmanager->add_field($table, $field);
        upgrade_block_savepoint($result, 2009010145, 'curr_admin');
    }

    if ($result && $oldversion < 2009010146) {
        // make sure trackclass's courseids are set

        // Let's just assume that all non-Postgres DB's use the same syntax as MySQL and call it a day.
        if ($CFG->dbfamily == 'postgres') {
            $sql = "UPDATE {crlm_track_class}
                       SET courseid = c.courseid
                      FROM {crlm_track_class} tc, {crlm_class} c
                     WHERE tc.classid = c.id AND tc.courseid = 0";
        } else {
            $sql = "UPDATE {crlm_track_class} tc, {crlm_class} c
                       SET tc.courseid = c.courseid
                     WHERE tc.classid = c.id AND tc.courseid = 0";
        }

        $result = $result && $DB->execute($sql);
        upgrade_block_savepoint($result, 2009010146, 'curr_admin');
    }

    if ($result && $oldversion < 2009010147) {
        // make sure all users have an idnumber
        $users = $DB->get_recordset('crlm_user', array('idnumber' => ''));

        foreach ($users as $user) {
            $mu = $DB->get_record('user', array('username' => $user->username));
            if (empty($mu->idnumber)) {
                $user->idnumber = $mu->idnumber = $mu->username;
                $DB->update_record('user', $mu);
                $DB->update_record('crlm_user', $user);
            } else if (!$DB->get_record('crlm_user', array('idnumber' => $mu->idnumber))) {
                $user->idnumber = $mu->idnumber;
                $DB->update_record('crlm_user', $user);
            } else if (!$DB->get_record('crlm_user', array('idnumber' => $user->username))) {
                $user->idnumber = $mu->idnumber;
                $DB->update_record('crlm_user', $user);
            }
        }
        $users->close();
        upgrade_block_savepoint($result, 2009010147, 'curr_admin');
    }

    if ($result && $oldversion < 2009010149) {
    /// Define index clusterid_idx (not unique) to be added to crlm_cluster_assignments
        $table = new XMLDBTable('crlm_cluster_assignments');
        $index = new XMLDBIndex('clusterid_idx');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('clusterid'));

    /// Launch add index clusterid_idx
        $result = $result && $dbmanager->add_index($table, $index);

    /// Define index userid_idx (not unique) to be added to crlm_cluster_assignments
        $table = new XMLDBTable('crlm_cluster_assignments');
        $index = new XMLDBIndex('userid_idx');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('userid'));

    /// Launch add index userid_idx
        $result = $result && $dbmanager->add_index($table, $index);

    /// Define index clusterid_idx (not unique) to be added to crlm_cluster_profile
        $table = new XMLDBTable('crlm_cluster_profile');
        $index = new XMLDBIndex('clusterid_idx');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('clusterid'));

    /// Launch add index clusterid_idx
        $result = $result && $dbmanager->add_index($table, $index);
        upgrade_block_savepoint($result, 2009010149, 'curr_admin');
    }

    if($result && $oldversion < 2009010150) {
        require_once(elispm::lib('data/curriculumcourse.class.php'));

        $sql = "SELECT cp.id, cp.courseid, cc.curriculumid
                FROM {crlm_course_prerequisite} cp
                JOIN {crlm_curriculum_course} cc ON cc.id = cp.curriculumcourseid
                WHERE cp.courseid NOT IN (
                    SELECT _cc.courseid
                    FROM {crlm_curriculum_course} _cc
                    WHERE _cc.curriculumid = cc.curriculumid
                )";

        $students = $DB->get_recordset_sql($sql);

        foreach($students as $student) {
            $data = new object();
            $data->curriculumid = $student->curriculumid;
            $data->courseid = $student->courseid;
            $data->timeperiod = 'year';

            $currprereq = new curriculumcourse($data);

            $results = $result && $currprereq->add();
        }
        $students->close();
        upgrade_block_savepoint($result, 2009010150, 'curr_admin');
    }

    if ($result && $oldversion < 2009010151) {
        $table = new XMLDBTable('crlm_curriculum');

        $field = new XMLDBField('priority');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

        $result = $result && $dbmanager->add_field($table, $field);
        upgrade_block_savepoint($result, 2009010151, 'curr_admin');
    }

    if ($result && $oldversion < 2009103001) {
        $table = new XMLDBTable('crlm_curriculum');

        $field = new XMLDBField('priority');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

        $result = $result && $dbmanager->add_field($table, $field);

    /// Define table context_levels to be created
        $table = new XMLDBTable('context_levels');

    /// Adding fields to table context_levels
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('component', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table context_levels
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table context_levels
        $table->addIndexInfo('name', XMLDB_INDEX_NOTUNIQUE, array('name'));
        $table->addIndexInfo('component', XMLDB_INDEX_NOTUNIQUE, array('component'));

    /// Launch create table for context_levels
        $result = $result && $dbmanager->create_table($table);
        upgrade_block_savepoint($result, 2009103001, 'curr_admin');
    }

    if ($result && $oldversion < 2009103003)  {

    /// Define table crlm_field to be created
        $table = new XMLDBTable('crlm_field');

    /// Adding fields to table crlm_field
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('shortname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('name', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('datatype', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', null, null, null, null, null, null);
        $table->addFieldInfo('categoryid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('required', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('locked', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('visible', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('forceunique', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('defaultdata', XMLDB_TYPE_TEXT, 'big', null, null, null, null, null, null);
        $table->addFieldInfo('params', XMLDB_TYPE_TEXT, 'big', null, null, null, null, null, null);
        $table->addFieldInfo('syncwithmoodle', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table crlm_field
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table crlm_field
        $table->addIndexInfo('shortname_idx', XMLDB_INDEX_NOTUNIQUE, array('shortname'));

    /// Launch create table for crlm_field
        $result = $result && $dbmanager->create_table($table);

    /// Define table crlm_field_category to be created
        $table = new XMLDBTable('crlm_field_category');

    /// Adding fields to table crlm_field_category
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table crlm_field_category
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for crlm_field_category
        $result = $result && $dbmanager->create_table($table);

    /// Define table crlm_field_contextlevel to be created
        $table = new XMLDBTable('crlm_field_contextlevel');

    /// Adding fields to table crlm_field_contextlevel
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('fieldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('contextlevel', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table crlm_field_contextlevel
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for crlm_field_contextlevel
        $result = $result && $dbmanager->create_table($table);

    /// Define table crlm_field_data to be created
        $table = new XMLDBTable('crlm_field_data');

    /// Adding fields to table crlm_field_data
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('contextid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('fieldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('data', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table crlm_field_data
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table crlm_field_data
        $table->addIndexInfo('context_idx', XMLDB_INDEX_NOTUNIQUE, array('contextid'));

    /// Launch create table for crlm_field_data
        $result = $result && $dbmanager->create_table($table);
        upgrade_block_savepoint($result, 2009103003, 'curr_admin');
    }

    if ($result && $oldversion < 2010040501) {
        $table = new XMLDBTable('crlm_field_map');

    /// Adding fields to table crlm_field_data
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('context', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('type', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('elis_field', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('data_field', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table crlm_field_data
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for crlm_field_data
        $result = $result && $dbmanager->create_table($table);

        require_once(elis::lib('data/customfield.class.php'));
        // make sure all ELIS users have a context
        update_capabilities('block/curr_admin');
        $ctxlvl = context_level_base::get_custom_context_level('user', 'block_curr_admin');
        $rs = get_recordset('crlm_user');
        foreach ($rs as $rec) {
            get_context_instance($ctxlvl, $rec->id);
        }
        $rs->close();

        // sync profile fields
        $fields = field::get_for_context_level($ctxlvl);
        $fields = $fields ? $fields : array();
        require_once(elis::plugin_file('elisfields_moodle_profile', 'custom_fields.php'));
        foreach ($fields as $field) {
            $fieldobj = new field($field);
            sync_profile_field_with_moodle($fieldobj);
        }

        require_once(elispm::lib('notifications.php'));

        if(!empty($CFG->coursemanager)) {

            $context_course = CONTEXT_COURSE;

            list($managerrolessql, $params) = $DB->get_in_or_equal(explode(',', $this->gradebookroles), SQL_PARAMS_NAMED, 'mgr0', false);

            $sql = "SELECT role_assignments.* FROM {role_assignments} role_assignments
                    JOIN {context} context
                    ON role_assignments.contextid = context.id
                    WHERE role_assignments.roleid {$managerrolessql}
                    AND context.contextlevel = {$context_course}";

            $records = $DB->get_recordset_sql($sql, $params);
            foreach($records as $record) {
                cm_assign_instructor_from_mdl($record);
            }
            $records->close();

        }
        upgrade_block_savepoint($result, 2010040501, 'curr_admin');
    }

    if ($result && $oldversion < 2010040505) {
        require_once(elispm::lib('lib.php'));
        $result = $result && cm_notify_duplicate_user_info(true);
        upgrade_block_savepoint($result, 2010040505, 'curr_admin');
    }

    if ($result && $oldversion < 2010040506 && $oldversion >= 2010040501) {
        global $CFG, $CURMAN;

        // fix instructor assignments that were migrated incorrectly in the
        // 2010040501 upgrade code (ELIS-1171)

        // remove the obvious errors (instructors assigned to a non-existent class)
        $context_course = CONTEXT_COURSE;

        $sql = "DELETE
                  FROM {crlm_class_instructor}
                 WHERE NOT EXISTS (SELECT 'x' FROM {crlm_class} cmclass
                                    WHERE cmclass.id = {crlm_class_instructor.classid})";

        $result = $result && $DB->execute($sql);

        // warn about other potentially incorrect instructor assignments
        require_once(elispm::lib('lib.php'));
        cm_notify_incorrect_instructor_assignment(true);

        // make sure the correct assignments are added
        if(!empty($CFG->coursemanager)) {
            require_once(elispm::lib('notifications.php'));

            $context_course = CONTEXT_COURSE;

            list($managerrolessql, $params) = $DB->get_in_or_equal(explode(',', $this->gradebookroles), SQL_PARAMS_NAMED, 'mgr0', false);

            $sql = "SELECT role_assignments.* FROM {role_assignments} role_assignments
                    JOIN {context} context
                    ON role_assignments.contextid = context.id
                    WHERE role_assignments.roleid {$managerrolessql}
                    AND context.contextlevel = {$context_course}";

            $records = $DB->get_recordset_sql($sql, $params);
            foreach($records as $record) {
                cm_assign_instructor_from_mdl($record);
            }
            $records->close();
        }
        upgrade_block_savepoint($result, 2010040506, 'curr_admin');
    }

    if($result && $oldversion < 2010063001) {
        $table = new XMLDBTable('crlm_curriculum_assignment');
        $field = new XMLDBField('user_idnumber');
        $result = $result && $dbmanager->drop_field($table, $field);

        $table = new XMLDBTable('crlm_class_enrolment');
        $field = new XMLDBField('user_idnumber');
        $result = $result && $dbmanager->drop_field($table, $field);

        $table = new XMLDBTable('crlm_class_instructor');
        $field = new XMLDBField('user_idnumber');
        $result = $result && $dbmanager->drop_field($table, $field);

        $table = new XMLDBTable('crlm_class_attendance');
        $field = new XMLDBField('user_idnumber');
        $result = $result && $dbmanager->drop_field($table, $field);
        upgrade_block_savepoint($result, 2010063001, 'curr_admin');
    }

    if ($result && $oldversion < 2010063002) {
        //get the class table
        $table = new XMLDBTable('crlm_class');

        //add the auto enrol enabled flag
        $field = new XMLDBField('enrol_from_waitlist');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');

        $result = $result && $dbmanager->add_field($table, $field);
        upgrade_block_savepoint($result, 2010063002, 'curr_admin');
    }

    if ($result && $oldversion < 2010063005) {

    /// Define table crlm_field_data to be dropped
        $table = new XMLDBTable('crlm_field_map');

    /// Launch drop table for crlm_field_data
        $result = $result && $dbmanager->drop_table($table);
        upgrade_block_savepoint($result, 2010063005, 'curr_admin');
    }

    if ($result && $oldversion < 2010063006) {

    /// Define table crlm_field_data to be renamed to crlm_field_data_text
        $table = new XMLDBTable('crlm_field_data');

    /// Define index context_idx (not unique) to be dropped form crlm_field_data_text
        $index = new XMLDBIndex('context_idx');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('contextid'));

    /// Launch drop index context_idx
        $result = $result && $dbmanager->drop_index($table, $index);

    /// Changing nullability of field contextid on table crlm_field_data_text to null
        $field = new XMLDBField('contextid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'id');

    /// Define index context_idx (not unique) to be added to crlm_field_data_text
        $index = new XMLDBIndex('context_idx');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('contextid'));

    /// Launch add index context_idx
        $result = $result && $dbmanager->add_index($table, $index);

    /// Define index field_idx (not unique) to be added to crlm_field_data_text
        $index = new XMLDBIndex('field_idx');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('fieldid'));

    /// Launch add index field_idx
        $result = $result && $dbmanager->add_index($table, $index);

    /// Launch change of nullability for field contextid
        $result = $result && $dbmanager->change_field_notnull($table, $field);

    /// Launch rename table for crlm_field_data
        $result = $result && $dbmanager->rename_table($table, 'crlm_field_data_text');


    /// Define table crlm_field_owner to be created
        $table = new XMLDBTable('crlm_field_owner');

    /// Adding fields to table crlm_field_owner
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('fieldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('plugin', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
        $table->addFieldInfo('exclude', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, null, null, '0');
        $table->addFieldInfo('params', XMLDB_TYPE_TEXT, 'big', null, null, null, null, null, null);

    /// Adding keys to table crlm_field_owner
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table crlm_field_owner
        $table->addIndexInfo('field_idx', XMLDB_INDEX_NOTUNIQUE, array('fieldid'));

    /// Launch create table for crlm_field_owner
        $result = $result && $dbmanager->create_table($table);


    /// Define table crlm_field_category_context to be created
        $table = new XMLDBTable('crlm_field_category_context');

    /// Adding fields to table crlm_field_category_context
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('categoryid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('contextlevel', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);

    /// Adding keys to table crlm_field_category_context
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table crlm_field_category_context
        $table->addIndexInfo('contextlevel_idx', XMLDB_INDEX_NOTUNIQUE, array('contextlevel'));
        $table->addIndexInfo('category_idx', XMLDB_INDEX_NOTUNIQUE, array('categoryid'));

    /// Launch create table for crlm_field_category_context
        $result = $result && $dbmanager->create_table($table);


        $usercontextid = context_level_base::get_custom_context_level('user', 'block_curr_admin');
        if ($usercontextid) {
            $sql = "INSERT INTO {$CFG->prefix}crlm_field_category_context
                           (categoryid, contextlevel)
                    SELECT id, $usercontextid
                      FROM {$CFG->prefix}crlm_field_category";
            $result = $result && $DB->execute($sql);
        }


    /// Define table crlm_field_data_int to be created
        $table = new XMLDBTable('crlm_field_data_int');

    /// Adding fields to table crlm_field_data_int
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('contextid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('fieldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('data', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table crlm_field_data_int
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table crlm_field_data_int
        $table->addIndexInfo('context_idx', XMLDB_INDEX_NOTUNIQUE, array('contextid'));
        $table->addIndexInfo('field_idx', XMLDB_INDEX_NOTUNIQUE, array('fieldid'));

    /// Launch create table for crlm_field_data_int
        $result = $result && $dbmanager->create_table($table);


    /// Define table crlm_field_data_num to be created
        $table = new XMLDBTable('crlm_field_data_num');

    /// Adding fields to table crlm_field_data_num
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('contextid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('fieldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('data', XMLDB_TYPE_NUMBER, '15, 5', null, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table crlm_field_data_num
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table crlm_field_data_num
        $table->addIndexInfo('context_idx', XMLDB_INDEX_NOTUNIQUE, array('contextid'));
        $table->addIndexInfo('field_idx', XMLDB_INDEX_NOTUNIQUE, array('fieldid'));

    /// Launch create table for crlm_field_data_num
        $result = $result && $dbmanager->create_table($table);


    /// Define table crlm_field_data_char to be created
        $table = new XMLDBTable('crlm_field_data_char');

    /// Adding fields to table crlm_field_data_char
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('contextid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('fieldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('data', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table crlm_field_data_char
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table crlm_field_data_char
        $table->addIndexInfo('context_idx', XMLDB_INDEX_NOTUNIQUE, array('contextid'));
        $table->addIndexInfo('field_idx', XMLDB_INDEX_NOTUNIQUE, array('fieldid'));

    /// Launch create table for crlm_field_data_char
        $result = $result && $dbmanager->create_table($table);


        $records = $DB->get_recordset('crlm_field');
        // FIXME: set data type based on old data type
        foreach ($records as $record) {
            unset($record->name);
            unset($record->shortname);
            unset($record->description);
            if (isset($record->syncwithmoodle)) {
                // make sure the crlm_field table hasn't been upgraded yet
                switch ($record->syncwithmoodle) {
                case 2:
                    // sync from Moodle
                    // create "moodle_profile" owner
                    if (!$DB->record_exists('crlm_field_owner', array('fieldid' => $record->id, 'plugin' => 'moodle_profile'))) {
                        $owner = new stdClass;
                        $owner->fieldid = $record->id;
                        $owner->plugin = 'moodle_profile';
                        $owner->exclude = true;
                        $result = $result && $DB->insert_record('crlm_field_owner', $owner);
                    }
                    // create "manual" owner
                    if (!$DB->record_exists('crlm_field_owner', array('fieldid' => $record->id, 'plugin' => 'manual'))) {
                        $owner = new stdClass;
                        $owner->fieldid = $record->id;
                        $owner->plugin = 'manual';
                        $owner->exclude = false;
                        $owner->params = array('edit_capability' => 'disabled');
                        if (!$record->visible) {
                            $owner->params['view_capability'] = 'moodle/user:viewhiddendetails';
                        }
                        $owner->params = serialize($owner->params);
                        $result = $result && $DB->insert_record('crlm_field_owner', $owner);
                    }
                    $record->datatype = 'text';
                    break;
                case 1:
                    // sync to Moodle
                    // create "moodle_profile" owner
                    if (!$DB->record_exists('crlm_field_owner', array('fieldid' => $record->id, 'plugin' => 'moodle_profile'))) {
                        $owner = new stdClass;
                        $owner->fieldid = $record->id;
                        $owner->plugin = 'moodle_profile';
                        $owner->exclude = false;
                        $result = $result && $DB->insert_record('crlm_field_owner', $owner);
                    }
                    // NOTE: fall through
                default:
                    // no sync or invalid user
                    // create "manual" owner
                    $controltype = $record->datatype;
                    $record->datatype = 'text';
                    if (!$DB->record_exists('crlm_field_owner', array('fieldid' => $record->id, 'plugin' => 'manual'))) {
                        $owner = new stdClass;
                        $owner->fieldid = $record->id;
                        $owner->plugin = 'manual';
                        $owner->exclude = false;
                        $owner->params = array('control' => $controltype,
                                               'required' => $record->required);
                        if ($record->locked) {
                            $owner->params['edit_capability'] = 'moodle/user:update';
                        }
                        if (!$record->visible) {
                            $owner->params['view_capability'] = 'moodle/user:viewhiddendetails';
                        }
                        if (!empty($record->params)) {
                            $owner->params += unserialize($record->params);
                        }
                        switch ($controltype) {
                        case 'checkbox':
                            // legacy checkboxes are all boolean
                            $record->datatype = 'bool';
                            $data_recs = $DB->get_recordset('crlm_field_data_text', array('fieldid' => $record->id));
                            foreach ($data_recs as $data_rec) {
                                $DB->delete_records('crlm_field_data_text', 'id', $data_rec->id);
                                unset($data_rec->id);
                                $DB->insert_record('crlm_field_data_int', $data_rec);
                            }
                            $data_recs->close();
                            break;
                        case 'menu':
                            // menu items should be short text
                            $record->datatype = 'char';
                            $data_recs = $DB->get_recordset('crlm_field_data_text', array('fieldid' => $record->id));
                            foreach ($data_recs as $data_rec) {
                                $DB->delete_records('crlm_field_data_text', 'id', $data_rec->id);
                                unset($data_rec->id);
                                $DB->insert_record('crlm_field_data_char', $data_rec);
                            }
                            $data_recs->close();
                            break;
                        case 'text':
                            $owner->params['columns'] = $owner->params['size'];
                            unset($owner->params['size']);
                            break;
                        }
                        $owner->params = serialize($owner->params);
                        $result = $result && $DB->insert_record('crlm_field_owner', $owner);
                    }
                    break;
                }
                $record->params = '';
                $result = $result && $DB->update_record('crlm_field', $record);
                if (!empty($record->defaultdata)) {
                    if (!$DB->record_exists_select('crlm_field_data_text', "fieldid = {$record->id} AND contextid IS NULL")) {
                        $defaultdata = new stdClass;
                        $defaultdata->fieldid = $record->id;
                        $defaultdata->data = $record->defaultdata;
                        $result = $result && $DB->insert_record('crlm_field_data_text', $defaultdata);
                    }
                }
            }
        }
        $records->close;

        $table = new XMLDBTable('crlm_field');

    /// Define field required to be dropped from crlm_field
        $field = new XMLDBField('required');

    /// Launch drop field required
        $result = $result && $dbmanager->drop_field($table, $field);


    /// Define field locked to be dropped from crlm_field
        $field = new XMLDBField('locked');

    /// Launch drop field locked
        $result = $result && $dbmanager->drop_field($table, $field);


    /// Define field visible to be dropped from crlm_field
        $field = new XMLDBField('visible');

    /// Launch drop field visible
        $result = $result && $dbmanager->drop_field($table, $field);


    /// Define field defaultdata to be dropped from crlm_field
        $field = new XMLDBField('defaultdata');

    /// Launch drop field defaultdata
        $result = $result && $dbmanager->drop_field($table, $field);


    /// Define field syncwithmoodle to be dropped from crlm_field
        $field = new XMLDBField('syncwithmoodle');

    /// Launch drop field syncwithmoodle
        $result = $result && $dbmanager->drop_field($table, $field);


    /// Define field multivalued to be added to crlm_field
        $field = new XMLDBField('multivalued');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, null, null, '0', 'sortorder');

    /// Launch add field multivalued
        $result = $result && $dbmanager->add_field($table, $field);
        upgrade_block_savepoint($result, 2010063006, 'curr_admin');
    }

    if ($result && $oldversion < 2010063007) {
        // install.xml accidentally had the char table use an integer data field

    /// Changing type of field data on table crlm_field_data_char to char
        $table = new XMLDBTable('crlm_field_data_char');
        $field = new XMLDBField('data');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null, 'fieldid');

    /// Launch change of type for field data
        $result = $result && $dbmanager->change_field_type($table, $field);
        upgrade_block_savepoint($result, 2010063007, 'curr_admin');
    }

    if ($result && $oldversion < 2010063008) {
        $table = new XMLDBTable('crlm_cluster_curriculum');
        $field = new XMLDBField('autoenrol');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '1', 'curriculumid');

        $result = $result && $dbmanager->add_field($table, $field);

        $table = new XMLDBTable('crlm_cluster_track');
        $field = new XMLDBField('autoenrol');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '1', 'trackid');

        $result = $result && $dbmanager->add_field($table, $field);

    /// Define field parent to be added to crlm_cluster
        $table = new XMLDBTable('crlm_cluster');
        $field = new XMLDBField('parent');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'display');

    /// Launch add field parent
        $result = $result && $dbmanager->add_field($table, $field);

    /// Define field depth to be added to crlm_cluster
        $field = new XMLDBField('depth');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '1', 'parent');

    /// Launch add field depth
        $result = $result && $dbmanager->add_field($table, $field);
        upgrade_block_savepoint($result, 2010063008, 'curr_admin');
    }

    if ($result && $oldversion < 2010063013) {
        /*
         * Curriculum
         */
        $table = new XMLDBTable('crlm_curriculum');

        //name field
        $index = new XMLDBIndex('name_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('name'));
        if(!index_exists($table, $index)) {
            $result = $result && $dbmanager->add_index($table, $index);
        }

        /*
         * Course
         */
        $table = new XMLDBTable('crlm_course');

        //name field
        $index = new XMLDBIndex('name_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('name'));
        if(!index_exists($table, $index)) {
            $result = $result && $dbmanager->add_index($table, $index);
        }

        //credits field
        $index = new XMLDBIndex('credits_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('credits'));
        if(!index_exists($table, $index)) {
            $result = $result && $dbmanager->add_index($table, $index);
        }

        /*
         * Class
         */
        $table = new XMLDBTable('crlm_class');

        //idnumber field
        $index = new XMLDBIndex('idnumber_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('idnumber'));
        if(!index_exists($table, $index)) {
            $result = $result && $dbmanager->add_index($table, $index);
        }

        //enddate field
        $index = new XMLDBIndex('enddate_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('enddate'));
        if(!index_exists($table, $index)) {
            $result = $result && $dbmanager->add_index($table, $index);
        }

        /*
         * Class enrolment
         */
        $table = new XMLDBTable('crlm_class_enrolment');

        //completetime field
        $index = new XMLDBIndex('completetime_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('completetime'));
        if(!index_exists($table, $index)) {
            $result = $result && $dbmanager->add_index($table, $index);
        }

        //completestatusid field
        $index = new XMLDBIndex('completestatusid_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('completestatusid'));
        if(!index_exists($table, $index)) {
            $result = $result && $dbmanager->add_index($table, $index);
        }

        /*
         * CM user
         */
        $table = new XMLDBTable('crlm_user');

        //lastname field
        $index = new XMLDBIndex('lastname_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('lastname'));
        if(!index_exists($table, $index)) {
            $result = $result && $dbmanager->add_index($table, $index);
        }

        //firstname field
        $index = new XMLDBIndex('firstname_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('firstname'));
        if(!index_exists($table, $index)) {
            $result = $result && $dbmanager->add_index($table, $index);
        }
        upgrade_block_savepoint($result, 2010063013, 'curr_admin');
    }

    if ($result && $oldversion < 2010063015) {

    /// Define field autocreated to be added to crlm_class_moodle
        $table = new XMLDBTable('crlm_class_moodle');
        $field = new XMLDBField('autocreated');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null, null, '-1', 'timemodified');

    /// Launch add field autocreated
        $result = $result && $dbmanager->add_field($table, $field);
        upgrade_block_savepoint($result, 2010063015, 'curr_admin');
    }

    if ($result && $oldversion < 2010111300) {
        $table = new XMLDBTable('crlm_curriculum_assignment');

        $field = new XMLDBField('timeexpired');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'timecompleted');

        // Launch add field multivalued
        $result = $result && $dbmanager->add_field($table, $field);
        upgrade_block_savepoint($result, 2011011300, 'curr_admin');
    }

    if ($result && $oldversion < 2011011802) {
        // delete duplicate records
        $sql = "DELETE FROM g
                 USING {crlm_class_graded} g, {crlm_class_graded} g2
                 WHERE g.userid = g2.userid
                       AND g.classid = g2.classid
                       AND g.completionid = g2.completionid
                       AND g.id < g2.id";
        $DB->execute($sql);
        upgrade_block_savepoint($result, 2011011802, 'curr_admin');
    }

    if ($result && $oldversion < 2011050200) {
        /// Define index startdate_ix (not unique) to be added to crlm_class
        $table = new XMLDBTable('crlm_class');
        $index = new XMLDBIndex('startdate_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('startdate'));
        $result = $result && $dbmanager->add_index($table, $index);

        /// Define index enrolmenttime_ix (not unique) to be added to crlm_class_enrolment
        $table = new XMLDBTable('crlm_class_enrolment');
        $index = new XMLDBIndex('enrolmenttime_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('enrolmenttime'));
        $result = $result && $dbmanager->add_index($table, $index);

        /// Define index locked_ix (not unique) to be added to crlm_class_graded
        $table = new XMLDBTable('crlm_class_graded');
        $index = new XMLDBIndex('locked_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('locked'));
        $result = $result && $dbmanager->add_index($table, $index);

        /// Define index timegraded_ix (not unique) to be added to crlm_class_graded
        $table = new XMLDBTable('crlm_class_graded');
        $index = new XMLDBIndex('timegraded_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('timegraded'));
        $result = $result && $dbmanager->add_index($table, $index);

        /// Define index classid_ix (not unique) to be added to crlm_class_moodle
        $table = new XMLDBTable('crlm_class_moodle');
        $index = new XMLDBIndex('classid_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('classid'));
        $result = $result && $dbmanager->add_index($table, $index);

        /// Define index curriculumid_ix (not unique) to be added to crlm_cluster_curriculum
        $table = new XMLDBTable('crlm_cluster_curriculum');
        $index = new XMLDBIndex('curriculumid_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('curriculumid'));
        $result = $result && $dbmanager->add_index($table, $index);

        /// Define index fieldid_ix (not unique) to be added to crlm_cluster_profile
        $table = new XMLDBTable('crlm_cluster_profile');
        $index = new XMLDBIndex('fieldid_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('fieldid'));
        $result = $result && $dbmanager->add_index($table, $index);

        /// Define index trackid_ix (not unique) to be added to crlm_cluster_track
        $table = new XMLDBTable('crlm_cluster_track');
        $index = new XMLDBIndex('trackid_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('trackid'));
        $result = $result && $dbmanager->add_index($table, $index);

        /// Define index idnumber_ix (not unique) to be added to crlm_course_completion
        $table = new XMLDBTable('crlm_course_completion');
        $index = new XMLDBIndex('idnumber_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('idnumber'));
        $result = $result && $dbmanager->add_index($table, $index);

        /// Define index sortorder_ix (not unique) to be added to crlm_field
        $table = new XMLDBTable('crlm_field');
        $index = new XMLDBIndex('sortorder_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('sortorder'));
        $result = $result && $dbmanager->add_index($table, $index);

        /// Define index username_ix (not unique) to be added to crlm_user
        $table = new XMLDBTable('crlm_user');
        $index = new XMLDBIndex('username_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('username'));
        $result = $result && $dbmanager->add_index($table, $index);

        /// Define index inactive_ix (not unique) to be added to crlm_user
        $table = new XMLDBTable('crlm_user');
        $index = new XMLDBIndex('inactive_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('inactive'));
        $result = $result && $dbmanager->add_index($table, $index);

        /// Define index userid_ix (not unique) to be added to crlm_user_track
        $table = new XMLDBTable('crlm_user_track');
        $index = new XMLDBIndex('userid_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $result = $result && $dbmanager->add_index($table, $index);

        /// Define index trackid_ix (not unique) to be added to crlm_user_track
        $table = new XMLDBTable('crlm_user_track');
        $index = new XMLDBIndex('trackid_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('trackid'));
        $result = $result && $dbmanager->add_index($table, $index);

        /// Define index classid_ix (not unique) to be added to crlm_wait_list
        $table = new XMLDBTable('crlm_wait_list');
        $index = new XMLDBIndex('classid_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('classid'));
        $result = $result && $dbmanager->add_index($table, $index);

        /// Define index userid_ix (not unique) to be added to crlm_wait_list
        $table = new XMLDBTable('crlm_wait_list');
        $index = new XMLDBIndex('userid_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $result = $result && $dbmanager->add_index($table, $index);
        upgrade_block_savepoint($result, 2011050200, 'curr_admin');
    }

    if ($result && $oldversion < 2011050201) {
        // make sure that hours are within 24 hours
        $sql = "UPDATE {crlm_class}
                   SET starttimehour = MOD(starttimehour, 24),
                       endtimehour = MOD(endtimehour, 24)";
        $result = $result && $DB->execute($sql);
        upgrade_block_savepoint($result, 2011050201, 'curr_admin');
    }

    if ($result && $oldversion < 2011050202) {

    /// Changing type of field credits on table crlm_class_enrolment to number
        $table = new XMLDBTable('crlm_class_enrolment');
        $field = new XMLDBField('credits');
        $field->setAttributes(XMLDB_TYPE_NUMBER, '10, 2', XMLDB_UNSIGNED, null, null, null, null, '0', 'grade');

    /// Launch change of type for field credits
        $result = $result && change_field_type($table, $field);

    /// Changing type of field credits on table crlm_curriculum_assignment to number
        $table = new XMLDBTable('crlm_curriculum_assignment');
        $field = new XMLDBField('credits');
        $field->setAttributes(XMLDB_TYPE_NUMBER, '10, 2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'timeexpired');

    /// Launch change of type for field credits
        $result = $result && change_field_type($table, $field);


    /// Changing type of field reqcredits on table crlm_curriculum to number
        $table = new XMLDBTable('crlm_curriculum');
        $field = new XMLDBField('reqcredits');
        $field->setAttributes(XMLDB_TYPE_NUMBER, '10, 2', XMLDB_UNSIGNED, null, null, null, null, null, 'description');

    /// Launch change of type for field reqcredits
        $result = $result && change_field_type($table, $field);

        // update student class credits with decimal credits
        $sql = "UPDATE mdl_crlm_class_enrolment e, mdl_crlm_class cls, mdl_crlm_course c
                   SET e.credits = c.credits
                 WHERE e.classid = cls.id
                   AND cls.courseid = c.id
                   AND e.credits = cast(c.credits as unsigned)";
        $result = $result && execute_sql($sql);
        upgrade_block_savepoint($result, 2011050202, 'curr_admin');
    }

    return $result;
}
