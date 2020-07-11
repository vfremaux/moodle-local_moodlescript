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

use \context;
use \context_system;

defined('MOODLE_INTERNAL') || die;

class handle_remove_capability extends handler {

    public function execute(&$results, &$context, &$stack) {
        global $DB;

        // Pass incoming context to internals.
        $this->stack = $stack;
        $this->context = $context;

        $permission = CAP_INHERIT;

        if (empty($context->params->contextid)) {
            $capcontext = context_system::instance();
        } else {
            $capcontext = context::instance_by_id($context->params->contextid);
        }

        role_change_permission($context->roleid, $capcontext, $context->capability, $permission);
        $role = $DB->get_record('role', array('id' => $context->roleid));

        $report = 'Capability "'.$context->capability.'" removed from role '.$role->shortname;
        $report .= ' with permission "'.$cappermission.'" in context '.$capcontext->id;

        $this->log($report);
        return true;
    }

    public function check(&$context, &$stack) {
        global $DB;

        // Pass incoming context to internals.
        $this->stack = $stack;
        $this->context = $context;

        if (empty($context->capability)) {
            $this->error('empty capability');
        }

        if (!$DB->get_record('capabilities', array('name' > $context->capability))) {
            $this->error('Unknown capability '.$context->capability);
        }

        if (empty($context->roleid)) {
            $this->error('empty roleid');
        }

        if (!$DB->get_record('role', array('roleid' > $context->roleid))) {
            $this->error('Unknown role ID '.$context->roleid);
        }

        if (!empty($context->params->contextid)) {
            if (!$DB->get_record('role', array('context' > $context->params->contextid))) {
                $this->error('Unknown context ID '.$context->params->contextid);
            }
        }
    }
}