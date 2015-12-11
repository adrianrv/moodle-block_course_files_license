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

$courseid = required_param('courseid', PARAM_INT); // If no courseid is given.
$course = $DB->get_record('course', array('id' => $courseid));

$context = context_course::instance($courseid);
$PAGE->set_course($course);
$PAGE->set_url('/blocks/course_files_license/view.php', array('courseid' => $courseid));
$PAGE->set_title($course->fullname.' '.get_string('files'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('course');
$PAGE->navbar->add(get_string('pluginname', 'block_course_files_license'));

require_capability('block/course_files_license:viewlist', $context);

// First of all we need to find those resources already identified that user has deleted
// on this course, so we delete it from the identified course file list
require_capability('block/course_files_license:deleteinstance', $context);
$unavailable_identifiedcoursefilelist = block_course_files_license_get_unavailable_identifiedcoursefilelist();
foreach ($unavailable_identifiedcoursefilelist as $identified_id => $identified_resource) {
    $DB->delete_records('block_course_files_license', array ('id'=>$identified_id));
}


if ($_POST) {

    // When a post request is received we get all resources of this course to be ensure
    // that the resources we are going to save are really resources of this course
    $coursefilelist_beforesave = block_course_files_license_get_coursefilelist();
    $file_instances = [];
    foreach ($coursefilelist_beforesave as $f_id => $f_value) {
        $file_instances[]=$f_value->id;
    }

    // Then with each post received we check if is a valid instance of the
    // course and then save it
    require_capability('block/course_files_license:addinstance', $context);
    foreach ($_POST as $resource_id => $resource_values) {
        // Validate if every post object we receive are valid file instances
        if (in_array($resource_id, $file_instances)) {
            //save the item
            $record = new stdClass();
            $record->userid = $USER->id;
            $record->courseid = $courseid;
            $record->resourceid = $resource_id;
            $record->resource_name = $coursefilelist_beforesave[$resource_id]->filename;

            $resource_url  = '/pluginfile.php/'.$coursefilelist_beforesave[$resource_id]->contextid;
            $resource_url .= '/'.$coursefilelist_beforesave[$resource_id]->component;
            $resource_url .= '/'.$coursefilelist_beforesave[$resource_id]->filearea;
            $resource_url .= '/'.$coursefilelist_beforesave[$resource_id]->itemid;
            $resource_url .= $coursefilelist_beforesave[$resource_id]->filepath;
            $resource_url .= $coursefilelist_beforesave[$resource_id]->filename;
            $record->resource_url = $resource_url;
            $record->resource_size = $coursefilelist_beforesave[$resource_id]->filesize;

            $record->uploaded_by = $coursefilelist_beforesave[$resource_id]->author;
            $record->timeuploaded = $coursefilelist_beforesave[$resource_id]->timecreated;
            $now=new DateTime();
            $record->timeidentified = $now->getTimestamp();
            $record->ownwork = $resource_values['ownwork'];
            $record->copyright = $resource_values['copyright'];
            $record->authorized = $resource_values['authorized'];
            $DB->insert_record('block_course_files_license', $record, false);
        }

    }
}

// After handling the post request (if it was a post request)
// we make the query again, so the resourses already saved will not be on the list
$coursefilelist = block_course_files_license_get_coursefilelist();

$identifiedcoursefilelist = block_course_files_license_get_identifiedcoursefilelist();

$ownwork_header  = '<div style="width:100%;text-align:center;">';
$ownwork_header .= get_string('ownwork', 'block_course_files_license');
$ownwork_header .= ' <a href="#" title="'.get_string('ownwork_help', 'block_course_files_license').'">';
$ownwork_header .= '<i class="fa fa-question-circle"></i></a>';
$ownwork_header .= '<br>(';
$ownwork_header .= '<a href="#" onclick="';
$ownwork_header .= ' $(\'.ownwork_yes\').prop(\'checked\', true);';
$ownwork_header .= ' return false">';
$ownwork_header .= get_string('yes', 'block_course_files_license');
$ownwork_header .= '</a> / ';
$ownwork_header .= '<a href="#" onclick="';
$ownwork_header .= ' $(\'.ownwork_no\').prop(\'checked\', true);';
$ownwork_header .= ' return false">';
$ownwork_header .= get_string('no', 'block_course_files_license');
$ownwork_header .= '</a>)';
$ownwork_header .= '</div>';

$copyright_header  = '<div style="width:100%;text-align:center;">';
$copyright_header .= get_string('copyright', 'block_course_files_license');
$copyright_header .= ' <a href="#" title="'.get_string('copyright_help', 'block_course_files_license').'">';
$copyright_header .= '<i class="fa fa-question-circle"></i></a>';
$copyright_header .= '<br>(';
$copyright_header .= '<a href="#" onclick="$(\'.copyright_yes\').prop(\'checked\', true); return false;">';
$copyright_header .= get_string('yes', 'block_course_files_license');
$copyright_header .= '</a> / ';
$copyright_header .= '<a href="#" onclick="$(\'.copyright_no\').prop(\'checked\', true); return false;">';
$copyright_header .= get_string('no', 'block_course_files_license');
$copyright_header .= '</a> / ';
$copyright_header .= '<a href="#" onclick="$(\'.copyright_dkna\').prop(\'checked\', true); return false;">';
$copyright_header .= get_string('dkna', 'block_course_files_license');
$copyright_header .= '</a>)';
$copyright_header .= '</div>';

$authorized_header  = '<div style="width:100%;text-align:center;">';
$authorized_header .= get_string('authorized', 'block_course_files_license');
$authorized_header .= ' <a href="#" title="'.get_string('authorized_help', 'block_course_files_license').'">';
$authorized_header .= '<i class="fa fa-question-circle"></i></a>';
$authorized_header .= '<br>(';
$authorized_header .= '<a href="#" onclick="$(\'.authorized_yes\').prop(\'checked\', true); return false;">';
$authorized_header .= get_string('yes', 'block_course_files_license');
$authorized_header .= '</a> / ';
$authorized_header .= '<a href="#" onclick="$(\'.authorized_no\').prop(\'checked\', true); return false;">';
$authorized_header .= get_string('no', 'block_course_files_license');
$authorized_header .= '</a> / ';
$authorized_header .= '<a href="#" onclick="$(\'.authorized_dkna\').prop(\'checked\', true); return false;">';
$authorized_header .= get_string('dkna', 'block_course_files_license');
$authorized_header .= '</a>)';
$authorized_header .= '</div>';

$operations_header = get_string('operations', 'block_course_files_license');

$table = new html_table();
$table->attributes = array('style' => 'font-size: 80%;');
$table->head = array(
    get_string('timecreated', 'block_course_files_license'),
    get_string('filename', 'block_course_files_license'),
    get_string('uploaded_by', 'block_course_files_license'),
    $ownwork_header,
    $copyright_header,
    $authorized_header,
    $operations_header
);

foreach ($coursefilelist as $coursefile) {

    $filename_cell = '<a href="'.$CFG->wwwroot.'/pluginfile.php/'.$coursefile->contextid.'/'.$coursefile->component.'/'.$coursefile->filearea.'/'.$coursefile->itemid.$coursefile->filepath.$coursefile->filename.'">';
    $filename_cell .= $coursefile->filename.'</a> ('.display_size($coursefile->filesize).')';

    $ownwork_cell  = '<div style="width:100%;text-align:center">';
    $ownwork_cell .= '<input type="radio" class="ownwork_yes"';
    $ownwork_cell .= ' name="'.$coursefile->id.'[ownwork]"';
    $ownwork_cell .= ' id="'.$courseid.'_'.$coursefile->id.'_ownwork_yes"';
    $ownwork_cell .= ' value="1" title="'.get_string('yes', 'block_course_files_license').'"> ';

    $ownwork_cell .= '<input type="radio" class="ownwork_no"';
    $ownwork_cell .= ' name="'.$coursefile->id.'[ownwork]"';
    $ownwork_cell .= ' id="'.$courseid.'_'.$coursefile->id.'_ownwork_no"';
    $ownwork_cell .= ' value="0" title="'.get_string('no', 'block_course_files_license').'">';

    $ownwork_cell .= '</div>';

    $copyright_cell  = '<div style="width:100%;text-align:center">';
    $copyright_cell .= '<input type="radio" class="copyright_yes"';
    $copyright_cell .= ' name="'.$coursefile->id.'[copyright]"';
    $copyright_cell .= ' id="'.$courseid.'_'.$coursefile->id.'_copyright_yes"';
    $copyright_cell .= ' value="1" title="'.get_string('yes', 'block_course_files_license').'"> ';
    $copyright_cell .= '<input type="radio" class="copyright_no"';
    $copyright_cell .= ' name="'.$coursefile->id.'[copyright]"';
    $copyright_cell .= ' id="'.$courseid.'_'.$coursefile->id.'_copyright_no"';
    $copyright_cell .= ' value="0" title="'.get_string('no', 'block_course_files_license').'"> ';
    $copyright_cell .= '<input type="radio" class="copyright_dkna"';
    $copyright_cell .= ' name="'.$coursefile->id.'[copyright]"';
    $copyright_cell .= ' id="'.$courseid.'_'.$coursefile->id.'_copyright_dkna"';
    $copyright_cell .= ' value="-1" title="'.get_string('dkna', 'block_course_files_license').'"> ';
    $copyright_cell .= '</div>';

    $authorized_cell  = '<div style="width:100%;text-align:center">';
    $authorized_cell .= '<input type="radio" class="authorized_yes"';
    $authorized_cell .= ' name="'.$coursefile->id.'[authorized]"';
    $authorized_cell .= ' id="'.$courseid.'_'.$coursefile->id.'_authorized_yes"';
    $authorized_cell .= ' value="1" title="'.get_string('yes', 'block_course_files_license').'"> ';
    $authorized_cell .= '<input type="radio" class="authorized_no"';
    $authorized_cell .= ' name="'.$coursefile->id.'[authorized]"';
    $authorized_cell .= ' id="'.$courseid.'_'.$coursefile->id.'_authorized_no"';
    $authorized_cell .= ' value="0" title="'.get_string('no', 'block_course_files_license').'"> ';
    $authorized_cell .= '<input type="radio" class="authorized_dkna"';
    $authorized_cell .= ' name="'.$coursefile->id.'[authorized]"';
    $authorized_cell .= ' id="'.$courseid.'_'.$coursefile->id.'_authorized_dkna"';
    $authorized_cell .= ' value="-1" title="'.get_string('dkna', 'block_course_files_license').'"> ';
    $authorized_cell .= '</div>';

    $operations_cell  = '<a href="#" onclick="';
    $operations_cell .= ' $(\'#'.$courseid.'_'.$coursefile->id.'_ownwork_yes\').prop(\'checked\', false);';
    $operations_cell .= ' $(\'#'.$courseid.'_'.$coursefile->id.'_ownwork_no\').prop(\'checked\', false);';
    $operations_cell .= ' $(\'#'.$courseid.'_'.$coursefile->id.'_copyright_yes\').prop(\'checked\', false);';
    $operations_cell .= ' $(\'#'.$courseid.'_'.$coursefile->id.'_copyright_no\').prop(\'checked\', false);';
    $operations_cell .= ' $(\'#'.$courseid.'_'.$coursefile->id.'_copyright_dkna\').prop(\'checked\', false);';
    $operations_cell .= ' $(\'#'.$courseid.'_'.$coursefile->id.'_authorized_yes\').prop(\'checked\', false);';
    $operations_cell .= ' $(\'#'.$courseid.'_'.$coursefile->id.'_authorized_no\').prop(\'checked\', false);';
    $operations_cell .= ' $(\'#'.$courseid.'_'.$coursefile->id.'_authorized_dkna\').prop(\'checked\', false);';
    $operations_cell .= ' return false;"';
    $operations_cell .= ' class="btn btn-xs btn-danger">';
    $operations_cell .= '<i class="fa fa-trash"></i> '.get_string('cleanrow', 'block_course_files_license').'</a>';

    $row = new html_table_row();
    $row->cells[] = date('d/m/y', $coursefile->timecreated);
    $row->cells[] = $filename_cell;
    $row->cells[] = $coursefile->author;
    $row->cells[] = $ownwork_cell; 
    $row->cells[] = $copyright_cell;
    $row->cells[] = $authorized_cell;
    $row->cells[] = $operations_cell;
    $table->data[] = $row;
}

if ($identifiedcoursefilelist) {
    $identified_table = new html_table();
    $identified_table->attributes = array('style' => 'font-size: 80%;');
    $identified_table->head = array(
        get_string('timeuploaded', 'block_course_files_license'),
        get_string('filename', 'block_course_files_license'),
        get_string('uploaded_by', 'block_course_files_license'),
        get_string('ownwork', 'block_course_files_license'),
        get_string('copyright', 'block_course_files_license'),
        get_string('authorized', 'block_course_files_license'),
        get_string('timeidentified', 'block_course_files_license'),
        get_string('identified_by', 'block_course_files_license'),
        $operations_header
    );
    
    foreach ($identifiedcoursefilelist as $identifiedcoursefile) {
        $row = new html_table_row();
        $row->cells[] = date('d/m/y', $identifiedcoursefile->timeuploaded);
        $filename_cell  = '<a href="'.$CFG->wwwroot.$identifiedcoursefile->resource_url.'">';
        $filename_cell .= $identifiedcoursefile->resource_name.'</a>';
        $filename_cell .= ' ('.display_size($identifiedcoursefile->resource_size).')';
        $row->cells[] = $filename_cell;
        $row->cells[] = $identifiedcoursefile->uploaded_by;
        if ($identifiedcoursefile->ownwork == 0) {
            $row->cells[] = get_string('no', 'block_course_files_license');
        } elseif ($identifiedcoursefile->ownwork == 1) {
            $row->cells[] = get_string('yes', 'block_course_files_license');
        } elseif ($identifiedcoursefile->ownwork == -1) {
            $row->cells[] = get_string('dkna', 'block_course_files_license');
        }
        if ($identifiedcoursefile->copyright == 0) {
            $row->cells[] = get_string('no', 'block_course_files_license');
        } elseif ($identifiedcoursefile->copyright == 1) {
            $row->cells[] = get_string('yes', 'block_course_files_license');
        } elseif ($identifiedcoursefile->copyright == -1) {
            $row->cells[] = get_string('dkna', 'block_course_files_license');
        }
        if ($identifiedcoursefile->authorized == 0) {
            $row->cells[] = get_string('no', 'block_course_files_license');
        } elseif ($identifiedcoursefile->authorized == 1) {
            $row->cells[] = get_string('yes', 'block_course_files_license');
        } elseif ($identifiedcoursefile->authorized == -1) {
            $row->cells[] = get_string('dkna', 'block_course_files_license');
        }
        $row->cells[] = date('d/m/y', $identifiedcoursefile->timeidentified);
        $uploaded_by_user = $DB->get_record('user', array('id' => $identifiedcoursefile->userid));
        $row->cells[] = $uploaded_by_user->firstname.' '.$uploaded_by_user->lastname;
        $delete_button  = '<form action="'.new moodle_url('/blocks/course_files_license/delete.php?courseid='.$courseid).'" method="POST">';
        $delete_button .= '<input type="radio" value="'.$identifiedcoursefile->id.'" checked="1" name="id"';
        $delete_button .= ' id="'.$identifiedcoursefile->id.'" value="'.$identifiedcoursefile->id.'"';
        $delete_button .= ' style="display:none;">';
        $delete_button .= '<button type="submit" class="btn btn-xs btn-danger">';
        $delete_button .= '<i class="fa fa-trash"></i> ';
        $delete_button .= get_string('deleterecord', 'block_course_files_license');
        $delete_button .= '</button>';
        $delete_button .= '</form>';
        $row->cells[] = $delete_button;
        $identified_table->data[] = $row;
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($course->fullname);
//echo $OUTPUT->heading(get_string('totalfilesize', 'block_course_files_license', display_size($sizetotal)), 3, 'main');

if ($coursefilelist) {
    echo '<p class="text-justify">';
    echo get_string('explanationmessage', 'block_course_files_license');
    echo '</p>';

    echo $OUTPUT->heading(get_string('not_identified_course_files', 'block_course_files_license'), 3, 'main');
    echo '<form action="'.$_SERVER['PHP_SELF'].'?courseid='.$courseid.'" method="POST">';
    echo html_writer::table($table);
    echo '<p class="text-center">';
    echo '<button type="submit" class="btn btn-success btn-sm"><i class="fa fa-check-square-o"></i> ';
    echo get_string('savebutton', 'block_course_files_license');
    echo '</button>';
    echo '</p>';
    echo '</form>';
} else {
    echo '<div class="alert alert-success" role="alert"><i class="fa fa-thumbs-o-up"></i> '.get_string('all_files_identified', 'block_course_files_license').'</div>';
}

if ($identifiedcoursefilelist) {
    echo $OUTPUT->heading(get_string('identified_course_files', 'block_course_files_license'), 3, 'main');
    echo html_writer::table($identified_table);
}

echo $OUTPUT->footer();
