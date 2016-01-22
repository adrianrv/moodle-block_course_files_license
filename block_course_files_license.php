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

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/blocks/course_files_license/locallib.php');

class block_course_files_license extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_course_files_license');
    }

    function applicable_formats() {
        return array('course' => true);
    }

    function has_config() {
        return true;
    }

    function instance_allow_multiple() {
        return false;
    }

    function get_content() {
        global $CFG, $DB, $OUTPUT, $COURSE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance)) {
            return $this->content;
        }

        if (!has_capability('block/course_files_license:viewlist', context_course::instance($COURSE->id))) {
            return $this->content;
        }

        $context = context_course::instance($COURSE->id);

        $contextcheck = $context->path . '/%';

        // Get the top file files used on the course by size.
        $filelist = block_course_files_license_get_coursefilelist();
        if ($filelist) {
            if (!isset($CFG->licensefilesmodal)) {
                $CFG->licensefilesmodal = false;
            }
            if ($CFG->licensefilesmodal == true) {
                $this->content->text .= '<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>';
                $this->content->text .= '<script type="text/javascript">';
                $this->content->text .= '        $(document).ready(function() {';
                $this->content->text .= '          $("#modal-msg").modal("show");';
                $this->content->text .= '        });';
                $this->content->text .= '</script>';
                $this->content->text .= '<div class="modal fade" id="modal-msg" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" data-backdrop="static">';
                $this->content->text .= '  <div class="modal-dialog" role="document">';
                $this->content->text .= '    <div class="modal-content">';
                $this->content->text .= '      <div class="modal-header">';
                $this->content->text .= '        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
                $this->content->text .= '        <h4 class="modal-title" id="myModalLabel">'.get_string('modaltitle', 'block_course_files_license').'</h4>';
                $this->content->text .= '      </div>';
                $this->content->text .= '      <div class="modal-body">';
                $this->content->text .= get_string('files_to_idenfity_modal', 'block_course_files_license');
                $this->content->text .= '      </div>';
                $this->content->text .= '      <div class="modal-footer">';
                $this->content->text .= '        <a href="'.new moodle_url('/blocks/course_files_license/view.php', array('courseid' => $COURSE->id)).'" ';
                $this->content->text .= 'class="btn btn-default">'.get_string('filelist', 'block_course_files_license').'</a>';
                $this->content->text .= '        <button type="button" class="btn btn-primary" data-dismiss="modal">'.get_string('close', 'block_course_files_license').'</button>';
                $this->content->text .= '      </div>';
                $this->content->text .= '    </div>';
                $this->content->text .= '  </div>';
                $this->content->text .= '</div>';
            }

            $this->content->text .= '<p class="justify">'.get_string('files_to_idenfity', 'block_course_files_license').'</p>';
            $this->content->text .= '<a class="btn btn-block btn-danger btn-sm" href="';
            $this->content->text .= new moodle_url('/blocks/course_files_license/view.php', array('courseid' => $COURSE->id)).'">';
            $this->content->text .= '<i class="fa fa-exclamation-triangle"></i> ';
            $this->content->text .= get_string('filelist', 'block_course_files_license').'</a>';
        } else {
            $this->content->text = '<p class="justify">'.get_string('all_files_idenfitied', 'block_course_files_license').'</p>';
            $this->content->text = '<a class="btn btn-block btn-success btn-sm" href="';
            $this->content->text .= new moodle_url('/blocks/course_files_license/view.php', array('courseid' => $COURSE->id)).'">';
            $this->content->text .= '<i class="fa fa-info-circle"></i> ';
            $this->content->text .= get_string('filelist', 'block_course_files_license').'</a>';
        }
        return $this->content;
    }
}
