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

    public function execute(&$results, &$context, &$stack) {
        global $DB;

        $this->stack = $stack;

        if ($context->rolecourseid == 'current') {
            $context->rolecourseid = $context->courseid;
        }

        $rolecontext = \context_course::instance($context->rolecourseid);

        $role = $DB->get_record('role', array('id' => $context->roleid));

        role_unassign($role->id, $context->userid, $rolecontext->id, '', 0);

        $this->log('Role '.$role->shortname.' removed for user '.$context->userid.' in context of course '.$context->rolecourseid);

        $result = 0;
        $results[] = $result;
        return $result;
    }

    public function check(&$context, &$stack) {
        global $DB, $CFG;

        $this->stack = $stack;

        if (empty($context->roleid)) {
            $this->error('missing role');
        }

        if (!$role = $DB->get_record('role', array('id' => $context->roleid))) {
            $this->error('Role '.$context->roleid.' as '.$context->rolename.' does not exist');
        }

        if (empty($context->unassignuserid)) {
            $this->error('Empty unassign userid ');
        }

        if ($context->unassignuserid != 'current') {
            if (!$user = $DB->get_record('user', array('id' => $context->unassignuserid))) {
                $this->error('No such user id '.$context->unassignuserid);
            }
        }

        if ($context->rolecourseid != 'current') {
            if (!$course = $DB->get_record('course', array('id' => $context->rolecourseid))) {
                $this->error('Missing target course for role suppression');
            }
        }
    }
}