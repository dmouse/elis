<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2010 Remote-Learner.net Inc (http://www.remote-learner.net)
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

defined('MOODLE_INTERNAL') || die();

require_once elis::lib('data/data_object.class.php');
require_once elis::lib('table.class.php');

require_once elispm::lib('lib.php');
require_once elispm::lib('deprecatedlib.php');
require_once elispm::lib('data/pmclass.class.php');

define ('ENGINETABLE', 'crlm_results_engine');

class resultsengine extends elis_data_object {
    const TABLE = ENGINETABLE;
    const LANG_FILE = 'elis_program';

    private $form_url = null;  //moodle_url object

    protected $_dbfield_id;
    protected $_dbfield_contextid;
    protected $_dbfield_active;
    protected $_dbfield_eventtriggertype;
    protected $_dbfield_lockedgrade;
    protected $_dbfield_triggerstartdate;
    protected $_dbfield_criteriatype;

    static $associations = array(
        'contextid' => array('class' => 'user',
                             'idfield' => 'contextid'),
    );

    static $validation_rules = array(array('validation_helper', 'not_empty_contextid'),
                                     'validate_associated_contextid_exists');

    /**
     * Validates that the associated user record exists
     */
    public function validate_associated_contextid_exists() {
        validate_associated_record_exists($this, 'context');
    }

    /**
     * Perform parent add
     */
    public function save() {
        parent::save();
    }

    /**
     * Perform parent delete
    */
    public function delete() {
        parent::delete();
    }

    public function set_from_data($data) {
        $this->_load_data_from_record($data, true);
    }
}