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
 * Settings for block report page
 *
 * @package    block_course_files_license
 * @copyright  2015 Adrian Rodriguez Vargas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
if (!isset($CFG->license_ext)) {
    $CFG->license_ext = ['odt', 'doc', 'docx', 'pdf'];
}

$ADMIN->add('reports', new admin_externalpage('reportcoursefiles', get_string('coursefilesusagereport', 'block_course_files_license'),
                       new moodle_url('/blocks/course_files_license/all.php'), 'block/course_files_license:viewlist'));

// No block settings.
$settings = null;
