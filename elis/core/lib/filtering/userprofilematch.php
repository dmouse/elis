<?php
/**
 * userprofilematch.php - PHP Report filter
 *
 * Group of filters for matching user profile fields
 *
 * Configuration options include:
 *  ['choices'] => array, defines included DB user fields [as key],
 *                 *optional* value (string) as form field label
 *                 language string key for options['langfile'] (below).
 * fields (keys) may include:
 * fullname, lastname, firstname, idnumber, email, city, country,
 * username. confirmed, crsrole, crscat, sysrole,
 * firstaccess, lastaccess, lastlogin, timemodified, auth
 * + any user_info_field.shortname custom profile fields
 * * filters displayed in the order of this array!
 *
 * ['advanced'] => array, DB user feilds which are advanced form elements.
 * ['notadvanced'] => array, DB user fields which are NOT advanced form elements
 * (use only one of keys 'advanced' or 'notadvanced')
 * ['langfile'] => string optional language file, default: 'elis_core'
 *   ['tables'] => optional array of tables as key, table alias as value
 * default values show below: array(
 *             'user' => 'u',
 *   'user_info_data' => 'uidata', // only required for extra profile fields
 * 'role_assignments' => 'ra' // only required for crsrole and sysrole
 * );
 *    ['extra'] => boolean : true includes all extra profile fields,
 *                 false (default) does not auto include them.
 *  ['heading'] => optional string (raw HTML) - NOT yet IMPLEMENTED
 *   ['footer'] => optional string (raw HTML) - NOT yet IMPLEMENTED
 *     ['help'] => optional array of arrays:
                   keys are user fields (above, or extra profile fields shortnames)
 *                 values are arrays to pass to setHelpButton($helpbuttonargs)
                   - not implemented in all sub-filters!
 *
 * (TBD: add layout options, columns/row designation ...)
 *
 * NOTES:
 * ======
 * 1. Since this is a compund filter it must be used a bit different then
 *    standard filters.
 *
 * 2. Call constructor method directly _NOT_ using:
 *        new generalized_filter_entry( ... ) - INCORRECT!
 *    Instead call:
 *        new generalized_filter_userprofilematch( ... ) - CORRECT!
 *
 * 3. Class constructor and get_filters() methods return an array!
 *    Therefore, do _NOT_ put return inside another array in your report's
 *    get_filters() method.
 *
 * E.g.
 * // CORRECT
 * class some_user_report extends table_report {
 *     ...
 *     function get_filters() {
 *         $userprofilefilters = new generalized_filter_userprofilematch( ... );
 *         return array_merge(
 *             $userprofilefilters->get_filters(),
 *             array(
 *                 new generalized_filter_entry( ... ),
 *                 new generalized_filter_entry( ... ) [, ...]
 *             )
 *         );
 *     }
 *     ...
 * }
 *
 * // INCORRECT
 * class some_user_report extends table_report {
 *     ...
 *     function get_filters() {
 *         return(
 *             array(
 *                 new generalized_filter_userprofilematch( ... ),
 *                 new generalized_filter_entry( ... ),
 *                 new generalized_filter_entry( ... ) [, ...]
               )
 *         );
 *     }
 *     ...
 * }
 *
 * // CORRECT: Report case with only the User Profile filters
 * class some_user_report extends table_report {
 *     ...
 *     function get_filters() {
 *         $userprofilefilters = new generalized_filter_userprofilematch( ... );
 *         return $userprofilefilters->get_filters();
 *     }
 *     ...
 * }
 *
 * @author Brent Boghosian <brent.boghosian@remote-learner.net>
 * @version $Id$
 * @package curriculum/lib/filtering
 * @license  http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 */

require_once($CFG->dirroot.'/user/filters/lib.php');

require_once(elis::lib('filtering/multifilter.php'));
require_once(elis::lib('filtering/userprofileselect.php'));
require_once(elis::lib('filtering/userprofiletext.php'));

/**
 * Generic userprofilematch filter class
 */
class generalized_filter_userprofilematch extends generalized_filter_multifilter {

    /**
     * Class contants: Additional required sub-filter types
     */
    const filtertype_userprofiletext   = 'userprofiletext';
    const filtertype_userprofileselect = 'userprofileselect';

    const languagefile = 'elis_core';

    // Data type map
    protected $datatypemap = array(
        'text'     => self::filtertype_userprofiletext,
        'textarea' => self::filtertype_userprofiletext,
        'checkbox' => self::filtertype_userprofileselect,
        'menu'     => self::filtertype_userprofileselect,
    );

    protected $selects = array(
        self::filtertypeselect => 1,
        self::filtertype_userprofileselect => 1,
    );

    protected $_fieldids = array();

    /**
     * Class properties
     */
    // Array $fieldtofiltermap maps fields to filter type
    protected $fieldtofiltermap = array(
        'up' => array(
            // fullname_field must be added later, requires user table alias
            'lastname'     => generalized_filter_userprofilematch::filtertypetext,
            'firstname'    => generalized_filter_userprofilematch::filtertypetext,
            'idnumber'     => generalized_filter_userprofilematch::filtertypetext,
            'email'        => generalized_filter_userprofilematch::filtertypetext,
            'city'         => generalized_filter_userprofilematch::filtertypetext,
            'country'      => generalized_filter_userprofilematch::filtertypecountry,
            'username'     => generalized_filter_userprofilematch::filtertypetext,
            'lang'         => generalized_filter_userprofilematch::filtertypeselect,
            'confirmed'    => generalized_filter_userprofilematch::filtertypetristate,
            'crsrole'      => generalized_filter_userprofilematch::filtertypeselect,
            'crscat'       => generalized_filter_userprofilematch::filtertypeselect,
            'sysrole'      => generalized_filter_userprofilematch::filtertypeselect,
            'firstaccess'  => generalized_filter_userprofilematch::filtertypedate,
            'lastaccess'   => generalized_filter_userprofilematch::filtertypedate,
            'lastlogin'    => generalized_filter_userprofilematch::filtertypedate,
            'timemodified' => generalized_filter_userprofilematch::filtertypedate,
            'auth'         => generalized_filter_userprofilematch::filtertypeselect
        )
    );

    // Array $labels are default user profile field labels
    // - maybe overridden in $options array
    protected $labels = array(
        'up' => array(
            'fullname'     => 'fld_fullname',
            'lastname'     => 'fld_lastname',
            'firstname'    => 'fld_firstname',
            'idnumber'     => 'fld_idnumber',
            'email'        => 'fld_email',
            'city'         => 'fld_city',
            'country'      => 'fld_country',
            'username'     => 'fld_username',
            'lang'         => 'fld_lang',
            'confirmed'    => 'fld_confirmed',
            // TBD: not sure if following 3 fields are part of user profile?
            //'crsrole'    => 'fld_courserole',
            //'crscat'     => 'fld_coursecat',
            //'sysrole'    => 'fld_systemrole',
            'firstaccess'  => 'fld_firstaccess',
            'lastaccess'   => 'fld_lastaccess',
            'lastlogin'    => 'fld_lastlogin',
            'timemodified' => 'fld_timemodified',
            'auth'         => 'fld_auth',
        )
    );

    protected $tables = array(
        'up' => array(
            'user'             => 'u',
            'user_info_data'   => 'uidata',
            'role_assignments' => 'ra'
        )
    );

    protected $_label;
    protected $_heading;
    protected $_footer;

    protected $languagefile = 'elis_core';

    protected $sections = array('up' => array('name' => 'user'));

    //specify that we use the "data" column to filter user profile data
    var $_outerfield = array('up' => 'data');

    /**
     * Constructor
     *
     * @param string $uniqueid Unique id for filter
     * @param string $label    Filter label
     * @param array  $options  Filter options (see above)
     * @return array of sub-filters
     * @uses $DB
     */
    function generalized_filter_userprofilematch($uniqueid, $label, $options = array()) {
        global $DB;

        parent::multifilter($uniqueid, $label, $options);

        if (empty($options['help'])) {
            $options['help'] = array();
        }
        $langfile = $this->languagefile;

        // Get table aliases
        if (empty($options['tables'])) {
            $options['tables'] = array();
        }

        $shortfields = array(); // to store/check truncated field names used for filterids
        foreach ($this->labels as $key => $val) {
            $shortfieldname = substr($key, 0, MAX_FILTER_SUFFIX_LEN);
            if (in_array($shortfieldname, $shortfields)) {
                error_log("generalized_filter_userprofilematch::non-unique field name: '{$shortfieldname}' in main user profile - modify code!");
            } else {
                $shortfields[] = $shortfieldname;
            }
        }

        // Check for & assign table aliases
        foreach ($this->tables['up'] as $key => $val) {
            if (! empty($options['tables']['up'][$key])) {
                // use defaults table aliases if not specified
                $this->tables['up'][$key] = $options['tables']['up'][$key];
            }
        }

        $this->fieldtofiltermap['up']['fullname'] =
                    generalized_filter_userprofilematch::filtertypetext;

        if (array_key_exists('choices', $options)) {
            $this->make_field_list($options['choices']);
        }

        $this->_filters['up'] = array();
        foreach ($this->_fields as $group => $fields) {
            foreach ($fields as $userfield => $fieldlabel) {
                // must setup select choices for specific fields
                $myoptions = $this->make_filter_options($group, $userfield, $options['help']);
                $filterid = $uniqueid . substr($userfield, 0, MAX_FILTER_SUFFIX_LEN);
                $ftype = (string)$this->fieldtofiltermap[$group][$userfield];
                $advanced = (!empty($options['advanced']) &&
                             in_array($userfield, $options['advanced']))
                            || (!empty($options['notadvanced']) &&
                                !in_array($userfield, $options['notadvanced']));
                //error_log("userprofilematch.php: creating filter using: new generalized_filter_entry( $filterid, $talias, $dbfield, $fieldlabel, $advanced, $ftype, $myoptions)");
                // Create the filter
                $this->_filters[$group][$userfield] =
                    new generalized_filter_entry($filterid, $myoptions['talias'], $myoptions['dbfield'],
                        $fieldlabel, $advanced, $ftype, $myoptions);
            }
        }
    }

    /**
     * Get Extra Options
     *
     * Created this function to simplify the constructor.
     *
     * @param array $fields An array of db records representing custom fields
     * @return array
     * @uses $DB
     */
    function get_custom_fields($group, $fields) {
        global $DB;

        // Array $xoptions to append to existing options['choices']
        $options = array();

        if (!empty($fields)) {
            // Array $choices to hold sub options for extra field ($myoptions)
            $choices = array();

            foreach ($fields as $field) {
                $this->_fieldids[$field->shortname] = $field->id;
                $this->record_short_field_name($field->shortname);
                $this->fieldtofiltermap[$group][$field->shortname] = $this->datatypemap[$field->datatype];
                $options[$field->shortname] = $field->name;
                switch ($field->datatype) {
                    case 'char':
                    case 'text':
                        // fall-thru case!
                    case 'textarea':
                        // no options required for text fields
                        break;

                    case 'bool':
                    case 'checkbox':
                        $this->_choices[$field->shortname] = array('0' => 'No', 1 => 'Yes');
                        break;

                    case 'menu':
                        $fieldvals = $DB->get_field('user_info_field', 'param1', array('shortname' => $field->shortname));
                        if (!empty($fieldvals)) {
                            $valarray = explode("\n", $fieldvals);
                            $this->_choices[$field->shortname] = array();
                            foreach($valarray as $opt) {
                                $this->_choices[$field->shortname][$opt] = $opt;
                            }
                        }
                        if (empty($fieldvals) || empty($choices[$field->shortname])) {
                            error_log("multifilter.php:: error getting menu choices for field: {$field->shortname}");
                        }
                        break;

                    default:
                        error_log("multifilter.php:: datatype = {$field->datatype} not supported");
                }
            }
        }

        $this->sections[$group]['custom'] = $options;
    }

    /**
     * Make Custom Filter Options
     *
     * This function handles filters that require custom values (languages, countries, etc).
     *
     * @param string $group  The index of the group to which the sub filter belongs to.
     * @param string $name   The name of the sub filter to process.
     * @param array  $help   An array representing the help icon for the filter
     * @return array The customized options for the selected sub-filter
     * @uses $DB
     */
    function make_filter_options_custom($options, $group, $name) {
        global $DB;

        $manager = get_string_manager();

        switch ($name) {
            case 'fullname':
                $firstname = $this->tables[$group]['user'] .'.firstname';
                $lastname  = $this->tables[$group]['user'] .'.lastname';
                $options['dbfield'] = $DB->sql_concat($firstname, "' '", $lastname);
                $options['talias'] = '';
                $this->fieldtofiltermap[$group][$options['dbfield']] =
                        generalized_filter_userprofilematch::filtertypetext;
                break;
            case 'country': // TBD: new 'country' filter spec???
                $countries = $manager->get_list_of_countries();
                $options['choices'] = $countries; // TBD: foreach assoc.
                //$this->err_dump($countries, '$countries');
                break;
            case 'confirmed': // TBD: yesno filter???
                $options['choices'] = array('0' => 'No', 1 => 'Yes');
                $options['numeric'] = 1;
                //$this->err_dump($myoptions['choices'],'options for confir
                break;
            case 'crsrole':
                $roles = $DB->get_records('role', array(), '', 'id,name');
                $options['choices'] = array();
                foreach ($roles as $role) {
                    $options['choices'][$role->id] = $role->name;
                }
                $options['numeric'] = 1;
                $options['talias'] = $this->tables[$group]['role_assignments'];
                $options['dbfield'] = 'roleid';
                break;
            case 'lang':
                $options['choices'] = $manager->get_list_of_translations(true); // TBD
                //$this->err_dump($myoptions['choices'], 'list_of_languages
                break;
            case 'crscat':
                break;
            case 'sysrole':
                break;
            case 'auth':
                $auths = get_list_of_plugins('auth');
                //$this->err_dump($auths, '$auths');
                $options['choices'] = array();
                foreach ($auths as $auth) {
                    $options['choices'][$auth] = $auth; // TBD
                }
                break;
        }

        if (array_key_exists($name, $this->_choices)) {
            $options['choices'] = $this->_choices[$name];
        }
        if (array_key_exists($name, $this->_fieldids)) {
            $options['fieldid'] = $this->_fieldids[$name];
        }
        return $options;
    }

}

?>
