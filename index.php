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
 * Displays information about all the planner modules in the requested course
 *
 * @copyright 2021 Brickfield Education Labs, www.brickfield.ie
 * @package mod_planner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT);   // Course id.

if (!$course = $DB->get_record('course', ['id' => $id])) {
    throw new \moodle_exception('invalidcourseid');
}

require_course_login($course);
$PAGE->set_url('/mod/planner/index.php', ['id' => $id]);
$PAGE->set_pagelayout('incourse');

$strplanner = get_string("modulename", "planner");
$strplanners = get_string("modulenameplural", "planner");
$PAGE->set_title($strplanners);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strplanners);
$PAGE->add_body_class('limitedwidth');
echo $OUTPUT->header();
echo $OUTPUT->heading($strplanners, 2);

$context = context_course::instance($course->id);

require_capability('mod/planner:view', $context);

if (! $planners = get_all_instances_in_course("planner", $course)) {
    notice(get_string('thereareno', 'moodle', $strplanners), "../../course/view.php?id=$course->id");
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();

if ($usesections) {
    if ($CFG->version < 2025041400) {
        $strsectionname = get_string('sectionname', 'format_'.$course->format);
    } else {
        $strsectionname = course_get_format($course)->get_generic_section_name();
    }
    $table->head  = [$strsectionname, get_string("name"), get_string("associatedactivity", "planner")];
    $table->align = ["left", "left", "left"];
} else {
    $table->head  = [get_string("name"), get_string("associatedactivity", "planner")];
    $table->align = ["left", "left"];
}

$currentsection = "";
$modinfo = get_fast_modinfo($course);

foreach ($planners as $planner) {
    if ($usesections) {
        $printsection = "";
        if ($planner->section !== $currentsection) {
            if (is_numeric($planner->section)) {
                $printsection = get_section_name($course->id, $planner->section);
            }
            if ($currentsection !== "") {
                $table->data[] = 'hr';
            }
            $currentsection = $planner->section;
        }
        // Getting associated activity name.
        $cm = $modinfo->get_cm($planner->activitycmid);
        $aa = $cm->name;
    }

    //Calculate the href
    if (!$planner->visible) {
        //Show dimmed if the mod is hidden
        $tt_href = "<a class=\"dimmed\" href=\"view.php?id=$planner->coursemodule\">" . format_string($planner->name,true) . "</a>";
    } else {
        //Show normal if the mod is visible
        $tt_href = "<a href=\"view.php?id=$planner->coursemodule\">" . format_string($planner->name,true) . "</a>";
    }
    if ($usesections) {
        $table->data[] = [$printsection, $tt_href, $aa];
    } else {
        $table->data[] = [$tt_href, $aa];
    }
}
echo "<br />";
echo html_writer::table($table);

echo $OUTPUT->footer();
