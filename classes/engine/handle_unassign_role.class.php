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

use \context_course;

class handle_unassign_role extends handler {

    public function execute(&$results, &$context, &$stack) {
        global $DB;

        $this->stack = $stack;

        if ($context->rolecourseid == 'current') {
            $context->rolecourseid = $context->courseid;
        }

        if ($this->is_runtime($context->rolecourseid)) {
            $identifier = new \local_moodlescript\engine\parse_identifier('course', $this);
            $context->rolecourseid = $identifier->parse($context->rolecourseid, 'shortname', 'runtime');
        }

        if (!$DB->record_exists('course', array('id' => $context->rolecourseid))) {
            throw new execution_exception("Unassign Role : Course target {$context->rolecourseid} nor defined");
        }

        $rolecontext = context_course::instance($context->rolecourseid);

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
            $this->error('Check Unassign Role : Empty unassign userid ');
        }

        if ($context->unassignuserid != 'current') {
            if (!$this->is_runtime($context->unassignuserid)) {
                if (!$user = $DB->get_record('user', array('id' => $context->unassignuserid))) {
                    $this->error('Check Unassign Role : No such user id '.$context->unassignuserid);
                }
            }
        }

        if ($context->rolecourseid != 'current') {
            if (!$this->is_runtime($context->rolecourseid)) {
                if (!$course = $DB->get_record('course', array('id' => $context->rolecourseid))) {
                    $this->error('Check Unassign Role : Missing target course for role suppression');
                }
            }
        }
    }
}