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
 * @subpackage program
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__) . '/../../core/test_config.php');
global $CFG;
require_once($CFG->dirroot . '/elis/program/lib/setup.php');
require_once(elis::lib('testlib.php'));
require_once('PHPUnit/Extensions/Database/DataSet/CsvDataSet.php');
require_once(elispm::lib('data/track.class.php'));

/** Since class is defined within track.class.php
 *  testDataObjectsFieldsAndAssociations.php will not auto test this class
 */
class trackassignmentTest extends PHPUnit_Framework_TestCase {
    protected $backupGlobalsBlacklist = array('DB');

    /**
     * The overlay database object set up by a test.
     */
    private static $overlaydb;
    /**
     * The original global $DB object.
     */
    private static $origdb;

    /**
     * Clean up the temporary database tables.
     */
    public static function tearDownAfterClass() {
        if (!empty(self::$overlaydb)) {
            self::$overlaydb->cleanup();
            self::$overlaydb = null;
        }
        if (!empty(self::$origdb)) {
            self::$origdb = null;
        }
    }

    public static function setUpBeforeClass() {
        // called before each test function
        global $DB;
        self::$origdb = $DB;
        self::$overlaydb = new overlay_database($DB,
                                      array('context'      => 'moodle',
                                            'course'       => 'moodle',
                                    trackassignment::TABLE => 'elis_program'));
    }

    /**
     * reset the $DB global
     */
    protected function tearDown() {
        global $DB;
        $DB = self::$origdb;
    }

    protected function setUp() {
        // called before each test method
        global $DB;
        self::$overlaydb->reset_overlay_tables();
        $this->setUpContextsTable();
        $DB = self::$overlaydb;
    }

    /**
     * Set up the contexts table with the minimum that we need.
     */
    private function setUpContextsTable() {
        $syscontext = self::$origdb->get_record('context', array('contextlevel' => CONTEXT_SYSTEM));
        self::$overlaydb->import_record('context', $syscontext);

        $site = self::$origdb->get_record('course', array('id' => SITEID));
        self::$overlaydb->import_record('course', $site);
        $sitecontext = self::$origdb->get_record('context', array('contextlevel' => CONTEXT_COURSE,
                                                                  'instanceid' => SITEID));
        self::$overlaydb->import_record('context', $sitecontext);
    }

    protected function load_csv_data() {
        // load initial data from a CSV file
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable(trackassignment::TABLE, elis::component_file('program', 'phpunit/trackassignment.csv')); // TBD: more generic 'phpunit/' . get_class($this) ???
        load_phpunit_data_set($dataset, true, self::$overlaydb);
    }

    /**
     * Test that data class has correct DB fields
     */
    public function testTrackAssignmentHasCorrectDBFields() {
        $testobj = new trackassignment(false, null, array(), false, array(), self::$origdb);
        $this->assertTrue($testobj->_test_dbfields(), 'Error(s) with class $_dbfield_ properties.');
    }

    /**
     * Test that data class has correct associations
     */
    public function testTrackAssignmentHasCorrectAssociations() {
        $testobj = new trackassignment(false, null, array(), false, array(), self::$origdb);
        $this->assertTrue($testobj->_test_associations(), 'Error(s) with class associations.');
    }

    /**
     * Test that a record can be created in the database.
     */
    public function testTrackAssignmentCanCreateRecord() {
        $this->markTestIncomplete('In development.');
        // create a record
        $src = new trackassignment(false, null, array(), false, array(), self::$overlaydb);
        // TBD: setup data
        $src->save();

        // read it back
        $retr = new trackassignment($src->id, null, array(), false, array(), self::$overlaydb);
        foreach ($src as $key => $value) {
            if (strpos($key, elis_data_object::FIELD_PREFIX) !== false) {
                $key = substr($key, strlen(elis_data_object::FIELD_PREFIX));
                $this->assertEquals($src->{$key}, $retr->{$key});
            }
        }
    }

    /**
     * Test that a record can be modified.
     */
    public function testTrackAssignmentCanUpdateRecord() {
        $this->markTestIncomplete('In development.');
        $this->load_csv_data();

        // read a record
        $src = new trackassignment(3, null, array(), false, array(), self::$overlaydb);
        // TBD: setup data
        $src->save();

        // read it back
        $retr = new trackassignment(3, null, array(), false, array(), self::$overlaydb);
        foreach ($src as $key => $value) {
            if (strpos($key, elis_data_object::FIELD_PREFIX) !== false) {
                $key = substr($key, strlen(elis_data_object::FIELD_PREFIX));
                $this->assertEquals($src->{$key}, $retr->{$key});
            }
        }
    }

}
