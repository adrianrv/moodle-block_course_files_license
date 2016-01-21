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
 * Local lib functions
 *
 * @package    block_course_files_licence
 * @copyright  2015 Adrian Rodriguez Vargas, Universidad de La Laguna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function block_course_files_license_get_coursefilelist($limit=0) {
    global $CFG, $COURSE, $DB;

    if (!isset($CFG->licensefilesextensions)) {
        $CFG->licensefilesextensions = array();
    }

    $context = context_course::instance($COURSE->id);
    $contextcheck = $context->path . '/%';

    // Get the files used on the course that are not already identified.
    $sql = "SELECT 
                id, contenthash, userid, author, filesize, filename,
                itemid, component, filearea, filepath, timecreated,
                contextid, contextlevel, instanceid, path, depth
            FROM (
                SELECT
                    distinct(f.contenthash) contenthash, f.id id, f.userid userid, f.author author, f.filesize filesize, f.filename filename,
                    f.itemid itemid, f.component component, f.filearea filearea, f.filepath filepath, f.timecreated timecreated,
                    ctx.id contextid, ctx.contextlevel contextlevel, ctx.instanceid instanceid, ctx.path path, ctx.depth depth
                FROM {files} f
                JOIN {context} ctx ON f.contextid = ctx.id
                WHERE ".$DB->sql_concat('ctx.path', "'/'")." LIKE ?
                AND f.filename <> '.'
                AND f.filearea <> 'submission_files'
                AND f.component NOT IN ('private','draft')
                AND f.id not in (SELECT resourceid
                                       FROM {block_course_files_license}
                                       WHERE courseid=".$COURSE->id.")";

    if ($CFG->licensefilesextensions != null ) {
        $sql .= " AND (";
    }
    $extensions_len = count($CFG->licensefilesextensions);
    $i = 0;
    foreach ($CFG->licensefilesextensions as $ext) {
        $sql .= " f.filename LIKE '%".$ext."'";
        $i++;
        if ($i < $extensions_len) {
            $sql .= " OR";
        } else {
            $sql .= ") ";
        }
    }
    $sql .= ") AS q ORDER BY timecreated ASC";
    $params = array($contextcheck);
    $coursefilelist = $DB->get_records_sql($sql, $params, 0, $limit);
    return $coursefilelist;
}

//Get course files with already identified license
function block_course_files_license_get_identifiedcoursefilelist($limit=0) {
    global $USER, $COURSE, $DB;

    //$context = context_course::instance($COURSE->id);
    //$contextcheck = $context->path . '/%';

    // Get the files used on the course by size.
    $sql = "SELECT *
            FROM {block_course_files_license} f
            WHERE courseid=".$COURSE->id." ORDER BY timeuploaded ASC";
    $identifiedcoursefilelist = $DB->get_records_sql($sql, $params=null, 0, $limit);

    return $identifiedcoursefilelist;
}

//Get identified course files that have been deleted from the course
function block_course_files_license_get_unavailable_identifiedcoursefilelist($limit=0) {
    global $USER, $COURSE, $DB;
    
    $sql = "SELECT *
            FROM {block_course_files_license}
            where resourceid not in (SELECT id FROM {files})";

    $identifiedcoursefilelist = $DB->get_records_sql($sql, $params=null, 0, $limit);

    return $identifiedcoursefilelist;
}

function block_course_files_license_get_total_filesize() {
    global $COURSE, $DB;

    $context = context_course::instance($COURSE->id);
    $contextcheck = $context->path . '/%';

    $sql = "SELECT SUM(f.filesize)
                FROM {files} f
                JOIN {context} ctx ON f.contextid = ctx.id
                WHERE ".$DB->sql_concat('ctx.path', "'/'")." LIKE ?
                AND f.filename != '.'";
    $params = array($contextcheck);
    $sizetotal = $DB->get_field_sql($sql, $params);

    return $sizetotal;
}

function block_course_files_license_get_all_courses($ownwork, $copyright, $authorized) {

    $filter_condition = "";
    if (($ownwork != NULL) || ($copyright != NULL) || ($authorized != NULL)) {
        $filter_condition = " AND (";
    }
    
    if ($ownwork != NULL) {
        $filter_condition .= "fl.ownwork=".$ownwork;
    }
    
    if (($ownwork != NULL) && ($copyright != NULL)) {
        $filter_condition .= " AND fl.copyright=".$copyright;
    } elseif ($copyright != NULL) {
        $filter_condition .= "fl.copyright=".$copyright;
    }

    if ((($copyright != NULL) && ($authorized != NULL)) || (($ownwork != NULL) && ($authorized != NULL))) {
        $filter_condition .= " AND fl.authorized=".$authorized;
    } elseif ($authorized != NULL) {
        $filter_condition .= "fl.authorized=".$authorized;
    }
    if (($ownwork != NULL) || ($copyright != NULL) || ($authorized != NULL)) {
        $filter_condition .= ')';
    }

    global $CFG, $DB;

    $sql = "SELECT cm.course as courseid, c.fullname as name, count(distinct(f.id)) as num_files, count(distinct(fl.id)) as identified_files
            FROM {files} f
            JOIN {context} cx ON f.contextid = cx.id
            JOIN {course_modules} cm ON cx.instanceid=cm.id
            JOIN {course} c ON cm.course=c.id
            LEFT OUTER JOIN {block_course_files_license} fl ON c.id=fl.courseid
            WHERE
            f.filearea <> 'submission_files' AND
            f.filename <> '.'";

    if ($filter_condition != "") {
        $sql .= $filter_condition;
    }

    if ($CFG->licensefilesextensions != null ) {
        $sql .= " AND (";
    }
    $extensions_len = count($CFG->licensefilesextensions);
    $i = 0;
    foreach ($CFG->licensefilesextensions as $ext) {
        $sql .= " f.filename LIKE '%".$ext."'";
        $i++;
        if ($i < $extensions_len) {
            $sql .= " OR";
        } else {
            $sql .= ") ";
        }
    }
    $sql .= "GROUP BY cm.course, c.fullname ORDER BY c.fullname";
    $courselist = $DB->get_records_sql($sql);

    return $courselist;
}
