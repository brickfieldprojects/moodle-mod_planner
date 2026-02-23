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

namespace mod_planner\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');
/**
 * Templates form class for planner module
 *
 * @copyright 2021 Brickfield Education Labs, www.brickfield.ie
 * @package   mod_planner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_template_form extends \moodleform {
    /**
     * Define the form.
     */
    public function definition() {
        $mform = $this->_form;
        $cid = $this->_customdata['cid'];
        $mform->addElement('filepicker', 'file', get_string('uploadtemplate', 'mod_planner'));
        $mform->addRule('file', get_string('required'), 'required');
        $mform->addElement('hidden', 'cid', $cid);
        $mform->settype('cid', PARAM_INT);
        $mform->addElement('hidden', 'progressed', true);
        $mform->settype('progressed', PARAM_BOOL);
        $this->add_action_buttons(true, get_string('submit'));
    }

    public function validation($data, $files) {
        global $USER;

        $errors = parent::validation($data, $files);

        // filepicker element name is "file".
        $draftitemid = $data['file'] ?? 0;
        if (empty($draftitemid)) {
            $errors['file'] = get_string('required');
            return $errors;
        }

        // Pull the uploaded file from the user draft area.
        $usercontext = \context_user::instance($USER->id);
        $fs = get_file_storage();
        $draftfiles = $fs->get_area_files(
            $usercontext->id,
            'user',
            'draft',
            $draftitemid,
            'id DESC',
            false
        );

        if (empty($draftfiles)) {
            $errors['file'] = get_string('required');
            return $errors;
        }

        // There should only be one.
        $file = reset($draftfiles);

        // Check extension.
        $filename = $file->get_filename();
        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'json') {
            $errors['file'] = get_string('invalidfiletype', 'error', '.json');
            return $errors;
        }

        // Read content.
        $content = $file->get_content();
        if ($content === '' || $content === null) {
            $errors['file'] = get_string('invalidjson', 'mod_planner');
            return $errors;
        }

        // Decode JSON.
        $decoded = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            $errors['file'] = get_string('invalidjson', 'mod_planner');
            return $errors;
        }

        // Validate structure.
        $structureerrors = self::validate_template_structure($decoded);
        if (!empty($structureerrors)) {
            // Show as a single validation error on the file element.
            $errors['file'] = implode("\n", $structureerrors);
            return $errors;
        }
        return $errors;
    }

    /**
     * Validate the required structure for the planner template JSON.
     * Return array of human-readable error strings.
     */
    private static function validate_template_structure(array $j): array {
        $errs = [];

        // Required top-level keys.
        $required = [
            'id' => 'scalar',
            'userid' => 'scalar',
            'name' => 'scalar',
            'disclaimer' => 'scalar',
            'personal' => 'scalar',
            'status' => 'scalar',
            'copied' => 'scalar',
            'timecreated' => 'scalar',
            'timemodified' => 'scalar',
            'plannertemplatesteps' => 'array',
        ];

        foreach ($required as $key => $type) {
            if (!array_key_exists($key, $j)) {
                $errs[] = "Missing required key: {$key}";
                continue;
            }
            if ($type === 'array' && !is_array($j[$key])) {
                $errs[] = "Key '{$key}' must be an object/array.";
            }
        }

        if (!empty($errs)) {
            return $errs;
        }

        return $errs;
    }
}