<?php
/**
 *
 *
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
 * @package
 * @subpackage
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

define('CLI_SCRIPT', true);
require_once(dirname(__FILE__).'/../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once(elis::lib('testlib.php'));
require_once($CFG->dirroot.'/repository/elis_files/ELIS_files_factory.class.php');
require_once($CFG->dirroot.'/repository/elis_files/lib/lib.php');

define('ONE_MB_BYTES', 1048576);
define('ELIS_FILES_PREFIX', 'elis_files_test_file_upload_');

/**
 *
 * @param integer $mbs The file size (in MB) to generate.
 */
function generate_temp_file($mbs) {
    global $CFG;

    $fname = tempnam($CFG->dataroot.'/temp/', ELIS_FILES_PREFIX);

    if (!$fh = fopen($fname, 'w+')) {
        error('Could not open temporary file');
    }

    $maxbytes = $mbs * ONE_MB_BYTES;
    $data     = '';
    $fsize    = 0;

    for ($i = 0; $i < $mbs; $i++) {
        while ((strlen($data) < ONE_MB_BYTES) && ((strlen($data) + $fsize) < $maxbytes)) {
            $data .= 'a';
        }

        fwrite($fh, $data);
        $fsize += strlen($data);
    }

    fclose($fh);

    return $fname;
}
class fileduplicatesTest extends elis_database_test {

    protected static function get_overlay_tables() {
        return array(
            'config_plugins' => 'moodle'
        );
    }

    protected function setUp() {
        parent::setUp();

        $rs = self::$origdb->get_recordset('config_plugins', array('plugin' => 'elis_files'));

        if ($rs->valid()) {
            foreach ($rs as $setting) {
                self::$overlaydb->import_record('config_plugins', $setting);
            }
            $rs->close();
        }

        $USER = get_admin();
        $GLOBALS['USER'] = $USER;
    }

    protected function tearDown() {
        $this->cleanupfiles();

        parent::tearDown();
    }

    public function cleanupfiles($uuid='') {
        if ($dir = elis_files_read_dir($uuid)) {
            foreach ($dir->files as $file) {
                if (strpos($file->title, ELIS_FILES_PREFIX) === 0) {
                    elis_files_delete($file->uuid);
                }
            }
        }
    }

    /**
     * Test that uploading a duplicate file handles overwrites
     */
    public function testOverwriteDuplicate() {
        global $CFG, $_POST;

        // Check if Alfresco is enabled, configured and running first
        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped();
        }

        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped('Repository not configured or enabled');
        }

        // Generate a file
        $filesize = 1 * ONE_MB_BYTES;
        $filename = generate_temp_file(1);

        $uploadresponse = elis_files_upload_file('',$filename);

        //set overwrite param
        $_POST['overwrite'] = true;

        //setup filemeta
        $path_parts = pathinfo($filename);
        $filemeta = new stdClass;
        $filemeta->name = $path_parts['basename'];
        $filemeta->filepath = $CFG->dataroot.'/temp/';
        $filemeta->type = mime_content_type($filename);
        $filemeta->size = $filesize;
        //we need the uuid of the file to send to the elis_files_handle_duplicate function
        $duplicateresponse = elis_files_handle_duplicate_file('', $filename, '', $uploadresponse->uuid, $filemeta);

        if (file_exists($filename)) {
            unlink($filename);
        }

        // Verify that we get a valid response
        $this->assertNotEquals(false, $duplicateresponse);
        // Verify that response has a uuid
        $this->assertObjectHasAttribute('uuid', $duplicateresponse);

        // Get info on the uploaded file's uuid...
        $response = $repo->get_info($duplicateresponse->uuid);

        // Verify that response has a type
        $this->assertObjectHasAttribute('type', $response);
        // Verify that type is folder
        $this->assertEquals(ELIS_files::$type_document, $response->type);
        // Verify that title is set
        $this->assertObjectHasAttribute('title', $response);
        // Verify that created is set
        $this->assertObjectHasAttribute('created', $response);
        // Verify that modified is set
        $this->assertObjectHasAttribute('modified', $response);
        // Verify that summary is set
        $this->assertObjectHasAttribute('summary', $response);
        // Verify that Owner is set
        $this->assertObjectHasAttribute('owner', $response);
    }

    /**
     * Test that uploading a duplicate file handles overwrites
     */
    public function testRenameDuplicate() {
        global $CFG, $_POST;

        // Check if Alfresco is enabled, configured and running first
        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped();
        }

        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped('Repository not configured or enabled');
        }

        // Generate a file
        $filesize = 1 * ONE_MB_BYTES;
        $filename = generate_temp_file(1);


        $uploadresponse = elis_files_upload_file('',$filename);

        //setup filemeta
        $path_parts = pathinfo($filename);
        $filemeta = new stdClass;
        $filemeta->name = $path_parts['basename'];
        $filemeta->filepath = $CFG->dataroot.'/temp/';
        $filemeta->type = mime_content_type($filename);
        $filemeta->size = $filesize;
        // Generate a duplicate filename
        $listing = array();
        $listing['list'] = array();
        $listing['list'][0]['title'] = $filemeta->name;
        $listing['list'][0]['path'] = base64_encode(serialize($uploadresponse->uuid));

        $filemeta->newfilename =  elis_files_generate_unique_filename($filemeta->name, $listing);

        //we need the uuid of the file to send to the elis_files_handle_duplicate function
        $duplicateresponse = elis_files_handle_duplicate_file('', $filename, '', $uploadresponse->uuid, $filemeta);

        if (file_exists($filename)) {
            unlink($filename);
        }
        if (file_exists($filemeta->filepath.$filemeta->newfilename)) {
            unlink($filemeta->filepath.$filemeta->newfilename);
        }
        // Verify that we get a valid response
        $this->assertNotEquals(false, $duplicateresponse);
        // Verify that response has a uuid
        $this->assertObjectHasAttribute('uuid', $duplicateresponse);

        // Check that the uuid returned exists
        $node = elis_files_node_properties($duplicateresponse->uuid);

        //Verify that the node title is the same as the new filename
        $this->assertEquals($filemeta->newfilename,$node->title);

        // Get info on the uploaded file's uuid...
        $response = $repo->get_info($duplicateresponse->uuid);

        // Verify that response has a type
        $this->assertObjectHasAttribute('type', $response);
        // Verify that type is folder
        $this->assertEquals(ELIS_files::$type_document, $response->type);
        // Verify that title is set
        $this->assertObjectHasAttribute('title', $response);
        // Verify that created is set
        $this->assertObjectHasAttribute('created', $response);
        // Verify that modified is set
        $this->assertObjectHasAttribute('modified', $response);
        // Verify that summary is set
        $this->assertObjectHasAttribute('summary', $response);
        // Verify that Owner is set
        $this->assertObjectHasAttribute('owner', $response);
    }
}
