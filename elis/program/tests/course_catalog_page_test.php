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
 * @package    elis_program
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/elis/program/lib/setup.php');

// Data objects.
require_once(elispm::lib('data/pmclass.class.php'));
require_once(elispm::lib('data/student.class.php'));
require_once(elispm::lib('data/course.class.php'));
require_once(elispm::lib('data/user.class.php'));
require_once(elispm::file('coursecatalogpage.class.php'));

/**
 * Test features of the course catalog page.
 * @group elis_program
 */
class course_catalog_page_testcase extends elis_database_test {

    /**
     * Load CSV data for courses, classes, students, and users.
     */
    protected function load_csv_data() {
        $dataset = $this->createCsvDataSet(array(
            course::TABLE => elis::component_file('program', 'tests/fixtures/pmcrs.csv'),
            pmclass::TABLE => elis::component_file('program', 'tests/fixtures/pmclass.csv'),
            student::TABLE => elis::component_file('program', 'tests/fixtures/student.csv'),
            user::TABLE => elis::component_file('program', 'tests/fixtures/user.csv'),
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Test addclasstable->get_item_display_classsize()
     */
    public function test_get_item_display_classsize() {
        $this->load_csv_data();

        $class = new pmclass(100);
        $class->load();
        $class->maxstudents = 10;

        $items = array();
        $url = new moodle_url('http://localhost/');
        $addclasstable = new addclasstable($items, $url);
        $classsize = $addclasstable->get_item_display_classsize('', $class);
        $expected = '1/10';

        $this->assertEquals($expected, $classsize);
    }

    /**
     * Test addclasstable->get_item_display_options()
     */
    public function test_get_item_display_options() {
        $this->load_csv_data();
        $class = new pmclass(100);
        $class->load();

        $items = array();
        $url = new moodle_url('http://localhost/');
        $addclasstable = new addclasstable($items, $url);
        $option = $addclasstable->get_item_display_options('', $class);
        $expected = '<a href="index.php?s=crscat&amp;section=curr&amp;clsid=100&amp;action=savenew">Choose</a>';

        $this->assertEquals($expected, $option);
    }
}