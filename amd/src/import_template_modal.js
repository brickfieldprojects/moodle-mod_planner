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

import SaveCancelModal from 'core/modal_save_cancel';
import Fragment from 'core/fragment';
import Templates from 'core/templates';
import Notification from 'core/notification';

export const init = (courseid, contextid) => {
    const btn = document.getElementById('fitem_mod-planner-import-template-btn');
    if (!btn) {
        return;
    }

    btn.addEventListener('click', async (e) => {
        e.preventDefault();

        try {
            console.log('temp');
            const modal = await SaveCancelModal.create({
                title: 'test',
                // body: await Templates.render('mod_planner/import_template_modal', {
                //     formhtml:  await Fragment.loadFragment(
                //         'mod_planner',
                //         'import_template_form',
                //         contextid,
                //         {courseid: courseid, contextid: contextid}
                //     ),
                //     test1: 'test1',
                //     test2: 'test2',
                // }),
                large: true,
            });

            modal.show();

                        // Load the form fragment AFTER the modal is in the DOM.
            const formhtml = await Fragment.loadFragment(
                'mod_planner',
                'import_template_form',
                contextid,
                {courseid, contextid}
            );

            const rendered = await Templates.render('mod_planner/import_template_modal', {
                formhtml,
                test1: 'test1',
                test2: 'test2',
            });

            modal.setBody(rendered);

            try {
                const filepicker = await import('core_filepicker');
                const api = filepicker.default ?? filepicker;

                if (api?.init) {
                    api.init(root);
                } else if (api?.enhance) {
                    api.enhance(root);
                }
            } catch (initErr) {
                // If this triggers, check the console for the right module path for your Moodle version.
                console.warn('Filepicker init failed:', initErr);
            }

            // If your mustache uses {{#js}} blocks, switch to renderForPromise + runTemplateJS.
            // await Templates.runTemplateJS(rendered);

        // 4) On submit, let it POST normally; server will redirect back to mod_form page.
            const root = modal.getRoot()[0];
            const form = root.querySelector('form');
            if (form) {
                form.addEventListener('submit', () => {
                    // Optional: you can hide immediately to feel snappier.
                    modal.hide();
                });
            }
        } catch (err) {
            Notification.exception(err);
        }
    });
};