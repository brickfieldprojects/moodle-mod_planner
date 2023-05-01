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

use stdClass;

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

       $templatestepdata = $DB->get_records_sql("SELECT * FROM {planner_step} WHERE plannerid = '".$planner->id."' ORDER BY id ASC");
       $templateuserstepdata = $DB->get_records_sql("SELECT pu.*,ps.name,ps.description FROM {planner_userstep} pu
        JOIN {planner_step} ps ON (ps.id = pu.stepid)
        WHERE ps.plannerid = '".$planner->id."' AND pu.userid = '".$userid."' ORDER BY pu.id ASC ");
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
    public function planner_user_step_delete ($planner, $userid, $starttime, $endtime) {
        global $DB;

        $templatestepdata = $DB->get_records_sql("SELECT * FROM {planner_step} WHERE plannerid = '".$planner->id."' ORDER BY id ASC");
        $templateuserstepdata = $DB->get_records_sql("SELECT pu.*,ps.name,ps.description FROM {planner_userstep} pu
        JOIN {planner_step} ps ON (ps.id = pu.stepid)
        WHERE ps.plannerid = '".$planner->id."' AND pu.userid = '".$userid."' ORDER BY pu.id ASC ");
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
                    $event->description = get_string('step', 'planner').' '.$i.' : '.$stepval['name'];
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
            $DB->delete_records('event', array('instance' => $planner->id, 'modulename' => 'planner',
            'eventtype' => 'due', 'userid' => $students));
            $i = 1;
            foreach ($stepsdata as $stepid => $stepval) {
                $event = new stdClass();
                $event->name = format_string($planner->name);
                $event->description = get_string('step', 'planner').' '.$i.' : '.$stepval['name'];
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
}
