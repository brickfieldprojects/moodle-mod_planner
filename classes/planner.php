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

namespace mod_planner;

defined('MOODLE_INTERNAL') || die();

use stdClass;

require_once("{$CFG->libdir}/csvlib.class.php");

/**
 * Helper class for utility functions.
 *
 * @package    mod_planner
 * @copyright  2020 onward Brickfield Education Labs Ltd, https://www.brickfield.ie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class planner {

    /**
     * Returns the Planner name
     *
     * @param object $planner
     * @return string
     */
    public function get_planner_name($planner) {
        $name = get_string('modulename', 'planner');
        return $name;
    }

    /**
     * Creates user steps for planner
     *
     * @param object $planner
     * @param int $userid
     * @param int $starttime
     * @param int $endtime
     * @return void
     */
    public function planner_user_step($planner, $userid, $starttime, $endtime) {
        global $DB;

        $templatestepdata = $DB->get_records_sql("SELECT * FROM {planner_step} WHERE plannerid = '" . $planner->id . "' ORDER BY id ASC");
        $templateuserstepdata = $DB->get_records_sql("SELECT pu.*,ps.name,ps.description FROM {planner_userstep} pu
        JOIN {planner_step} ps ON (ps.id = pu.stepid)
        WHERE ps.plannerid = '" . $planner->id . "' AND pu.userid = '" . $userid . "' ORDER BY pu.id ASC ");
        $totaltime = $endtime - $starttime;
        $exsitingsteptime = $starttime;
        $stepsdata = array();
        foreach ($templatestepdata as $stepkey => $stepval) {
            $existingsteptemp = ($totaltime * $stepval->timeallocation) / 100;
            $exsitingsteptime = $existingsteptemp + $exsitingsteptime;
            $stepsdata[$stepkey]['name'] = $stepval->name;
            $stepsdata[$stepkey]['timedue'] = $exsitingsteptime;
        }
        if ($templateuserstepdata) {
            $i = 0;
            foreach ($templateuserstepdata as $stepid => $stepdata) {
                $updatestep = new stdClass();
                $updatestep->id = $stepdata->id;
                $updatestep->duedate = $stepsdata[$stepdata->stepid]['timedue'];
                if ($i == 0) {
                    $updatestep->timestart = $starttime;
                }
                $updatestep->completionstatus = 0;
                $updatestep->timemodified = 0;
                $DB->update_record('planner_userstep', $updatestep);
                $i++;
            }
        } else {
            $i = 0;
            foreach ($stepsdata as $stepid => $stepdata) {
                $insertstep = new stdClass();
                $insertstep->stepid = $stepid;
                $insertstep->userid = $userid;
                $insertstep->duedate = $stepdata['timedue'];
                if ($i == 0) {
                    $insertstep->timestart = $starttime;
                }
                $insertstep->completionstatus = 0;
                $insertstep->timemodified = 0;
                $DB->insert_record('planner_userstep', $insertstep);
                $i++;
            }
        }
        $this->planner_update_events($planner, $userid, $stepsdata, false);
    }

    /**
     * Deleting a user step for a planner
     *
     * @param object $planner
     * @param int $userid
     * @param int $starttime
     * @param int $endtime
     * @return void
     */
    public function planner_user_step_delete($planner, $userid, $starttime, $endtime) {
        global $DB;

        $templatestepdata = $DB->get_records_sql("SELECT * FROM {planner_step} WHERE plannerid = '" . $planner->id . "' ORDER BY id ASC");
        $templateuserstepdata = $DB->get_records_sql("SELECT pu.*,ps.name,ps.description FROM {planner_userstep} pu
        JOIN {planner_step} ps ON (ps.id = pu.stepid)
        WHERE ps.plannerid = '" . $planner->id . "' AND pu.userid = '" . $userid . "' ORDER BY pu.id ASC ");
        $totaltime = $endtime - $starttime;
        $exsitingsteptime = $starttime;
        $stepsdata = array();
        foreach ($templatestepdata as $stepkey => $stepval) {
            $existingsteptemp = ($totaltime * $stepval->timeallocation) / 100;
            $exsitingsteptime = $existingsteptemp + $exsitingsteptime;
            $stepsdata[$stepkey]['name'] = $stepval->name;
            $stepsdata[$stepkey]['timedue'] = $exsitingsteptime;
        }
        if ($templateuserstepdata) {
            $i = 0;
            foreach ($templateuserstepdata as $stepid => $stepdata) {
                $updatestep = new stdClass();
                $updatestep->id = $stepdata->id;
                $updatestep->duedate = $stepsdata[$stepdata->stepid]['timedue'];
                if ($i == 0) {
                    $updatestep->timestart = null;
                }
                $updatestep->completionstatus = 0;
                $updatestep->timemodified = 0;
                $DB->update_record('planner_userstep', $updatestep);
            }
        }
        $this->planner_update_events($planner, $userid, $stepsdata, false);
    }

    /**
     * Updates events for Planner activity
     *
     * @param object $planner
     * @param object $students
     * @param object $stepsdata
     * @param boolean $alluser
     * @return void
     */
    public function planner_update_events($planner, $students, $stepsdata, $alluser = true) {
        global $DB;

        if ($alluser) {
            $DB->delete_records('event', array('instance' => $planner->id, 'modulename' => 'planner', 'eventtype' => 'due'));

            foreach ($students as $studentkey => $studentdata) {
                $i = 1;
                foreach ($stepsdata as $stepid => $stepval) {
                    $event = new stdClass();
                    $event->name = format_string($planner->name);
                    $event->description = get_string('step', 'planner') . ' ' . $i . ' : ' . $stepval['name'];
                    $event->format = FORMAT_HTML;
                    $event->userid = $studentkey;
                    $event->modulename = 'planner';
                    $event->instance = $planner->id;
                    $event->type = CALENDAR_EVENT_TYPE_ACTION;
                    $event->eventtype = 'due';
                    $event->timestart = $stepval['timedue'];
                    $event->timesort = $stepval['timedue'];
                    \calendar_event::create($event, false);
                    $i++;
                }
            }
        } else {
            $DB->delete_records('event', array(
                'instance' => $planner->id, 'modulename' => 'planner',
                'eventtype' => 'due', 'userid' => $students
            ));
            $i = 1;
            foreach ($stepsdata as $stepid => $stepval) {
                $event = new stdClass();
                $event->name = format_string($planner->name);
                $event->description = get_string('step', 'planner') . ' ' . $i . ' : ' . $stepval['name'];
                $event->format = FORMAT_HTML;
                $event->userid = $students;
                $event->modulename = 'planner';
                $event->instance = $planner->id;
                $event->type = CALENDAR_EVENT_TYPE_ACTION;
                $event->eventtype = 'due';
                $event->timestart = $stepval['timedue'];
                $event->timesort = $stepval['timedue'];
                \calendar_event::create($event, false);
                $i++;
            }
        }
    }

    /**
     * Update or delete a template based on input.
     *
     * @param string $action
     * @param int $id
     * @param string $confirm
     * @param string $pageurl
     * @param int $cid
     * @return void
     */
    public function template_crud_handler($action, $id, $confirm, $pageurl, $cid) {
        global $DB, $PAGE;

        $plannertemplatedata = $DB->get_record('plannertemplate', array('id' => $id));
        $renderer = $PAGE->get_renderer('mod_planner');
        if (($action == 'delete') and confirm_sesskey()) {
            if ($plannertemplatedata) {
                if ($confirm != md5($id)) {
                    $renderer->display_template_delete_page($plannertemplatedata, $pageurl, $id, $cid);
                } else if (data_submitted()) {
                    $DB->delete_records('plannertemplate_step', array('plannerid' => $id));
                    if ($DB->delete_records('plannertemplate', array('id' => $id))) {
                        redirect($pageurl);
                    } else {
                        redirect(
                            $pageurl,
                            get_string('deletednottemplate', 'planner', $plannertemplatedata->name),
                            null,
                            \core\output\notification::NOTIFY_ERROR
                        );
                    }
                }
            }
        } else if (($action == 'enable') and confirm_sesskey()) {
            if ($plannertemplatedata) {
                $updatestatus = new stdClass();
                $updatestatus->id = $id;
                $updatestatus->status = 1;
                $DB->update_record('plannertemplate', $updatestatus);
            }
        } else if (($action == 'disable') and confirm_sesskey()) {
            if ($plannertemplatedata) {
                $updatestatus = new stdClass();
                $updatestatus->id = $id;
                $updatestatus->status = 0;
                $DB->update_record('plannertemplate', $updatestatus);
            }
        }
    }

    /**
     * Create a the search form.
     *
     * @param int $cid
     * @return array
     */
    public function create_template_search_form($cid) {
        $mform = new \mod_planner\form\search(null, array('cid' => $cid));

        /** @var cache_session $cache */
        $cache = \cache::make_from_params(\cache_store::MODE_SESSION, 'mod_planner', 'search');
        if ($cachedata = $cache->get('data')) {
            $mform->set_data($cachedata);
        }

        $searchclauses = '';

        // Check if we have a form submission, or a cached submission.
        $data = ($mform->is_submitted() ? $mform->get_data() : fullclone($cachedata));
        if ($data instanceof stdClass) {
            if (!empty($data->searchgroup['setting'])) {
                $searchclauses = $data->searchgroup['setting'];
            }
            // Cache form submission so that it is preserved while paging through the report.
            unset($data->submitbutton);
            $cache->set('data', $data);
        }
        return ['searchclauses' => $searchclauses, 'mform' => $mform];
    }

    /**
     * Updates records for templates and template steps.
     *
     * @param object $templatedata
     * @return void
     */
    public function update_planner_template_step($templatedata) {
        global $DB;

        // Update case.
        $updatetemplate = new stdClass();
        $updatetemplate->id = $templatedata->id;
        $updatetemplate->name = $templatedata->name;
        $disclaimer = $templatedata->disclaimer;
        $updatetemplate->disclaimer = $disclaimer['text'];
        $updatetemplate->personal = $templatedata->personal;
        $updatetemplate->timemodified = time();
        if ($DB->update_record('plannertemplate', $updatetemplate)) {
            $DB->delete_records('plannertemplate_step', array('plannerid' => $templatedata->id));
            $stepnames = $templatedata->stepname;
            $stepstepallocations = $templatedata->stepallocation;
            $stepstepdescriptions = $templatedata->stepdescription;
            if ($templatedata->option_repeats > 0) {
                for ($i = 0; $i < $templatedata->option_repeats; $i++) {
                    if ($stepnames[$i]) {
                        $insertrecord = new stdClass();
                        $insertrecord->plannerid = $templatedata->id;
                        $insertrecord->name = $stepnames[$i];
                        $insertrecord->timeallocation = $stepstepallocations[$i];
                        $description = $stepstepdescriptions[$i];
                        $insertrecord->description = $description['text'];
                        $DB->insert_record('plannertemplate_step', $insertrecord);
                    }
                }
            }
        }
    }

    /**
     * Inserts records for templates and template steps
     *
     * @param object $templatedata
     * @return void
     */
    public function insert_planner_template_step($templatedata) {
        global $DB, $USER;

        // Insert case.
        $inserttemplate = new stdClass();
        $inserttemplate->userid = $USER->id;
        $inserttemplate->name = $templatedata->name;
        $disclaimer = $templatedata->disclaimer;
        $inserttemplate->disclaimer = $disclaimer['text'];
        $inserttemplate->status = 1;
        $inserttemplate->personal = $templatedata->personal;
        $inserttemplate->timecreated = time();
        if ($insertedtemplateid = $DB->insert_record('plannertemplate', $inserttemplate)) {
            $stepnames = $templatedata->stepname;
            $stepstepallocations = $templatedata->stepallocation;
            $stepstepdescriptions = $templatedata->stepdescription;
            if ($templatedata->option_repeats > 0) {
                for ($i = 0; $i < $templatedata->option_repeats; $i++) {
                    if ($stepnames[$i]) {
                        $insertrecord = new stdClass();
                        $insertrecord->plannerid = $insertedtemplateid;
                        $insertrecord->name = $stepnames[$i];
                        $insertrecord->timeallocation = $stepstepallocations[$i];
                        $description = $stepstepdescriptions[$i];
                        $insertrecord->description = $description['text'];
                        $DB->insert_record('plannertemplate_step', $insertrecord);
                    }
                }
            }
        }
    }

    /**
     * Returns the students and groups for a course.
     *
     * @param string $group
     * @param object $course
     * @param object $context
     * @param object $coursecontext
     * @param int $groupuserid
     *
     * @return array
     */
    public function get_students_and_groups($group, $course, $context, $coursecontext, $groupuserid) {
        global $DB;

        // Apply group restrictions.
        $params = array();
        $groupidnums = array();

        if (has_capability('moodle/site:accessallgroups', $context)) {
            $groupuserid = 0;
        }
        $groupjoin = '';
        if ((substr($group, 0, 6) == 'group-') && ($groupid = intval(substr($group, 6)))) {
            $groupjoin = 'JOIN {groups_members} g ON (g.groupid = :groupselected AND g.userid = u.id)';
            $params['groupselected'] = $groupid;
        } else if ((substr($group, 0, 9) == 'grouping-') && ($groupingid = intval(substr($group, 9)))) {
            $groupjoin = 'JOIN {groups_members} g ON ' .
                '(g.groupid IN (SELECT DISTINCT groupid FROM {groupings_groups} WHERE groupingid = :groupingselected) ' .
                'AND g.userid = u.id)';
            $params['groupingselected'] = $groupingid;
        } else if ($groupuserid != 0 && !empty($groupidnums)) {
            $groupjoin = 'JOIN {groups_members} g ON (g.groupid IN (' . implode(',', $groupidnums) . ') AND g.userid = u.id)';
        }

        // Get the list of users enrolled in the course.
        $sql = "SELECT u.*
                FROM {user} u
                JOIN {role_assignments} a ON (a.contextid = :contextid AND a.userid = u.id AND a.roleid = '5')
                $groupjoin
            ";
        $params['contextid'] = $coursecontext->id;
        $params['courseid'] = $course->id;

        $userrecords = $DB->get_records_sql($sql, $params);

        $students = array_values($userrecords);
        return $students;
    }

    /**
     * Downloads the report as a CSV file.
     *
     * @param object $planner
     * @param int $plannersteps
     * @param array $students
     * @param object $course
     */
    public function export_report_to_csv($planner, $plannersteps, $students, $course) {
        global $DB;

        $modinfo = get_fast_modinfo($course);
        foreach ($modinfo->instances as $modulename => $modinstances) {
            foreach ($modinstances as $cm) {
                if ($cm->id == $planner->activitycmid) {
                    $cmname = $cm->name;
                }
            }
        }

        $export = new \csv_export_writer();
        $export->set_filename(format_string($planner->name) . "_" . $cmname);
        $row = array();
        $row[] = get_string('studentname', 'planner');
        $row[] = get_string('email', 'planner');
        for ($i = 1; $i <= $plannersteps; $i++) {
            $row[] = get_string('stepnumber', 'planner', $i);
        }
        $export->add_data($row);
        if ($students) {
            foreach ($students as $studentdata) {
                $getusersteps = $DB->get_records_sql("SELECT pus.*  FROM {planner_userstep} pus
                JOIN {planner_step} ps ON (ps.id = pus.stepid) WHERE ps.plannerid = '" . $planner->id . "'
                AND pus.userid = '" . $studentdata->id . "' ORDER BY pus.stepid ASC");
                $row = array();
                if ($studentdata->idnumber) {
                    $row[] = fullname($studentdata) . ' (' . $studentdata->idnumber . ')';
                } else {
                    $row[] = fullname($studentdata);
                }
                $row[] = $studentdata->email;
                foreach ($getusersteps as $step) {
                    if ($step->completionstatus == '1') {
                        $row[] = get_string('completed', 'planner');
                    } else {
                        $row[] = get_string('pending', 'planner');
                    }
                }
                $export->add_data($row);
            }
        }
        $export->download_file();
    }

    /**
     * Returns the planner times.
     *
     * @param object $planner
     * @param object $cm
     * @return object
     */
    public function get_planner_times($planner, $cm) {
        global $DB, $USER;
        $cminfoactivity = $DB->get_record_sql("SELECT cm.id,cm.instance,cm.module,m.name FROM {course_modules} cm
        JOIN {modules} m ON (m.id = cm.module) WHERE cm.id = '" . $planner->activitycmid . "'");

        $modinfo = get_fast_modinfo($planner->course);
        foreach ($modinfo->instances as $modname => $modinstances) {
            foreach ($modinstances as $cmnew) {
                if ($cmnew->deletioninprogress == 0 && $cmnew->id == $planner->activitycmid) {
                    $modulename = $DB->get_record($cminfoactivity->name, array('id' => $cminfoactivity->instance));
                } else if ($cmnew->deletioninprogress == 1 && $cmnew->id == $planner->activitycmid) {
                    throw new \moodle_exception(
                        'relatedactivitynotexistdelete',
                        'planner',
                        new \moodle_url("/course/view.php?id=$planner->course")
                    );
                }
            }
        }

        $time = new stdClass();
        if ($cminfoactivity->name == 'assign') {
            $time->starttime = $modulename->allowsubmissionsfromdate;
            $time->endtime = $modulename->duedate;
            $time->defaultstarttime = $modulename->allowsubmissionsfromdate;
            $time->defaultendtime = $modulename->duedate;
        } else if ($cminfoactivity->name == 'quiz') {
            $time->starttime = $modulename->timeopen;
            $time->endtime = $modulename->timeclose;
            $time->defaultstarttime = $modulename->timeopen;
            $time->defaultendtime = $modulename->timeclose;
        }
        $time->userstartdate = $DB->get_record_sql("SELECT pu.* FROM {planner_userstep} pu JOIN {planner_step} ps ON (ps.id = pu.stepid)
         WHERE ps.plannerid = '" . $cm->instance . "' AND pu.userid = '" . $USER->id . "' ORDER BY pu.id ASC LIMIT 1");

        if ($time->userstartdate) {
            if ($time->userstartdate->timestart) {
                $$time->starttime = $time->userstartdate->timestart;
            }
        }
        $time->userenddate = $DB->get_record_sql("SELECT pu.* FROM {planner_userstep} pu JOIN {planner_step} ps ON (ps.id = pu.stepid)
         WHERE ps.plannerid = '" . $cm->instance . "' AND pu.userid = '" . $USER->id . "' ORDER BY pu.id DESC LIMIT 1");

        $datediff = $time->endtime - $time->starttime;
        $time->days = round($datediff / (60 * 60 * 24));
        return $time;
    }

    /**
     * Handles the CRUD actions for userstep table.
     *
     * @param string $action
     * @param object $planner
     * @param object $course
     * @param string $redirecturl
     * @param object $context
     * @param object $cm
     * @return object
     */
    public function planner_crud_handler($action, $planner, $course, $redirecturl, $context, $cm) {
        global $DB, $USER;

        $templatestepdata = $DB->get_records_sql("SELECT * FROM {planner_step} WHERE plannerid = '" . $planner->id . "' ORDER BY id ASC");
        $time = $this->get_planner_times($planner, $cm);
        if (($action == "studentsteps") or ($action == "recalculatesteps")) {
            if ($action == "recalculatesteps") {
                $updaterecord = new stdClass();
                $updaterecord->id = $planner->id;
                $updaterecord->timeopen = $time->starttime;
                $updaterecord->timeclose = $time->endtime;
                $DB->update_record('planner', $updaterecord);
            }

            $totaltime = $time->endtime - $time->starttime;
            $exsitingsteptime = $time->starttime;
            $stepsdata = array();
            foreach ($templatestepdata as $stepkey => $stepval) {
                $existingsteptemp = ($totaltime * $stepval->timeallocation) / 100;
                $exsitingsteptime = $existingsteptemp + $exsitingsteptime;
                $stepsdata[$stepkey]['name'] = $stepval->name;
                $stepsdata[$stepkey]['timedue'] = $exsitingsteptime;
            }

            $coursecontext = \context_course::instance($course->id);
            $studentroleid = $DB->get_record('role', array('shortname' => 'student'));
            $students = get_role_users($studentroleid->id, $coursecontext);
            if ($students) {
                foreach ($students as $studentkey => $studentdata) {
                    foreach ($stepsdata as $stepid => $stepval) {
                        $DB->delete_records('planner_userstep', array('stepid' => $stepid, 'userid' => $studentkey));

                        $insertstudentstep = new stdClass();
                        $insertstudentstep->stepid = $stepid;
                        $insertstudentstep->duedate = $stepval['timedue'];
                        $insertstudentstep->userid = $studentkey;
                        $insertstudentstep->completionstatus = 0;
                        $DB->insert_record('planner_userstep', $insertstudentstep);
                    }
                }
                $plannerclass = new planner();
                $plannerclass->planner_update_events($planner, $students, $stepsdata, true);
                if ($action == "recalculatesteps") {
                    redirect(
                        $redirecturl,
                        get_string('recalculatedstudentsteps', 'planner'),
                        null,
                        \core\output\notification::NOTIFY_SUCCESS
                    );
                } else {
                    redirect($redirecturl, get_string('studentstepupdated', 'planner'), null, \core\output\notification::NOTIFY_SUCCESS);
                }
            }
        }
        if ($action == "stepsubmit") {
            $stepid = required_param('stepid', PARAM_INT);
            $uncheckstep = optional_param('uncheckstep', 0, PARAM_INT);

            $checkexistingstep = $DB->get_record_sql("SELECT * from {planner_userstep}
            WHERE id = '" . $stepid . "' AND userid = '" . $USER->id . "'");

            if ($checkexistingstep) {
                $updateuserstep = new stdClass();
                $updateuserstep->id = $checkexistingstep->id;
                $updateuserstep->userid = $USER->id;
                if ($uncheckstep == 1) {
                    $updateuserstep->completionstatus = 0;
                } else {
                    $updateuserstep->completionstatus = 1;
                }
                $updateuserstep->timemodified = time();
                $DB->update_record('planner_userstep', $updateuserstep);

                $params = array(
                    'objectid' => $planner->id,
                    'relateduserid' => $USER->id,
                    'courseid' => $course->id,
                    'context' => $context,
                    'other' => array(
                        'plannerid' => $planner->id,
                        'stepid' => $checkexistingstep->stepid,
                        'stepname' => $templatestepdata[$checkexistingstep->stepid]->name
                    )
                );
                if ($uncheckstep == 1) {
                    $event = \mod_planner\event\step_pending::create($params);
                    $event->trigger();
                    redirect(
                        $redirecturl,
                        get_string('studentstepmarkpending', 'planner'),
                        null,
                        \core\output\notification::NOTIFY_SUCCESS
                    );
                } else {
                    $event = \mod_planner\event\step_completed::create($params);
                    $event->trigger();
                    redirect(
                        $redirecturl,
                        get_string('studentstepmarkcompleted', 'planner'),
                        null,
                        \core\output\notification::NOTIFY_SUCCESS
                    );
                }
            }
        }

        $templateuserstepdata = $DB->get_records_sql("SELECT pu.*,ps.name,ps.description FROM {planner_userstep} pu
         JOIN {planner_step} ps ON (ps.id = pu.stepid)
         WHERE ps.plannerid = '" . $cm->instance . "' AND pu.userid = '" . $USER->id . "' ORDER BY pu.id ASC ");

        $data = new stdClass();
        $data->templateuserstepdata = $templateuserstepdata;
        $data->templatestepdata = $templatestepdata;

        return $data;
    }

    /**
     * Create the planner user form.
     *
     * @param object $planner
     * @param object $data
     * @param int $id
     * @param object $cm
     * @param object $course
     * @param object $context
     * @param string $redirecturl
     * @return object
     */
    public function create_planner_user_form($planner, $data, $id, $cm, $course, $context, $redirecturl) {
        global $DB, $USER;

        $time = $this->get_planner_times($planner, $cm);
        $templateform = new \mod_planner\form\user_form(
            'view.php',
            array(
                'id' => $id, 'startdate' => $time->defaultstarttime,
                'studentstartime' => $time->starttime, 'enddate' => $time->defaultendtime
            )
        );

        if ($templatedata = $templateform->get_data()) {
            $plannerid = $templatedata->id;
            $starttime = $templatedata->userstartdate;
            $totaltime = $time->endtime - $starttime;
            $exsitingsteptime = $starttime;
            $stepsdata = array();
            foreach ($data->templatestepdata as $stepkey => $stepval) {
                $existingsteptemp = ($totaltime * $stepval->timeallocation) / 100;
                $exsitingsteptime = $existingsteptemp + $exsitingsteptime;
                $stepsdata[$stepkey]['name'] = $stepval->name;
                $stepsdata[$stepkey]['timedue'] = $exsitingsteptime;
            }
            if ($data->templateuserstepdata) {
                $i = 0;
                foreach ($data->templateuserstepdata as $stepid => $stepdata) {
                    $updatestep = new stdClass();
                    $updatestep->id = $stepdata->id;
                    $updatestep->duedate = $stepsdata[$stepdata->stepid]['timedue'];
                    if ($i == 0) {
                        $updatestep->timestart = $starttime;
                    }
                    $updatestep->completionstatus = 0;
                    $updatestep->timemodified = 0;
                    $DB->update_record('planner_userstep', $updatestep);
                    $i++;
                }
                $this->planner_update_events($planner, $USER->id, $stepsdata, false);

                $params = array(
                    'objectid' => $planner->id,
                    'relateduserid' => $USER->id,
                    'courseid' => $course->id,
                    'context' => $context,
                    'other' => array(
                        'plannerid' => $planner->id,
                    )
                );
                $event = \mod_planner\event\step_updated::create($params);
                $event->trigger();

                redirect($redirecturl, get_string('studentstepupdated', 'planner'), null, \core\output\notification::NOTIFY_SUCCESS);
            }
        }
        return $templateform;
    }

    /**
     * Returns a list of created templates.
     *
     * @param object $table
     * @param array $searchclauses
     * @param int $perpage
     * @return array
     */
    public function get_templatelist($table, $searchclauses, $perpage) {

        global $USER, $DB;

        $admins = get_admins();
        $isadmin = false;
        foreach ($admins as $admin) {
            if ($USER->id == $admin->id) {
                $isadmin = true;
                break;
            }
        }
        $select = "SELECT pt.*,u.firstname,u.lastname,u.middlename,u.firstnamephonetic,u.lastnamephonetic,u.alternatename";
        $from = "FROM {plannertemplate} pt LEFT JOIN {user} u ON (u.id = pt.userid)";
        $where = "";
        $params = array();
        $wheres = array();
        $wheresearch = "";
        $whereteacher = "";
        if (!$isadmin) {
            $whereteacher = "((pt.userid = '" . $USER->id . "' AND pt.personal = 1) OR (pt.personal = 0))";
        }
        if ($searchclauses) {
            $wheres[] = $DB->sql_like('name', ':search1', false, false);
            $wheres[] = $DB->sql_like('firstname', ':search2', false, false);
            $wheres[] = $DB->sql_like('lastname', ':search3', false, false);
            $params['search1'] = "%$searchclauses%";
            $params['search2'] = "%$searchclauses%";
            $params['search3'] = "%$searchclauses%";
            $wheresearch = implode(" OR ", $wheres);
        }
        if ($whereteacher && $wheresearch) {
            $where = "WHERE " . $whereteacher . " AND " . $wheresearch;
        } else if ($wheresearch) {
            $where = "WHERE " . $wheresearch;
        } else if ($whereteacher) {
            $where = "WHERE " . $whereteacher;
        }
        if ($table->get_sql_sort()) {
            $sort = ' ORDER BY ' . $table->get_sql_sort();
        } else {
            $sort = '';
        }
        $matchcount = $DB->count_records_sql("SELECT COUNT(pt.id) $from $where", $params);

        $table->pagesize($perpage, $matchcount);

        $templatelist = $DB->get_recordset_sql("$select $from $where $sort", $params, $table->get_page_start(), $table->get_page_size());
        return $templatelist;
    }
}
