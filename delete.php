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
 * Block to show course files and usage
 *
 * @package   block_course_files_licence
 * @copyright 2015 Adrian Rodriguez Vargas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/blocks/course_files_license/locallib.php');

require_login();

$courseid = required_param('courseid', PARAM_INT); // If no courseid is given.
$course = $DB->get_record('course', array('id' => $courseid));

$context = context_course::instance($courseid);
require_capability('block/course_files_license:viewlist', $context);

$coursefilelist = get_course_files_list();
$identifiedcoursefilelist = get_identified_course_files_list();

$file_instances = [];
foreach ($identifiedcoursefilelist as $f_id => $f_value) {
    $file_instances[]=$f_value->id;
}

if ($_POST) {
    require_capability('block/course_files_license:deleteinstance', $context);
    if (in_array('all', array_keys($_POST))) {
        if ($_POST['all'] == true){
            $DB->delete_records('block_course_files_license_f', array ('courseid'=>$_POST['courseid']));
        }
    } elseif(in_array('id', array_keys($_POST))) {
        $DB->delete_records('block_course_files_license_f', array ('id'=>$_POST['id']));
    }
}

$view_url = new moodle_url('/blocks/course_files_license/view.php', array('courseid' => $courseid));
redirect($view_url);
