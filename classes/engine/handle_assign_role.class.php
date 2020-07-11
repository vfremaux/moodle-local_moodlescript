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

require_once($CFG->dirroot.'/local/moodlescript/classes/exceptions/execution_exception.class.php');
require_once($CFG->dirroot.'/lib/enrollib.php');

defined('MOODLE_INTERNAL') || die;

class handle_assign_role extends handler {

    public function execute(&$results, &$context, &$stack) {
        global $DB, $USER;

        $this->stack = $stack;

        if ($context->rolecourseid == 'current') {
            $context->rolecourseid = $context->courseid;
        }

        if ($context->assignuserid == 'current') {
            if (!empty($context->userid)) {
                $context->assignuserid = $context->userid;
            } else {
                $context->assignuserid = $USER->id;
            }
        }

        if (!$course = $DB->get_record('course', array('id' => $context->rolecourseid))) {
            throw new execution_exception('Assign role Runtime: Missing target course for role addition');
        }

        if (!$user = $DB->get_record('user', array('id' => $context->assignuserid))) {
            throw new execution_exception('Assign role Runtime: Bad user id');
        }

        $rolecontext = \context_course::instance($course->id);

        if (!$role = $DB->get_record('role', array('id' => $context->roleid))) {
            throw new execution_exception('Assign role Runtime: Bad rol id');
        }

        debug_trace("Role assign {$role->id}, {$context->assignuserid}, {$rolecontext->id}");
        $raid = role_assign($role->id, $context->assignuserid, $rolecontext->id, '', 0, time());
        debug_trace("Post Role assign");

        $this->log('Role '.$role->shortname.' added for user '.$context->assignuserid.' in context of course '.$context->rolecourseid);

        if (is_null($results)) {
            $results = array();
        }
        $results[] = $raid;
    }

    public function check(&$context, &$stack) {
        global $DB;

        $this->stack = $stack;
        $this->context = $context;

        if (empty($context->roleid)) {
            $this->error('Assign role : Missing role '.$context->roleid);
        }

        if (!$role = $DB->get_record('role', array('id' => $context->roleid))) {
            $this->error('Assign role : Role id '.$context->roleid.' as '.$context->rolename.' does not exist');
        }

        if (empty($context->assignuserid)) {
            $this->error('Assign role : missing user');
        }

        if ($context->assignuserid != 'current') {
            if (!$user = $DB->get_record('user', array('id' => $context->assignuserid))) {
                $this->error('Assign role : Missing target user for role assignment');
            }
        }

        if ($context->rolecourseid != 'current') {
            if (!$course = $DB->get_record('course', array('id' => $context->rolecourseid))) {
                $this->error('Assign role : Missing target course for role addition');
            }
        }
    }
}