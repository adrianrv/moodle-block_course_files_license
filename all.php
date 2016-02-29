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

global $CFG;
// Verify entries in table block_course_files_license_l
$licenses = $DB->get_records('block_course_files_license_l', null, $sort='sortorder');
if (!$licenses) {
    die(get_string('nolicensesavailables', 'block_course_files_license'));
}

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

// Delete all instances of identified resources that have a license previously
// deleted
delete_unavailable_files();

// check for parameter in url
if(!array_key_exists('license', $_GET)) {
    $_GET['license'] = NULL;
}
$courselist = get_all_courses($_GET['license']);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('courses_list', 'block_course_files_license'));


if ($_GET['license'] != NULL) {
    $identified_files_col_header = get_string('identified_files_with_filter', 'block_course_files_license');
} else {
    $identified_files_col_header = get_string('identified_files', 'block_course_files_license');
}

$total_files = 0;
$total_identified_files = 0;

$table = new html_table();

    $table->attributes = array('style' => 'font-size: 80%;');
    $table->head = array(
        get_string('name'),
        get_string('total_files', 'block_course_files_license'),
        $identified_files_col_header
    );


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


echo '<div class="row">';
echo '<div class="col-md-6">';
if ($_GET['license'] != NULL) {
    echo '<h4>' . get_string('statistics', 'block_course_files_license') . ' ';
    echo get_string('with_filter','block_course_files_license') . '</h4>';
} else {
    echo '<h4>' . get_string('statistics', 'block_course_files_license') . '</h4>';
}
echo '<p>';
echo get_string('totalcourses', 'block_course_files_license') . ': ';
echo count($courselist).'<br>';
echo get_string('totalfiles', 'block_course_files_license') . ': ';
echo $total_files.'<br>';
echo get_string('identified_files', 'block_course_files_license') . ': ';
echo $total_identified_files.'<br>';
echo get_string('percentage_identified', 'block_course_files_license') . ': ';
echo number_format((float)($total_identified_files*100/$total_files), 2).' %';
echo '</p>';
echo '</div>';

echo '<div class="col-md-6">';
echo '<h4>' . get_string('filters', 'block_course_files_license') . '</h4>';
echo '<form action="'.$_SERVER['PHP_SELF'].'" method="GET">';

echo get_string('license', 'block_course_files_license').': ';

if ($licenses) {
    echo '<select name="license">';
    echo '<option value="">' . get_string('select_license', 'block_course_files_license') . '</option>';
}


foreach ($licenses as $l) {
    if ($_GET['license'] == $l->id) {
        echo '<option value="' . $l->id . '" selected="selected">' . $l->name . '</option>';
    } else {
        echo '<option value="' . $l->id . '">' . $l->name . '</option>';
    }
}
if ($licenses) {
    echo '</select> ';
}

echo '<button type="submit" class="btn btn-success btn-sm"><i class="fa fa-check-square-o"></i> ';
echo get_string('applyfilters', 'block_course_files_license');
echo '</button>';

echo '</form>';

echo '</div>';
echo '</div>';

if ($courselist) {
    echo html_writer::table($table);
} else {
    echo '<p>' . get_string('no_results', 'block_course_files_license') . '</p>';
}

echo $OUTPUT->footer();
