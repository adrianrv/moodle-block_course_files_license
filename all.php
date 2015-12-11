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
 * @package   block_course_files_license
 * @copyright 2015 Adrian Rodriguez Vargas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/blocks/course_files_license/locallib.php');

require_login();
$pluginstr = get_string('pluginname', 'block_course_files_license');
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/blocks/course_files_license/all.php');
$PAGE->set_title($pluginstr);
$PAGE->set_heading($pluginstr);
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add($pluginstr);

require_capability('block/course_files_license:viewall', $context);

require_capability('block/course_files_license:deleteinstance', $context);
// First of all we need to find those resources already identified that user has deleted
// on this course, so we delete it from the identified course file list
$unavailable_identifiedcoursefilelist = block_course_files_license_get_unavailable_identifiedcoursefilelist();
foreach ($unavailable_identifiedcoursefilelist as $identified_id => $identified_resource) {
    $DB->delete_records('block_course_files_license', array ('id'=>$identified_id));
}

$courselist = block_course_files_license_get_all_courses();

$table = new html_table();
$table->attributes = array('style' => 'font-size: 80%;');
$table->head = array(
    get_string('name'),
    get_string('total_files', 'block_course_files_license'),
    get_string('identified_files', 'block_course_files_license')
);

$total_files = 0;
$total_identified_files = 0;

foreach ($courselist as $course) {
    $row = new html_table_row();
    $courselink = new moodle_url('/course/view.php', array('id' => $course->courseid));
    $coursefileslink = new moodle_url('/blocks/course_files_license/view.php', array('courseid' => $course->courseid));
    $row->cells[] = html_writer::link($courselink, $course->name)
                        .' ('.html_writer::link($coursefileslink, get_string('viewcoursefiles', 'block_course_files_license')).')';
    $row->cells[] = $course->num_files;
    $row->cells[] = $course->identified_files;
    $table->data[] = $row;
    $total_files += $course->num_files;
    $total_identified_files += $course->identified_files;
}


echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('courses_list', 'block_course_files_license'));
echo '<p>'.get_string('total_files', 'block_course_files_license').': '.$total_files.'<br>';
echo get_string('identified_files', 'block_course_files_license').': '.$total_identified_files.'</p>';

echo html_writer::table($table);

echo $OUTPUT->footer();
