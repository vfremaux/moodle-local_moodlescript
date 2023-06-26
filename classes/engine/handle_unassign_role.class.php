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

class handle_unassign_role extends handler {

    public function execute(&$results, &$stack) {
        global $DB, $COURSE;

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if ($context->rolecourseid == 'current') {
            if (isset($context->courseid)) {
                $context->rolecourseid = $context->courseid;
            } else {
                $context->rolecourseid = $COURSE->id;
            }
        }

        $rolecontext = \context_course::instance($context->rolecourseid);

        $role = $DB->get_record('role', array('id' => $context->roleid));

        role_unassign($role->id, $context->userid, $rolecontext->id, '', 0);

        $this->log('Role '.$role->shortname.' removed for user '.$context->userid.' in context of course '.$context->rolecourseid);

        $result = 0;
        $results[] = $result;
        return $result;
    }

    /**
     * Pre-checks executability conditions (static).
     * Must NOT modify context.
     */
    public function check(&$stack) {
        global $DB;

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if (empty($context->roleid)) {
            $this->error('missing role');
        }

        if (!$role = $DB->get_record('role', array('id' => $context->roleid))) {
            $this->error('Role '.$context->roleid.' as '.$context->rolename.' does not exist');
        }

        if (empty($context->unassignuserid)) {
            $this->error('Check Unassign Role : Empty unassign userid ');
        }

        if (!$this->is_runtime($context->unassignuserid)) {
            if ($context->unassignuserid != 'current') {
                $unassignuserid = $context->unassignuserid;
            } else {
                $unassignuserid = $context->userid;
            }

            if (!$user = $DB->get_record('user', array('id' => $unassignuserid))) {
                $this->error('Check Unassign Role : No such user id '.$unassignuserid);
            }
        } else {
            $this->warn('Check Unassign role : Course id is runtime and thus unchecked. It may fail on execution.');
        }

        if (!$this->is_runtime($context->rolecourseid)) {
            if ($context->rolecourseid != 'current') {
                $rolecourseid = $context->rolecourseid;
            } else {
                $rolecourseid = $context->courseid;
            }

            if (!$course = $DB->get_record('course', array('id' => $rolecourseid))) {
                $this->error('Check Unassign Role : Missing target course for role suppression');
            }
        } else {
            $this->warn('Check Unassign role : Role id is runtime and thus unchecked. It may fail on execution.');
        }
    }
}