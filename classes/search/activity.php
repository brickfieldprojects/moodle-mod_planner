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

namespace mod_planner\search;

/**
 * Search area for mod_planner activities.
 *
 * Although there is no name field the intro value is stored internally, so no need
 * to overwrite self::get_document.
 *
 * @copyright 2021 Brickfield Education Labs, www.brickfield.ie
 * @package    mod_planner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity extends \core_search\base_activity {

    /**
     * Returns true if this area uses file indexing.
     *
     * @return bool
     */
    public function uses_file_indexing() {
        return true;
    }

    /**
     * Overwritten as planners are displayed in-course.
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    public function get_doc_url(\core_search\document $doc) {
        // Get correct URL to section that contains planner, from course format.
        $cminfo = $this->get_cm($this->get_module_name(), strval($doc->get('itemid')), $doc->get('courseid'));
        $format = course_get_format($cminfo->get_course());
        $url = $format->get_view_url($cminfo->sectionnum);

        // Add the ID of the planner to the section URL.
        $url->set_anchor('module-' . $cminfo->id);
        return $url;
    }

    /**
     * Overwritten as planners are displayed in-course. Link to the course.
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    public function get_context_url(\core_search\document $doc) {
        return new \moodle_url('/course/view.php', ['id' => $doc->get('courseid')]);

    }

}
