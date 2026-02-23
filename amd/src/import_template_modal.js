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
 * Creates the modal for uploading template JSON.
 *
 * @author      Jay Churchward <jay@brickfieldlabs.ie>
 * @copyright   2021 Brickfield Education Labs <jay@brickfieldlabs.ie>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';

export const init = (courseid) => {
    const btn = document.getElementById('mod-planner-import-template-btn');
    if (!btn) {
        return;
    }

    btn.addEventListener('click', (e) => {
        e.preventDefault();

        const modalForm = new ModalForm({
            formClass: 'mod_planner\\form\\import_template_form',
            args: {courseid},
            modalConfig: {
                title: 'Upload template',
                large: true,
            },
            returnFocus: btn,
        });

        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (event) => {
            const data = event.detail;

            if (data && data.templateid) {
                const url = new URL(window.location.href);
                url.searchParams.set('templateid', data.templateid);
                window.location.href = url.toString();
            } else {
                window.location.reload();
            }
        });

        modalForm.show();
    });
};
