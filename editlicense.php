<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Script to let a user edit the properties of a particular RSS feed.
 *
 * @package   block_course_files_license
 * @copyright 2009 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir .'/simplepie/moodle_simplepie.php');
require_once($CFG->dirroot.'/blocks/course_files_license/locallib.php');

$context = context_system::instance();
$PAGE->set_context($context);
require_capability('block/course_files_license:managelicenses', $context);

class license_edit_form extends moodleform {
    protected $isadding;
    protected $name = '';
    protected $description = '';

    function __construct($actionurl, $isadding, $name, $description) {
        $this->isadding = $isadding;
        $this->name = $name;
        $this->description = $description;
        parent::moodleform($actionurl);
    }

    function validation($data, $files) {
        global $DB;
        $license = $DB->get_records('block_course_files_license_l', array('name'=>$data['name']));
        if (($license) && ($license->id != $data->id)) {
            return array('name' => get_string('license_name_duplicated', 'block_course_files_license'));
        }
        return true;
    }

    function definition() {
        $mform =& $this->_form;

        // Then show the fields about where this block appears.
        $mform->addElement('header', 'licenseeditheader', get_string('license', 'block_course_files_license'));

        $mform->addElement('text', 'name', get_string('license_name', 'block_course_files_license'), array('size' => 50));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');
        $mform->addHelpButton('name', 'license_name', 'block_course_files_license');

        $mform->addElement('textarea', 'description', get_string('license_description', 'block_course_files_license'), array('size' => 50));
        $mform->setType('description', PARAM_RAW);
        $mform->addRule('description', null, 'required');

        $submitlabel = null; // Default
        if ($this->isadding) {
            $submitlabel = get_string('addnewlicense', 'block_course_files_license');
        }
        $this->add_action_buttons(true, $submitlabel);
    }

    function definition_after_data(){
        $mform =& $this->_form;
    }

}

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$licenseid = optional_param('id', 0, PARAM_INT); // 0 mean create new.

$urlparams = array('id' => $licenseid);
if ($returnurl) {
    $urlparams['returnurl'] = $returnurl;
}
$managelicenses = new moodle_url('/blocks/course_files_license/managelicenses.php', $urlparams);

$PAGE->set_url('/blocks/course_files_license/editlicense.php', $urlparams);
$PAGE->set_pagelayout('admin');

if ($licenseid) {
    $isadding = false;
    $licenserecord = $DB->get_record('block_course_files_license_l', array('id' => $licenseid), '*', MUST_EXIST);
} else {
    $isadding = true;
    $licenserecord = new stdClass;
}

$mform = new license_edit_form($PAGE->url, $isadding, '', '');
$mform->set_data($licenserecord);

if ($mform->is_cancelled()) {
    redirect($managelicenses);

} else if ($data = $mform->get_data()) {
    if ($isadding) {
        $last_position = get_last_license_position();
        if ($last_position != null) {
            $data->sortorder = ($last_position + 1);
        } else {
            $data->sortorder = 0;
        }
        $DB->insert_record('block_course_files_license_l', $data);
    } else {
        $data->id = $licenseid;
        $DB->update_record('block_course_files_license_l', $data);
    }

    redirect($managelicenses);

} else {
    echo '<pre>Tercera</pre>';
    if ($isadding) {
        $strtitle = get_string('addnewlicense', 'block_course_files_license');
    } else {
        $strtitle = get_string('editalicense', 'block_course_files_license');
    }

    $PAGE->set_title($strtitle);
    $PAGE->set_heading($strtitle);

    $PAGE->navbar->add(get_string('blocks'));
    $PAGE->navbar->add(get_string('pluginname', 'block_course_files_license'));
    $PAGE->navbar->add(get_string('managelicenses', 'block_course_files_license'), $managelicenses );
    $PAGE->navbar->add($strtitle);

    echo $OUTPUT->header();
    echo $OUTPUT->heading($strtitle, 2);

    $mform->display();

    echo $OUTPUT->footer();
}

