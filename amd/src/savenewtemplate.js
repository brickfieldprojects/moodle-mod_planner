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
 * Creates the modals for the guide tips.
 *
 * @author      Jay Churchward <jay@brickfieldlabs.ie>
 * @copyright   2021 Brickfield Education Labs <jay@brickfieldlabs.ie>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalFactory from 'core/modal_factory';
import ModalSaveNewTemplate from './modal_save_new_template';
import PlannerEvents from './events';
import ModalEvents from 'core/modal_events';
import Ajax from "core/ajax";
import Notification from "core/notification";

/**
 * Register event listeners for the module.
 */
const registerEventListeners = (templateData,) => {
    document.addEventListener('click', e => {
        const trigger = e.target.closest('#id_savenewtemplate');
        if (trigger) {
            e.preventDefault();

            show(trigger, { focusOnClose: e.target }, templateData, repeatNo);
        }
    });
};

/**
 * Shows the gateway selector modal.
 *
 * @param {HTMLElement} rootNode
 * @param {Object} options - Additional options
 * @param {HTMLElement} options.focusOnClose The element to focus on when the modal is closed.
 */
const show = async (rootNode, { focusOnClose = null } = {}) => {
    const modal = await ModalFactory.create({ type: ModalSaveNewTemplate.TYPE });

    modal.show();

    modal.getRoot().on(ModalEvents.hidden, () => {
        // Destroy when hidden.
        modal.destroy();
        try {
            focusOnClose.focus();
        } catch (e) {
            // eslint-disable-line
        }
    });

    modal.getRoot().on(PlannerEvents.savenewtemplate, (e) => {
        // Get value from input field.
        const templateName = document.getElementById('newTemplateName').value;
        const disclaimer = document.getElementById('id_disclaimereditable').innerHTML;
        const stepName = [];
        const stepAllocation = [];
        const stepDescription = [];
        for (let i = 0; i < repeatNo; i++) {
            let str = ''.concat('id_stepname_', + i);
            stepName.push(document.getElementById(''.concat('id_stepname_', + i)).value);
            stepAllocation.push(document.getElementById('id_stepallocation_' + i).value);
            stepDescription.push(document.getElementById('id_stepdescription_' + i + 'editable').innerHTML);
        }
        Ajax.call([{
            methodname: 'mod_planner_save_new_template',
            args: {
                name: templateName,
                disclaimer: disclaimer,
                personal: isPersonal,
                stepname: stepName,
                stepallocation: stepAllocation,
                stepdescription: stepDescription,
                optionrepeats: repeatNo,
            },
            fail: Notification.exception,
        }]);
    });

};

/**
 * Set up the payment actions.
 */
export const init = (templatedata, repeatno, personal) => {
    templateData = templatedata;
    repeatNo = repeatno;
    isPersonal = personal;
    if (!init.initialised) {
        // Event listeners should only be registered once.
        init.initialised = true;
        registerEventListeners();
    }
};

let templateData = '';
let repeatNo = 0;
let isPersonal = '';
/**
 * Whether the init function was called before.
 *
 * @static
 * @type {boolean}
 */
init.initialised = false;