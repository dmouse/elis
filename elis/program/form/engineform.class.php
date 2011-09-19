<?php
/**
 *  ELIS(TM): Enterprise Learning Intelligence Suite
 *
 *  Copyright (C) 2008-2011 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @package    elis
 *  @subpackage programmanagement
 *  @author     Remote-Learner.net Inc
 *  @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 *  @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 */

defined('MOODLE_INTERNAL') || die();

require_once elispm::file('form/cmform.class.php');
require_once elispm::lib('lib.php');

/**
 * the form element for curriculum
 */
class cmEngineForm extends cmform {
    const LANG_FILE = 'elis_program';

    // Form html
    protected $_html = array();

    // Layout switching
    protected $_layout = 'default';

    /**
     * defines items in the form
     */
    public function definition() {
        $configData = array('title');

        $this->defineActivation();
        $this->defineResults();

        $this->add_action_buttons();
    }

    /**
     * Define the activation section of the form.
     *
     * @uses $DB
     */
    protected function defineActivation() {
        global $DB;

        $grades = array(0 => get_string('engineform:class_grade', self::LANG_FILE));
        $dates  = array(1 => get_string('engineform:after_class_start', self::LANG_FILE),
                        2 => get_string('engineform:before_class_end', self::LANG_FILE),
                        3 => get_string('engineform:after_class_end', self::LANG_FILE));

        $conditions = array('courseid' => $this->_customdata['obj']->courseid);

        $completions = $DB->get_records(coursecompletion::TABLE, $conditions);

        foreach ($completions as $completion) {
            $grades[$completion->id] = $completion->name;
        }

        $activationrules = get_string('engineform:activation_rules', self::LANG_FILE);
        $eventtrigger    = get_string('engineform:event_trigger', self::LANG_FILE);
        $activaterule    = get_string('engineform:activate_this_rule', self::LANG_FILE);
        $uselocked       = get_string('engineform:use_locked_grades',self::LANG_FILE);
        $gradeset        = get_string('engineform:when_student_grade_set', self::LANG_FILE);
        $on              = get_string('engineform:on', self::LANG_FILE);
        $days            = get_string('days');
        $manualtrigger   = get_string('engineform:manual_trigger', self::LANG_FILE);
        $criterion       = get_string('engineform:criterion', self::LANG_FILE);
        $selectgrade     = get_string('engineform:select_grade', self::LANG_FILE);

        if ($this->_layout == 'custom') {
            // Setup an alternate html output so we can make the form user friendly.
            $html = array();
            $html[] = '<form>';
            $html[] = '<fieldset class="engineform">';
            $html[] = '<legend>'. $activationrules .'</legend>';
            $html[] = '<input type="hidden" name="action" value="edit" />';
            $html[] = '<input type="hidden" name="id" value="'. $this->_customdata['id'] .'" />';
            $html[] = '<input type="hidden" name="contextid" value="'. $this->_customdata['contextid'] .'" />';
            $html[] = '<input type="hidden" name="s" value="'. $this->_customdata['data'] .'" />';
            $html[] = $activaterule .' <input type="checkbox" name="active" value="1" /><br />';

            $html[] = '<fieldset class="engineform">';
            $html[] = '<legend>'. $eventtrigger .'</legend>';
            $html[] = '<input type="radio" name="eventtriggertype" value="grade" />'. $gradeset
                    .' <input type="checkbox" name="lockedgrade" value="1" />'. $uselocked .'<br />';

            $html[] = '<input type="radio" name="eventtriggertype" value="date" />'. $on;
            $html[] = ' <input type="text" name="days" value="" /> '. $days;

            $html[] = '<select name="triggerstartdate">';
            foreach ($dates as $id => $string) {
                $html[] = "<option value=\"{$id}\">{$string}</option>";
            }
            $html[] = '</select><br />';

            $html[] = '<input type="radio" name="eventtriggertype" value="manual" />'. $manualtrigger;
            $html[] = '</fieldset>';

            $html[] = '<fieldset class="engineform">';
            $html[] = '<legend>'. $criterion .'</legend>';
            $html[] = $selectgrade .':<br />';

            $html[] = '<select name="grade">';
            foreach ($grades as $id => $string) {
                $html[] = "<option value=\"{$id}\">{$string}</option>";
            }
            $html[] = '</select><br />';

            $html[] = '</fieldset>';

            $html[] = '</fieldset>';

            $this->_html = $html;
        } else {
            $mform =& $this->_form;

            $mform->addElement('hidden', 'action', 'edit');
            $mform->addElement('hidden', 'id', $this->_customdata['obj']->id);
            $mform->addElement('hidden', 'contextid', $this->_customdata['obj']->contextid);
            $mform->addElement('hidden', 's', $this->_customdata['obj']->page);

            $mform->addElement('html', '<fieldset class="engineform">');
            $mform->addElement('html', '<legend>'. $activationrules .'</legend>');

            $mform->addElement('advcheckbox', 'active', $activaterule);
            $mform->setType('active', PARAM_BOOL);

            $mform->addElement('html', '<fieldset class="engineform">');
            $mform->addElement('html', '<legend>'. $eventtrigger .'</legend>');

            $grade = array();
            $grade[] = $mform->createElement('radio', 'eventtriggertype', '', $gradeset, 1);
            $grade[] = $mform->createElement('advcheckbox', 'lockedgrade', '', $uselocked);

            $date = array();
            $date[] = $mform->createElement('radio', 'eventtriggertype', '', $on, 2);
            $date[] = $mform->createElement('text', 'days', '', 'size="2"');
            $date[] = $mform->createElement('select', 'triggerstartdate', '', $dates);

            $manual = array();
            $manual[] = $mform->createElement('radio', 'eventtriggertype', '', $manualtrigger, 3);

            $mform->setDefaults(array('eventtriggertype' => 3));

            $mform->addGroup($grade, '', '', ' ', false);
            $mform->addGroup($date, '', '', array(' ', ' '. $days .' '), false);
            $mform->addGroup($manual, '', '', ' ', false);

            $mform->setType('locked', PARAM_BOOL);
            $mform->addElement('html', '</fieldset>');

            $mform->addElement('html', '<fieldset class="engineform">');
            $mform->addElement('html', '<legend>'. $criterion .'</legend>');

            $grade = array();
            $grade[] = $mform->createElement('select', 'criteriatype', '', $grades);

            $mform->addElement('html', $selectgrade .'<br />');
            $mform->addGroup($grade);

            $mform->addElement('html', '</fieldset>');
            $mform->addElement('html', '</fieldset>');
        }
    }

    /**
     * Define the results section of the form.
     */
    protected function defineResults() {
    }

    function check_unique($table, $field, $value, $id) {
        global $DB;
        return !$DB->record_exists_select($table, "$field = ? AND id <> ?", array($value, $id));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if(!empty($data['timetocomplete'])) {
            $datedelta = new datedelta($data['timetocomplete']);

            if(!$datedelta->getDateString()) {
                $errors['timetocomplete'] = get_string('error_not_timeformat', 'elis_program');
            }
        }

        if(!empty($data['frequency'])) {
            $datedelta = new datedelta($data['frequency']);

            if(!$datedelta->getDateString()) {
                $errors['frequency'] = get_string('error_not_durrationformat', 'elis_program');
            }
        }

        if (!empty($data['idnumber'])) {
            if (!$this->check_unique(curriculum::TABLE, 'idnumber', $data['idnumber'], $data['id'])) {
                $errors['idnumber'] = get_string('badidnumber', 'elis_program');
            }
        }

        return $errors;
    }

    /**
     * Display HTML
     *
     * This function works around the limitations of the moodle forms by printing html
     * directly.  This allows for more custom designed forms.
     */
    function display() {
        if ($this->_layout == 'custom') {
            print(implode("\n", $this->_html));
        } else {
            parent::display();
        }
    }
}
