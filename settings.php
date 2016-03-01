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
 * Settings for block
 *
 * @package   block_course_files_licence
 * @copyright 2016 Adrian Rodriguez Vargas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext_extensions('block_course_files_license_extensions', get_string('extensions', 'block_course_files_license'),
        get_string('extensionsconfig', 'block_course_files_license'), 'pdf, odt, doc, docx, xls, xlsx', PARAM_TEXT));

    $settings->add(new admin_setting_configcheckbox('block_course_files_license_popup', new lang_string('popup', 'block_course_files_license'),
        new lang_string('popupconfig', 'block_course_files_license'), 0));

    $settings->add(new admin_setting_configtextarea('block_course_files_license_info', new lang_string('license_instructions', 'block_course_files_license'),
        new lang_string('license_instructions_desc', 'block_course_files_license'), new lang_string('license_instructions_default', 'block_course_files_license')));

    $link_manage_licenses ='<a href="'.$CFG->wwwroot.'/blocks/course_files_license/managelicenses.php">'.get_string('managelicenses', 'block_course_files_license').'</a>';
    $settings->add(new admin_setting_heading('course_files_license_manage_licenses', '', $link_manage_licenses));

    $link_overview ='<a href="'.$CFG->wwwroot.'/blocks/course_files_license/all.php">'.get_string('coursesoverview', 'block_course_files_license').'</a>';
    $settings->add(new admin_setting_heading('course_files_license_overview', '', $link_overview));
}
