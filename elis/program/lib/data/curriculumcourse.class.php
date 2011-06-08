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

require_once elis::lib('data/data_object_with_custom_fields.class.php');
require_once elispm::lib('data/course.class.php');
require_once elispm::lib('data/curriculum.class.php');
require_once elispm::lib('data/pmclass.class.php');
require_once elispm::lib('data/student.class.php');
require_once elispm::file('form/curriculumcourseform.class.php');

/*
require_once(CURMAN_DIRLOCATION . '/lib/datarecord.class.php');             // ok
require_once(CURMAN_DIRLOCATION . '/lib/course.class.php');                 // ok
require_once(CURMAN_DIRLOCATION . '/lib/curriculum.class.php');             // ok
require_once(CURMAN_DIRLOCATION . '/lib/cmclass.class.php');                // ok
require_once(CURMAN_DIRLOCATION . '/lib/student.class.php');                // ok
require_once(CURMAN_DIRLOCATION . '/form/coursecurriculumform.class.php');  // ok
*/

/*
define ('CURCRSTABLE',    'crlm_curriculum_course');
define ('CRSPREREQTABLE', 'crlm_course_prerequisite');
define ('CRSCOREQTABLE',  'crlm_course_corequisite');
*/

class curriculumcourse extends data_object_with_custom_fields {
    const TABLE = 'crlm_curriculum_course';

    static $associations = array(
        'curriculum' => array(
            'class' => 'curriculum',
            'idfield' => 'curriculumid'
        ),
        'course' => array(
            'class' => 'course',
            'idfield' => 'courseid'
        ),
    );

    /*
    var $id;           // INT - The data id if in the database.
    var $curriculumid; // INT - The id of the curriclum this relationship belongs to.
    var $curriculum;   // OBJECT - Curriculum object.
    var $courseid;     // INT - The id of the course this relationship belongs to.
    var $course;       // OBJECT - Course object.
    var $required;     // BOOLEAN - True if course is required for this curriculum.
    var $frequency;    // INT - How many times the course must be re-taken.
    var $timeperiod;   // STRING - enum(day, week, month, year)
    var $position;     // INT - ?
    var $timecreated;  // INT - Timestamp.
    var $timemodified; // INT - Timestamp.

    var $_dbloaded;         // BOOLEAN - True if loaded from database.
    */

    protected $_dbfield_curriculumid;
    protected $_dbfield_courseid;
    protected $_dbfield_required;
    protected $_dbfield_frequency;
    protected $_dbfield_timeperiod;
    protected $_dbfield_position;
    protected $_dbfield_timecreated;
    protected $_dbfield_timemodifieid;

    private $form_url = null;  //moodle_url object

    // Allowable values for the timeperiod property.
    var $timeperiod_values = array(
        'year'  => 'Years',  // Default
        'month' => 'Months',
        'week'  => 'Weeks',
        'day'   => 'Days'
    );

    // STRING - Styles to use for edit form.
    var $_editstyle = '
    .curriculumcourseeditform input,
    .curriculumcourseeditform textarea,
    .curriculumcourseeditform select {
        margin: 0;
        display: block;
    }
    ';


    /**
     * Contructor.
     *
     * @param $curcrsdata int/object/array The data id of a data record or data elements to load manually.
     */
    /*
    function curriculumcourse ($curcrsdata = false) {
        parent::datarecord();

        $this->set_table(CURCRSTABLE);
        $this->add_property('id', 'int');
        $this->add_property('curriculumid', 'int');
        $this->add_property('courseid', 'int');
        $this->add_property('required', 'int');
        $this->add_property('frequency', 'int');
        $this->add_property('timeperiod', 'string');
        $this->add_property('position', 'int');
        $this->add_property('timecreated', 'int');
        $this->add_property('timemodifieid', 'int');

        if (is_numeric($curcrsdata)) {
            $this->data_load_record($curcrsdata);
        } else if (is_array($curcrsdata)) {
            $this->data_load_array($curcrsdata);
        } else if (is_object($curcrsdata)) {
            $this->data_load_array(get_object_vars($curcrsdata));
        }

        if (!empty($this->curriculumid)) {
            $this->curriculum = new curriculum($this->curriculumid);
        }

        if (!empty($this->courseid)) {
            $this->course = new course($this->courseid);
        }
    }
    */

    protected function get_field_context_level() {
        return context_level_base::get_custom_context_level('curriculum', 'elis_program');
    }

    public static function delete_for_curriculum($id) {
        global $DB;
        return $DB->delete_records(curriculumcourse::TABLE, array('curriculumid'=>$id));
    }

    public static function delete_for_course($id) {
        global $DB;
    	return $DB->delete_records(curriculumcourse::TABLE, array('courseid'=>$id));
    }

    function delete() {
        $this->delete_all_prerequisites();
        $this->delete_all_corequisites();
        $this->delete_all_track_classes();
        parent::delete();
    }

    function add() {
        parent::add();
        // TO-DO: what do we do about tables that aren't even defined as a variable?
        events_trigger('crlm_curriculum_course_associated', $this);
    }

    /////////////////////////////////////////////////////////////////////
    //                                                                 //
    //  FORM FUNCTIONS:                                                //
    //                                                                 //
    /////////////////////////////////////////////////////////////////////

    /**
     * Return the formslib form to edit a specific curriculum course.
     *
     * @param $formid string A suffix to put on all 'id' and index for all 'name' attributes.
     *                       This should be unique if being used more than once in a form.
     * @param $extraclass string Any extra class information to add to the output.
     * @param $rows int height of textareas
     * @param $cols int width of textareas
     *
     * @return string The form HTML, without the form.
     */
    public function create_edit_form($formid='', $extraclass='', $rows=2, $cols=40) {
        $config_data = array();
        $config_data['formid'] = $formid;
        $config_data['rows'] = $rows;
        $config_data['cols'] = $cols;

        $config_data['curricula_avail'] = $this->get_curricula_avail();
        $config_data['courses_avail'] = $this->get_courses_avail();

        $config_data_set = array('id', 'curriculumid', 'courseid', 'timeperiod_values');

        foreach($config_data_set as $d) {
            if(!empty($this->{$d})) {
                $config_data[$d] = $this->{$d};
            }
        }

        return new coursecurriculumform($this->form_url, $config_data);
    }

    /**
     * Return the HTML to edit a specific curriculum course.
     *
     * @param $formid string A suffix to put on all 'id' and index for all 'name' attributes.
     *                       This should be unique if being used more than once in a form.
     * @param $extraclass string Any extra class information to add to the output.
     * @param $rows int height of textareas
     * @param $cols int width of textareas
     *
     * @return string The form HTML, without the form.
     */
    public function edit_form_html($formid='', $extraclass='', $rows='2', $cols='40') {
        $output = '';

        $cmCurCourseForm = $this->create_edit_form($formid, $extraclass, $rows, $cols);

        $data_set = array('curriculumid', 'courseid', 'required', 'frequency', 'timeperiod', 'position');

        if (!empty($this->curriculumid)) {
            if (!isset($this->curriculum)) {
                $this->curriculum = new curriculum($this->curriculumid);
            }

            $data['curriculumname'] = $this->curriculum->name;
        }

        if (!empty($this->courseid)) {
            if (!isset($this->course)) {
                $this->course = new course($this->courseid);
            }

            $data['coursename'] = $this->course->name;
        }

        foreach($data_set as $d) {
            if(isset($this->{$d})){
                $data[$d] = $this->{$d};
            }
        }

        $cmCurCourseForm->set_data($data);

        ob_start();
        $cmCurCourseForm->display();
        $output .= ob_get_contents();
        ob_end_clean();

        return $output;
    }

    public function setUrl($url = null, $action = array()) {
        if(!($url instanceof moodle_url)) {
            $url = new moodle_url($url, $action);
        }

        $this->form_url = $url;
    }

    /**
     * creates the prerequisite form
     *
     * @param INT $formid
     * @param STR $extraclass
     * @param INT $rows
     * @param INT $cols
     * @return <type>
     */
    public function create_prerequisite_form($formid='', $extraclass='', $rows=2, $cols=40) {
        $config_data = array();
        $config_data['formid'] = $formid;
        $config_data['rows'] = $rows;
        $config_data['cols'] = $cols;

        $courseListing = course_get_listing();
        unset($courseListing[$this->courseid]);

        if (!empty($courseListing)) {
            $existingPrerequisites = array();

            foreach ($courseListing as $crsid => $crs) {
                if ($this->is_prerequisite($crsid)) {
                    $existingPrerequisites[$crsid] = '(' . $crs->idnumber . ')' . $crs->name;
                }
            }

            $config_data['existingPrerequisites'] = $existingPrerequisites;

            $availablePrerequisites = array();

            foreach ($courseListing as $crsid => $crs) {
                if (!$this->is_prerequisite($crsid) && !$this->is_corequisite($crsid)) {
                    $availablePrerequisites[$crsid] = '(' . $crs->idnumber . ')' . $crs->name;
                }
            }

            $config_data['availablePrerequisites'] = $availablePrerequisites;
        }

        //curriculumid, courseid, id
        $config_data['association_id'] = $this->id;
        $config_data['courseid'] = $this->courseid;
        $config_data['curriculumid'] = $this->curriculumid;

        return new prerequisiteform($this->form_url, $config_data);
    }


    /**
     * Return the HTML to edit the prerequisites for a specific course.
     *
     * @param $formid string A suffix to put on all 'id' and index for all 'name' attributes.
     *                       This should be unique if being used more than once in a form.
     * @param $extraclass string Any extra class information to add to the output.
     *
     * @return string The form HTML, without the form.
     */
    function prerequisite_form_html($formid = '', $extraclass = '', $rows = 2, $cols = 40) {
        $cmPrereqForm = $this->create_prerequisite_form($formid, $extraclass, $rows, $cols);

        ob_start();
        $cmPrereqForm->display();
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    public function create_corequisite_form($formid = '', $extraclass = '', $rows = '2', $cols = '40') {
        $config_data = array();
        $config_data['formid'] = $formid;
        $config_data['rows'] = $rows;
        $config_data['cols'] = $cols;

        $courseListing = course_get_listing();
        unset($courseListing[$this->courseid]);

        if (!empty($courseListing)) {
            $existingCorequisites = array();

            foreach ($courseListing as $crsid => $crs) {
                if ($this->is_corequisite($crsid)) {
                    $existingCorequisites[$crsid] = '(' . $crs->idnumber . ')' . $crs->name;
                }
            }

            $config_data['existingCorequisites'] = $existingCorequisites;

            $availableCorequisites = array();

            foreach ($courseListing as $crsid => $crs) {
                if (!$this->is_prerequisite($crsid) && !$this->is_corequisite($crsid)) {
                    $availableCorequisites[$crsid] = '(' . $crs->idnumber . ')' . $crs->name;
                }
            }

            $config_data['availableCorequisites'] = $availableCorequisites;
        }

        $config_data['association_id'] = $this->id;
        $config_data['courseid'] = $this->courseid;
        $config_data['curriculumid'] = $this->curriculumid;

        return new corequisiteform($this->form_url, $config_data);
    }

    /**
     * Return the HTML to edit the corequisites for a specific course.
     *
     * @param $formid string A suffix to put on all 'id' and index for all 'name' attributes.
     *                       This should be unique if being used more than once in a form.
     * @param $extraclass string Any extra class information to add to the output.
     * @param $rows int number of rows for any text areas in form
     * @param $cols in number of columns for any text areas in form
     * @return string The form HTML, without the form.
     */
    function corequisite_form_html($formid = '', $extraclass = '', $rows = '2', $cols = '40') {
        $cmCoreqForm = $this->create_corequisite_form($formid, $extraclass, $rows, $cols);

        ob_start();
        $cmCoreqForm->display();
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }


    /////////////////////////////////////////////////////////////////////
    //                                                                 //
    //  DATA FUNCTIONS:                                                //
    //                                                                 //
    /////////////////////////////////////////////////////////////////////

    /**
     * Get a list of prerequisite courses for the current curriculum.
     *
     * @param none
     * @return int The number of classes currently defined for this curriculum.
     */
    function count_courses() {
        if (!$this->_dbloaded || !$this->id) {
            return 0;
        }

        //return $this->_db->count_records('curriculum_');
    }

    function delete_all_prerequisites() {
        if (!$this->id || !$this->_dbloaded) {
            return false;
        }

        return $this->_db->delete_records(courseprerequisite::TABLE, array('curriculumcourseid'=>$this->id));
    }

    function delete_all_corequisites() {
        if (!$this->id || !$this->_dbloaded) {
            return false;
        }

        return $this->_db->delete_records(coursecorequisite::TABLE, array('curriculumcourseid'=>$this->id));
    }

    function delete_all_track_classes() {
        if (!$this->courseid || !$this->curriculumid || !$this->_dbloaded) {
            return false;
        }

        $tracks = $this->_db->get_records(track::TABLE, 'curid', $this->curriculumid);

        if (is_array($tracks)) {
            foreach ($tracks as $track_id=>$track_obj) {
                $result = $this->_db->delete_records(trackclass::TABLE, array('trackid'=>$track_obj->id, 'courseid'=>$this->courseid));
            }
        }
        return true;
    }

    /**
     * Add a new prerequisite for a course.
     *
     * @param int $cid The course ID to add as a prerequisite.
     * @param bool True on success, False otherwise.
     */
    function add_prerequisite($cid, $add_to_curriculum = false) {
        if (empty($this->id)) {
            return false;
        }

        if ($this->is_prerequisite($cid) || $this->is_corequisite($cid)) {
            return false;
        }

        $cp = new stdClass;
        $cp->curriculumcourseid = $this->id;
        $cp->courseid           = $cid;
        $cp->id = $this->_db->insert_record(courseprerequisite::TABLE, $cp);

        $result = !empty($cp->id);

        if ($result && $add_to_curriculum) {
            $data = new object();
            $data->curriculumid = $this->curriculumid;
            $data->courseid = $cid;
            $data->timeperiod = 'year';

            $currprereq = new curriculumcourse($data);
            if(!$currprereq->is_recorded()){
                $currprereq->add();
            }
        }

        return $result;
    }


    /**
     * Delete a course pre-requisite.
     *
     * @param int $cid The course ID to delete as a prerequisite.
     * @return bool True on success, False otherwise.
     */
    function del_prerequisite($cid) {
        if (!$this->id || !$this->_dbloaded) {
            return false;
        }

        return $this->_db->delete_records(courseprerequisite::TABLE, array('curriculumcourseid'=>$this->id, 'courseid'=>$cid));
    }


    /**
     * Determine if a given course ID is a prerequisite for the current course.
     *
     * @param int $cid The course ID to add as a prerequisite.
     * @param bool True on success, False otherwise.
     */
    function is_prerequisite($cid) {
        if (!$this->id || !$this->_dbloaded) {
            return false;
        }

        return $this->_db->record_exists(courseprerequisite::TABLE, 'curriculumcourseid', $this->id, 'courseid', $cid);
    }


    /**
     * Get a list of the course IDs that are a prerequisite for the current corse.
     *
     * @param none
     * @return array The list of course IDs.
     */
    function get_prerequisites() {
        $cids = array();

        if (!$this->id || !$this->_dbloaded) {
            return false;
        }

        if ($courses = $this->_db->get_records(courseprerequisite::TABLE, 'curriculumcourseid', $this->id)) {
            foreach ($courses as $course) {
                $cids[] = $course->courseid;
            }
        }

        return $cids;
    }

    /**
     * Determine whether the user has satisfied all the prerequisites for this
     * course.
     */
    function prerequisites_satisfied($userid) {
        // see if there are any prerequisites that are NOT completed
        $sql = 'SELECT p.courseid
                  FROM {'.curriculumcourse::TABLE.'} cc
                       -- find the prerequisite courses
            INNER JOIN {'.courseprerequisite::TABLE.'} p ON cc.id = p.curriculumcourseid
       LEFT OUTER JOIN ( -- find the courses the user has completed
                         -- when we require that courseid is NULL, we get the
                         -- courses the user has NOT completed
                         SELECT cls.courseid
                                -- find possible classes for the prereq courses
                           FROM {'.pmclass::TABLE.'} cls
                                -- find classes that the user has completed
                     INNER JOIN {'.student::TABLE.'} e ON cls.id = e.classid
                          WHERE e.userid = $userid. AND e.completestatusid = 2
                       ) pcls ON  pcls.courseid = p.courseid
                 WHERE cc.curriculumid = '.$this->curriculumid.' AND cc.courseid = '.$this->courseid.' AND pcls.courseid IS NULL';

        // prereqs satisfied if there are no unsatisfied prereqs
        return !$this->_db->record_exists_sql($sql);
    }


    /**
     * Add a new corequisite for a course.
     *
     * @param int $cid The course ID to add as a corequisite.
     * @param bool True on success, False otherwise.
     */
    function add_corequisite($cid, $add_to_curriculum = false) {
        if (empty($this->id)) {
            return false;
        }

        if ($this->is_prerequisite($cid) || $this->is_corequisite($cid)) {
            return false;
        }

        $cp = new stdClass;
        $cp->curriculumcourseid = $this->id;
        $cp->courseid           = $cid;
        $cp->id = $this->_db->insert_record(coursecorequisite::TABLE, $cp);

        $result = !empty($cp->id);

        if ($result && $add_to_curriculum) {
            $data = new object();
            $data->curriculumid = $this->curriculumid;
            $data->courseid = $cid;
            $data->timeperiod = 'year';

            $currprereq = new curriculumcourse($data);
            if(!$currprereq->is_recorded()){
                $currprereq->add();
            }
        }

        return $result;
    }


    /**
     * Delete a course pre-requisite.
     *
     * @param int $cid The course ID to delete as a corequisite.
     * @return bool True on success, False otherwise.
     */
    function del_corequisite($cid) {
        if (!$this->id || !$this->_dbloaded) {
            return false;
        }

        return $this->_db->delete_records(coursecorequisite::TABLE, array('curriculumcourseid'=>$this->id, 'courseid'=>$cid));
    }


    /**
     * Determine if a given course ID is a corequisite for the current course.
     *
     * @param int $cid The course ID to add as a corequisite.
     * @param bool True on success, False otherwise.
     */
    function is_corequisite($cid) {
        if (!$this->id || !$this->_dbloaded) {
            return false;
        }

        return $this->_db->record_exists(coursecorequisiteTABLE, 'curriculumcourseid', $this->id, 'courseid', $cid);
    }


    /**
     * Get a list of the course IDs that are a corequisite for the current corse.
     *
     * @param none
     * @return array The list of course IDs.
     */
    function get_corequisites() {
        $cids = array();

        if (!$this->id || !$this->_dbloaded) {
            return false;
        }

        if ($courses = $this->_db->get_records(coursecorequisite::TABLE, 'curriculumcourseid', $this->id)) {
            foreach ($courses as $course) {
                $cids[] = $course->courseid;
            }
        }

        return $cids;
    }

    function is_recorded() {
        $retval = $this->_db->record_exists(curriculumcourse::TABLE, 'curriculumid', $this->curriculumid, 'courseid', $this->courseid);
        return $retval;
    }

    /**
     * Get a list of courses available to add to this curriculum in a format
     * that can be easily added to an HTML form.
     *
     * @param none
     * @return A list of course names, indexed by their ID values.
     */
    function get_courses_avail($filters=array()) {
        $sql = 'SELECT crs.id, crs.name, crs.idnumber
                  FROM {'.course::TABLE.'} crs
             LEFT JOIN {'.curriculumcourse::TABLE.'} curcrs on curcrs.courseid = crs.id AND curcrs.curriculumid = '.$this->curriculumid.'
                 WHERE curcrs.id IS NULL ';
        if (isset($filters['contexts'])) {
            $filter_object = $filters['contexts']->filter_for_context_level('cur.id', 'course');
            $sql = $sql.' AND '.$filter_object->get_sql().' ';
        }
        $sql .= 'ORDER BY crs.name ASC';

        return get_records_sql($sql);
    }

    function get_curricula_avail($filters=array()) {
        $sql = 'SELECT cur.id, cur.name, cur.idnumber
                  FROM {'.curriculum::TABLE.'} cur
             LEFT JOIN {'.curriculumcourse::TABLE.'} curcrs ON curcrs.curriculumid = cur.id AND curcrs.courseid = '.$this->courseid.'
                 WHERE curcrs.id IS NULL ';
        if (isset($filters['contexts'])) {
            $filter_object = $filters['contexts']->filter_for_context_level('cur.id', 'curriculum');
            $sql = $sql.' AND '.$filter_object->get_sql().' ';
        }
        $sql = $sql . 'ORDER BY cur.name ASC';

        return get_records_sql($sql);
    }

    /**
     * Returns true if course is required for the curriculum
     *
     * @return boolean true if course is required
     */
    function is_course_required() {
        $required = false;
        $result = $this->_db->get_field(curriculumcourse::TABLE, 'required',
                                        'curriculumid', $this->curriculumid,
                                        'courseid', $this->courseid);
        if ($result) {
            if (1 == $result) {
                $required = true;
            }
        }
        return $required;
    }

    static $validation_rules = array(
        'validate_course_name_not_empty'
    );

    function validate_course_name_not_empty() {
        return validate_not_empty($this, 'courseid');
    }

    public function save() {
        parent::save();

        // add/update crap goes here
    }

}


/// Non-class supporting functions. (These may be able to replaced by a generic container/listing class)


/**
 * Gets a curriculum course listing with specific sort and other filters.
 *
 * @param int $curid The curriculum ID.
 * @param string $sort Field to sort on.
 * @param string $dir Direction of sort.
 * @param int $startrec Record number to start at.
 * @param int $perpage Number of records per page.
 * @param string $namesearch Search string for curriculum name.
 * @param string $descsearch Search string for curriculum description.
 * @param string $alpha Start initial of curriculum name filter.
 * @return object array Returned records.
 */
function curriculumcourse_get_listing($curid, $sort='position', $dir='ASC', $startrec=0, $perpage=0, $namesearch='', $alpha='') {
    global $DB;

    // $LIKE = $CURMAN->db->sql_compare();
    $LIKE = 'LIKE';

    $select = 'SELECT curcrs.*, crs.name AS coursename ';
    $tables = 'FROM {'.curriculumcourse::TABLE.'} curcrs ';
    $join   = 'INNER JOIN {'.course::TABLE.'} crs ';
    $on     = 'ON curcrs.courseid = crs.id ';
    $where  = 'curcrs.curriculumid = \'' . $curid . '\'';

    if (!empty($namesearch)) {
        $namesearch = trim($namesearch);
        $where .= (!empty($where) ? ' AND ' : '') . "(crs.name $LIKE  '%$namesearch%') ";
    }

    if ($alpha) {
        $where .= (!empty($where) ? ' AND ' : '') . "(crs.name $LIKE '$alpha%') ";
    }

    if (!empty($where)) {
        $where = 'WHERE '.$where.' ';
    }

    if ($sort) {
        $sort = 'ORDER BY '.$sort .' '. $dir.' ';
    }

    if (!empty($perpage)) {
        //if ($CURMAN->db->_dbconnection->databaseType == 'postgres7') {
        //    $limit = 'LIMIT '.$perpage.' OFFSET '.$startrec;
        //} else {
            $limit = 'LIMIT '.$startrec.', '.$perpage;
        //}
    } else {
        $limit = '';
    }

    $sql = $select.$tables.$join.$on.$where.$sort.$limit;

    return $DB->get_records_sql($sql);
}


function curriculumcourse_count_records($curid, $namesearch = '', $alpha = '') {
    global $DB;

    $select = '';

    //$LIKE = $CURMAN->db->sql_compare();
    $LIKE = 'LIKE';

    $select = 'SELECT COUNT(curcrs.id) ';
    $tables = 'FROM {'.curriculumcourse::TABLE.'} curcrs ';
    $join   = 'INNER JOIN {'.course::TABLE.'} crs ';
    $on     = 'ON curcrs.courseid = crs.id ';
    $where  = 'curcrs.curriculumid = \''.$curid.'\'';

    if (!empty($namesearch)) {
        $namesearch = trim($namesearch);
        $where .= (!empty($where) ? ' AND ' : '') . "(crs.name $LIKE  '%$namesearch%') ";
    }

    if ($alpha) {
        $where .= (!empty($where) ? ' AND ' : '') . "(crs.name $LIKE '$alpha%') ";
    }

    if (!empty($where)) {
        $where = 'WHERE '.$where.' ';
    }

    $sql = $select . $tables . $join . $on . $where;

    return $DB->count_records_sql($sql);
}

/**
 * Gets a curriculum course listing with specific sort and other filters.
 *
 * @param int $crsid The course ID.
 * @param string $sort Field to sort on.
 * @param string $dir Direction of sort.
 * @param int $startrec Record number to start at.
 * @param int $perpage Number of records per page.
 * @param string $namesearch Search string for curriculum name.
 * @param string $descsearch Search string for curriculum description.
 * @param string $alpha Start initial of curriculum name filter.
 * @return object array Returned records.
 */
function curriculumcourse_get_curriculum_listing($crsid, $sort='position', $dir='ASC', $startrec=0, $perpage=0, $namesearch='',
                                                 $alpha='', $contexts = null) {
    global $DB;

    //$LIKE = $CURMAN->db->sql_compare();
    $LIKE = 'LIKE';

    $select = 'SELECT curcrs.*, cur.name AS curriculumname ';
    $tables = 'FROM {'.curriculumcourse::TABLE.'} curcrs ';
    $join   = 'INNER JOIN {'.curriculum::TABLE.'} cur ';
    $on     = 'ON curcrs.curriculumid = cur.id ';
    $where  = 'curcrs.courseid = \'' . $crsid . '\'';

    if (!empty($namesearch)) {
        $namesearch = trim($namesearch);
        $where .= (!empty($where) ? ' AND ' : '') . "(cur.name $LIKE '%$namesearch%') ";
    }

    if ($alpha) {
        $where .= (!empty($where) ? ' AND ' : '') . "(cur.name $LIKE '$alpha%') ";
    }

    if ($contexts !== null) {
        //$where .= ' AND ' . $contexts->sql_filter_for_context_level('cur.id', 'curriculum');

        //$filter_object = $contexts->filter_for_context_level('cur.id', 'curriculum');
        //$where .= $filter_object->get_sql();
    }

    if (!empty($where)) {
        $where = 'WHERE '.$where.' ';
    }

    if ($sort) {
        $sort = 'ORDER BY '.$sort .' '. $dir.' ';
    }

    if (!empty($perpage)) {
        //if ($CURMAN->db->_dbconnection->databaseType == 'postgres7') {
        //    $limit = 'LIMIT ' . $perpage . ' OFFSET ' . $startrec;
        //} else {
            $limit = 'LIMIT '.$startrec.', '.$perpage;
        //}
    } else {
        $limit = '';
    }

    $sql = $select.$tables.$join.$on.$where.$sort.$limit;

    return $DB->get_records_sql($sql);
}

function curriculumcourse_count_curriculum_records($crsid, $namesearch = '', $alpha = '', $contexts = null) {
    global $DB;

    $select = '';

    //$LIKE = $CURMAN->db->sql_compare();
    $LIKE = 'LIKE';

    $select = 'SELECT COUNT(curcrs.id) ';
    $tables = 'FROM {'.curriculumcourseTABLE.'} curcrs ';
    $join   = 'INNER JOIN {'.curriculum::TABLE.'} cur ';
    $on     = 'ON curcrs.curriculumid = cur.id ';
    $where  = 'curcrs.courseid = \'' . $crsid . '\'';

    if (!empty($namesearch)) {
        $namesearch = trim($namesearch);
        $where .= (!empty($where) ? ' AND ' : '') . "(cur.name $LIKE '%$namesearch%') ";
    }

    if ($alpha) {
        $where .= (!empty($where) ? ' AND ' : '') . "(cur.name $LIKE '$alpha%') ";
    }

    if ($contexts !== null) {
        //$where .= ' AND ' . $contexts->sql_filter_for_context_level('cur.id', 'curriculum');

        //$filter_object = $contexts->filter_for_context_level('cur.id', 'curriculum');
        //$where .= $filter_object->get_sql();
    }

    if (!empty($where)) {
        $where = 'WHERE '.$where.' ';
    }

    $sql = $select.$tables.$join.$on.$where;

    return $DB->count_records_sql($sql);
}

/**
 * Returns a list of records based on the course id
 *
 * @param int courseid Course id
 * @return mixed array of crlm_curriculum_course objects or false
 */
function curriculumcourse_get_list_by_course($courseid) {
    global $DB;

    $curcourse = $DB->get_records(curriculumcourse::TABLE, 'courseid', $courseid);

    return $curcourse;
}

/**
 * Returns a list of records base on the curriculum id
 *
 * @param int curriculumid Curriculum id
 * @return mixed array of crlm_curriculum_course objects or false
 */
function curriculumcourse_get_list_by_curr($curriculumid) {
    global $DB;

    $curcourse = $DB->get_records(curriculumcourse::TABLE, 'curriculumid', $curriculumid);

    return $curcourse;
}

class courseprerequisite extends elis_data_object {
    const TABLE = 'crlm_course_prerequisite';

    static $associations = array(
        'curriculumcourse' => array(
            'class' => 'curriculumcourse',
            'idfield' => 'curriculumcourseid'
        ),
        'course' => array(
            'class' => 'course',
            'idfield' => 'courseid'
        ),
    );

    protected $_dbfield_curriculumcourseid;
    protected $_dbfield_courseid;
}

class coursecorequisite extends elis_data_object {
    const TABLE = 'crlm_course_corequisite';

    static $associations = array(
        'curriculumcourse' => array(
            'class' => 'curriculumcourse',
            'idfield' => 'curriculumcourseid'
        ),
        'course' => array(
            'class' => 'course',
            'idfield' => 'courseid'
        ),
    );

    protected $_dbfield_curriculumcourseid;
    protected $_dbfield_courseid;
}
