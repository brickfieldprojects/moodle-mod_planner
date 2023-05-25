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
 * External service definitions for mod_planner.
 *
 * @package     mod_planner
 * @copyright   2020 Brickfield Education Labs <jay@brickfieldlabs.ie>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_planner_save_new_template' => [
        'classname'   => 'mod_planner\external\save_new_template',
        'methodname'  => 'execute',
        'description' => 'Saves an existing template as a new template',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/planner:managetemplates',
    ],
];

$services = [
    'mod_planner' => [
        'functions' => [
            'mod_planner_save_new_template',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
    ]
];