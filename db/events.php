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
 * Add event handlers for the planner
 *
 * @copyright 2021 Brickfield Education Labs, www.brickfield.ie
 * @package    mod_planner
 * @category   event
 * @copyright  2016 Ilya Tregubov
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\core\event\role_assigned',
        'callback'    => '\mod_planner\observer::role_assigned',
    ],
    [
        'eventname'   => '\core\event\role_unassigned',
        'callback'    => '\mod_planner\observer::role_unassigned',
    ],
    [
        'eventname' => '\mod_assign\event\user_override_created',
        'callback' => '\mod_planner\observer::assign_user_override_created',
    ],
    [
        'eventname' => '\mod_assign\event\user_override_updated',
        'callback' => '\mod_planner\observer::assign_user_override_updated',
    ],
    [
        'eventname' => '\mod_assign\event\user_override_deleted',
        'callback' => '\mod_planner\observer::assign_user_override_deleted',
    ],
    [
        'eventname' => '\mod_assign\event\group_override_created',
        'callback' => '\mod_planner\observer::assign_group_override_created',
    ],
    [
        'eventname' => '\mod_assign\event\group_override_updated',
        'callback' => '\mod_planner\observer::assign_group_override_updated',
    ],
    [
        'eventname' => '\mod_assign\event\group_override_deleted',
        'callback' => '\mod_planner\observer::assign_group_override_deleted',
    ],
    [
        'eventname' => '\mod_quiz\event\user_override_created',
        'callback' => '\mod_planner\observer::quiz_user_override_created',
    ],
    [
        'eventname' => '\mod_quiz\event\user_override_updated',
        'callback' => '\mod_planner\observer::quiz_user_override_updated',
    ],
    [
        'eventname' => '\mod_quiz\event\user_override_deleted',
        'callback' => '\mod_planner\observer::quiz_user_override_deleted',
    ],
    [
        'eventname' => '\mod_quiz\event\group_override_created',
        'callback' => '\mod_planner\observer::quiz_group_override_created',
    ],
    [
        'eventname' => '\mod_quiz\event\group_override_updated',
        'callback' => '\mod_planner\observer::quiz_group_override_updated',
    ],
    [
        'eventname' => '\mod_quiz\event\group_override_deleted',
        'callback' => '\mod_planner\observer::quiz_group_override_deleted',
    ],
];
