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
 * @package local_moodlescript
 * @category local
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright (c) 2017 onwards Valery Fremaux (http://www.mylearningfactory.com)
 */
namespace local_moodlescript\engine;

defined('MOODLE_INTERNAL') || die;

class handle_add_capability extends handler {

    public function execute(&$results, &$stack) {
        global $DB;

        // Pass incoming context to internals.
        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if (empty($context->params->permission)) {
            $cappermission = 'allow';
        } else {
            $cappermission = $context->params->permission;
        }

        switch ($cappermission) {
            case 'allow': {
                $permission = CAP_ALLOW;
                break;
            }

            case 'prevent': {
                $permission = CAP_PREVENT;
                break;
            }

            case 'prohibit': {
                $permission = CAP_PROHIBIT;
                break;
            }

            case 'inherit': {
                $permission = CAP_INHERIT;
                break;
            }
        }

        if (empty($context->params->contextid)) {
            $capcontext = \context_system::instance();
        } else {
            $capcontext = \context::instance_by_id($context->params->contextid);
        }

        role_change_permission($context->roleid, $capcontext, $context->capability, $permission);
        $role = $DB->get_record('role', array('id' => $context->roleid));

        $this->log('Capability "'.$context->capability.'" added to role '.$role->shortname.' with permission "'.$cappermission.'"');

        return true;
    }

    /**
     * Remind that Check MUST NOT alter the context. Just execute any pre-execution tests that might 
     * be necessary. Workable context is provided updated with whatever is resolvable by preceeding steps.
     * @param $array &$stack the script stack.
     */
    public function check(&$stack) {
        global $DB;

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if (empty($context->capability)) {
            $this->error('Add capability : empty capability');
        }

        if ($DB->record_exists('capabilities', ['name' => $context->capability])) {
            $this->warn("Add capability : capability exists already. Will not be added.");
        }

        if (empty($context->roleid)) {
            $this->error('Add capability : empty roleid');
        }

        // check if we really need this, parsing might have checked.
        if (!$DB->record_exists('role', ['roleid' => $context->roleid])) {
            $this->error("Add capability : capability exists already. Will not be added.");
        }
    }

}