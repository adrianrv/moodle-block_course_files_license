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

// check if the get variables passed exists and have the correct values
if(array_key_exists('ownwork', $_GET)) {
    if (!in_array($_GET['ownwork'], array(0,1))) {
        $_GET['ownwork'] = NULL;
    }
} else {
    $_GET['ownwork'] = NULL;
}

if(array_key_exists('copyright', $_GET)) {
    if (!in_array($_GET['copyright'], array(-1,0,1))) {
        $_GET['copyright'] = NULL;
    }
} else {
    $_GET['copyright'] = NULL;
}

if(array_key_exists('authorized', $_GET)) {
    if (!in_array($_GET['authorized'], array(-1,0,1))) {
        $_GET['authorized'] = NULL;
    }
} else {
    $_GET['authorized'] = NULL;
}

$courselist = block_course_files_license_get_all_courses($_GET['ownwork'], $_GET['copyright'], $_GET['authorized']);

$identified_files_col_header = get_string('identified_files', 'block_course_files_license');
if (($_GET['ownwork'] != NULL) || ($_GET['copyright'] != NULL) || ($_GET['authorized'] != NULL)) {
    $identified_files_col_header = get_string('identified_files_filter', 'block_course_files_license');
}

$table = new html_table();
$table->attributes = array('style' => 'font-size: 80%;');
$table->head = array(
    get_string('name'),
    get_string('total_files', 'block_course_files_license'),
    $identified_files_col_header
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


echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<h4>'.get_string('statistics', 'block_course_files_license').'</h4>';
echo '<p>';
echo get_string('totalcourses', 'block_course_files_license').': '.count($courselist).'<br>';
echo get_string('totalfiles', 'block_course_files_license').': '.$total_files.'<br>';
echo get_string('identified_files', 'block_course_files_license').': '.$total_identified_files.'<br>';
echo get_string('percentage_identified', 'block_course_files_license').': '.number_format((float)($total_identified_files*100/$total_files), 2).' %';
echo '</p>';
echo '</div>';

echo '<div class="col-md-6">';
echo '<h4>'.get_string('filters', 'block_course_files_license').'</h4>';
echo '<form action="'.$_SERVER['PHP_SELF'].'" method="GET">';
echo get_string('ownwork', 'block_course_files_license').': ';
echo '<label class="checkbox-inline">';
echo '<input type="radio" class="ownwork_yes"';
if ($_GET['ownwork'] != NULL) {
    if ($_GET['ownwork'] == 1) {
        echo ' checked="1"';
    }
}
echo 'name="ownwork"';
echo 'id="ownwork_yes"';
echo 'value="1"';
echo 'title="'.get_string('yes', 'block_course_files_license').'"> ';
echo get_string('yes', 'block_course_files_license').' ';
echo '</label>';

echo '<label class="checkbox-inline">';
echo '<input type="radio" class="ownwork_no"';
if ($_GET['ownwork'] != NULL) {
    if ($_GET['ownwork'] == 0) {
        echo ' checked="1"';
    }
}
echo ' name="ownwork"';
echo ' id="ownwork_no"';
echo ' value="0"';
echo ' title="'.get_string('no', 'block_course_files_license').'"> ';
echo get_string('no', 'block_course_files_license').' ';
echo '      </label><br>';

echo get_string('copyright', 'block_course_files_license').': ';
echo '<label class="checkbox-inline">';
echo ' <input type="radio" class="copyright_yes"';
if ($_GET['copyright'] != NULL) {
    if ($_GET['copyright'] == 1) {
        echo ' checked="1"';
    }
}
echo ' name="copyright"';
echo ' id="copyright_yes"';
echo ' value="1"';
echo ' title="'.get_string('yes', 'block_course_files_license').'"> ';
echo get_string('yes', 'block_course_files_license').' ';
echo '</label>';

echo '<label class="checkbox-inline">';
echo '<input type="radio" class="copyright_no"';
if ($_GET['copyright'] != NULL) {
    if ($_GET['copyright'] == 0) {
        echo ' checked="1"';
    }
}
echo ' name="copyright"';
echo ' id="copyright_no"';
echo ' value="0"';
echo ' title="'.get_string('no', 'block_course_files_license').'"> ';
echo get_string('no', 'block_course_files_license').' ';
echo '</label>';

echo '<label class="checkbox-inline">';
echo '<input type="radio" class="copyright_dkna"';
if ($_GET['copyright'] != NULL) {
    if ($_GET['copyright'] == -1) {
        echo ' checked="1"';
    }
}
echo ' name="copyright"';
echo ' id="copyright_dkna"';
echo ' value="-1"';
echo ' title="'.get_string('dkna', 'block_course_files_license').'"> ';
echo get_string('dkna', 'block_course_files_license');
echo '</label>';

echo '</br>';

echo get_string('authorized', 'block_course_files_license').': ';
echo '<label class="checkbox-inline">';
echo '<input type="radio" class="authorized_yes"';
if ($_GET['authorized'] != NULL) {
    if ($_GET['authorized'] == 1) {
        echo ' checked="1"';
    }
}
echo ' name="authorized"';
echo ' id="authorized_yes"';
echo ' value="1"';
echo ' title="'.get_string('yes', 'block_course_files_license').'"> ';
echo get_string('yes', 'block_course_files_license').' ';
echo '</label>';

echo '<label class="checkbox-inline">';
echo '<input type="radio" class="authorized_no"';
if ($_GET['authorized'] != NULL) {
    if ($_GET['authorized'] == 0) {
        echo ' checked="1"';
    }
}
echo ' name="authorized"';
echo ' id="authorized_no"';
echo ' value="0"';
echo ' title="'.get_string('no', 'block_course_files_license').'"> ';
echo get_string('no', 'block_course_files_license').' ';
echo '</label>';

echo '<label class="checkbox-inline">';
echo '<input type="radio" class="authorized_dkna"';
if ($_GET['authorized'] != NULL) {
    if ($_GET['authorized'] == -1) {
        echo ' checked="1"';
    }
}
echo ' name="authorized"';
echo ' id="authorized_dkna"';
echo ' value="-1"';
echo ' title="'.get_string('dkna', 'block_course_files_license').'"> ';
echo get_string('dkna', 'block_course_files_license');
echo '</label>';

echo '<br><br>';

echo '<button type="submit" class="btn btn-primary btn-sm" ';
echo 'onclick="$(\'#ownwork_yes\').prop(\'checked\', false);';
echo '$(\'#ownwork_no\').prop(\'checked\', false);';
echo '$(\'#copyright_yes\').prop(\'checked\', false);';
echo '$(\'#copyright_no\').prop(\'checked\', false);';
echo '$(\'#copyright_dkna\').prop(\'checked\', false);';
echo '$(\'#authorized_yes\').prop(\'checked\', false);';
echo '$(\'#authorized_no\').prop(\'checked\', false);';
echo '$(\'#authorized_dkna\').prop(\'checked\', false);return false;">';
echo '<i class="fa fa-trash"></i> ';
echo get_string('deletefilters', 'block_course_files_license');
echo '</button> ';

echo '<button type="submit" class="btn btn-success btn-sm"><i class="fa fa-check-square-o"></i> ';
echo get_string('applyfilters', 'block_course_files_license');
echo '</button>';

echo '</form>';

echo '</div>';
echo '</div>';

echo html_writer::table($table);

echo $OUTPUT->footer();
