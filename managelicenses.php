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
 * Script to let a user manage their RSS feeds.
 *
 * @package   block_course_files_license
 * @copyright 2009 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');

require_login();

$context = context_system::instance();
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$deletelicenseid = optional_param('deleteid', 0, PARAM_INT);

$moveupid = optional_param('moveupid', 0, PARAM_INT);
$movedownid = optional_param('movedownid', 0, PARAM_INT);

if ($moveupid || $movedownid) {
    if ($moveupid) {
        $select = 'sortorder < ?';
        $sort = 'sortorder DESC';
        $licensetomove = $DB->get_record('block_course_files_license_l', array('id' => $moveupid), '*', MUST_EXIST);
    } else if ($movedownid) {
        $select = 'sortorder > ?';
        $sort = 'sortorder ASC';
        $licensetomove = $DB->get_record('block_course_files_license_l', array('id' => $movedownid), '*', MUST_EXIST);
    }
    $swaplicense = $DB->get_records_select('block_course_files_license_l', $select, array($licensetomove->sortorder), $sort);
    if ($swaplicense) {
        $swaplicense = reset($swaplicense);
        $DB->set_field('block_course_files_license_l', 'sortorder', $swaplicense->sortorder, array('id' => $licensetomove->id));
        $DB->set_field('block_course_files_license_l', 'sortorder', $licensetomove->sortorder, array('id' => $swaplicense->id));
    }
}

$PAGE->set_context($context);
require_capability('block/course_files_license:managelicenses', $context);

$urlparams = array();
$extraparams = '';
if ($returnurl) {
    $urlparams['returnurl'] = $returnurl;
    $extraparams = '&returnurl=' . $returnurl;
}
$baseurl = new moodle_url('/blocks/course_files_license/managelicenses.php', $urlparams);
$PAGE->set_url($baseurl);

// Process any actions
if ($deletelicenseid && confirm_sesskey()) {
    $DB->delete_records('block_course_files_license_f', array('license'=>$deletelicenseid));
    $DB->delete_records('block_course_files_license_l', array('id'=>$deletelicenseid));
    redirect($PAGE->url, get_string('licensedeleted', 'block_course_files_license'));
}

// Display the list of licenses.
$licenses = $DB->get_records('block_course_files_license_l', null, $sort='sortorder');

$strtitle = get_string('managelicenses', 'block_course_files_license');

$PAGE->set_pagelayout('admin');
$PAGE->set_title($strtitle);
$PAGE->set_heading($strtitle);

$managelicenses = new moodle_url('/blocks/course_files_license/managelicenses.php', $urlparams);
$urlparams_settings = array('section' => 'blocksettingcourse_files_license');
$settingslicenses = new moodle_url('/admin/settings.php?section=blocksettingcourse_files_license');
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_course_files_license'), $settingslicenses);
$PAGE->navbar->add(get_string('managelicenses', 'block_course_files_license'), $managelicenses );

echo $OUTPUT->header();
echo $OUTPUT->heading($strtitle, 2);

$table = new flexible_table('license-display');

$table->define_columns(array('name', 'description', 'actions'));
$table->define_headers(
    array(
        get_string('license_name', 'block_course_files_license'),
        get_string('license_description', 'block_course_files_license'),
        get_string('actions', 'moodle')
    )
);
$table->define_baseurl($baseurl);

$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'licenses-table');
$table->set_attribute('class', 'generaltable generalbox');
$table->column_class('license', 'license');
$table->column_class('actions', 'actions');
$table->column_style('actions', 'text-align', 'right');

$table->setup();

$i = 0;
$len = count($licenses);
$upicon = new pix_icon('t/up', get_string('up'));
$downicon = new pix_icon('t/down', get_string('down'));
foreach($licenses as $license) {

    $l_name = '<div class="license-name">' . $license->name . '</div>';
    $l_description = '<div class="license-description">' . $license->description .'</div>';

    $editurl = new moodle_url('/blocks/course_files_license/editlicense.php?id=' . $license->id . $extraparams);
    $editaction = $OUTPUT->action_icon($editurl, new pix_icon('t/edit', get_string('edit')));

    $deleteurl = new moodle_url('/blocks/course_files_license/managelicenses.php?deleteid=' . $license->id . '&sesskey=' . sesskey() . $extraparams);
    $deleteicon = new pix_icon('t/delete', get_string('delete'));
    $deleteaction = $OUTPUT->action_icon($deleteurl, $deleteicon, new confirm_action(get_string('deletelicenseconfirm', 'block_course_files_license')));

    $moveaction = '';
    if (($i == 0) && ($len > 1)) {
        // first element
        $downurl = new moodle_url('/blocks/course_files_license/managelicenses.php?movedownid=' . $license->id . '&sesskey=' . sesskey() . $extraparams);
        $moveaction .= $OUTPUT->action_icon($downurl, $downicon);
    } else if (($i == $len - 1) && ($len > 1)){
        // last element
        $upurl = new moodle_url('/blocks/course_files_license/managelicenses.php?moveupid=' . $license->id . '&sesskey=' . sesskey() . $extraparams);
        $moveaction .= $OUTPUT->action_icon($upurl, $upicon);
    } else if (($i > 0) && ($i != $len)) {
        $upurl = new moodle_url('/blocks/course_files_license/managelicenses.php?moveupid=' . $license->id . '&sesskey=' . sesskey() . $extraparams);
        $moveaction .= $OUTPUT->action_icon($upurl, $upicon);
        $downurl = new moodle_url('/blocks/course_files_license/managelicenses.php?movedownid=' . $license->id . '&sesskey=' . sesskey() . $extraparams);
        $moveaction .= $OUTPUT->action_icon($downurl, $downicon);
    }

    $l_icons = $moveaction . ' ' . $editaction . ' ' . $deleteaction . ' ';

    $table->add_data(array($l_name, $l_description, $l_icons));
    $i++;
}

$table->print_html();

$url = $CFG->wwwroot . '/blocks/course_files_license/editlicense.php?' . substr($extraparams, 1);
echo '<div class="actionbuttons">' . $OUTPUT->single_button($url, get_string('addnewlicense', 'block_course_files_license'), 'get') . '</div>';


if ($returnurl) {
    echo '<div class="backlink">' . html_writer::link($returnurl, get_string('back')) . '</div>';
}

echo $OUTPUT->footer();
