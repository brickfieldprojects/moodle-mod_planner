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

namespace mod_planner\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;

/**
 * External service definitions for mod_planner.
 *
 * @package    mod_planner
 * @author     Michael Pound <michael@brickfieldlabs.ie>
 * @copyright  2021 Brickfield Education Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_student_notifications extends external_api {

    /**
     * Describes the update_student_notifications parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'plannerid' => new external_value(PARAM_INT, 'The id of the planner.', VALUE_REQUIRED),
            'userid' => new external_value(PARAM_INT, 'The id of the user.', VALUE_REQUIRED),
            'value' => new external_value(PARAM_INT, 'The new value for the student notify', VALUE_REQUIRED),
        ]);
    }

    /**
     * Web service to update students planner notification status.
     *
     * @param int $plannerid
     * @param int $userid
     * @param int $value
     * @return bool
     */
    public static function execute(int $plannerid, int $userid, $value): bool {
        global $DB;

        $params = self::validate_parameters(
            self::execute_parameters(), [
                'plannerid' => $plannerid,
                'userid' => $userid,
                'value' => $value,
            ]
        );

        $status = true;
        $steps = $DB->get_records('planner_step', ['plannerid' => $params['plannerid']]);
        foreach ($steps as $step) {
            if (!$DB->set_field('planner_userstep', 'notify', $params['value'],
                    ['stepid' => $step->id, 'userid' => $params['userid']])) {
                $status = false;
            }
        }

        return $status;
    }

    /**
     * Describes the return structure of the service.
     *
     * @return external_value
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_BOOL, 'Completion');
    }
}
