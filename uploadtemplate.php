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
 * Page to upload templates.
 *
 * @copyright  2021 Brickfield Education Labs, www.brickfield.ie
 * @package    mod_planner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_planner\form\upload_template_form;
use mod_planner\planner;

define('NO_OUTPUT_BUFFERING', true);
require(__DIR__.'/../../config.php');
$cid = optional_param('cid', 0, PARAM_INT);
$progressed = optional_param('progressed', 0, PARAM_BOOL);

if ($cid) {
    if (! $course = $DB->get_record("course", ["id" => $cid])) {
        throw new moodle_exception('coursemisconf');
    }
    require_login($course);
    $context = context_course::instance($course->id);
    navigation_node::override_active_url(new moodle_url('/mod/planner/template.php', ['cid' => $cid]));
    $PAGE->set_heading($course->fullname);
    $PAGE->set_context($context);
} else {
    require_login(0, false);
    $context = context_system::instance();
    $PAGE->set_context($context);
    admin_externalpage_setup('planner/template');
}
$PAGE->set_url('/mod/planner/uploadtemplate.php', ['cid' => $cid]);
$PAGE->set_title("{$SITE->shortname}");

$redirecturl = new moodle_url("/mod/planner/template.php", ['cid' => $cid]);
$form = new upload_template_form(null, ['cid' => $cid]);


// $progress = new \core\progress\display();

if ($data = $form->get_data()) {
    // $progress->start_progress('', 10);
    $templatedata = json_decode($form->get_file_content('file'));
    planner::create_template_from_json($templatedata);
    redirect($redirecturl, get_string('successfullyadded', 'planner'), null, \core\output\notification::NOTIFY_SUCCESS);
} else if ($form->is_cancelled()) {
    redirect($redirecturl);
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('uploadtemplate', 'planner'));
$form->display();
if ($progressed) {
    // $progress->end_progress();
}
echo $OUTPUT->footer();
