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

    public function execute($result, &$context, &$stack) {
        global $DB;

        // Pass incoming context to internals.
        $this->stack = &$stack;
        $this->context = &$context;

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
    }

    public function check(&$context, &$stack) {
        global $DB;

        // Pass incoming context to internals.
        $this->stack = &$stack;
        $this->context = &$context;

        if (empty($context->capability)) {
            $this->error('empty capability');
        }

        if (empty($context->roleid)) {
            $this->error('empty roleid');
        }
    }

}