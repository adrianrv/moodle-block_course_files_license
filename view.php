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

global $CFG;

// Verify entries in table block_course_files_license_l
//$licenses = $DB->get_records_select('block_course_files_license_l', $select, null, $DB->sql_order_by_text('sortorder'));
$licenses = $DB->get_records('block_course_files_license_l', null, $sort='sortorder');
if (!$licenses) {
    die(get_string('nolicensesavailables', 'block_course_files_license'));
}

$courseid = required_param('courseid', PARAM_INT); // If no courseid is given.
$course = $DB->get_record('course', array('id' => $courseid));

$context = context_course::instance($courseid);
$PAGE->set_course($course);
$PAGE->set_url('/blocks/course_files_license/view.php', array('courseid' => $courseid));
$PAGE->set_title($course->fullname.' '.get_string('files'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('course');
$PAGE->navbar->add(get_string('pluginname', 'block_course_files_license'));
$PAGE->navbar->add(get_string('pluginname', 'block_course_files_license'));

require_capability('block/course_files_license:viewlist', $context);

// Cleaning table 'block_course_files_license_f': delete all files that have
// been already identified but later those files have been deleted. So they
// do not have to be stored.
require_capability('block/course_files_license:deleteinstance', $context);
delete_unavailable_files();

if ($_POST) {
    // When a post request is received we get all resources of this course to be ensure
    // that the resources we are going to save are really resources of this course
    $coursefilelist_beforesave = get_course_files_list();
    $file_instances = [];
    foreach ($coursefilelist_beforesave as $f_id => $f_value) {
        $file_instances[]=$f_value->id;
    }

    // Then with each post received we check if is a valid instance of the
    // course and then save it
    require_capability('block/course_files_license:addinstance', $context);
    foreach ($_POST as $resource_id => $resource_values) {
        // We will look only in items received with at least 'license' key
        if (in_array('license', array_keys($resource_values))) {

            // Now we need to verify if the received item has a valid file instances asociated
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
                $record->license = $resource_values['license'];
                $record->cite = $resource_values['cite'];
                $DB->insert_record('block_course_files_license_f', $record, false);
            }
        }

    }
}

// After handling the post request (if it was a post request)
// we make the query again, so the resourses already saved will not be on the list
$coursefilelist = get_course_files_list();

$identifiedfileslist = get_identified_course_files_list();

$table = new html_table();
$table->attributes = array('style' => 'font-size: 80%;');
$table->head = array(
    get_string('timecreated', 'block_course_files_license'),
    get_string('filename', 'block_course_files_license'),
    get_string('uploaded_by', 'block_course_files_license'),
);

foreach ($licenses as $l) {
    $header  = '<div style="width:100%;text-align:center;">';
    $header .= '(<a href="#" onclick="';
    $header .= ' $(\'.'.$l->id.'\').prop(\'checked\', true);';
    $header .= ' return false">';
    $header .= get_string('mark_all', 'block_course_files_license');
    $header .= '</a>)<br>';
    $header .= $l->name;
    $header .= '</div>';
    array_push($table->head, $header);
}

$operations_header = '<div style="width:100%;text-align:center;">';
$operations_header .= get_string('operations', 'block_course_files_license');
$operations_header .= '</div>';
array_push($table->head, $operations_header);

foreach ($coursefilelist as $coursefile) {

    $filename_cell = '<a href="'.$CFG->wwwroot.'/pluginfile.php/'.$coursefile->contextid.'/'.$coursefile->component.'/'.$coursefile->filearea.'/'.$coursefile->itemid.$coursefile->filepath.$coursefile->filename.'">';
    $filename_cell .= $coursefile->filename.'</a> ('.display_size($coursefile->filesize).')';

    $cite_cell = '<textarea class="form-control" rows=3" id="'.$courseid.'_'.$coursefile->id.'_cite" name="'.$coursefile->id.'[cite]" placeholder="'.get_string('resource_cite', 'block_course_files_license').'" style="margin-bottom:20px;"></textarea>';

    $operations_cell  = '<a href="#" onclick="';

    foreach ($licenses as $l) {
        $operations_cell .= ' $(\'#'.$courseid.'_'.$coursefile->id.'_'.$l->id.'\').prop(\'checked\', false);';
    }
    $operations_cell .= ' $(\'#'.$courseid.'_'.$coursefile->id.'_cite\').val(\'\');';
    $operations_cell .= ' return false;"';
    $operations_cell .= ' class="btn btn-xs btn-danger" style="margin-bottom:5px;width:100%;text-align:center;" title="'.get_string('cleanrow', 'block_course_files_license').'">';
    $operations_cell .= '<i class="fa fa-trash"></i> '.get_string('cleanrow', 'block_course_files_license').'</a><br>';
    $operations_cell .= ' <a href="#" onclick="$(\'#'.$courseid.'_'.$coursefile->id.'_cite_cell\').toggle();return false;"';
    $operations_cell .= ' class="btn btn-xs btn-primary" style="margin-bottom:5px;width:100%;text-align:center;" title="'.get_string('resource_cite', 'block_course_files_license').'">';
    $operations_cell .= '<i class="fa fa-pencil"></i> '.get_string('cite','block_course_files_license').'</a>';

    $row = new html_table_row();
    $row->cells[] = date('d/m/y', $coursefile->timecreated);
    $row->cells[] = $filename_cell;
    if ($coursefile->author) {
        $row->cells[] = $coursefile->author;
    } else {
        $row->cells[] = get_string('unknown' ,'block_course_files_license');
    }


    foreach ($licenses as $l) {
        $new_cell  = '<div style="width:100%;text-align:center">';
        $new_cell .= '<input type="radio" class="'.$l->id.'"';
        $new_cell .= ' name="'.$coursefile->id.'[license]"';
        $new_cell .= ' id="'.$courseid.'_'.$coursefile->id.'_'.$l->id.'"';
        $new_cell .= ' value="'.$l->id.'" title="'.$l->name.'"> ';
        $new_cell .= '</div>';
        $row->cells[] = $new_cell;
    }

    $row->cells[] = $operations_cell;
    $table->data[] = $row;

    // license cite text area (optional)
    $row = new html_table_row();
    $cell_cite = new html_table_cell();
    $cell_cite->colspan = 10;
    $cell_cite->id = $courseid.'_'.$coursefile->id.'_cite_cell';
    $cell_cite->style = 'display:none;';
    $cell_cite->text = $cite_cell;
    $row->cells[] = $cell_cite;
    $table->data[] = $row;
}

if ($identifiedfileslist) {
    $identified_table = new html_table();
    $identified_table->attributes = array('style' => 'font-size: 80%;');
    $identified_table->head = array(
        get_string('timeuploaded', 'block_course_files_license'),
        get_string('filename', 'block_course_files_license'),
        get_string('uploaded_by', 'block_course_files_license'),
        get_string('copyright', 'block_course_files_license'),
        get_string('timeidentified', 'block_course_files_license'),
        get_string('identified_by', 'block_course_files_license'),
    );

    $operations_header = '<div style="width:100%;text-align:right;">';
    $operations_header .= get_string('operations', 'block_course_files_license');
    $operations_header .= '</div>';
    array_push($identified_table->head, $operations_header);

    foreach ($identifiedfileslist as $identifiedfile_id=>$identifiedfile) {
        $row = new html_table_row();
        $row->cells[] = date('d/m/y', $identifiedfile->timeuploaded);
        $filename_cell  = '<a href="'.$CFG->wwwroot.$identifiedfile->resource_url.'">';
        $filename_cell .= $identifiedfile->resource_name.'</a>';
        $filename_cell .= ' ('.display_size($identifiedfile->resource_size).')';
        $row->cells[] = $filename_cell;
        if ($identifiedfile->uploaded_by) {
            $row->cells[] = $identifiedfile->uploaded_by;
        } else {
            $row->cells[] = get_string('unknown', 'block_course_files_license');
        }
        $row->cells[] = $licenses[$identifiedfile->license]->name;
        $row->cells[] = date('d/m/y', $identifiedfile->timeidentified);
        $uploaded_by_user = $DB->get_record('user', array('id' => $identifiedfile->userid));
        $row->cells[] = $uploaded_by_user->firstname.' '.$uploaded_by_user->lastname;

        $form_actions  = '<form action="'.new moodle_url('/blocks/course_files_license/delete.php?courseid='.$courseid).'" method="POST">';

        $delete_btn = '<input type="radio" value="'.$identifiedfile->id.'" checked="1" name="id"';
        $delete_btn .= ' id="'.$identifiedfile->id.'" value="'.$identifiedfile->id.'"';
        $delete_btn .= ' style="display:none;">';
        $delete_btn .= '<button type="submit" class="btn btn-xs btn-danger">';
        $delete_btn .= '<i class="fa fa-trash"></i> ';
        $delete_btn .= '</button>';


        // if this identification record has cite text show the button show action
        if ($identifiedfile->cite) {
            $show_cite_btn = ' <a href="#" onclick="$(\'#identified_'.$identifiedfile->id.'_cite_cell\').toggle();return false;"';
            $show_cite_btn .= ' class="btn btn-xs btn-primary" style="margin-right:5px;" title="'.get_string('resource_cite', 'block_course_files_license').'">';
            $show_cite_btn .= '<i class="fa fa-pencil"></i> '.get_string('show_cite','block_course_files_license').'</a>';

            $form_actions .= $show_cite_btn . ' ';
        }
        $form_actions .= $delete_btn;
        $form_actions .= '</form>';

        $cell_actions = new html_table_cell();
        $cell_actions->style = 'text-align:right;';
        $cell_actions->text = $form_actions;
        $row->cells[] = $cell_actions;

        // add the row with the identified file
        $identified_table->data[] = $row;

        // if this identification record has cite text we have to show the text in new row
        if ($identifiedfile->cite) {
            $row_cite = new html_table_row();
            $cell_cite = new html_table_cell();
            $cell_cite->colspan = 10;
            $cell_cite->id = 'identified_' . $identifiedfile->id . '_cite_cell';
            $cell_cite->style = 'display:none;padding-bottom:30px;';
            $cell_cite->text = $identifiedfile->cite;
            $row_cite->cells[] = $cell_cite;
            $identified_table->data[] = $row_cite;
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($course->fullname);

if ($coursefilelist) {
    echo '<p class="text-justify">';
    if (isset($CFG->block_course_files_license_info) && $CFG->block_course_files_license_info != ''){
        echo $CFG->block_course_files_license_info;

        if ($licenses) {
            echo '<ul>';
            foreach ($licenses as $l) {
                echo '<li>' . $l->description . '</li>';
            }
            echo '</ul>';
        } else {

        }
    } else {
        echo get_string('explanationmessage', 'block_course_files_license');
    }
    echo '</p>';

    echo $OUTPUT->heading(get_string('not_identified_course_files', 'block_course_files_license'), 3, 'main');
    echo '<form action="' . $_SERVER['PHP_SELF'] . '?courseid='.$courseid.'" method="POST">';
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

if ($identifiedfileslist) {
    echo $OUTPUT->heading(get_string('identified_course_files', 'block_course_files_license'), 3, 'main');
    echo html_writer::table($identified_table);

    $action = new confirm_action(get_string('confirm_delete_all_records', 'block_course_files_license'), 'openpopup');
    $action->jsfunctionargs['callbackargs'] = array(
        null,   // Always null in this case
        array(  // An array of args to pass to the callback function
            'url'=>$PAGE->url->out(false, array('confirmed'=>'true'))
        )
    );
    $button = new single_button(new moodle_url('/blocks/course_files_license/delete.php', array('courseid' => $courseid, 'all' => 'true'), array('class'=>'btn btn-sm btn-danger')), get_string('delete_all_records', 'block_course_files_license'));
    $button->add_action($action);
    echo '<div style="width:100%;text-align:center;">' . $OUTPUT->render($button) . '</div>';
}

echo $OUTPUT->footer();
